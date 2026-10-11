<?php

namespace App\Models;

use CodeIgniter\Model;

class ResolutionModel extends Model
{
    protected $table         = 'resolution';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['assemblee_id', 'numero', 'texte', 'voix_pour', 'voix_contre', 'abstentions', 'voix_blanches', 'majorite_requise_num', 'majorite_requise_den', 'adoptee', 'sens_vote_president', 'voix_preponderante_utilisee', 'motif', 'source', 'statut_regle'];
}
