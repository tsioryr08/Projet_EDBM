<?php

namespace App\Models;

use CodeIgniter\Model;

class EtapeCreationModel extends Model
{
    protected $table = 'etape_creation';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['ordre', 'libelle', 'delai_indicatif', 'source'];
}
