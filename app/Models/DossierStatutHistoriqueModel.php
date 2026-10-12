<?php

namespace App\Models;

use CodeIgniter\Model;

class DossierStatutHistoriqueModel extends Model
{
    protected $table = 'dossier_statut_historique';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'dossier_id',
        'ancien_statut',
        'nouveau_statut',
        'date_changement',
        'auteur',
    ];
}
