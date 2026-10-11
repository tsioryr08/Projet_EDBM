<?php

namespace App\Models;

use CodeIgniter\Model;

class ReunionModel extends Model
{
    protected $table         = 'reunion';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['societe_id', 'nature_code', 'numero_convocation', 'reunion_precedente_id', 'date_reunion', 'heure', 'lieu', 'ordre_du_jour', 'mode_calcul_delai', 'delai_applique', 'date_convocation', 'statut'];
}
