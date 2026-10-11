<?php

namespace App\Models;

use CodeIgniter\Model;

class AssembleeModel extends Model
{
    protected $table         = 'assemblee';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['reunion_id', 'date_tenue', 'president_seance', 'secretaire', 'voix_totales', 'voix_presentes_ou_representees', 'quorum_requis_num', 'quorum_requis_den', 'quorum_atteint', 'motif_quorum'];
}
