<?php

namespace App\Models;

use CodeIgniter\Model;

class InterventionModel extends Model
{
    protected $table = 'intervention';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['assemblee_id', 'participant_id', 'texte'];
}
