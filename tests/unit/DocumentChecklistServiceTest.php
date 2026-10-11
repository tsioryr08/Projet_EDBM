<?php

use App\Libraries\DocumentChecklistService;
use CodeIgniter\Test\CIUnitTestCase;

final class DocumentChecklistServiceTest extends CIUnitTestCase
{
    public function testChecklistMarksMissingAndExpiredDocuments(): void
    {
        $service = new DocumentChecklistService();

        $contexte = [
            'type_societe' => 'SARL',
            'mode_administration' => 'gerance',
            'date_depot_prevue' => '2026-10-11',
            'documents' => [
                'STATUTS_SARL' => ['date_emission' => '2026-01-10'],
                'DIR_ID' => ['date_emission' => '2026-09-02'],
                'ASSOC_ID' => ['date_emission' => '2026-09-15'],
                'DIR_RESID' => ['date_emission' => '2026-06-30'],
            ],
        ];

        $result = $service->genererChecklist($contexte, [
            ['code' => 'STATUTS_SARL', 'categorie' => 'etablissement', 'libelle' => 'Statuts', 'condition_code' => null, 'validite_mois' => 12, 'obligatoire' => 1],
            ['code' => 'DIR_ID', 'categorie' => 'dirigeant', 'libelle' => 'Pièce d’identité du dirigeant', 'condition_code' => null, 'validite_mois' => 3, 'obligatoire' => 1],
            ['code' => 'ASSOC_ID', 'categorie' => 'associe', 'libelle' => 'Pièce d’identité de l’associé', 'condition_code' => null, 'validite_mois' => 3, 'obligatoire' => 1],
            ['code' => 'DIR_RESID', 'categorie' => 'dirigeant', 'libelle' => 'Certificat de résidence', 'condition_code' => null, 'validite_mois' => 3, 'obligatoire' => 1],
            ['code' => 'SIEGE_BAIL', 'categorie' => 'siege', 'libelle' => 'Contrat de bail', 'condition_code' => null, 'validite_mois' => 12, 'obligatoire' => 1],
        ]);

        $this->assertSame('incomplet', $result['dossier_statut']);
        $this->assertGreaterThanOrEqual(1, $result['pieces_manquantes']);
        $this->assertGreaterThanOrEqual(1, $result['pieces_expirees']);
    }
}
