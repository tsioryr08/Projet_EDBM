<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Réunions, convoqués, assemblées, participants, pouvoirs, résolutions, votes, documents générés.
 */
class CreateReunions extends Migration
{
    public function up()
    {
        // ---- reunion ----
        $this->forge->addField([
            'id'                    => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'societe_id'            => ['type' => 'INT', 'unsigned' => true],
            'nature_code'           => ['type' => 'VARCHAR', 'constraint' => 5],
            'numero_convocation'    => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1, 'comment' => '1 = première, 2 = suivantes'],
            'reunion_precedente_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'Réunion de 1re convocation dont celle-ci est la suite (quorum non atteint)'],
            'date_reunion'          => ['type' => 'DATE'],
            'heure'                 => ['type' => 'TIME'],
            'lieu'                  => ['type' => 'VARCHAR', 'constraint' => 255],
            'ordre_du_jour'         => ['type' => 'TEXT', 'null' => true],
            'mode_calcul_delai'     => ['type' => 'ENUM', 'constraint' => ['calendaire', 'franc', 'ouvrable'], 'default' => 'franc'],
            'delai_applique'        => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true, 'comment' => 'Délai pris dans regle_delai au moment du calcul'],
            'date_convocation'      => ['type' => 'DATE', 'null' => true, 'comment' => 'Date limite d\'envoi, calculée par l\'application'],
            'statut'                => ['type' => 'ENUM', 'constraint' => ['planifiee', 'convoquee', 'tenue', 'annulee'], 'default' => 'planifiee'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('societe_id', 'societe', 'id', '', 'CASCADE', 'fk_reunion_societe');
        $this->forge->addForeignKey('nature_code', 'nature_reunion', 'code', '', '', 'fk_reunion_nature');
        $this->forge->addForeignKey('reunion_precedente_id', 'reunion', 'id', '', '', 'fk_reunion_prec');
        $this->forge->createTable('reunion');

        // ---- convoque ----
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'reunion_id'   => ['type' => 'INT', 'unsigned' => true],
            'role'         => ['type' => 'ENUM', 'constraint' => ['associe', 'actionnaire', 'administrateur', 'dirigeant', 'commissaire_aux_comptes']],
            'associe_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'dirigeant_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'cac_id'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'nom_affiche'  => ['type' => 'VARCHAR', 'constraint' => 150],
            'mode_envoi'   => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'comment' => 'lettre recommandée, journal d\'annonces légales, ...'],
            'date_envoi'   => ['type' => 'DATE', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('reunion_id', 'reunion', 'id', '', 'CASCADE', 'fk_conv_reunion');
        $this->forge->addForeignKey('associe_id', 'associe', 'id', '', '', 'fk_conv_associe');
        $this->forge->addForeignKey('dirigeant_id', 'dirigeant', 'id', '', '', 'fk_conv_dirigeant');
        $this->forge->addForeignKey('cac_id', 'commissaire_aux_comptes', 'id', '', '', 'fk_conv_cac');
        $this->forge->createTable('convoque');

        // ---- assemblee (registre : une ligne par réunion tenue) ----
        $this->forge->addField([
            'id'                             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'reunion_id'                     => ['type' => 'INT', 'unsigned' => true],
            'date_tenue'                     => ['type' => 'DATE', 'null' => true],
            'president_seance'               => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'secretaire'                     => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'voix_totales'                   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'voix_presentes_ou_representees' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'quorum_requis_num'              => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true, 'comment' => 'Copie de la règle appliquée (traçabilité)'],
            'quorum_requis_den'              => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'quorum_atteint'                 => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true, 'comment' => 'Calculé par calculerQuorum()'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('reunion_id');
        $this->forge->addForeignKey('reunion_id', 'reunion', 'id', '', 'CASCADE', 'fk_assemblee_reunion');
        $this->forge->createTable('assemblee');

        // ---- participant ----
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'assemblee_id' => ['type' => 'INT', 'unsigned' => true],
            'convoque_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'nom'          => ['type' => 'VARCHAR', 'constraint' => 150],
            'statut'       => ['type' => 'ENUM', 'constraint' => ['present', 'represente', 'absent']],
            'voix'         => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('assemblee_id', 'assemblee', 'id', '', 'CASCADE', 'fk_part_assemblee');
        $this->forge->addForeignKey('convoque_id', 'convoque', 'id', '', '', 'fk_part_convoque');
        $this->forge->createTable('participant');

        // ---- pouvoir (procuration) ----
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'assemblee_id'  => ['type' => 'INT', 'unsigned' => true],
            'mandant_id'    => ['type' => 'INT', 'unsigned' => true, 'comment' => 'Participant représenté'],
            'mandataire_id' => ['type' => 'INT', 'unsigned' => true, 'comment' => 'Participant qui représente'],
            'date_pouvoir'  => ['type' => 'DATE', 'null' => true],
            'fichier'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['assemblee_id', 'mandant_id'], 'uq_pouvoir_mandant');
        $this->forge->addForeignKey('assemblee_id', 'assemblee', 'id', '', 'CASCADE', 'fk_pouv_assemblee');
        $this->forge->addForeignKey('mandant_id', 'participant', 'id', '', 'CASCADE', 'fk_pouv_mandant');
        $this->forge->addForeignKey('mandataire_id', 'participant', 'id', '', 'CASCADE', 'fk_pouv_mandataire');
        $this->forge->createTable('pouvoir');

        // ---- resolution ----
        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'assemblee_id'         => ['type' => 'INT', 'unsigned' => true],
            'numero'               => ['type' => 'TINYINT', 'unsigned' => true],
            'texte'                => ['type' => 'TEXT'],
            'voix_pour'            => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'voix_contre'          => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'abstentions'          => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'voix_blanches'        => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'majorite_requise_num' => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true, 'comment' => 'Copie de la règle appliquée'],
            'majorite_requise_den' => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
            'adoptee'              => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true, 'comment' => 'Calculé par resolutionAdoptee()'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['assemblee_id', 'numero'], 'uq_resolution');
        $this->forge->addForeignKey('assemblee_id', 'assemblee', 'id', '', 'CASCADE', 'fk_res_assemblee');
        $this->forge->createTable('resolution');

        // ---- vote (nominal) ----
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'resolution_id'  => ['type' => 'INT', 'unsigned' => true],
            'participant_id' => ['type' => 'INT', 'unsigned' => true],
            'sens'           => ['type' => 'ENUM', 'constraint' => ['pour', 'contre', 'abstention', 'blanc']],
            'voix'           => ['type' => 'INT', 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['resolution_id', 'participant_id'], 'uq_vote');
        $this->forge->addForeignKey('resolution_id', 'resolution', 'id', '', 'CASCADE', 'fk_vote_resolution');
        $this->forge->addForeignKey('participant_id', 'participant', 'id', '', 'CASCADE', 'fk_vote_participant');
        $this->forge->createTable('vote');

        // ---- document_genere ----
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'societe_id'  => ['type' => 'INT', 'unsigned' => true],
            'reunion_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'nature'      => ['type' => 'ENUM', 'constraint' => ['statuts', 'convocation', 'pv_ordinaire', 'pv_constitutif', 'pv_conseil_administration', 'autre']],
            'fichier_pdf' => ['type' => 'VARCHAR', 'constraint' => 255],
            'genere_le'   => ['type' => 'TIMESTAMP', 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('societe_id', 'societe', 'id', '', 'CASCADE', 'fk_dg_societe');
        $this->forge->addForeignKey('reunion_id', 'reunion', 'id', '', 'SET NULL', 'fk_dg_reunion');
        $this->forge->createTable('document_genere');
    }

    public function down()
    {
        $this->db->disableForeignKeyChecks();
        foreach (['document_genere', 'vote', 'resolution', 'pouvoir', 'participant', 'assemblee', 'convoque', 'reunion'] as $t) {
            $this->forge->dropTable($t, true);
        }
        $this->db->enableForeignKeyChecks();
    }
}
