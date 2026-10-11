<?php

namespace App\Models;

use CodeIgniter\Model;

class DossierModel extends Model
{
    protected $table = 'dossier';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['societe_id', 'statut', 'date_depot_prevue', 'date_soumission', 'date_validation', 'date_immatriculation'];
}
