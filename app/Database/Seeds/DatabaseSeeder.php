<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Point d'entrée : php spark db:seed DatabaseSeeder
 * L'ordre compte (clés étrangères).
 */
class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call('TypeSocieteSeeder');
        $this->call('NatureReunionSeeder');
        $this->call('EtapeCreationSeeder');
        $this->call('RegleDelaiSeeder');
        $this->call('RegleQuorumSeeder');
        $this->call('DocumentRequisSeeder');
    }
}
