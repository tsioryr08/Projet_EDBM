<?php

namespace App\Controllers;

use App\Libraries\AccesDossier;
use App\Libraries\DossierService;
use App\Libraries\InterventionService;
use App\Libraries\Justification;
use App\Libraries\PhaseBService;
use App\Libraries\SuiviDossierService;
use App\Libraries\DecisionAssocieUniqueService;
use App\Models\DossierModel;
use App\Models\SocieteModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

class Dossiers extends BaseController
{
    private function verifierAcces(int $societeId): ?ResponseInterface
    {
        return AccesDossier::ouvert($societeId) ? null : AccesDossier::redirection($societeId);
    }

    public function access(int $societeId): string|ResponseInterface
    {
        $societe = (new SocieteModel())->find($societeId);
        if (! $societe) {
            throw PageNotFoundException::forPageNotFound('Société introuvable.');
        }

        if ($this->request->getMethod(true) === 'POST') {
            $motDePasse = (string) $this->request->getPost('mot_de_passe');
            if (empty($societe['mot_de_passe'])) {
                return view('dossier/access', [
                    'societe' => $societe,
                    'errors' => ['Aucun mot de passe n’est configuré pour cette société. Créez une nouvelle société avec un mot de passe, ou configurez celui-ci avant d’ouvrir ce dossier.'],
                ]);
            }
            if (! empty($societe['mot_de_passe']) && password_verify($motDePasse, $societe['mot_de_passe'])) {
                session()->set('societe_access_' . $societeId, true);
                return redirect()->to(site_url('dossiers/' . $societeId));
            }
            return view('dossier/access', [
                'societe' => $societe,
                'errors' => ['Le mot de passe est incorrect.'],
            ]);
        }

        return view('dossier/access', [
            'societe' => $societe,
            'errors' => [],
        ]);
    }

    public function sarluDecision(int $societeId): string|ResponseInterface
    {
        if ($redirect = $this->verifierAcces($societeId)) {
            return $redirect;
        }

        $societe = (new SocieteModel())->find($societeId);
        if (! $societe) {
            throw PageNotFoundException::forPageNotFound('Société introuvable.');
        }
        $type = db_connect()->table('type_societe')->where('id', $societe['type_societe_id'])->get()->getRowArray();
        if (($type['code'] ?? null) !== 'SARLU') {
            return redirect()->to(site_url('dossiers/' . $societeId . '/phase-b'))
                ->with('phase_b_error', 'La décision de l’associé unique est réservée à une SARLU. La forme enregistrée pour ce dossier est « ' . ($type['code'] ?? 'inconnue') . ' ».');
        }

        return view('dossier/sarlu_decision', [
            'societeId' => $societeId,
            ... (new DecisionAssocieUniqueService())->preparer($societeId),
        ]);
    }

    private function donneesDossier(int $societeId): array
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

        return $data;
    }

    public function show(int $societeId): string|ResponseInterface
    {
        if ($redirect = $this->verifierAcces($societeId)) {
            return $redirect;
        }

        return view('dossier/index', $this->donneesDossier($societeId));
    }

    public function suivi(int $societeId): string|ResponseInterface
    {
        if ($redirect = $this->verifierAcces($societeId)) {
            return $redirect;
        }

        return view('dossier/suivi', $this->donneesDossier($societeId));
    }

    public function pieces(int $societeId): string|ResponseInterface
    {
        if ($redirect = $this->verifierAcces($societeId)) {
            return $redirect;
        }

        return view('dossier/pieces', $this->donneesDossier($societeId));
    }

    public function documents(int $societeId): string|ResponseInterface
    {
        if ($redirect = $this->verifierAcces($societeId)) {
            return $redirect;
        }

        return view('dossier/documents', $this->donneesDossier($societeId));
    }

    public function phaseB(int $societeId): string|ResponseInterface
    {
        if ($redirect = $this->verifierAcces($societeId)) {
            return $redirect;
        }

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
        $typeSociete = db_connect()->table('type_societe')->where('id', $societe['type_societe_id'])->get()->getRowArray();

        return view('dossier/phase_b', [
            'societe' => $societe,
            'reunions' => $reunions,
            'assemblees' => $assemblees,
            'societeId' => $societeId,
            'typeSociete' => $typeSociete,
        ]);
    }

    public function phaseBPlanifier(int $societeId): string|ResponseInterface
    {
        if ($redirect = $this->verifierAcces($societeId)) {
            return $redirect;
        }

        $societe = (new SocieteModel())->find($societeId);
        if (! $societe) {
            throw PageNotFoundException::forPageNotFound('Société introuvable.');
        }

        $reunions = db_connect()->table('reunion')
            ->where('societe_id', $societeId)
            ->orderBy('id', 'DESC')
            ->get()->getResultArray();

        $preview = session()->getFlashdata('phase_b_planifier');
        $planningForm = session()->getFlashdata('planning_form') ?? [];
        $planningError = session()->getFlashdata('planning_error');

        return view('dossier/phase_b_planifier', [
            'societe' => $societe,
            'societeId' => $societeId,
            'reunions' => $reunions,
            'preview' => $preview,
            'planningForm' => $planningForm,
            'planningError' => $planningError,
        ]);
    }

    public function phaseBJourJ(int $societeId, ?int $reunionId = null): string|ResponseInterface
    {
        if ($redirect = $this->verifierAcces($societeId)) {
            return $redirect;
        }

        $etapeDeroulementDemandee = $reunionId !== null || $this->request->getGet('etape') === 'deroulement';
        $reunionId ??= (int) ($this->request->getGet('reunion_id') ?: 0) ?: null;
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
        $assemblee = $selectedReunion
            ? db_connect()->table('assemblee')->where('reunion_id', $selectedReunion['id'])->get()->getRowArray()
            : null;
        $etapeDeroulement = $etapeDeroulementDemandee && (int) ($assemblee['quorum_atteint'] ?? 0) === 1;
        $participantsAssemblee = $assemblee
            ? db_connect()->table('participant')->where('assemblee_id', $assemblee['id'])->orderBy('nom')->get()->getResultArray()
            : [];
        $participantNomParId = array_column($participantsAssemblee, 'nom', 'id');
        $participantsPresents = array_values(array_filter($participantsAssemblee, static fn (array $p): bool => $p['statut'] === 'present'));
        $interventionsTableReady = db_connect()->tableExists('intervention');
        $interventionAuteurReady = $interventionsTableReady && db_connect()->fieldExists('auteur_externe', 'intervention');
        $pouvoirExterneReady = db_connect()->fieldExists('mandataire_nom_externe', 'pouvoir');
        $intervenants = array_map(static fn (array $p): array => [
            'value' => 'participant:' . (int) $p['id'],
            'nom' => $p['nom'],
            'precision' => 'présent',
        ], $participantsPresents);
        $mandatsParMandataire = [];
        $mandatairesAssocies = $assemblee
            ? db_connect()->table('pouvoir p')
                ->select($pouvoirExterneReady
                    ? 'p.mandant_id, p.mandataire_id, COALESCE(m.nom, p.mandataire_nom_externe) AS nom_mandataire, p.mandataire_nom_externe'
                    : 'p.mandant_id, p.mandataire_id, m.nom AS nom_mandataire, NULL AS mandataire_nom_externe')
                ->join('participant m', 'm.id = p.mandataire_id', 'left')
                ->where('p.assemblee_id', $assemblee['id'])
                ->get()->getResultArray()
            : [];
        $mandataireParMandant = [];
        $mandataireExternePresent = false;
        foreach ($mandatairesAssocies as $mandataire) {
            if (! empty($mandataire['nom_mandataire'])) {
                $mandataireParMandant[(int) $mandataire['mandant_id']] = $mandataire['nom_mandataire'];
                if (! empty($mandataire['mandataire_id'])) {
                    $mandataireId = (int) $mandataire['mandataire_id'];
                    $mandatsParMandataire[$mandataireId][] = $participantNomParId[(string) $mandataire['mandant_id']] ?? 'participant représenté';
                }
            }
            if (! empty($mandataire['mandataire_nom_externe'])) {
                $mandataireExternePresent = true;
                $intervenants[] = [
                    'value' => 'mandataire:' . (int) $mandataire['mandant_id'],
                    'nom' => $mandataire['mandataire_nom_externe'],
                    'precision' => 'mandataire de ' . ($participantNomParId[(string) $mandataire['mandant_id']] ?? 'participant représenté'),
                    'disponible' => $interventionAuteurReady,
                ];
            }
        }
        foreach ($intervenants as &$intervenant) {
            if (str_starts_with($intervenant['value'], 'participant:')) {
                $participantId = (int) substr($intervenant['value'], strlen('participant:'));
                if (! empty($mandatsParMandataire[$participantId])) {
                    $intervenant['precision'] .= ' · mandataire de ' . implode(', ', array_unique($mandatsParMandataire[$participantId]));
                }
            }
            $intervenant['disponible'] ??= true;
        }
        unset($intervenant);
        foreach ($participantsAssemblee as &$participant) {
            $participant['mandataire_nom'] = $mandataireParMandant[(int) $participant['id']] ?? null;
        }
        unset($participant);
        $interventions = $assemblee && $interventionsTableReady
            ? (new InterventionService())->pourAssemblee((int) $assemblee['id'])
            : [];
        $convoques = $selectedReunion
            ? db_connect()->table('convoque')->where('reunion_id', $selectedReunion['id'])->orderBy('nom_affiche')->get()->getResultArray()
            : [];

        if ($selectedReunion && $assemblee && (int) $assemblee['quorum_atteint'] !== 1) {
            $type = db_connect()->table('type_societe')->where('id', $societe['type_societe_id'])->get()->getRowArray();
            $numeroSuivant = (int) $selectedReunion['numero_convocation'] + 1;
            $regleQuorumSuivante = db_connect()->table('regle_quorum')
                ->where('type_societe_id', $societe['type_societe_id'])
                ->where('nature_code', $selectedReunion['nature_code'])
                ->where('numero_convocation', $numeroSuivant)
                ->get()->getRowArray();
            if (! $regleQuorumSuivante && $selectedReunion['nature_code'] === 'CA') {
                $regleQuorumSuivante = db_connect()->table('regle_quorum')
                    ->where('type_societe_id', $societe['type_societe_id'])
                    ->where('nature_code', 'CA')
                    ->where('numero_convocation', 1)
                    ->get()->getRowArray();
            }
            $regleDelaiSuivante = db_connect()->table('regle_delai')
                ->where('type_societe_id', $societe['type_societe_id'])
                ->where('nature_code', $selectedReunion['nature_code'])
                ->where('numero_convocation', $numeroSuivant)
                ->get()->getRowArray();
            $sourceQuorum = db_connect()->table('regle_quorum')
                ->where('type_societe_id', $societe['type_societe_id'])
                ->where('nature_code', $selectedReunion['nature_code'])
                ->where('numero_convocation', $selectedReunion['numero_convocation'])
                ->get()->getRowArray();

            return view('dossier/phase_b_quorum_absent', [
                'societe' => $societe,
                'societeId' => $societeId,
                'selectedReunion' => $selectedReunion,
                'assemblee' => $assemblee,
                'participantsAssemblee' => $participantsAssemblee,
                'mandataireParMandant' => $mandataireParMandant,
                'regleQuorumSuivante' => $regleQuorumSuivante,
                'regleDelaiSuivante' => $regleDelaiSuivante,
                'reconvocationPossible' => $regleQuorumSuivante !== null && (
                    ($regleDelaiSuivante !== null && $regleDelaiSuivante['delai_jours'] !== null)
                    || $selectedReunion['nature_code'] === 'CA'
                ),
                'baseLegale' => (new Justification())->baseLegaleNonQuorum(
                    (string) ($type['code'] ?? ''),
                    (string) $selectedReunion['nature_code'],
                    (int) $selectedReunion['numero_convocation'],
                    $sourceQuorum['source'] ?? null,
                ),
            ]);
        }

        return view('dossier/phase_b_jour_j', [
            'societe' => $societe,
            'societeId' => $societeId,
            'reunions' => $reunions,
            'selectedReunion' => $selectedReunion,
            'assemblee' => $assemblee,
            'etapeDeroulement' => $etapeDeroulement,
            'participantsAssemblee' => $participantsAssemblee,
            'participantsPresents' => $participantsPresents,
            'intervenants' => $intervenants,
            'interventions' => $interventions,
            'interventionsTableReady' => $interventionsTableReady,
            'interventionAuteurReady' => $interventionAuteurReady,
            'mandataireExternePresent' => $mandataireExternePresent,
            'convoques' => $convoques,
            'result' => $result,
        ]);
    }

    public function phaseBVotes(int $societeId, ?int $assembleeId = null): string|ResponseInterface
    {
        if ($redirect = $this->verifierAcces($societeId)) {
            return $redirect;
        }

        $assembleeId ??= (int) ($this->request->getGet('assemblee_id') ?: 0) ?: null;
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

    public function phaseBRegistre(int $societeId): string|ResponseInterface
    {
        if ($redirect = $this->verifierAcces($societeId)) {
            return $redirect;
        }

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
        if ($redirect = $this->verifierAcces($societeId)) {
            return $redirect;
        }

        $nature = strtoupper((string) $this->request->getPost('nature'));
        $dateReunion = (string) $this->request->getPost('date_reunion');
        $heure = (string) $this->request->getPost('heure');
        $lieu = (string) $this->request->getPost('lieu');
        $ordreDuJour = preg_split('/\r\n|\r|\n/', (string) $this->request->getPost('ordre_du_jour'));
        $ordreDuJour = array_values(array_filter(array_map('trim', $ordreDuJour ?? []), static fn ($ligne): bool => $ligne !== ''));
        $numeroConvocation = (int) ($this->request->getPost('numero_convocation') ?: 1);
        $delaiStatutsPost = $this->request->getPost('delai_statuts');
        $delaiStatuts = $delaiStatutsPost !== null && $delaiStatutsPost !== '' ? (int) $delaiStatutsPost : null;
        $reunionPrecedenteId = $this->request->getPost('reunion_precedente_id');
        $reunionPrecedente = $reunionPrecedenteId
            ? db_connect()->table('reunion')->where('id', (int) $reunionPrecedenteId)->where('societe_id', $societeId)->get()->getRowArray()
            : null;
        $natureEffective = $reunionPrecedente['nature_code'] ?? $nature;
        if ($natureEffective === 'CA' && $delaiStatuts === null) {
            return redirect()->to('/dossiers/' . $societeId . '/phase-b/planifier')
                ->with('planning_error', 'Pour planifier un CA, indique le délai de convocation prévu dans les statuts. Il n’existe pas de délai général de six jours configuré pour le CA.')
                ->with('planning_form', [
                    'nature' => $nature,
                    'date_reunion' => $dateReunion,
                    'heure' => $heure,
                    'lieu' => $lieu,
                    'numero_convocation' => $numeroConvocation,
                    'reunion_precedente_id' => $reunionPrecedenteId,
                    'ordre_du_jour' => (string) $this->request->getPost('ordre_du_jour'),
                    'delai_statuts' => '',
                ]);
        }

        $result = (new PhaseBService())->ecranPlanification(
            $societeId,
            $nature,
            $dateReunion,
            $heure,
            $lieu,
            $ordreDuJour,
            $numeroConvocation,
            'franc',
            $delaiStatuts,
            true,
            $reunionPrecedenteId ? (int) $reunionPrecedenteId : null,
        );

        return redirect()->to('/dossiers/' . $societeId . '/phase-b/planifier')
            ->with('phase_b_planifier', $result)
            ->with('success', 'Réunion planifiée : ' . $result['resume']['nature'] . ' le ' . $result['resume']['date_reunion']);
    }

    public function tenirPhaseB(int $societeId): ResponseInterface
    {
        if ($redirect = $this->verifierAcces($societeId)) {
            return $redirect;
        }

        $reunionId = (int) $this->request->getPost('reunion_id');
        $participants = [];
        $rows = $this->request->getPost('participants_rows');
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (is_array($row) && ! empty($row['nom'])) {
                    $autre = ($row['par'] ?? '') === '__autre__';
                    $participants[] = [
                        'nom' => (string) $row['nom'],
                        'statut' => (string) ($row['statut'] ?? 'absent'),
                        'par' => $autre
                            ? trim((string) ($row['autre_nom'] ?? ''))
                            : (! empty($row['par']) ? (string) $row['par'] : null),
                        'par_externe' => $autre,
                    ];
                }
            }
        }
        if ($participants === []) {
            $rawParticipants = preg_split('/\r\n|\r|\n/', (string) $this->request->getPost('participants'));
            foreach ($rawParticipants ?? [] as $ligne) {
                $parts = array_map('trim', explode(';', trim((string) $ligne)));
                if (! empty($parts[0])) $participants[] = ['nom' => $parts[0], 'statut' => $parts[1] ?? 'present', 'par' => $parts[2] ?? null];
            }
        }

        $result = (new PhaseBService())->ecranJourJ(
            $reunionId,
            $participants,
            (string) $this->request->getPost('president'),
            (string) $this->request->getPost('secretaire'),
            (string) $this->request->getPost('date_tenue') ?: null,
        );

        return redirect()->to('/dossiers/' . $societeId . '/phase-b/jour-j/' . $reunionId)
            ->with('phase_b_jour_j', $result)
            ->with('success', 'Assemblée tenue : quorum ' . (($result['resume']['quorum_atteint']) ? 'atteint' : 'non atteint'));
    }

    public function ajouterIntervention(int $societeId): ResponseInterface
    {
        if ($redirect = $this->verifierAcces($societeId)) {
            return $redirect;
        }

        $assembleeId = (int) $this->request->getPost('assemblee_id');
        $auteurSelection = (string) $this->request->getPost('auteur_selection');
        $texte = (string) $this->request->getPost('texte');
        $assemblee = db_connect()->table('assemblee a')
            ->select('a.id, a.reunion_id, a.quorum_atteint')
            ->join('reunion r', 'r.id = a.reunion_id')
            ->where('a.id', $assembleeId)
            ->where('r.societe_id', $societeId)
            ->get()->getRowArray();

        if (! $assemblee) {
            throw PageNotFoundException::forPageNotFound('Assemblée introuvable pour cette société.');
        }
        if ((int) $assemblee['quorum_atteint'] !== 1) {
            return redirect()->to('/dossiers/' . $societeId . '/phase-b/jour-j/' . $assemblee['reunion_id'])
                ->with('error', 'Le quorum n’est pas atteint : le déroulement et la saisie des interventions restent fermés.');
        }

        (new InterventionService())->ajouter($assembleeId, $auteurSelection, $texte);

        return redirect()->to('/dossiers/' . $societeId . '/phase-b/jour-j/' . $assemblee['reunion_id'])
            ->with('success', 'Intervention ajoutée au déroulement de la réunion.');
    }

    public function voterPhaseB(int $societeId): ResponseInterface
    {
        if ($redirect = $this->verifierAcces($societeId)) {
            return $redirect;
        }

        $assembleeId = (int) $this->request->getPost('assemblee_id');
        $resolutions = [];
        $rows = $this->request->getPost('resolutions_rows');
        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (is_array($row) && ! empty($row['texte'])) {
                    $resolutions[] = [
                        'texte' => (string) $row['texte'],
                        'pour' => (int) ($row['pour'] ?? 0),
                        'contre' => (int) ($row['contre'] ?? 0),
                        'abstention' => (int) ($row['abstention'] ?? 0),
                        'blanc' => (int) ($row['blanc'] ?? 0),
                        'sens_vote_president' => $row['sens_vote_president'] ?: null,
                    ];
                }
            }
        }
        if ($resolutions === []) {
            $rawResolutions = preg_split('/\r\n|\r|\n/', (string) $this->request->getPost('resolutions'));
            foreach ($rawResolutions ?? [] as $ligne) {
                $parts = array_map('trim', explode('|', trim((string) $ligne)));
                if (! empty($parts[0]) && count($parts) >= 3) {
                    $resolutions[] = [
                        'texte' => $parts[0],
                        'pour' => (int) $parts[1],
                        'contre' => (int) $parts[2],
                        'abstention' => (int) ($parts[3] ?? 0),
                        'blanc' => (int) ($parts[4] ?? 0),
                        'sens_vote_president' => isset($parts[5]) && $parts[5] !== '' ? strtolower($parts[5]) : null,
                    ];
                }
            }
        }

        $result = (new PhaseBService())->ecranVotes($assembleeId, $resolutions);
        $adoptees = count(array_filter($result['resultat'] ?? [], static fn ($r): bool => (bool) ($r['adoptee'] ?? false)));

        return redirect()->to('/dossiers/' . $societeId . '/phase-b/votes/' . $assembleeId)
            ->with('phase_b_votes', $result)
            ->with('success', 'Votes enregistrés : ' . $adoptees . ' résolution(s) adoptée(s)');
    }

    public function enregistrerPieces(int $societeId): ResponseInterface
    {
        if ($redirect = $this->verifierAcces($societeId)) {
            return $redirect;
        }

        $pieces = $this->request->getPost('pieces');
        $nb = 0;

        if (is_array($pieces)) {
            $service = new DossierService();
            foreach ($pieces as $documentId => $champs) {
                $fichier = trim((string) ($champs['fichier'] ?? ''));
                $date    = trim((string) ($champs['date_piece'] ?? ''));

                if ($fichier === '' && $date === '') {
                    continue;
                }

                $service->enregistrerPiece($societeId, (int) $documentId, [
                    'date_piece' => $date !== '' ? $date : null,
                    'fichier'    => $fichier !== '' ? $fichier : null,
                ]);
                $nb++;
            }
        }

        return redirect()->to(site_url('dossiers/' . $societeId . '/pieces'))
            ->with('success', $nb . ' pièce(s) enregistrée(s).');
    }

    public function status(int $societeId): ResponseInterface
    {
        if ($redirect = $this->verifierAcces($societeId)) {
            return $redirect;
        }

        $status = (string) $this->request->getPost('statut');
        try {
            $result = (new SuiviDossierService())->changerStatut($societeId, $status);
        } catch (\RuntimeException $e) {
            return redirect()->to('/dossiers/' . $societeId . '/suivi')
                ->with('errors', ['bloquants' => [$e->getMessage()], 'avertissements' => [], 'infos' => []]);
        }

        return redirect()->to('/dossiers/' . $societeId . '/suivi')
            ->with('success', $result['message']);
    }
}