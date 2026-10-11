<?php

namespace App\Libraries;

use App\Models\DossierModel;
use App\Models\DocumentRequisModel;
use App\Models\EtapeCreationModel;
use App\Models\PieceFournieModel;
use App\Models\SocieteModel;
use RuntimeException;

class DossierService
{
    public function __construct(
        private ?SocieteModel $societes = null,
        private ?DossierModel $dossiers = null,
        private ?DocumentRequisModel $documents = null,
        private ?PieceFournieModel $pieces = null,
        private ?EtapeCreationModel $etapes = null,
    ) {
        $this->societes = $societes ?? new SocieteModel();
        $this->dossiers = $dossiers ?? new DossierModel();
        $this->documents = $documents ?? new DocumentRequisModel();
        $this->pieces = $pieces ?? new PieceFournieModel();
        $this->etapes = $etapes ?? new EtapeCreationModel();
    }

    public function afficher(int $societeId): array
    {
        $societe = $this->societes->find($societeId);
        if (! $societe) {
            throw new RuntimeException("Société #{$societeId} introuvable.");
        }
        $type = db_connect()->table('type_societe')->where('id', $societe['type_societe_id'])->get()->getRowArray();
        $dossier = $this->dossiers->where('societe_id', $societeId)->first();
        $pieces = $this->pieces->where('societe_id', $societeId)->findAll();
        $documents = [];
        if ($type) {
            $documents = db_connect()->table('document_requis d')
                ->select('d.*')
                ->join('document_type_societe dt', 'dt.document_id = d.id')
                ->where('dt.type_societe_id', $type['id'])
                ->orderBy('d.categorie')->orderBy('d.id')->get()->getResultArray();
        }
        $pieceByDocument = [];
        foreach ($pieces as $piece) {
            $pieceByDocument[$piece['document_id']] = $piece;
        }
        foreach ($documents as &$document) {
            $document['piece'] = $pieceByDocument[$document['id']] ?? null;
            $document['etat'] = $this->etatPiece($document, $document['piece'], $dossier['date_depot_prevue'] ?? null);
        }
        unset($document);
        return [
            'societe' => $societe,
            'dossier' => $dossier,
            'documents' => $documents,
            'etapes' => $this->etapes->orderBy('ordre')->findAll(),
        ];
    }

    public function enregistrerPiece(int $societeId, int $documentId, array $data): int
    {
        if (! $this->societes->find($societeId) || ! $this->documents->find($documentId)) {
            throw new RuntimeException('Société ou document requis introuvable.');
        }
        $existing = $this->pieces->where(['societe_id' => $societeId, 'document_id' => $documentId])->first();
        $payload = [
            'societe_id' => $societeId,
            'document_id' => $documentId,
            'date_piece' => $data['date_piece'] ?? null,
            'fichier' => $data['fichier'] ?? null,
        ];
        if ($existing) {
            $this->pieces->update($existing['id'], $payload);
            return (int) $existing['id'];
        }
        return (int) $this->pieces->insert($payload);
    }

    private function etatPiece(array $document, ?array $piece, ?string $dateDepot): string
    {
        if (! $piece) {
            return 'manquante';
        }
        if (! empty($document['validite_mois']) && ! empty($piece['date_piece']) && ! empty($dateDepot)) {
            if (! (new CalculRegles())->pieceEstValide($piece['date_piece'], (int) $document['validite_mois'], $dateDepot)) {
                return 'périmée';
            }
        }
        return 'fournie';
    }
}
