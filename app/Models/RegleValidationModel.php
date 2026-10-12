<?php

namespace App\Models;

use CodeIgniter\Model;

class RegleValidationModel extends Model
{
    protected $table = 'regle_validation';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'code', 'type_societe_id', 'valeur_min', 'valeur_max', 'severite',
        'message', 'source', 'verifie', 'note',
    ];

    public function trouver(string $code, ?int $typeSocieteId = null): ?array
    {
        if (! $this->db->tableExists($this->table)) {
            return null;
        }

        return $this->where('code', $code)
            ->groupStart()
            ->where('type_societe_id', $typeSocieteId)
            ->orWhere('type_societe_id IS NULL', null, false)
            ->groupEnd()
            ->orderBy('type_societe_id', 'DESC')
            ->first();
    }
}
