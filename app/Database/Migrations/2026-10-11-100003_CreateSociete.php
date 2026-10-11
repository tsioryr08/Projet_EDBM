<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Société, associés, dirigeants, commissaire aux comptes, dossier, pièces fournies.
 */
class CreateSociete extends Migration
{
    public function up()
    {
        // ---- societe ----
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'type_societe_id'      => ['type' => 'TINYINT', 'unsigned' => true],
            'mode_administration'  => ['type' => 'ENUM', 'constraint' => ['AG', 'CA'], 'null' => true, 'comment' => 'SA seulement : administrateur général ou conseil d\'administration'],
            'denomination'         => ['type' => 'VARCHAR', 'constraint' => 150],
            'siege'                => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'objet'                => ['type' => 'TEXT', 'null' => true],
            'capital'              => ['type' => 'DECIMAL', 'constraint' => '18,2', 'null' => true],
            'duree_annees'         => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true, 'comment' => 'Maximum 99 ans (art. 23 de la loi 2003-036)'],
            'code_activite'        => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'comment' => 'Code NOMAC'],
            'activite_reglementee' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'cree_le'              => ['type' => 'TIMESTAMP', 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('type_societe_id', 'type_societe', 'id', '', '', 'fk_societe_type');
        $this->forge->createTable('societe');
        // CHECK : MySQL >= 8.0.16 ou MariaDB >= 10.2.1 (ignoré silencieusement sur les anciennes versions)
        $this->db->query('ALTER TABLE societe ADD CONSTRAINT ck_societe_duree CHECK (duree_annees IS NULL OR duree_annees <= 99)');

        // ---- associe ----
        $this->forge->addField([
            'id'                    => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'societe_id'            => ['type' => 'INT', 'unsigned' => true],
            'nom'                   => ['type' => 'VARCHAR', 'constraint' => 150],
            'categorie'             => ['type' => 'ENUM', 'constraint' => ['physique', 'morale_malagasy', 'morale_etrangere'], 'default' => 'physique'],
            'date_naissance'        => ['type' => 'DATE', 'null' => true],
            'mineur_non_emancipe'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'conjoint_de_associe_id'=> ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'Règle des époux (art. 6), utile en SNC'],
            'apport'                => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'nb_titres'             => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'comment' => 'Actions ou parts ; sert de nombre de voix'],
            'est_commandite'        => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true, 'comment' => 'SCS / SCA : 1 = commandité, 0 = commanditaire'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('societe_id', 'societe', 'id', '', 'CASCADE', 'fk_associe_societe');
        $this->forge->addForeignKey('conjoint_de_associe_id', 'associe', 'id', '', '', 'fk_associe_conjoint');
        $this->forge->createTable('associe');

        // ---- dirigeant ----
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'societe_id'       => ['type' => 'INT', 'unsigned' => true],
            'associe_id'       => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'Renseigné si le dirigeant est aussi associé'],
            'nom'              => ['type' => 'VARCHAR', 'constraint' => 150],
            'fonction'         => ['type' => 'ENUM', 'constraint' => ['gerant', 'cogerant', 'administrateur_general', 'pdg', 'pca', 'dg', 'administrateur', 'commandite']],
            'nomme_par_statuts'=> ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'actionnaire'      => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true, 'comment' => 'Administrateur : 1 = actionnaire (max. un tiers de non-actionnaires)'],
            'date_nomination'  => ['type' => 'DATE', 'null' => true],
            'fin_mandat'       => ['type' => 'DATE', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('societe_id', 'societe', 'id', '', 'CASCADE', 'fk_dir_societe');
        $this->forge->addForeignKey('associe_id', 'associe', 'id', '', '', 'fk_dir_associe');
        $this->forge->createTable('dirigeant');

        // ---- commissaire_aux_comptes ----
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'societe_id' => ['type' => 'INT', 'unsigned' => true],
            'nom'        => ['type' => 'VARCHAR', 'constraint' => 150],
            'categorie'  => ['type' => 'ENUM', 'constraint' => ['physique', 'morale'], 'default' => 'physique'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('societe_id', 'societe', 'id', '', 'CASCADE', 'fk_cac_societe');
        $this->forge->createTable('commissaire_aux_comptes');

        // ---- dossier ----
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'societe_id'           => ['type' => 'INT', 'unsigned' => true],
            'statut'               => ['type' => 'ENUM', 'constraint' => ['brouillon', 'soumis', 'valide', 'immatricule'], 'default' => 'brouillon'],
            'date_depot_prevue'    => ['type' => 'DATE', 'null' => true, 'comment' => 'Sert à vérifier la validité des pièces'],
            'date_soumission'      => ['type' => 'DATE', 'null' => true],
            'date_validation'      => ['type' => 'DATE', 'null' => true],
            'date_immatriculation' => ['type' => 'DATE', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('societe_id');
        $this->forge->addForeignKey('societe_id', 'societe', 'id', '', 'CASCADE', 'fk_dossier_societe');
        $this->forge->createTable('dossier');

        // ---- piece_fournie ----
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'societe_id'  => ['type' => 'INT', 'unsigned' => true],
            'document_id' => ['type' => 'INT', 'unsigned' => true],
            'associe_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'dirigeant_id'=> ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'date_piece'  => ['type' => 'DATE', 'null' => true, 'comment' => 'Date d\'établissement (contrôle de validité)'],
            'fichier'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('societe_id', 'societe', 'id', '', 'CASCADE', 'fk_pf_societe');
        $this->forge->addForeignKey('document_id', 'document_requis', 'id', '', '', 'fk_pf_doc');
        $this->forge->addForeignKey('associe_id', 'associe', 'id', '', 'CASCADE', 'fk_pf_associe');
        $this->forge->addForeignKey('dirigeant_id', 'dirigeant', 'id', '', 'CASCADE', 'fk_pf_dirigeant');
        $this->forge->createTable('piece_fournie');
    }

    public function down()
    {
        $this->db->disableForeignKeyChecks();
        foreach (['piece_fournie', 'dossier', 'commissaire_aux_comptes', 'dirigeant', 'associe', 'societe'] as $t) {
            $this->forge->dropTable($t, true);
        }
        $this->db->enableForeignKeyChecks();
    }
}
