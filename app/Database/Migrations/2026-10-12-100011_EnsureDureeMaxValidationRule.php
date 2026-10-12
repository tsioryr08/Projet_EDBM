<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnsureDureeMaxValidationRule extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('regle_validation')) {
            return;
        }

        $table = $this->db->table('regle_validation');
        $exists = $table
            ->where('code', 'duree_max')
            ->where('type_societe_id IS NULL', null, false)
            ->countAllResults();

        if ($exists === 0) {
            $table->insert([
                'code' => 'duree_max',
                'type_societe_id' => null,
                'valeur_min' => null,
                'valeur_max' => 99,
                'severite' => 'bloquant',
                'message' => 'La durée de la société dépasse la durée maximale configurée.',
                'source' => 'Loi 2003-036, art. 23',
                'verifie' => 1,
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('regle_validation')) {
            $this->db->table('regle_validation')
                ->where('code', 'duree_max')
                ->where('type_societe_id IS NULL', null, false)
                ->delete();
        }
    }
}
