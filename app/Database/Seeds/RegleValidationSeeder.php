<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RegleValidationSeeder extends Seeder
{
    public function run()
    {
        $types = array_column($this->db->table('type_societe')->get()->getResultArray(), 'id', 'code');
        $rows = [
            ['duree_max', null, null, 99, 'bloquant', 'La durée de la société dépasse la durée maximale configurée.', 'Loi 2003-036, art. 23', 1],
            ['SA_ADMINISTRATEURS', $types['SA'] ?? null, 3, 12, 'bloquant', 'Le nombre d’administrateurs est hors de la plage autorisée.', 'Loi 2003-036, art. 439', 1],
            ['SA_NON_ACTIONNAIRES', $types['SA'] ?? null, null, 0.3333, 'bloquant', 'La proportion de non-actionnaires dépasse le maximum configuré.', 'Loi 2003-036, art. 440', 1],
            ['SA_ADMIN_GENERAL', $types['SA'] ?? null, null, 3, 'bloquant', 'Un administrateur général est requis lorsque le nombre d’actionnaires est inférieur ou égal au maximum configuré.', 'Loi 2003-036, art. 519', 1],
            ['SA_ADMIN_PARITE', $types['SA'] ?? null, null, null, 'avertissement', 'Le nombre d’administrateurs pair est signalé comme avertissement.', 'Loi 2003-036, à confirmer', 0],
            ['SNC_MINEUR', $types['SNC'] ?? null, null, 0, 'bloquant', 'Un associé mineur non émancipé est interdit pour cette forme.', 'Loi 2003-036, art. 5 et 6', 1],
            ['SNC_EPOUX', $types['SNC'] ?? null, null, 0, 'bloquant', 'Les époux associés ne sont pas admis ensemble pour cette forme.', 'Loi 2003-036, art. 5 et 6', 1],
        ];

        foreach ($rows as [$code, $type, $min, $max, $severity, $message, $source, $verified]) {
            $exists = $this->db->table('regle_validation')
                ->where(['code' => $code, 'type_societe_id' => $type])
                ->countAllResults();
            if ($exists === 0) {
                $this->db->table('regle_validation')->insert([
                    'code' => $code, 'type_societe_id' => $type, 'valeur_min' => $min,
                    'valeur_max' => $max, 'severite' => $severity, 'message' => $message,
                    'source' => $source, 'verifie' => $verified,
                ]);
            }
        }
    }
}
