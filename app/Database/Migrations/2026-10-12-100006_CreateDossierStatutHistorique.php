<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDossierStatutHistorique extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'dossier_id' => ['type' => 'INT', 'unsigned' => true],
            'ancien_statut' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'nouveau_statut' => ['type' => 'VARCHAR', 'constraint' => 20],
            'date_changement' => ['type' => 'DATE'],
            'auteur' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('dossier_id', 'dossier', 'id', 'CASCADE', 'CASCADE', 'fk_dossier_statut_historique');
        $this->forge->createTable('dossier_statut_historique');
    }

    public function down()
    {
        $this->forge->dropTable('dossier_statut_historique', true);
    }
}
