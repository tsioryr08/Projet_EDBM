<?php

namespace App\Models;

use CodeIgniter\Model;

class AssocieModel extends Model
{
    protected $table         = 'associe';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['societe_id', 'nom', 'categorie', 'date_naissance', 'mineur_non_emancipe', 'conjoint_de_associe_id', 'apport', 'nb_titres', 'est_commandite'];
}
