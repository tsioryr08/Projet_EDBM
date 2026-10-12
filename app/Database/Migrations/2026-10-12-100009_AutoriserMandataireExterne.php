<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AutoriserMandataireExterne extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('mandataire_id', 'pouvoir')) {
            $this->forge->modifyColumn('pouvoir', [
                'mandataire_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'Participant qui représente, si convoqué'],
            ]);
        }

        if (! $this->db->fieldExists('mandataire_nom_externe', 'pouvoir')) {
            $this->forge->addColumn('pouvoir', [
                'mandataire_nom_externe' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'mandataire_id'],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('pouvoir', 'mandataire_nom_externe');
        $this->forge->modifyColumn('pouvoir', [
            'mandataire_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false, 'comment' => 'Participant qui représente'],
        ]);
    }
}
