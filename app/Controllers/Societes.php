<?php

namespace App\Controllers;

use App\Models\AssocieModel;
use App\Models\DirigeantModel;
use App\Models\DossierModel;
use App\Models\SocieteModel;
use App\Models\TypeSocieteModel;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

class Societes extends BaseController
{
    public function new(): string
    {
        return view('societe/form', [
            'types' => (new TypeSocieteModel())->orderBy('code')->findAll(),
            'errors' => [],
            'selectedType' => (int) ($this->request->getGet('type_societe_id') ?? 0),
        ]);
    }

    public function type(): string
    {
        return view('societe/type', [
            'types' => (new TypeSocieteModel())->orderBy('code')->findAll(),
        ]);
    }

    public function create(): ResponseInterface
    {
        $validation = service('validation');
        $validation->setRules([
            'type_societe_id' => 'required|is_natural_no_zero',
            'denomination' => 'required|max_length[150]',
            'siege' => 'permit_empty|max_length[255]',
            'objet' => 'permit_empty',
            'capital' => 'permit_empty|decimal',
            'duree_annees' => 'permit_empty|is_natural|less_than_equal_to[99]',
        ]);
        if (! $validation->withRequest($this->request)->run()) {
            return view('societe/form', [
                'types' => (new TypeSocieteModel())->orderBy('code')->findAll(),
                'errors' => $validation->getErrors(),
                'selectedType' => (int) $this->request->getPost('type_societe_id'),
            ]);
        }

        $societes = new SocieteModel();
        $societeId = $societes->insert([
            'type_societe_id' => (int) $this->request->getPost('type_societe_id'),
            'mode_administration' => $this->request->getPost('mode_administration') ?: null,
            'denomination' => $this->request->getPost('denomination'),
            'siege' => $this->request->getPost('siege') ?: null,
            'objet' => $this->request->getPost('objet') ?: null,
            'capital' => $this->request->getPost('capital') ?: null,
            'duree_annees' => $this->request->getPost('duree_annees') ?: null,
            'code_activite' => $this->request->getPost('code_activite') ?: null,
            'activite_reglementee' => $this->request->getPost('activite_reglementee') ? 1 : 0,
        ]);
        if (! $societeId) {
            throw new RuntimeException('Impossible d’enregistrer la société.');
        }

        $dossierId = (new DossierModel())->insert([
            'societe_id' => (int) $societeId,
            'statut'     => 'brouillon',
        ]);
        if (! $dossierId) {
            throw new RuntimeException('Impossible de créer le dossier de la société.');
        }

        $this->saveRows((int) $societeId, new AssocieModel(), 'associes', [
            'nom', 'categorie', 'apport', 'nb_titres', 'est_commandite',
        ]);
        $this->saveRows((int) $societeId, new DirigeantModel(), 'dirigeants', [
            'nom', 'fonction', 'nomme_par_statuts', 'actionnaire',
        ]);
        return redirect()->to('/dossiers/' . $societeId);
    }

    private function saveRows(int $societeId, object $model, string $key, array $fields): void
    {
        $rows = $this->request->getPost($key);
        if (! is_array($rows)) {
            return;
        }
        foreach ($rows as $row) {
            if (! is_array($row) || empty($row['nom'])) {
                continue;
            }
            $payload = ['societe_id' => $societeId];
            foreach ($fields as $field) {
                if (array_key_exists($field, $row)) {
                    $payload[$field] = $row[$field] === '' ? null : $row[$field];
                }
            }
            $model->insert($payload);
        }
    }
}
