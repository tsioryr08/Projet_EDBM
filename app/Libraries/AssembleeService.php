<?php

namespace App\Libraries;

use App\Models\AssembleeModel;
use App\Models\AssocieModel;
use App\Models\ConvoqueModel;
use App\Models\DirigeantModel;
use App\Models\ParticipantModel;
use App\Models\PouvoirModel;
use App\Models\RegleQuorumModel;
use App\Models\ReunionModel;
use App\Models\ResolutionModel;
use App\Models\SocieteModel;
use App\Models\TypeSocieteModel;

/**
 * Jour J d'une réunion : participants, procurations, quorum, résolutions, adoption.
 * Quorum et majorité sont lus dans regle_quorum (jamais en dur).
 */
class AssembleeService
{
    private CalculRegles $calcul;
    private ReunionModel $reunions;
    private SocieteModel $societes;
    private TypeSocieteModel $types;
    private RegleQuorumModel $quorums;
    private ConvoqueModel $convoques;
    private AssocieModel $associes;
    private DirigeantModel $dirigeants;
    private AssembleeModel $assemblees;
    private ParticipantModel $participants;
    private PouvoirModel $pouvoirs;
    private ResolutionModel $resolutions;

    public function __construct()
    {
        $this->calcul       = new CalculRegles();
        $this->reunions     = new ReunionModel();
        $this->societes     = new SocieteModel();
        $this->types        = new TypeSocieteModel();
        $this->quorums      = new RegleQuorumModel();
        $this->convoques    = new ConvoqueModel();
        $this->associes     = new AssocieModel();
        $this->dirigeants   = new DirigeantModel();
        $this->assemblees   = new AssembleeModel();
        $this->participants = new ParticipantModel();
        $this->pouvoirs     = new PouvoirModel();
        $this->resolutions  = new ResolutionModel();
    }

    /**
     * Enregistre la tenue de la réunion et vérifie le quorum.
     *
     * @param array $participants lignes ['nom' => ..., 'statut' => present|represente|absent, 'par' => nom du mandataire (si represente), 'voix' => optionnel]
     *                            Les convoqués non listés sont ajoutés comme absents. Les voix viennent de la base (nb_titres) sauf indication.
     *
     * @return array résumé du quorum (+ proposition de 2e convocation si non atteint)
     */
    public function tenir(int $reunionId, array $participants, ?string $president = null, ?string $secretaire = null, ?string $dateTenue = null): array
    {
        $reunion = $this->reunions->find($reunionId) ?? throw new \RuntimeException("Réunion #{$reunionId} introuvable.");
        if ($this->assemblees->where('reunion_id', $reunionId)->first()) {
            throw new \RuntimeException("L'assemblée de la réunion #{$reunionId} est déjà enregistrée.");
        }

        $societe = $this->societes->find($reunion['societe_id']);
        $type    = $this->types->find($societe['type_societe_id']);
        $nature  = $reunion['nature_code'];
        $numero  = (int) $reunion['numero_convocation'];

        $regle = $this->quorums->trouver((int) $type['id'], $nature, $numero)
            ?? throw new \RuntimeException("Règle de quorum non renseignée pour {$type['code']} / {$nature} / convocation n°{$numero} (à chercher dans la loi).");

        $voixTotales = $this->voixTotales($societe, $nature);

        // Convoqués de la réunion, par nom
        $convoques = [];
        foreach ($this->convoques->where('reunion_id', $reunionId)->findAll() as $c) {
            $convoques[$c['nom_affiche']] = $c;
        }

        // Construction des lignes de participants
        $lignes = [];
        foreach ($participants as $p) {
            $nom = $p['nom'];
            if (! isset($convoques[$nom])) {
                throw new \RuntimeException("{$nom} n'a pas été convoqué(e) à cette réunion.");
            }
            if (isset($lignes[$nom])) {
                throw new \RuntimeException("{$nom} est saisi(e) deux fois.");
            }
            if (! in_array($p['statut'], ['present', 'represente', 'absent'], true)) {
                throw new \RuntimeException("Statut invalide pour {$nom} : {$p['statut']}");
            }
            if ($p['statut'] === 'represente' && empty($p['par'])) {
                throw new \RuntimeException("{$nom} est représenté(e) : indiquez le mandataire (champ 'par').");
            }
            $lignes[$nom] = [
                'nom' => $nom, 'statut' => $p['statut'], 'par' => $p['par'] ?? null,
                'voix' => (int) ($p['voix'] ?? $this->voixDe($convoques[$nom], $nature)),
                'convoque_id' => (int) $convoques[$nom]['id'],
            ];
        }
        // Convoqués oubliés = absents
        foreach ($convoques as $nom => $c) {
            if (! isset($lignes[$nom])) {
                $lignes[$nom] = ['nom' => $nom, 'statut' => 'absent', 'par' => null,
                    'voix' => $this->voixDe($c, $nature), 'convoque_id' => (int) $c['id']];
            }
        }

        $quorumAtteint = $this->calcul->calculerQuorum($lignes, $regle, $voixTotales);
        $voixPresentes = 0;
        foreach ($lignes as $l) {
            if ($l['statut'] !== 'absent') {
                $voixPresentes += $l['voix'];
            }
        }

        $db = db_connect();
        $db->transBegin();
        try {
            $assembleeId = $this->assemblees->insert([
                'reunion_id'                     => $reunionId,
                'date_tenue'                     => $dateTenue ?? $reunion['date_reunion'],
                'president_seance'               => $president,
                'secretaire'                     => $secretaire,
                'voix_totales'                   => $voixTotales,
                'voix_presentes_ou_representees' => $voixPresentes,
                'quorum_requis_num'              => $regle['quorum_num'],
                'quorum_requis_den'              => $regle['quorum_den'],
                'quorum_atteint'                 => $quorumAtteint ? 1 : 0,
            ]);

            $ids = [];
            foreach ($lignes as $nom => $l) {
                $ids[$nom] = $this->participants->insert([
                    'assemblee_id' => $assembleeId, 'convoque_id' => $l['convoque_id'],
                    'nom' => $nom, 'statut' => $l['statut'], 'voix' => $l['voix'],
                ]);
            }

            // Procurations (contrôle : art. 485 au CA, pas d'auto-représentation, un pouvoir par mandant)
            $existants = [];
            foreach ($lignes as $nom => $l) {
                if ($l['statut'] !== 'represente') {
                    continue;
                }
                if (! isset($ids[$l['par']]) || $lignes[$l['par']]['statut'] !== 'present') {
                    throw new \RuntimeException("Le mandataire de {$nom} ({$l['par']}) doit être présent(e) à la réunion.");
                }
                if (! $this->calcul->procurationValide($nature, (int) $ids[$nom], (int) $ids[$l['par']], $existants)) {
                    throw new \RuntimeException("Procuration refusée : {$nom} représenté(e) par {$l['par']} (une seule procuration par mandataire au conseil d'administration, art. 485).");
                }
                $existants[] = ['mandant_id' => $ids[$nom], 'mandataire_id' => $ids[$l['par']]];
                $this->pouvoirs->insert([
                    'assemblee_id' => $assembleeId, 'mandant_id' => $ids[$nom], 'mandataire_id' => $ids[$l['par']],
                ]);
            }

            if ($quorumAtteint) {
                $this->reunions->update($reunionId, ['statut' => 'tenue']);
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }

        $resume = [
            'assemblee_id'  => $assembleeId,
            'quorum_requis' => $regle['quorum_num'] . '/' . $regle['quorum_den'],
            'voix_presentes_ou_representees' => $voixPresentes,
            'voix_totales'  => $voixTotales,
            'quorum_atteint' => $quorumAtteint,
            'source'        => $regle['source'],
            'verifie'       => (bool) $regle['verifie'],
        ];

        if (! $quorumAtteint) {
            $suivante = $this->quorums->trouver((int) $type['id'], $nature, $numero + 1);
            $resume['deuxieme_convocation'] = [
                'possible'      => $suivante !== null,
                'numero'        => $numero + 1,
                'quorum_requis' => $suivante ? $suivante['quorum_num'] . '/' . $suivante['quorum_den'] : null,
                'message'       => $suivante
                    ? "Quorum non atteint : l'assemblée ne peut pas délibérer. Une {$nature} de convocation n°" . ($numero + 1) . ' est possible.'
                    : "Quorum non atteint et règle de 2e convocation non renseignée (à chercher).",
            ];
        }

        return $resume;
    }

    /**
     * Enregistre les résolutions et calcule l'adoption de chacune.
     *
     * @param array $resolutions lignes ['texte', 'pour', 'contre', 'abstention', 'blanc'] (nombre de voix)
     */
    public function voter(int $assembleeId, array $resolutions): array
    {
        $assemblee = $this->assemblees->find($assembleeId) ?? throw new \RuntimeException("Assemblée #{$assembleeId} introuvable.");
        if ((int) $assemblee['quorum_atteint'] !== 1) {
            throw new \RuntimeException("Quorum non atteint : l'assemblée ne peut pas délibérer.");
        }

        $reunion = $this->reunions->find($assemblee['reunion_id']);
        $societe = $this->societes->find($reunion['societe_id']);
        $regle   = $this->quorums->trouver((int) $societe['type_societe_id'], $reunion['nature_code'], (int) $reunion['numero_convocation']);
        if ($regle['majorite_base'] === null) {
            throw new \RuntimeException("Majorité non renseignée pour {$reunion['nature_code']} (à chercher dans la loi).");
        }

        $voixPresentes = (int) $assemblee['voix_presentes_ou_representees'];
        $resultats = [];
        $numero = (int) ($this->resolutions->where('assemblee_id', $assembleeId)->countAllResults()) + 1;

        foreach ($resolutions as $r) {
            $voix = [
                'pour'       => (int) ($r['pour'] ?? 0),
                'contre'     => (int) ($r['contre'] ?? 0),
                'abstention' => (int) ($r['abstention'] ?? 0),
                'blanc'      => (int) ($r['blanc'] ?? 0),
            ];
            if (array_sum($voix) > $voixPresentes) {
                throw new \RuntimeException("Résolution {$numero} : {$voix['pour']}+{$voix['contre']}+... voix dépassent les {$voixPresentes} voix présentes ou représentées.");
            }

            $adoptee = $this->calcul->resolutionAdoptee($voix, $regle, $voixPresentes);

            $this->resolutions->insert([
                'assemblee_id' => $assembleeId, 'numero' => $numero, 'texte' => $r['texte'],
                'voix_pour' => $voix['pour'], 'voix_contre' => $voix['contre'],
                'abstentions' => $voix['abstention'], 'voix_blanches' => $voix['blanc'],
                'majorite_requise_num' => $regle['majorite_num'], 'majorite_requise_den' => $regle['majorite_den'],
                'adoptee' => $adoptee ? 1 : 0,
            ]);

            $resultats[] = [
                'numero' => $numero, 'texte' => $r['texte'],
                'pour' => $voix['pour'], 'contre' => $voix['contre'],
                'majorite_requise' => $regle['majorite_num'] . '/' . $regle['majorite_den'],
                'adoptee' => $adoptee,
                // égalité au CA : la voix du président est prépondérante (art. 483) -> décision à saisir par le président
                'egalite_voix_president' => (int) $regle['voix_preponderante_president'] === 1 && $voix['pour'] === $voix['contre'],
            ];
            $numero++;
        }

        return $resultats;
    }

    /** Objet « assemblee » du contrat d'interface JSON (entrée du générateur de PV). */
    public function versJson(int $assembleeId): array
    {
        $assemblee = $this->assemblees->find($assembleeId) ?? throw new \RuntimeException("Assemblée #{$assembleeId} introuvable.");
        $parts = $this->participants->where('assemblee_id', $assembleeId)->findAll();

        $noms = array_column($parts, 'nom', 'id');
        $par  = [];
        foreach ($this->pouvoirs->where('assemblee_id', $assembleeId)->findAll() as $p) {
            $par[$p['mandant_id']] = $noms[$p['mandataire_id']] ?? null;
        }

        $participants = [];
        foreach ($parts as $p) {
            $ligne = ['nom' => $p['nom'], 'statut' => $p['statut']];
            if ($p['statut'] === 'represente') {
                $ligne['par'] = $par[$p['id']] ?? null;
            }
            $ligne['voix'] = (int) $p['voix'];
            $participants[] = $ligne;
        }

        $resolutions = [];
        foreach ($this->resolutions->where('assemblee_id', $assembleeId)->orderBy('numero')->findAll() as $r) {
            $resolutions[] = [
                'texte' => $r['texte'], 'pour' => (int) $r['voix_pour'], 'contre' => (int) $r['voix_contre'],
                'adoptee' => (bool) $r['adoptee'],
            ];
        }

        return [
            'participants'   => $participants,
            'quorum_atteint' => (bool) $assemblee['quorum_atteint'],
            'resolutions'    => $resolutions,
        ];
    }

    /** Voix totales : somme des titres (assemblée) ou nombre d'administrateurs (CA). */
    private function voixTotales(array $societe, string $nature): int
    {
        if ($nature === 'CA') {
            return $this->dirigeants->where('societe_id', $societe['id'])
                ->whereIn('fonction', ['administrateur', 'pca', 'pdg'])->countAllResults();
        }
        $row = $this->associes->selectSum('nb_titres', 'total')->where('societe_id', $societe['id'])->first();
        return (int) ($row['total'] ?? 0);
    }

    /** Voix d'un convoqué : ses titres (associé) ou 1 (administrateur). */
    private function voixDe(array $convoque, string $nature): int
    {
        if ($nature === 'CA') {
            return 1;
        }
        if ($convoque['associe_id']) {
            return (int) $this->associes->find($convoque['associe_id'])['nb_titres'];
        }
        return 0;
    }
}
