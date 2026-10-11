<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Référentiels : types de société, natures de réunion, jours fériés, étapes de création.
 */
class CreateReferentiels extends Migration
{
    public function up()
    {
        // ---- type_societe ----
        $this->forge->addField([
            'id'                 => ['type' => 'TINYINT', 'unsigned' => true, 'auto_increment' => true],
            'code'               => ['type' => 'VARCHAR', 'constraint' => 10],
            'libelle'            => ['type' => 'VARCHAR', 'constraint' => 100],
            'est_commerciale'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'dans_liste_orinasa' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'organe_direction'   => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'associes_min'       => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true, 'comment' => 'NULL = à chercher'],
            'associes_max'       => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true, 'comment' => 'NULL = à chercher ou sans limite'],
            'capital_minimum'    => ['type' => 'DECIMAL', 'constraint' => '18,2', 'null' => true, 'comment' => 'NULL = à chercher ; 0 = aucun minimum légal'],
            'note'               => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('type_societe');

        // ---- nature_reunion ----
        $this->forge->addField([
            'code'    => ['type' => 'VARCHAR', 'constraint' => 5],
            'libelle' => ['type' => 'VARCHAR', 'constraint' => 80],
        ]);
        $this->forge->addPrimaryKey('code');
        $this->forge->createTable('nature_reunion');

        // ---- jour_ferie (mode de calcul "ouvrable") ----
        $this->forge->addField([
            'date_ferie' => ['type' => 'DATE'],
            'libelle'    => ['type' => 'VARCHAR', 'constraint' => 100],
        ]);
        $this->forge->addPrimaryKey('date_ferie');
        $this->forge->createTable('jour_ferie');

        // ---- etape_creation ----
        $this->forge->addField([
            'id'             => ['type' => 'TINYINT', 'unsigned' => true, 'auto_increment' => true],
            'ordre'          => ['type' => 'TINYINT', 'unsigned' => true],
            'libelle'        => ['type' => 'VARCHAR', 'constraint' => 150],
            'delai_indicatif'=> ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'source'         => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('etape_creation');
    }

    public function down()
    {
        $this->db->disableForeignKeyChecks();
        foreach (['etape_creation', 'jour_ferie', 'nature_reunion', 'type_societe'] as $t) {
            $this->forge->dropTable($t, true);
        }
        $this->db->enableForeignKeyChecks();
    }
}
