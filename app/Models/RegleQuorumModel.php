<?php

namespace App\Models;

use CodeIgniter\Model;

class RegleQuorumModel extends Model
{
    protected $table         = 'regle_quorum';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['type_societe_id', 'nature_code', 'numero_convocation', 'quorum_base', 'quorum_num', 'quorum_den', 'majorite_base', 'majorite_num', 'majorite_den', 'majorite_stricte', 'voix_preponderante_president', 'source', 'verifie', 'note'];

    public function trouver(int $typeSocieteId, string $nature, int $numeroConvocation): ?array
    {
        return $this->where([
            'type_societe_id'    => $typeSocieteId,
            'nature_code'        => $nature,
            'numero_convocation' => $numeroConvocation,
        ])->first();
    }
}
