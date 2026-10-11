<?php

namespace App\Models;

use CodeIgniter\Model;

class RegleDelaiModel extends Model
{
    protected $table         = 'regle_delai';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['type_societe_id', 'nature_code', 'numero_convocation', 'delai_jours', 'mode_calcul', 'forme_envoi', 'source', 'verifie', 'note'];

    /** Règle applicable, ou null si elle n'est pas (encore) renseignée : « à chercher ». */
    public function trouver(int $typeSocieteId, string $nature, int $numeroConvocation): ?array
    {
        return $this->where([
            'type_societe_id'    => $typeSocieteId,
            'nature_code'        => $nature,
            'numero_convocation' => $numeroConvocation,
        ])->first();
    }
}
