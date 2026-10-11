<?php

namespace App\Models;

use CodeIgniter\Model;

class PieceFournieModel extends Model
{
    protected $table = 'piece_fournie';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['societe_id', 'document_id', 'associe_id', 'dirigeant_id', 'date_piece', 'fichier'];
}
