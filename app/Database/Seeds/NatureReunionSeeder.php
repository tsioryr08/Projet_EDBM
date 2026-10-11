<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class NatureReunionSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('nature_reunion')->insertBatch([
            ['code' => 'AGC', 'libelle' => 'Assemblée générale constitutive'],
            ['code' => 'AGO', 'libelle' => 'Assemblée générale ordinaire'],
            ['code' => 'AGE', 'libelle' => 'Assemblée générale extraordinaire'],
            ['code' => 'CA',  'libelle' => "Conseil d'administration"],
        ]);
    }
}
