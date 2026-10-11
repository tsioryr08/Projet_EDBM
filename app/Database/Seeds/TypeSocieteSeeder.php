<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TypeSocieteSeeder extends Seeder
{
    public function run()
    {
        $rows = [
            ['code' => 'SA', 'libelle' => 'Société anonyme', 'est_commerciale' => 1, 'dans_liste_orinasa' => 1,
             'organe_direction' => "Conseil d'administration ou administrateur général",
             'associes_min' => null, 'associes_max' => null, 'capital_minimum' => null,
             'note' => 'Administrateur général obligatoire si 3 actionnaires ou moins (art. 519). Capital minimum à chercher.'],
            ['code' => 'SARL', 'libelle' => 'Société à responsabilité limitée', 'est_commerciale' => 1, 'dans_liste_orinasa' => 1,
             'organe_direction' => 'Gérant(s)',
             'associes_min' => null, 'associes_max' => null, 'capital_minimum' => 0.00,
             'note' => 'Aucun capital minimum légal : fixé par les statuts (art. 327, loi 2014-010).'],
            ['code' => 'SARLU', 'libelle' => 'SARL unipersonnelle (associé unique)', 'est_commerciale' => 1, 'dans_liste_orinasa' => 1,
             'organe_direction' => 'Gérant',
             'associes_min' => 1, 'associes_max' => 1, 'capital_minimum' => 0.00,
             'note' => 'SARL à associé unique. Aucun capital minimum légal (art. 327).'],
            ['code' => 'SCS', 'libelle' => 'Société en commandite simple', 'est_commerciale' => 1, 'dans_liste_orinasa' => 1,
             'organe_direction' => 'Associés commandités',
             'associes_min' => null, 'associes_max' => null, 'capital_minimum' => null, 'note' => null],
            ['code' => 'SCA', 'libelle' => 'Société en commandite par actions', 'est_commerciale' => 1, 'dans_liste_orinasa' => 0,
             'organe_direction' => null,
             'associes_min' => null, 'associes_max' => null, 'capital_minimum' => null,
             'note' => 'Absente de la liste Orinasa : tout est à chercher dans la loi.'],
            ['code' => 'SNC', 'libelle' => 'Société en nom collectif', 'est_commerciale' => 1, 'dans_liste_orinasa' => 1,
             'organe_direction' => 'Gérants',
             'associes_min' => null, 'associes_max' => null, 'capital_minimum' => null,
             'note' => 'Mineurs non émancipés et époux : règles des art. 5 et 6.'],
            ['code' => 'SC', 'libelle' => 'Société civile', 'est_commerciale' => 0, 'dans_liste_orinasa' => 1,
             'organe_direction' => 'Gérant',
             'associes_min' => null, 'associes_max' => null, 'capital_minimum' => null,
             'note' => 'Société civile : loi 2001-026 (à lire).'],
        ];
        $this->db->table('type_societe')->insertBatch($rows);
    }
}
