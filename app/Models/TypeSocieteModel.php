<?php

namespace App\Models;

use CodeIgniter\Model;

class TypeSocieteModel extends Model
{
    protected $table         = 'type_societe';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['code', 'libelle', 'est_commerciale', 'dans_liste_orinasa', 'organe_direction', 'associes_min', 'associes_max', 'capital_minimum', 'note'];

    public function parCode(string $code): ?array
    {
        return $this->where('code', $code)->first();
    }
}
