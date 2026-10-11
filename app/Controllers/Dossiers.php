<?php

namespace App\Controllers;

use App\Libraries\DossierService;
use App\Models\DossierModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Exceptions\PageNotFoundException;

class Dossiers extends BaseController
{
    public function show(int $societeId): string
    {
        $data = (new DossierService())->afficher($societeId);
        $data['societeId'] = $societeId;
        $data['reunions'] = db_connect()->table('reunion')
            ->where('societe_id', $societeId)
            ->orderBy('id', 'DESC')
            ->get()->getResultArray();
        $data['assemblees'] = db_connect()->table('assemblee a')
            ->select('a.id, a.reunion_id, r.nature_code, r.date_reunion')
            ->join('reunion r', 'r.id = a.reunion_id')
            ->where('r.societe_id', $societeId)
            ->orderBy('a.id', 'DESC')
            ->get()->getResultArray();
        return view('dossier/index', $data);
    }

    public function piece(int $societeId, int $documentId): ResponseInterface
    {
        (new DossierService())->enregistrerPiece($societeId, $documentId, [
            'date_piece' => $this->request->getPost('date_piece'),
            'fichier' => $this->request->getPost('fichier'),
        ]);
        return redirect()->to('/dossiers/' . $societeId);
    }

    public function status(int $societeId): ResponseInterface
    {
        $status = (string) $this->request->getPost('statut');
        $allowed = ['brouillon', 'soumis', 'valide', 'immatricule'];
        if (! in_array($status, $allowed, true)) {
            throw PageNotFoundException::forPageNotFound('Statut de dossier invalide.');
        }

        $dateSoumission = $this->request->getPost('date_soumission');
        $dateValidation = $this->request->getPost('date_validation');
        $dateImmatriculation = $this->request->getPost('date_immatriculation');
        $dateDepot = $this->request->getPost('date_depot_prevue');
        $dates = [
            'date_depot_prevue' => $dateDepot ?: null,
            'date_soumission' => $status === 'brouillon' ? null : ($dateSoumission ?: null),
            'date_validation' => in_array($status, ['valide', 'immatricule'], true) ? ($dateValidation ?: null) : null,
            'date_immatriculation' => $status === 'immatricule' ? ($dateImmatriculation ?: null) : null,
        ];
        $dossier = (new DossierModel())->where('societe_id', $societeId)->first();
        if (! $dossier) {
            (new DossierModel())->insert(['societe_id' => $societeId, 'statut' => $status] + $dates);
        } else {
            (new DossierModel())->update($dossier['id'], ['statut' => $status] + $dates);
        }
        return redirect()->to('/dossiers/' . $societeId);
    }
}
