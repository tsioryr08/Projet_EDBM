<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration FUSIONNÉE (remplace les deux versions précédentes) :
 *  - regle_delai / regle_quorum : texte_officiel (passage de la loi à recopier) ;
 *  - assemblee : motif_quorum ;
 *  - resolution : vote du président de séance, usage de la voix prépondérante (art. 483),
 *    justification juridique (motif, source, statut) ;
 *  - reunion.statut : nouvelle valeur "sans_quorum".
 */
class AjouterJustificationEtVoixPreponderante extends Migration
{
    public function up()
    {
        $texte = ['type' => 'TEXT', 'null' => true, 'comment' => 'Passage du texte officiel, à recopier'];
        $this->forge->addColumn('regle_delai', ['texte_officiel' => $texte]);
        $this->forge->addColumn('regle_quorum', ['texte_officiel' => $texte]);

        $this->forge->addColumn('assemblee', [
            'motif_quorum' => ['type' => 'TEXT', 'null' => true, 'comment' => 'Explication du calcul du quorum'],
        ]);

        $this->forge->addColumn('resolution', [
            'sens_vote_president'         => ['type' => 'ENUM', 'constraint' => ['pour', 'contre', 'abstention'], 'null' => true, 'after' => 'adoptee', 'comment' => 'Vote du président de séance (abstention = abstenu ou absent)'],
            'voix_preponderante_utilisee' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'sens_vote_president'],
            'motif'                       => ['type' => 'TEXT', 'null' => true, 'after' => 'voix_preponderante_utilisee'],
            'source'                      => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'after' => 'motif'],
            'statut_regle'                => ['type' => 'ENUM', 'constraint' => ['verifie', 'a_confirmer', 'deduction'], 'null' => true, 'after' => 'source'],
        ]);

        $this->forge->modifyColumn('reunion', [
            'statut' => ['type' => 'ENUM', 'constraint' => ['planifiee', 'convoquee', 'tenue', 'sans_quorum', 'annulee'], 'default' => 'planifiee'],
        ]);
    }

    public function down()
    {
        $this->db->table('reunion')->where('statut', 'sans_quorum')->update(['statut' => 'planifiee']);
        $this->forge->modifyColumn('reunion', [
            'statut' => ['type' => 'ENUM', 'constraint' => ['planifiee', 'convoquee', 'tenue', 'annulee'], 'default' => 'planifiee'],
        ]);

        $this->forge->dropColumn('resolution', ['sens_vote_president', 'voix_preponderante_utilisee', 'motif', 'source', 'statut_regle']);
        $this->forge->dropColumn('assemblee', 'motif_quorum');
        $this->forge->dropColumn('regle_quorum', 'texte_officiel');
        $this->forge->dropColumn('regle_delai', 'texte_officiel');
    }
}
