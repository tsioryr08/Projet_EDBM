<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentGenereModel extends Model
{
    protected $table         = 'document_genere';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['societe_id', 'reunion_id', 'nature', 'fichier_pdf'];
}
