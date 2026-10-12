<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class InterventionsMandatairesExternes extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('participant_id', 'intervention')) {
            $this->forge->modifyColumn('intervention', [
                'participant_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            ]);
        }

        if (! $this->db->fieldExists('auteur_externe', 'intervention')) {
            $this->forge->addColumn('intervention', [
                'auteur_externe' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'participant_id'],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('intervention', 'auteur_externe');
        $this->forge->modifyColumn('intervention', [
            'participant_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
        ]);
    }
}
