<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class EtapeCreationSeeder extends Seeder
{
    public function run()
    {
        $src = 'Orinasa, « Comment ça marche »';
        $existants = array_column($this->db->table('etape_creation')->select('ordre')->get()->getResultArray(), 'ordre');
        $rows = [
            ['ordre' => 1, 'libelle' => 'Créer un espace personnel (compte, activation par email)', 'delai_indicatif' => null, 'source' => $src],
            ['ordre' => 2, 'libelle' => 'Constituer le dossier en ligne (société, associés et dirigeants, siège)', 'delai_indicatif' => 'Minimum 20 minutes de saisie', 'source' => $src],
            ['ordre' => 3, 'libelle' => 'Étude du dossier, puis paiement des frais et dépôt de la liasse', 'delai_indicatif' => 'Environ 24 h, jours ouvrés (lundi au vendredi, 8h30 à 16h30)', 'source' => $src],
            ['ordre' => 4, 'libelle' => "Immatriculation et suivi sur l'espace client", 'delai_indicatif' => null, 'source' => $src],
        ];

        $rows = array_values(array_filter($rows, static fn (array $row): bool => ! in_array((int) $row['ordre'], $existants, true)));

        if ($rows !== []) {
            $this->db->table('etape_creation')->insertBatch($rows);
        }
    }
}
