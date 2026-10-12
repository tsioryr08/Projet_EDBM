<?php

namespace App\Controllers;

use App\Libraries\DossierService;
use App\Libraries\PhaseBService;
use App\Libraries\SuiviDossierService;
use App\Models\DossierModel;
use App\Models\SocieteModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

class Dossiers extends BaseController
{
    public function show(int $societeId): string
    {
        $data = (new DossierService())->afficher($societeId);
        $data['societeId'] = $societeId;
        $data['suivi'] = (new SuiviDossierService())->etat($societeId);
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

    public function phaseB(int $societeId): string
    {
        $societe = (new SocieteModel())->find($societeId);
        if (! $societe) {
            throw PageNotFoundException::forPageNotFound('Société introuvable.');
        }

        $reunions = db_connect()->table('reunion')
            ->where('societe_id', $societeId)
            ->orderBy('id', 'DESC')
            ->get()->getResultArray();
        $assemblees = db_connect()->table('assemblee a')
            ->select('a.*, r.nature_code, r.date_reunion, r.numero_convocation, r.lieu')
            ->join('reunion r', 'r.id = a.reunion_id')
            ->where('r.societe_id', $societeId)
            ->orderBy('a.id', 'DESC')
            ->get()->getResultArray();

        return view('dossier/phase_b', [
            'societe' => $societe,
            'reunions' => $reunions,
            'assemblees' => $assemblees,
            'societeId' => $societeId,
        ]);
    }

    public function phaseBPlanifier(int $societeId): string
    {
        $societe = (new SocieteModel())->find($societeId);
        if (! $societe) {
            throw PageNotFoundException::forPageNotFound('Société introuvable.');
        }

        $reunions = db_connect()->table('reunion')
            ->where('societe_id', $societeId)
            ->orderBy('id', 'DESC')
            ->get()->getResultArray();

        $preview = session()->getFlashdata('phase_b_planifier');

        return view('dossier/phase_b_planifier', [
            'societe' => $societe,
            'societeId' => $societeId,
            'reunions' => $reunions,
            'preview' => $preview,
        ]);
    }

    public function phaseBJourJ(int $societeId, ?int $reunionId = null): string
    {
        $societe = (new SocieteModel())->find($societeId);
        if (! $societe) {
            throw PageNotFoundException::forPageNotFound('Société introuvable.');
        }

        $reunions = db_connect()->table('reunion')
            ->where('societe_id', $societeId)
            ->orderBy('id', 'DESC')
            ->get()->getResultArray();

        $result = session()->getFlashdata('phase_b_jour_j');
        $selectedReunion = $reunionId !== null
            ? db_connect()->table('reunion')->where('id', $reunionId)->where('societe_id', $societeId)->get()->getRowArray()
            : null;

        return view('dossier/phase_b_jour_j', [
            'societe' => $societe,
            'societeId' => $societeId,
            'reunions' => $reunions,
            'selectedReunion' => $selectedReunion,
            'result' => $result,
        ]);
    }

    public function phaseBVotes(int $societeId, ?int $assembleeId = null): string
    {
        $societe = (new SocieteModel())->find($societeId);
        if (! $societe) {
            throw PageNotFoundException::forPageNotFound('Société introuvable.');
        }

        $assemblees = db_connect()->table('assemblee a')
            ->select('a.*, r.nature_code, r.date_reunion, r.numero_convocation')
            ->join('reunion r', 'r.id = a.reunion_id')
            ->where('r.societe_id', $societeId)
            ->orderBy('a.id', 'DESC')
            ->get()->getResultArray();

        $selectedAssemblee = $assembleeId !== null
            ? db_connect()->table('assemblee a')
                ->select('a.*, r.nature_code, r.date_reunion, r.numero_convocation, r.lieu')
                ->join('reunion r', 'r.id = a.reunion_id')
                ->where('a.id', $assembleeId)
                ->where('r.societe_id', $societeId)
                ->get()->getRowArray()
            : null;

        $result = session()->getFlashdata('phase_b_votes');

        return view('dossier/phase_b_votes', [
            'societe' => $societe,
            'societeId' => $societeId,
            'assemblees' => $assemblees,
            'selectedAssemblee' => $selectedAssemblee,
            'result' => $result,
        ]);
    }

    public function phaseBRegistre(int $societeId): string
    {
        $societe = (new SocieteModel())->find($societeId);
        if (! $societe) {
            throw PageNotFoundException::forPageNotFound('Société introuvable.');
        }

        $registre = (new PhaseBService())->registreAssemblies($societeId);

        return view('dossier/phase_b_registre', [
            'societe' => $societe,
            'societeId' => $societeId,
            'registre' => $registre['registre'] ?? [],
        ]);
    }

    public function planifierPhaseB(int $societeId): ResponseInterface
    {
        $nature = strtoupper((string) $this->request->getPost('nature'));
        $dateReunion = (string) $this->request->getPost('date_reunion');
        $heure = (string) $this->request->getPost('heure');
        $lieu = (string) $this->request->getPost('lieu');
        $ordreDuJour = preg_split('/\r\n|\r|\n/', (string) $this->request->getPost('ordre_du_jour'));
        $ordreDuJour = array_values(array_filter(array_map('trim', $ordreDuJour ?? []), static fn ($ligne): bool => $ligne !== ''));
        $numeroConvocation = (int) ($this->request->getPost('numero_convocation') ?: 1);

        $result = (new PhaseBService())->ecranPlanification(
            $societeId,
            $nature,
            $dateReunion,
            $heure,
            $lieu,
            $ordreDuJour,
            $numeroConvocation,
            'franc',
            null,
            true,
        );

        return redirect()->to('/dossiers/' . $societeId . '/phase-b/planifier')
            ->with('phase_b_planifier', $result)
            ->with('success', 'Réunion planifiée : ' . $result['resume']['nature'] . ' le ' . $result['resume']['date_reunion']);
    }

    public function tenirPhaseB(int $societeId): ResponseInterface
    {
        $reunionId = (int) $this->request->getPost('reunion_id');
        $participants = [];
        $rawParticipants = preg_split('/\r\n|\r|\n/', (string) $this->request->getPost('participants'));
        foreach ($rawParticipants ?? [] as $ligne) {
            $ligne = trim((string) $ligne);
            if ($ligne === '') {
                continue;
            }
            $parts = array_map('trim', explode(';', $ligne));
            $nom = $parts[0] ?? '';
            if ($nom === '') {
                continue;
            }
            $participants[] = [
                'nom' => $nom,
                'statut' => $parts[1] ?? 'present',
                'par' => $parts[2] ?? null,
            ];
        }

        $result = (new PhaseBService())->ecranJourJ(
            $reunionId,
            $participants,
            (string) $this->request->getPost('president'),
            (string) $this->request->getPost('secretaire'),
            (string) $this->request->getPost('date_tenue') ?: null,
        );

        return redirect()->to('/dossiers/' . $societeId . '/phase-b/jour-j')
            ->with('phase_b_jour_j', $result)
            ->with('success', 'Assemblée tenue : quorum ' . (($result['resume']['quorum_atteint']) ? 'atteint' : 'non atteint'));
    }

    public function voterPhaseB(int $societeId): ResponseInterface
    {
        $assembleeId = (int) $this->request->getPost('assemblee_id');
        $resolutions = [];
        $rawResolutions = preg_split('/\r\n|\r|\n/', (string) $this->request->getPost('resolutions'));
        foreach ($rawResolutions ?? [] as $ligne) {
            $ligne = trim((string) $ligne);
            if ($ligne === '') {
                continue;
            }
            $parts = array_map('trim', explode('|', $ligne));
            if (count($parts) < 3) {
                continue;
            }
            $resolutions[] = [
                'texte' => $parts[0],
                'pour' => (int) $parts[1],
                'contre' => (int) $parts[2],
            ];
        }

        $result = (new PhaseBService())->ecranVotes($assembleeId, $resolutions);
        $adoptees = count(array_filter($result['resultat'] ?? [], static fn ($r): bool => (bool) ($r['adoptee'] ?? false)));

        return redirect()->to('/dossiers/' . $societeId . '/phase-b/votes/' . $assembleeId)
            ->with('phase_b_votes', $result)
            ->with('success', 'Votes enregistrés : ' . $adoptees . ' résolution(s) adoptée(s)');
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
        try {
            $result = (new SuiviDossierService())->changerStatut($societeId, $status);
        } catch (\RuntimeException $e) {
            return redirect()->to('/dossiers/' . $societeId)
                ->with('errors', ['bloquants' => [$e->getMessage()], 'avertissements' => [], 'infos' => []]);
        }
        return redirect()->to('/dossiers/' . $societeId)
            ->with('success', $result['message']);
    }
}
