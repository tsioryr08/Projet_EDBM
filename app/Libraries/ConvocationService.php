<?php

namespace App\Libraries;

use App\Models\ConvoqueModel;
use App\Models\RegleDelaiModel;
use App\Models\ReunionModel;
use App\Models\SocieteModel;
use App\Models\TypeSocieteModel;
use RuntimeException;

/**
 * Prépare une convocation à partir d'une réunion déjà planifiée.
 *
 * Les dates, délais, modes de calcul et sources sont uniquement recopiés
 * depuis la réunion et les données retournées par la planification.
 */
class ConvocationService
{
    public function __construct(
        private ?ReunionModel $reunions = null,
        private ?SocieteModel $societes = null,
        private ?TypeSocieteModel $types = null,
        private ?ConvoqueModel $convoques = null,
        private ?RegleDelaiModel $delais = null,
    ) {
        $this->reunions  ??= new ReunionModel();
        $this->societes  ??= new SocieteModel();
        $this->types     ??= new TypeSocieteModel();
        $this->convoques ??= new ConvoqueModel();
        $this->delais    ??= new RegleDelaiModel();
    }

    /**
     * @return array{
     *     vue:string,
     *     societe_id:int,
     *     reunion_id:int,
     *     nature:string,
     *     nom_fichier:string,
     *     data:array<string,mixed>
     * }
     */
    public function preparer(int $reunionId): array
    {
        $reunion = $this->reunions->find($reunionId);
        if (! $reunion) {
            throw new RuntimeException("Réunion #{$reunionId} introuvable.");
        }

        $societe = $this->societes->find((int) $reunion['societe_id']);
        if (! $societe) {
            throw new RuntimeException("Société #{$reunion['societe_id']} introuvable.");
        }

        $type = $this->types->find((int) $societe['type_societe_id']);
        if (! $type) {
            throw new RuntimeException("Type de société {$societe['type_societe_id']} introuvable.");
        }

        $ordreDuJour = json_decode((string) ($reunion['ordre_du_jour'] ?? '[]'), true);
        if (! is_array($ordreDuJour)) {
            throw new RuntimeException("L'ordre du jour de la réunion #{$reunionId} est invalide.");
        }

        $nature = (string) $reunion['nature_code'];
        $convoques = $this->convoques
            ->where('reunion_id', $reunionId)
            ->orderBy('id', 'ASC')
            ->findAll();
        $regle = $this->delais->trouver(
            (int) $type['id'],
            $nature,
            (int) $reunion['numero_convocation']
        );

        $data = [
            'titre'             => $this->titre($nature),
            'societe'           => $societe['denomination'],
            'type'              => $type['code'],
            'nature'            => $nature,
            'numero_convocation'=> (int) $reunion['numero_convocation'],
            'date_reunion'      => $reunion['date_reunion'],
            'heure'             => substr((string) $reunion['heure'], 0, 5),
            'lieu'              => $reunion['lieu'],
            'ordre_du_jour'     => array_values($ordreDuJour),
            'delai_jours'       => $reunion['delai_applique'],
            'mode_calcul'       => $reunion['mode_calcul_delai'],
            'date_convocation'  => $reunion['date_convocation'],
            'convoques'         => $convoques,
            'forme_envoi'       => $regle['forme_envoi'] ?? null,
            'source'            => $regle['source'] ?? null,
            'verifie'           => $regle === null ? null : (bool) $regle['verifie'],
            'justification'     => null,
        ];

        return [
            'vue'         => $nature === 'CA'
                ? 'pdf/convocation/ca'
                : 'pdf/convocation/ag',
            'societe_id'  => (int) $societe['id'],
            'reunion_id'  => $reunionId,
            'nature'      => 'convocation',
            'nom_fichier' => $this->nomFichier((string) $societe['denomination'], $nature, $reunionId),
            'data'        => $data,
        ];
    }

    private function titre(string $nature): string
    {
        return $nature === 'CA'
            ? 'Convocation du conseil d’administration'
            : 'Convocation à une assemblée';
    }

    private function nomFichier(string $societe, string $nature, int $reunionId): string
    {
        $base = preg_replace('/[^A-Za-z0-9]+/', '-', $societe) ?? 'societe';
        return 'convocation-' . trim($base, '-') . '-' . $nature . '-' . $reunionId . '.pdf';
    }
}
