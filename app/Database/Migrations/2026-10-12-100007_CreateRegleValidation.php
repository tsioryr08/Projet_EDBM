<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRegleValidation extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'code'             => ['type' => 'VARCHAR', 'constraint' => 80],
            'type_societe_id'  => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'valeur_min'       => ['type' => 'DECIMAL', 'constraint' => '10,4', 'null' => true],
            'valeur_max'       => ['type' => 'DECIMAL', 'constraint' => '10,4', 'null' => true],
            'severite'         => ['type' => 'ENUM', 'constraint' => ['bloquant', 'avertissement'], 'default' => 'bloquant'],
            'message'          => ['type' => 'VARCHAR', 'constraint' => 255],
            'source'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'verifie'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'note'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['code', 'type_societe_id'], 'uq_regle_validation');
        $this->forge->addForeignKey('type_societe_id', 'type_societe', 'id', 'CASCADE', 'SET NULL', 'fk_rv_type');
        $this->forge->createTable('regle_validation');
    }

    public function down()
    {
        $this->forge->dropTable('regle_validation', true);
    }
}
