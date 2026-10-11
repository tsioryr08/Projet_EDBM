<?php

namespace App\Models;

use CodeIgniter\Model;

class ConvoqueModel extends Model
{
    protected $table         = 'convoque';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['reunion_id', 'role', 'associe_id', 'dirigeant_id', 'cac_id', 'nom_affiche', 'mode_envoi', 'date_envoi'];
}
