<?php

namespace App\Libraries;

class SuiviDossierService
{
    private const STATUTS_AUTORISES = ['brouillon', 'soumis', 'valide', 'immatricule'];
    private const TRANSITIONS = [
        'brouillon' => ['brouillon', 'soumis'],
        'soumis' => ['brouillon', 'valide'],
        'valide' => ['immatricule'],
        'immatricule' => [],
    ];

    /**
     * Change le statut du dossier et enregistre la date correspondante.
     *
     * @return array{
     *   societe_id:int,
     *   statut:string,
     *   ancien_statut:string|null,
     *   date_soumission:string|null,
     *   date_validation:string|null,
     *   date_immatriculation:string|null,
     *   date_maj:string,
     *   message:string
     * }
     */
    public function changerStatut(int $societeId, string $nouveauStatut, ?string $date = null): array
    {
        $db = db_connect();
        $statutCible = $this->normaliserStatut($nouveauStatut);
        $dateMaj = $date ?? date('Y-m-d');

        $dossier = $db->table('dossier')->where('societe_id', $societeId)->get()->getRowArray();
        if ($dossier === null) {
            if ($statutCible !== 'brouillon') {
                throw new \RuntimeException('Le dossier doit être créé en brouillon avant toute soumission.');
            }
            $db->table('dossier')->insert([
                'societe_id' => $societeId,
                'statut'     => $statutCible,
            ]);
            $dossier = $db->table('dossier')->where('societe_id', $societeId)->get()->getRowArray();
        }

        $ancienStatut = $dossier['statut'] ?? null;
        if ($ancienStatut !== null && ! in_array($statutCible, self::TRANSITIONS[$ancienStatut] ?? [], true)) {
            throw new \RuntimeException("Transition impossible : {$ancienStatut} → {$statutCible}. Transitions autorisées : " . implode(', ', self::TRANSITIONS[$ancienStatut] ?? []) . '.');
        }
        $updates = ['statut' => $statutCible];

        switch ($statutCible) {
            case 'brouillon':
                $updates['date_soumission'] = null;
                $updates['date_validation'] = null;
                $updates['date_immatriculation'] = null;
                break;

            case 'soumis':
                $updates['date_soumission'] = $dossier['date_soumission'] ?? $dateMaj;
                break;

            case 'valide':
                $updates['date_validation'] = $dossier['date_validation'] ?? $dateMaj;
                break;

            case 'immatricule':
                $updates['date_immatriculation'] = $dossier['date_immatriculation'] ?? $dateMaj;
                break;
        }

        $db->transStart();
        $db->table('dossier')->where('societe_id', $societeId)->update($updates);
        if ($ancienStatut !== $statutCible && $db->tableExists('dossier_statut_historique')) {
            $db->table('dossier_statut_historique')->insert([
                'dossier_id' => $dossier['id'],
                'ancien_statut' => $ancienStatut,
                'nouveau_statut' => $statutCible,
                'date_changement' => $dateMaj,
                'auteur' => 'interface',
            ]);
        }
        $db->transComplete();

        $dossierActuel = $db->table('dossier')->where('societe_id', $societeId)->get()->getRowArray();

        return [
            'societe_id' => (int) $societeId,
            'statut' => $dossierActuel['statut'],
            'ancien_statut' => $ancienStatut,
            'date_soumission' => $dossierActuel['date_soumission'] ?? null,
            'date_validation' => $dossierActuel['date_validation'] ?? null,
            'date_immatriculation' => $dossierActuel['date_immatriculation'] ?? null,
            'date_maj' => $dateMaj,
            'message' => $this->messagePourStatut($dossierActuel['statut']),
            'messages' => [
                'infos' => [$this->messagePourStatut($dossierActuel['statut'])],
                'avertissements' => [],
                'bloquants' => [],
            ],
        ];
    }

    /**
     * Retourne l’état actuel du dossier pour une société.
     */
    public function etat(int $societeId): array
    {
        $db = db_connect();
        $dossier = $db->table('dossier')->where('societe_id', $societeId)->get()->getRowArray();

        if ($dossier === null) {
            return [
                'societe_id' => $societeId,
                'statut' => 'brouillon',
                'date_soumission' => null,
                'date_validation' => null,
                'date_immatriculation' => null,
            ];
        }

        return [
            'societe_id' => (int) $societeId,
            'statut' => $dossier['statut'] ?? 'brouillon',
            'date_soumission' => $dossier['date_soumission'] ?? null,
            'date_validation' => $dossier['date_validation'] ?? null,
            'date_immatriculation' => $dossier['date_immatriculation'] ?? null,
            'transitions_possibles' => $this->transitionsPossibles($dossier['statut'] ?? 'brouillon'),
            'historique' => $this->historique($dossier['id'] ?? null),
        ];
    }

    public function transitionsPossibles(string $statut): array
    {
        return self::TRANSITIONS[$statut] ?? [];
    }

    public function historique(?int $dossierId): array
    {
        if ($dossierId === null || ! db_connect()->tableExists('dossier_statut_historique')) {
            return [];
        }

        return db_connect()->table('dossier_statut_historique')
            ->where('dossier_id', $dossierId)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();
    }

    private function normaliserStatut(string $statut): string
    {
        $statut = strtolower(trim($statut));
        $map = [
            'validé' => 'valide',
            'valide' => 'valide',
            'immatriculé' => 'immatricule',
            'immatricule' => 'immatricule',
        ];

        $statut = $map[$statut] ?? $statut;

        if (! in_array($statut, self::STATUTS_AUTORISES, true)) {
            throw new \RuntimeException("Statut du dossier invalide : {$statut}. Valeurs attendues : " . implode(', ', self::STATUTS_AUTORISES) . '.');
        }

        return $statut;
    }

    private function messagePourStatut(string $statut): string
    {
        return match ($statut) {
            'brouillon' => 'Le dossier est en cours de constitution.',
            'soumis' => 'Le dossier a été soumis au guichet de formalités.',
            'valide' => 'Le dossier a été validé par l’autorité compétente.',
            'immatricule' => 'La société est immatriculée.',
            default => 'Statut du dossier inconnu.',
        };
    }
}
