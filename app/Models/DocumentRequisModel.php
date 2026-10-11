<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentRequisModel extends Model
{
    protected $table = 'document_requis';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['code', 'categorie', 'libelle', 'condition_code', 'validite_mois', 'modele_disponible', 'note'];
}
