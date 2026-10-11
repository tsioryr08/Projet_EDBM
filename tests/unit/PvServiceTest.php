<?php

use App\Libraries\PvService;
use CodeIgniter\Test\CIUnitTestCase;

final class PvServiceTest extends CIUnitTestCase
{
    public function testGeneratesPvWhenQuorumIsReached(): void
    {
        $service = new PvService();
        $json = [
            'societe' => 'EXEMPLE SA',
            'nature' => 'AGO',
            'date_reunion' => '2026-12-15',
            'lieu' => 'Siège social',
            'president_seance' => 'RAKOTO Jean',
            'secretaire' => 'RABE Hanta',
            'quorum_requis' => '1/4',
            'quorum_atteint' => true,
            'participants' => [
                ['nom' => 'RAKOTO Jean', 'statut' => 'present', 'voix' => 100],
                ['nom' => 'RABE Hanta', 'statut' => 'represente', 'par' => 'RAKOTO Jean', 'voix' => 50],
                ['nom' => 'ANDRIA Mamy', 'statut' => 'absent', 'voix' => 50],
            ],
            'resolutions' => [
                ['texte' => 'Approbation des comptes', 'pour' => 150, 'contre' => 0, 'adoptee' => true],
            ],
        ];

        $pv = $service->generer($json);

        $this->assertStringContainsString('PROCÈS-VERBAL', $pv);
        $this->assertStringContainsString('Quorum atteint : Oui', $pv);
        $this->assertStringContainsString('ADOPTÉE', $pv);
    }

    public function testGeneratesPvWhenQuorumIsNotReached(): void
    {
        $service = new PvService();
        $json = [
            'societe' => 'EXEMPLE SA',
            'nature' => 'AGE',
            'date_reunion' => '2026-12-20',
            'lieu' => 'Siège social',
            'president_seance' => 'RAKOTO Jean',
            'secretaire' => 'RABE Hanta',
            'quorum_requis' => '1/2',
            'quorum_atteint' => false,
            'pv_constat' => [
                'message' => 'Le quorum n’est pas atteint : la séance est constatée comme annulée pour défaut de quorum.',
            ],
            'participants' => [
                ['nom' => 'RAKOTO Jean', 'statut' => 'present', 'voix' => 100],
            ],
            'resolutions' => [],
        ];

        $pv = $service->generer($json);

        $this->assertStringContainsString('Le quorum n’est pas atteint', $pv);
        $this->assertStringContainsString('La séance est annulée pour défaut de quorum', $pv);
        $this->assertStringContainsString('Aucune délibération ne peut être prise', $pv);
    }

    public function testDocumentFinalIncludesLegalJustification(): void
    {
        $service = new PvService();
        $json = [
            'societe' => 'EXEMPLE SA',
            'nature' => 'AGO',
            'date_reunion' => '2026-12-15',
            'lieu' => 'Siège social',
            'president_seance' => 'RAKOTO Jean',
            'secretaire' => 'RABE Hanta',
            'quorum_requis' => '1/4',
            'quorum_atteint' => true,
            'motif_quorum' => 'Quorum atteint selon la règle de 25 %.',
            'source_regle' => 'Loi 2003-036, art. 570',
            'regle_verifiee' => true,
            'participants' => [
                ['nom' => 'RAKOTO Jean', 'statut' => 'present', 'voix' => 100],
            ],
            'resolutions' => [
                ['texte' => 'Approbation des comptes', 'pour' => 100, 'contre' => 0, 'adoptee' => true, 'motif' => 'Adoptée', 'source' => 'Loi 2003-036, art. 571', 'statut_regle' => 'verifie'],
            ],
        ];

        $document = $service->documentFinal($json);
        $html = $service->exportHtml($json);

        $this->assertStringContainsString('JUSTIFICATION JURIDIQUE', $document);
        $this->assertStringContainsString('Loi 2003-036, art. 570', $document);
        $this->assertStringContainsString('<html', $html);
        $this->assertStringContainsString('Procès-verbal', $html);
    }
}
