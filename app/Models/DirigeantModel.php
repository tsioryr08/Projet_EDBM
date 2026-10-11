<?php

namespace App\Models;

use CodeIgniter\Model;

class DirigeantModel extends Model
{
    protected $table         = 'dirigeant';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['societe_id', 'associe_id', 'nom', 'fonction', 'nomme_par_statuts', 'actionnaire', 'date_nomination', 'fin_mandat'];
}
