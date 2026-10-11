<?php

namespace App\Models;

use CodeIgniter\Model;

class SocieteModel extends Model
{
    protected $table         = 'societe';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['type_societe_id', 'mode_administration', 'denomination', 'siege', 'objet', 'capital', 'duree_annees', 'code_activite', 'activite_reglementee'];
}
