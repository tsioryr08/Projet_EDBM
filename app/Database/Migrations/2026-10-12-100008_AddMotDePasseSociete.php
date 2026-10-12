<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMotDePasseSociete extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('mot_de_passe', 'societe')) {
            $this->forge->addColumn('societe', [
                'mot_de_passe' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'comment'    => 'Hash du mot de passe d’accès au dossier',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('mot_de_passe', 'societe')) {
            $this->forge->dropColumn('societe', 'mot_de_passe');
        }
    }
}
