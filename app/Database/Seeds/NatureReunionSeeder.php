<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class NatureReunionSeeder extends Seeder
{
    public function run()
    {
        $existants = array_column($this->db->table('nature_reunion')->select('code')->get()->getResultArray(), 'code');
        $rows = [
            ['code' => 'AGC', 'libelle' => 'Assemblée générale constitutive'],
            ['code' => 'AGO', 'libelle' => 'Assemblée générale ordinaire'],
            ['code' => 'AGE', 'libelle' => 'Assemblée générale extraordinaire'],
            ['code' => 'CA',  'libelle' => "Conseil d'administration"],
        ];

        $rows = array_values(array_filter($rows, static fn (array $row): bool => ! in_array($row['code'], $existants, true)));

        if ($rows !== []) {
            $this->db->table('nature_reunion')->insertBatch($rows);
        }
    }
}
