<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tables de règles juridiques : les délais, quorums, majorités et documents sont des DONNÉES.
 */
class CreateRegles extends Migration
{
    public function up()
    {
        // ---- regle_delai ----
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'type_societe_id'    => ['type' => 'TINYINT', 'unsigned' => true],
            'nature_code'        => ['type' => 'VARCHAR', 'constraint' => 5],
            'numero_convocation' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1, 'comment' => '1 = première convocation, 2 = suivantes'],
            'delai_jours'        => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true, 'comment' => 'Délai minimum ; NULL = fixé par les statuts'],
            'mode_calcul'        => ['type' => 'ENUM', 'constraint' => ['calendaire', 'franc', 'ouvrable'], 'default' => 'franc'],
            'forme_envoi'        => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'source'             => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'verifie'            => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'note'               => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['type_societe_id', 'nature_code', 'numero_convocation'], 'uq_regle_delai');
        $this->forge->addForeignKey('type_societe_id', 'type_societe', 'id', '', '', 'fk_rd_type');
        $this->forge->addForeignKey('nature_code', 'nature_reunion', 'code', '', '', 'fk_rd_nature');
        $this->forge->createTable('regle_delai');

        // ---- regle_quorum ----
        $this->forge->addField([
            'id'                          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'type_societe_id'             => ['type' => 'TINYINT', 'unsigned' => true],
            'nature_code'                 => ['type' => 'VARCHAR', 'constraint' => 5],
            'numero_convocation'          => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'quorum_base'                 => ['type' => 'ENUM', 'constraint' => ['actions_droit_vote', 'membres_conseil', 'parts_ou_associes']],
            'quorum_num'                  => ['type' => 'TINYINT', 'unsigned' => true, 'comment' => 'Fraction minimale requise : 0/1 = aucun quorum'],
            'quorum_den'                  => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'majorite_base'               => ['type' => 'ENUM', 'constraint' => ['voix_exprimees', 'presents_ou_representes'], 'null' => true, 'comment' => 'NULL = à chercher'],
            'majorite_num'                => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'majorite_den'                => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'majorite_stricte'            => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true, 'comment' => '1 = strictement plus que la fraction ; 0 = au moins la fraction'],
            'voix_preponderante_president'=> ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'source'                      => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'verifie'                     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'note'                        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['type_societe_id', 'nature_code', 'numero_convocation'], 'uq_regle_quorum');
        $this->forge->addForeignKey('type_societe_id', 'type_societe', 'id', '', '', 'fk_rq_type');
        $this->forge->addForeignKey('nature_code', 'nature_reunion', 'code', '', '', 'fk_rq_nature');
        $this->forge->createTable('regle_quorum');

        // ---- document_requis ----
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'code'             => ['type' => 'VARCHAR', 'constraint' => 40],
            'categorie'        => ['type' => 'ENUM', 'constraint' => ['etablissement', 'associe', 'capital', 'dirigeant', 'commissaire_aux_comptes', 'siege', 'activite', 'mandataire']],
            'libelle'          => ['type' => 'VARCHAR', 'constraint' => 255],
            'condition_code'   => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'comment' => 'NULL = toujours exigé ; sinon condition interprétée dans le PHP'],
            'validite_mois'    => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true, 'comment' => '3 = moins de 3 mois, 12 = moins d\'un an'],
            'modele_disponible'=> ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'comment' => '1 = modèle proposé par Orinasa'],
            'note'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('document_requis');

        // ---- document_type_societe (table pivot) ----
        $this->forge->addField([
            'document_id'     => ['type' => 'INT', 'unsigned' => true],
            'type_societe_id' => ['type' => 'TINYINT', 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey(['document_id', 'type_societe_id']);
        $this->forge->addForeignKey('document_id', 'document_requis', 'id', '', '', 'fk_dts_doc');
        $this->forge->addForeignKey('type_societe_id', 'type_societe', 'id', '', '', 'fk_dts_type');
        $this->forge->createTable('document_type_societe');
    }

    public function down()
    {
        $this->db->disableForeignKeyChecks();
        foreach (['document_type_societe', 'document_requis', 'regle_quorum', 'regle_delai'] as $t) {
            $this->forge->dropTable($t, true);
        }
        $this->db->enableForeignKeyChecks();
    }
}
