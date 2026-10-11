<?php

namespace App\Controllers;

use App\Libraries\PdfService;
use App\Models\AssocieModel;
use App\Models\DirigeantModel;
use App\Models\SocieteModel;
use App\Models\TypeSocieteModel;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

class Constituants extends BaseController
{
    public function generate(int $societeId, string $document): ResponseInterface
    {
        $societe = (new SocieteModel())->find($societeId);
        if (! $societe) {
            throw new RuntimeException("Société #{$societeId} introuvable.");
        }
        $type = (new TypeSocieteModel())->find($societe['type_societe_id']);
        $data = [
            'titre' => $this->titre($document),
            'societe' => $societe,
            'type' => $type,
            'associes' => (new AssocieModel())->where('societe_id', $societeId)->findAll(),
            'dirigeants' => (new DirigeantModel())->where('societe_id', $societeId)->findAll(),
            'document' => $document,
        ];
        $result = (new PdfService())->generateFromView('pdf/constitutifs/document', $data, [
            'societe_id' => $societeId,
            'nature' => $document === 'pv_ag_constitutive' ? 'pv_constitutif' : 'autre',
            'nom_fichier' => 'document-' . $document . '-' . $societeId . '-' . date('YmdHis') . '.pdf',
        ]);
        return $this->response->download($result['chemin'], null)->setFileName(basename($result['chemin']));
    }

    private function titre(string $document): string
    {
        return match ($document) {
            'bulletin_souscription' => 'Bulletin de souscription',
            'declaration_souscription' => 'Déclaration de souscription et de versement',
            'etat_souscription' => 'État de souscription et de versement',
            'attestation_blocage' => 'Attestation de blocage du capital libéré',
            'pv_ag_constitutive' => 'Procès-verbal de l’assemblée générale constitutive',
            'pv_nomination_gerants' => 'Procès-verbal de nomination des gérants',
            'declaration_dirigeants' => 'Déclaration relative aux dirigeants sociaux',
            'decision_associe_unique' => 'Décision de l’associé unique',
            default => throw new RuntimeException("Document constitutif inconnu : {$document}."),
        };
    }
}
