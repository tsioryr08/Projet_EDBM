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
 * Chaque décision est accompagnée de sa justification juridique (Justification).
 */
class AssembleeService
{
    private CalculRegles $calcul;
    private Justification $justif;
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
        $this->justif       = new Justification();
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
     * @param array $participants lignes ['nom', 'statut' => present|represente|absent, 'par' => mandataire (si represente), 'voix' => optionnel]
     *                            Les convoqués non listés sont ajoutés comme absents. Les voix viennent de la base (nb_titres) sauf indication.
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

        $regle = $this->quorums->trouver((int) $type['id'], $nature, $numero);
        if (! $regle && $nature === 'CA' && $numero > 1) {
            // L'article 483 maintient le quorum du CA à chaque séance; les statuts peuvent prévoir davantage.
            $regle = $this->quorums->trouver((int) $type['id'], $nature, 1);
        }
        $regle ??= throw new \RuntimeException("Règle de quorum non renseignée pour {$type['code']} / {$nature} / convocation n°{$numero} (à chercher dans la loi).");

        $voixTotales = $this->voixTotales($societe, $nature);
        $lignes      = $this->construireLignesParticipants($participants, $this->convoques->where('reunion_id', $reunionId)->findAll(), $nature);
        $quorumAtteint = $this->calcul->calculerQuorum($lignes, $regle, $voixTotales);
        $voixPresentes = $this->totalVoixPresentes($lignes);
        $justification = $this->justif->quorum($regle, $voixPresentes, $voixTotales, $quorumAtteint);

        $db = db_connect();
        $db->transBegin();
        try {
            $assembleeId = $this->créerAssemblee($reunionId, $dateTenue, $president, $secretaire, $voixTotales, $voixPresentes, $regle, $quorumAtteint, $justification);
            $ids = $this->enregistrerParticipants($assembleeId, $lignes);
            $this->enregistrerPouvoirs($assembleeId, $lignes, $nature, $ids);
            $this->reunions->update($reunionId, ['statut' => $quorumAtteint ? 'tenue' : 'sans_quorum']);
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }

        $resume = [
            'assemblee_id'   => $assembleeId,
            'quorum_requis'  => $regle['quorum_num'] . '/' . $regle['quorum_den'],
            'voix_presentes_ou_representees' => $voixPresentes,
            'voix_totales'   => $voixTotales,
            'quorum_atteint' => $quorumAtteint,
            'source'         => $regle['source'],
            'verifie'        => (bool) $regle['verifie'],
            'justification'  => $justification,
            'pv_annulation'  => $quorumAtteint
                ? null
                : [
                    'type' => 'constat_non_quorum',
                    'message' => "Le quorum n'est pas atteint : la séance est constatée comme annulée pour défaut de quorum. Un procès-verbal de constat ou d'annulation doit être dressé.",
                ],
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

    private function construireLignesParticipants(array $participants, array $convoques, string $nature): array
    {
        $listeParNom = [];
        foreach ($convoques as $c) {
            $listeParNom[$c['nom_affiche']] = $c;
        }

        $lignes = [];
        foreach ($participants as $p) {
            $nom = $p['nom'];
            if (! isset($listeParNom[$nom])) {
                throw new \RuntimeException("{$nom} n'a pas été convoqué(e) à cette réunion.");
            }
            if (isset($lignes[$nom])) {
                throw new \RuntimeException("{$nom} est saisi(e) deux fois.");
            }
            $this->validerParticipant($nom, $p, $nature);
            $lignes[$nom] = [
                'nom' => $nom, 'statut' => $p['statut'], 'par' => $p['par'] ?? null,
                'par_externe' => (bool) ($p['par_externe'] ?? false),
                'voix' => (int) ($p['voix'] ?? $this->voixDe($listeParNom[$nom], $nature)),
                'convoque_id' => (int) $listeParNom[$nom]['id'],
            ];
        }

        foreach ($listeParNom as $nom => $c) {
            if (! isset($lignes[$nom])) {
                $lignes[$nom] = [
                    'nom' => $nom,
                    'statut' => 'absent',
                    'par' => null,
                    'par_externe' => false,
                    'voix' => $this->voixDe($c, $nature),
                    'convoque_id' => (int) $c['id'],
                ];
            }
        }

        return $lignes;
    }

    private function validerParticipant(string $nom, array $p, string $nature): void
    {
        if (! in_array($p['statut'], ['present', 'represente', 'absent'], true)) {
            throw new \RuntimeException("Statut invalide pour {$nom} : {$p['statut']}");
        }
        if ($p['statut'] === 'represente' && empty($p['par'])) {
            throw new \RuntimeException("{$nom} est représenté(e) : indiquez le mandataire (champ 'par').");
        }
        if (($p['par_externe'] ?? false) && $nature === 'CA') {
            throw new \RuntimeException("Pour un conseil d'administration, le mandataire doit être choisi parmi les membres convoqués et présents.");
        }
    }

    private function totalVoixPresentes(array $lignes): int
    {
        $total = 0;
        foreach ($lignes as $ligne) {
            if ($ligne['statut'] !== 'absent') {
                $total += $ligne['voix'];
            }
        }
        return $total;
    }

    private function créerAssemblee(int $reunionId, ?string $dateTenue, ?string $president, ?string $secretaire, int $voixTotales, int $voixPresentes, array $regle, bool $quorumAtteint, array $justification): int
    {
        return $this->verifierInsertion($this->assemblees->insert([
            'reunion_id'                     => $reunionId,
            'date_tenue'                     => $dateTenue ?? $this->reunions->find($reunionId)['date_reunion'],
            'president_seance'               => $president,
            'secretaire'                     => $secretaire,
            'voix_totales'                   => $voixTotales,
            'voix_presentes_ou_representees' => $voixPresentes,
            'quorum_requis_num'              => $regle['quorum_num'],
            'quorum_requis_den'              => $regle['quorum_den'],
            'quorum_atteint'                 => $quorumAtteint ? 1 : 0,
            'motif_quorum'                   => $justification['motif'],
        ]), 'assemblée');
    }

    private function enregistrerParticipants(int $assembleeId, array $lignes): array
    {
        $ids = [];
        foreach ($lignes as $nom => $ligne) {
            $ids[$nom] = $this->verifierInsertion($this->participants->insert([
                'assemblee_id' => $assembleeId,
                'convoque_id' => $ligne['convoque_id'],
                'nom' => $nom,
                'statut' => $ligne['statut'],
                'voix' => $ligne['voix'],
            ]), "participant {$nom}");
        }
        return $ids;
    }

    private function enregistrerPouvoirs(int $assembleeId, array $lignes, string $nature, array $ids): void
    {
        $existants = [];
        foreach ($lignes as $nom => $ligne) {
            if ($ligne['statut'] !== 'represente') {
                continue;
            }
            if (! empty($ligne['par_externe'])) {
                $this->verifierInsertion($this->pouvoirs->insert([
                    'assemblee_id' => $assembleeId,
                    'mandant_id' => $ids[$nom],
                    'mandataire_id' => null,
                    'mandataire_nom_externe' => $ligne['par'],
                ]), "mandataire externe de {$nom}");
                continue;
            }
            if (! isset($ids[$ligne['par']]) || $lignes[$ligne['par']]['statut'] !== 'present') {
                throw new \RuntimeException("Le mandataire de {$nom} ({$ligne['par']}) doit être présent(e) à la réunion.");
            }
            if (! $this->calcul->procurationValide($nature, (int) $ids[$nom], (int) $ids[$ligne['par']], $existants)) {
                throw new \RuntimeException("Procuration refusée : {$nom} représenté(e) par {$ligne['par']} (une seule procuration par mandataire au conseil d'administration, art. 485).");
            }
            $existants[] = ['mandant_id' => $ids[$nom], 'mandataire_id' => $ids[$ligne['par']]];
            $this->verifierInsertion($this->pouvoirs->insert([
                'assemblee_id' => $assembleeId,
                'mandant_id' => $ids[$nom],
                'mandataire_id' => $ids[$ligne['par']],
            ]), "procuration de {$nom}");
        }
    }

    /**
     * Enregistre les résolutions et calcule l'adoption de chacune.
     *
     * @param array $resolutions lignes ['texte', 'pour', 'contre', 'abstention', 'blanc',
     *                           'sens_vote_president' => 'pour'|'contre'|'abstention' (optionnel)]
     *
     * Le président de séance vote comme les autres : son vote est déjà compris dans pour/contre.
     * Partage des voix : si la règle donne la voix prépondérante au président (CA, art. 483), c'est son vote
     * qui l'emporte ('abstention' = abstenu ou absent -> rejetée). S'il n'est pas encore connu (null),
     * la résolution est enregistrée « en attente » (adoptee = null) ; utiliser departager().
     */
    public function voter(int $assembleeId, array $resolutions): array
    {
        $assemblee = $this->assemblees->find($assembleeId) ?? throw new \RuntimeException("Assemblée #{$assembleeId} introuvable.");
        if ((int) $assemblee['quorum_atteint'] !== 1) {
            throw new \RuntimeException("Quorum non atteint : l'assemblée ne peut pas délibérer.");
        }

        $regle         = $this->regleDe($assemblee);
        $voixPresentes = (int) $assemblee['voix_presentes_ou_representees'];
        $resultats     = [];
        $numero        = (int) $this->resolutions->where('assemblee_id', $assembleeId)->countAllResults() + 1;

        foreach ($resolutions as $r) {
            $voix = [
                'pour'       => (int) ($r['pour'] ?? 0),
                'contre'     => (int) ($r['contre'] ?? 0),
                'abstention' => (int) ($r['abstention'] ?? 0),
                'blanc'      => (int) ($r['blanc'] ?? 0),
            ];
            if (array_sum($voix) > $voixPresentes) {
                throw new \RuntimeException("Résolution {$numero} : le total des voix ({$this->somme($voix)}) dépasse les {$voixPresentes} voix présentes ou représentées.");
            }
            $sensPresident = $r['sens_vote_president'] ?? null;
            if (! in_array($sensPresident, [null, 'pour', 'contre', 'abstention'], true)) {
                throw new \RuntimeException("sens_vote_president doit valoir 'pour', 'contre' ou 'abstention'.");
            }

            [$adoptee, $enAttente, $prepUtilisee] = $this->decider($regle, $voix, $voixPresentes, $sensPresident);
            $justification = $this->justif->adoption($regle, $voix, $this->baseMajorite($regle, $voix, $voixPresentes), $adoptee, $sensPresident);

            $adopteeBinaire = $adoptee === null ? null : ($adoptee ? 1 : 0);

            $this->resolutions->insert([
                'assemblee_id' => $assembleeId, 'numero' => $numero, 'texte' => $r['texte'],
                'voix_pour' => $voix['pour'], 'voix_contre' => $voix['contre'],
                'abstentions' => $voix['abstention'], 'voix_blanches' => $voix['blanc'],
                'majorite_requise_num' => $regle['majorite_num'], 'majorite_requise_den' => $regle['majorite_den'],
                'adoptee' => $adopteeBinaire,
                'sens_vote_president' => $sensPresident,
                'voix_preponderante_utilisee' => $prepUtilisee ? 1 : 0,
                'motif' => $justification['motif'],
                'source' => $regle['source'],
                'statut_regle' => $justification['statut'],
            ]);

            $resultats[] = [
                'numero' => $numero, 'texte' => $r['texte'],
                'pour' => $voix['pour'], 'contre' => $voix['contre'],
                'abstention' => $voix['abstention'], 'blanc' => $voix['blanc'],
                'sens_vote_president' => $sensPresident,
                'majorite_requise' => $regle['majorite_num'] . '/' . $regle['majorite_den'],
                'adoptee' => $adoptee,                  // true | false | null (en attente de la voix du président)
                'en_attente_voix_president' => $enAttente,
                'voix_preponderante_utilisee' => $prepUtilisee,
                'justification' => $justification,
            ];
            $numero++;
        }

        return $resultats;
    }

    /**
     * Départage une résolution restée en attente (partage des voix, art. 483).
     * $sensPresident : vote du PRÉSIDENT DE SÉANCE ('pour' | 'contre' | 'abstention' = abstenu ou absent).
     */
    public function departager(int $assembleeId, int $numeroResolution, string $sensPresident): array
    {
        if (! in_array($sensPresident, ['pour', 'contre', 'abstention'], true)) {
            throw new \RuntimeException("Le sens du vote du président doit être 'pour', 'contre' ou 'abstention'.");
        }
        $assemblee  = $this->assemblees->find($assembleeId) ?? throw new \RuntimeException("Assemblée #{$assembleeId} introuvable.");
        $resolution = $this->resolutions->where(['assemblee_id' => $assembleeId, 'numero' => $numeroResolution])->first()
            ?? throw new \RuntimeException("Résolution n°{$numeroResolution} introuvable.");
        if ($resolution['adoptee'] !== null) {
            throw new \RuntimeException("La résolution n°{$numeroResolution} n'est pas en attente de la voix du président.");
        }

        $regle = $this->regleDe($assemblee);
        $voix  = ['pour' => (int) $resolution['voix_pour'], 'contre' => (int) $resolution['voix_contre'],
                  'abstention' => (int) $resolution['abstentions'], 'blanc' => (int) $resolution['voix_blanches']];

        [$adoptee, , $prepUtilisee] = $this->decider($regle, $voix, (int) $assemblee['voix_presentes_ou_representees'], $sensPresident);
        $justification = $this->justif->adoption($regle, $voix, $this->baseMajorite($regle, $voix, (int) $assemblee['voix_presentes_ou_representees']), $adoptee, $sensPresident);

        $this->resolutions->update($resolution['id'], [
            'adoptee' => $adoptee ? 1 : 0, 'sens_vote_president' => $sensPresident,
            'voix_preponderante_utilisee' => $prepUtilisee ? 1 : 0,
            'motif' => $justification['motif'], 'source' => $regle['source'], 'statut_regle' => $justification['statut'],
        ]);

        return ['numero' => $numeroResolution, 'texte' => $resolution['texte'], 'adoptee' => $adoptee,
                'voix_preponderante_utilisee' => $prepUtilisee, 'justification' => $justification];
    }

    /** Objet « assemblee » du contrat d'interface JSON (entrée du générateur de PV). */
    public function versJson(int $assembleeId): array
    {
        $assemblee = $this->assemblees->find($assembleeId) ?? throw new \RuntimeException("Assemblée #{$assembleeId} introuvable.");
        $regle     = $this->regleDe($assemblee, false); // pas d'erreur si la majorité est « à chercher » (ex. quorum non atteint)
        $parts     = $this->participants->where('assemblee_id', $assembleeId)->findAll();

        $noms = array_column($parts, 'nom', 'id');
        $par  = [];
        foreach ($this->pouvoirs->where('assemblee_id', $assembleeId)->findAll() as $p) {
            $par[$p['mandant_id']] = ($p['mandataire_nom_externe'] ?? null) ?: ($noms[$p['mandataire_id']] ?? null);
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
            $prep = (bool) $r['voix_preponderante_utilisee'];
            $resolutions[] = [
                'texte' => $r['texte'], 'pour' => (int) $r['voix_pour'], 'contre' => (int) $r['voix_contre'],
                'adoptee' => $r['adoptee'] === null ? null : (bool) $r['adoptee'],
                'sens_vote_president' => $r['sens_vote_president'],
                'voix_preponderante' => $prep,
                // à reprendre tel quel dans le PV quand la voix prépondérante a décidé
                'mention_pv' => $prep ? 'Voix prépondérante du président de séance (' . ($r['source'] ?? 'art. 483') . ')' : null,
                'motif' => $r['motif'],
                'source' => $r['source'],
                'statut_regle' => $r['statut_regle'],
            ];
        }

        $reunion = $this->reunions->find($assemblee['reunion_id']);
        $societe = $this->societes->find($reunion['societe_id']);
        $typeSociete = $this->types->find($societe['type_societe_id']);

        $ordreDuJour = json_decode($reunion['ordre_du_jour'] ?? '[]', true);

        return [
            // --- la réunion (pour choisir le gabarit de PV et remplir l'en-tête) ---
            'societe'            => $societe['denomination'],
            'type_societe'       => $typeSociete['code'] ?? '',
            'nature'             => $reunion['nature_code'],          // AGC | AGO | AGE | CA
            'numero_convocation' => (int) $reunion['numero_convocation'],
            'date_reunion'       => $reunion['date_reunion'],
            'heure'              => substr((string) $reunion['heure'], 0, 5),
            'lieu'               => $reunion['lieu'],
            'ordre_du_jour'      => is_array($ordreDuJour) ? $ordreDuJour : [],
            // --- la séance (T1) ---
            'date_tenue'         => $assemblee['date_tenue'],
            'president_seance'   => $assemblee['president_seance'],
            'secretaire'         => $assemblee['secretaire'],
            // --- quorum ---
            'voix_totales'       => (int) $assemblee['voix_totales'],
            'voix_presentes_ou_representees' => (int) $assemblee['voix_presentes_ou_representees'],
            'quorum_requis'      => $assemblee['quorum_requis_num'] . '/' . $assemblee['quorum_requis_den'],   // ex. "1/4"
            'quorum_atteint'     => (bool) $assemblee['quorum_atteint'],
            'motif_quorum'       => $assemblee['motif_quorum'],
            'pv_constat'         => (bool) $assemblee['quorum_atteint'] ? null : [
                'type' => 'constat_non_quorum',
                'message' => "Le quorum n'est pas atteint : la séance est constatée comme annulée. Un procès-verbal de constat ou d'annulation est dressé pour formaliser le défaut de quorum.",
            ],
            // --- majorité (null si « à chercher ») ---
            'majorite_requise'   => $regle['majorite_num'] !== null ? $regle['majorite_num'] . '/' . $regle['majorite_den'] : null,
            'majorite_stricte'   => $regle['majorite_stricte'] !== null ? (bool) $regle['majorite_stricte'] : null, // true : « plus de » ; false : « au moins »
            'majorite_base'      => $regle['majorite_base'],         // voix_exprimees | presents_ou_representes | null
            // --- participants et décisions ---
            'participants'       => $participants,
            'resolutions'        => $resolutions,
            'source_regle'       => $regle['source'] ?? null,
            'regle_verifiee'     => (bool) ($regle['verifie'] ?? false),
            'base_legale_non_quorum' => $this->justif->baseLegaleNonQuorum(
                (string) ($typeSociete['code'] ?? ''),
                (string) $reunion['nature_code'],
                (int) $reunion['numero_convocation'],
                $regle['source'] ?? null,
            ),
        ];
    }

    // ------------------------------------------------------------------

    /**
     * Une insertion qui échoue renvoie false sans exception quand le mode d'erreurs de CI4 est coupé
     * (environnement « production »). On la transforme en exception claire plutôt que de continuer avec un identifiant vide.
     */
    private function verifierInsertion(mixed $id, string $objet): int
    {
        if (! $id) {
            $erreur = db_connect()->error();
            throw new \RuntimeException("Échec de l'enregistrement ({$objet}) : " . ($erreur['message'] ?: 'erreur inconnue (voir writable/logs)'));
        }
        return (int) $id;
    }

    /** Décide l'adoption. Renvoie [adoptee (bool|null), enAttente (bool), voixPreponderanteUtilisee (bool)]. */
    private function decider(array $regle, array $voix, int $voixPresentes, ?string $sensPresident): array
    {
        $egalite = $voix['pour'] === $voix['contre'] && $voix['pour'] > 0;

        if ($egalite && (int) $regle['voix_preponderante_president'] === 1) {
            if ($sensPresident === null) {
                return [null, true, false];
            }
            // 'abstention' (abstenu ou absent) : pas de voix prépondérante exercée -> rejetée
            return [$sensPresident === 'pour', false, in_array($sensPresident, ['pour', 'contre'], true)];
        }
        return [$this->calcul->resolutionAdoptee($voix, $regle, $voixPresentes), false, false];
    }

    private function baseMajorite(array $regle, array $voix, int $voixPresentes): int
    {
        return $regle['majorite_base'] === 'voix_exprimees' ? $voix['pour'] + $voix['contre'] : $voixPresentes;
    }

    private function somme(array $voix): int
    {
        return array_sum($voix);
    }

    /** Règle de quorum/majorité de l'assemblée ; exception si la majorité est « à chercher » (sauf si $exigerMajorite = false). */
    private function regleDe(array $assemblee, bool $exigerMajorite = true): array
    {
        $reunion = $this->reunions->find($assemblee['reunion_id']);
        $societe = $this->societes->find($reunion['societe_id']);
        $regle   = $this->quorums->trouver((int) $societe['type_societe_id'], $reunion['nature_code'], (int) $reunion['numero_convocation']);
        if ($regle === null || ($exigerMajorite && $regle['majorite_base'] === null)) {
            throw new \RuntimeException("Majorité non renseignée pour {$reunion['nature_code']} (à chercher dans la loi).");
        }
        return $regle;
    }

    private function voixTotales(array $societe, string $nature): int
    {
        if ($nature === 'CA') {
            return $this->dirigeants->where('societe_id', $societe['id'])
                ->whereIn('fonction', ['administrateur', 'pca', 'pdg'])->countAllResults();
        }
        $row = $this->associes->selectSum('nb_titres', 'total')->where('societe_id', $societe['id'])->first();
        return (int) ($row['total'] ?? 0);
    }

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
