<?php

namespace App\Libraries;

use App\Models\AssocieModel;
use App\Models\SocieteModel;
use App\Models\TypeSocieteModel;
use RuntimeException;

class DecisionAssocieUniqueService
{
    public function preparer(int $societeId): array
    {
        $societe = (new SocieteModel())->find($societeId);
        if (! $societe) throw new RuntimeException("Société #{$societeId} introuvable.");
        $type = (new TypeSocieteModel())->find((int) $societe['type_societe_id']);
        if (($type['code'] ?? null) !== 'SARLU') throw new RuntimeException('Ce parcours est réservé aux SARLU.');

        return [
            'societe' => $societe,
            'type' => $type,
            'associes' => (new AssocieModel())->where('societe_id', $societeId)->findAll(),
            'statut_regle' => 'à confirmer',
        ];
    }
}
