<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/** Interventions consignées pendant une assemblée, pour intégration au procès-verbal. */
class CreateInterventions extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'assemblee_id' => ['type' => 'INT', 'unsigned' => true],
            'participant_id' => ['type' => 'INT', 'unsigned' => true],
            'texte' => ['type' => 'TEXT'],
            'cree_le' => ['type' => 'TIMESTAMP', 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('assemblee_id', 'assemblee', 'id', '', 'CASCADE', 'fk_intervention_assemblee');
        $this->forge->addForeignKey('participant_id', 'participant', 'id', '', '', 'fk_intervention_participant');
        $this->forge->createTable('intervention');
    }

    public function down()
    {
        $this->forge->dropTable('intervention', true);
    }
}
