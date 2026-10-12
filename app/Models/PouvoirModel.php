<?php

namespace App\Models;

use CodeIgniter\Model;

class PouvoirModel extends Model
{
    protected $table         = 'pouvoir';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['assemblee_id', 'mandant_id', 'mandataire_id', 'mandataire_nom_externe', 'date_pouvoir', 'fichier'];
}
