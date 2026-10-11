<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Données de TEST (pas des règles juridiques) : « EXEMPLE SA ».
 * Lancer à part : php spark db:seed DemoSeeder
 * 4 actionnaires + conseil d'administration (avec 3 actionnaires ou moins, l'administrateur général serait obligatoire, art. 519).
 */
class DemoSeeder extends Seeder
{
    public function run()
    {
        if ($this->db->table('societe')->where('denomination', 'EXEMPLE SA')->countAllResults() > 0) {
            echo "EXEMPLE SA existe déjà : rien à faire.\n";
            return;
        }

        $sa = $this->db->table('type_societe')->where('code', 'SA')->get()->getRow()->id;

        $this->db->table('societe')->insert([
            'type_societe_id'     => $sa,
            'mode_administration' => 'CA',
            'denomination'        => 'EXEMPLE SA',
            'siege'               => 'Antananarivo',
            'objet'               => 'Société de démonstration pour le logiciel',
            'capital'             => 10000000,
            'duree_annees'        => 99,
        ]);
        $societeId = $this->db->insertID();

        $associes = [
            ['RAKOTO Jean', 100],
            ['RABE Hanta', 50],
            ['ANDRIA Mamy', 50],
            ['RASOA Voahangy', 100],
        ];
        $ids = [];
        foreach ($associes as [$nom, $titres]) {
            $this->db->table('associe')->insert([
                'societe_id' => $societeId, 'nom' => $nom, 'categorie' => 'physique',
                'apport'     => $titres * 10000, 'nb_titres' => $titres,
            ]);
            $ids[] = $this->db->insertID();
        }

        // 3 administrateurs (minimum légal), pris parmi les actionnaires
        $fonctions = ['pca', 'administrateur', 'administrateur'];
        foreach ($fonctions as $i => $fonction) {
            $this->db->table('dirigeant')->insert([
                'societe_id' => $societeId, 'associe_id' => $ids[$i], 'nom' => $associes[$i][0],
                'fonction'   => $fonction, 'nomme_par_statuts' => 1, 'actionnaire' => 1,
            ]);
        }

        $this->db->table('dossier')->insert(['societe_id' => $societeId, 'statut' => 'brouillon']);
        echo "EXEMPLE SA créée (id {$societeId}) : 4 actionnaires, 3 administrateurs.\n";
    }
}
