<?php

namespace App\Libraries;

use App\Models\AssocieModel;
use App\Models\ConvoqueModel;
use App\Models\DirigeantModel;
use App\Models\RegleDelaiModel;
use App\Models\ReunionModel;
use App\Models\SocieteModel;
use App\Models\TypeSocieteModel;
use DateTimeImmutable;

/**
 * Planifie une réunion : lit le délai dans regle_delai (jamais en dur),
 * calcule la date limite de convocation, enregistre la réunion et ses convoqués.
 */
class ReunionService
{
    public function __construct(
        private ?CalculRegles $calcul = null,
        private ?SocieteModel $societes = null,
        private ?TypeSocieteModel $types = null,
        private ?RegleDelaiModel $delais = null,
        private ?ReunionModel $reunions = null,
        private ?ConvoqueModel $convoques = null,
        private ?AssocieModel $associes = null,
        private ?DirigeantModel $dirigeants = null,
    ) {
        $this->calcul     ??= new CalculRegles();
        $this->societes   ??= new SocieteModel();
        $this->types      ??= new TypeSocieteModel();
        $this->delais     ??= new RegleDelaiModel();
        $this->reunions   ??= new ReunionModel();
        $this->convoques  ??= new ConvoqueModel();
        $this->associes   ??= new AssocieModel();
        $this->dirigeants ??= new DirigeantModel();
    }

    /**
     * @param string[] $ordreDuJour
     * @param ?string  $modeCalcul   null = mode de la règle en base (franc par défaut)
     * @param ?int     $delaiStatuts délai imposé par les statuts quand la loi ne le fixe pas (ex. CA)
     *
     * @return array résultat du calcul (+ 'reunion_id' si enregistré)
     * @throws \RuntimeException si la règle est « à chercher » ou la société introuvable
     */
    public function planifier(
        int $societeId,
        string $nature,
        string $dateReunion,
        string $heure,
        string $lieu,
        array $ordreDuJour = [],
        int $numeroConvocation = 1,
        ?string $modeCalcul = null,
        ?int $delaiStatuts = null,
        bool $enregistrer = true,
    ): array {
        $societe = $this->societes->find($societeId);
        if (! $societe) {
            throw new \RuntimeException("Société #{$societeId} introuvable.");
        }
        $type = $this->types->find($societe['type_societe_id']);

        $regle = $this->delais->trouver((int) $societe['type_societe_id'], $nature, $numeroConvocation);
        if (! $regle) {
            throw new \RuntimeException(
                "Règle de délai non renseignée pour {$type['code']} / {$nature} / convocation n°{$numeroConvocation} (à chercher dans la loi)."
            );
        }

        $delai = $regle['delai_jours'] !== null ? (int) $regle['delai_jours'] : $delaiStatuts;
        if ($delai === null) {
            throw new \RuntimeException("Le délai de {$nature} est fixé par les statuts : indiquez-le (paramètre delaiStatuts).");
        }

        $mode = $modeCalcul ?? $regle['mode_calcul'];

        $joursFeries = [];
        if ($mode === 'ouvrable') {
            $joursFeries = array_column(db_connect()->table('jour_ferie')->select('date_ferie')->get()->getResultArray(), 'date_ferie');
        }

        $dateConvocation = $this->calcul->calculerDateConvocation($dateReunion, $delai, $mode, $joursFeries);

        $resultat = [
            'societe'          => $societe['denomination'],
            'type'             => $type['code'],
            'nature'           => $nature,
            'numero_convocation' => $numeroConvocation,
            'date_reunion'     => $dateReunion,
            'heure'            => $heure,
            'lieu'             => $lieu,
            'delai_jours'      => $delai,
            'mode_calcul'      => $mode,
            'date_convocation' => $dateConvocation->format('Y-m-d'),
            'forme_envoi'      => $regle['forme_envoi'],
            'source'           => $regle['source'],
            'verifie'          => (bool) $regle['verifie'],
            // true si la date limite d'envoi est déjà passée
            'en_retard'        => $dateConvocation < new DateTimeImmutable('today'),
        ];

        if (! $enregistrer) {
            return $resultat;
        }

        $db = db_connect();
        $db->transStart();

        $reunionId = $this->reunions->insert([
            'societe_id'         => $societeId,
            'nature_code'        => $nature,
            'numero_convocation' => $numeroConvocation,
            'date_reunion'       => $dateReunion,
            'heure'              => $heure,
            'lieu'               => $lieu,
            'ordre_du_jour'      => json_encode($ordreDuJour, JSON_UNESCAPED_UNICODE),
            'mode_calcul_delai'  => $mode,
            'delai_applique'     => $delai,
            'date_convocation'   => $resultat['date_convocation'],
            'statut'             => 'planifiee',
        ]);

        $resultat['reunion_id'] = $reunionId;
        $resultat['convoques']  = $this->creerConvoques((int) $reunionId, $societe, $type['code'], $nature);

        $db->transComplete();

        return $resultat;
    }

    /** Qui est convoqué : les administrateurs pour un CA, les associés/actionnaires pour une assemblée. */
    private function creerConvoques(int $reunionId, array $societe, string $typeCode, string $nature): array
    {
        $lignes = [];

        if ($nature === 'CA') {
            $admins = $this->dirigeants->where('societe_id', $societe['id'])
                ->whereIn('fonction', ['administrateur', 'pca', 'pdg'])->findAll();
            foreach ($admins as $d) {
                $lignes[] = ['reunion_id' => $reunionId, 'role' => 'administrateur', 'dirigeant_id' => $d['id'], 'nom_affiche' => $d['nom']];
            }
        } else {
            $role = $typeCode === 'SA' ? 'actionnaire' : 'associe';
            foreach ($this->associes->where('societe_id', $societe['id'])->findAll() as $a) {
                $lignes[] = ['reunion_id' => $reunionId, 'role' => $role, 'associe_id' => $a['id'], 'nom_affiche' => $a['nom']];
            }
        }

        if ($lignes) {
            $this->convoques->insertBatch($lignes);
        }

        return array_column($lignes, 'nom_affiche');
    }
}
