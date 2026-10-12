<?php

use CodeIgniter\Test\CIUnitTestCase;

final class WorkflowSurfaceTest extends CIUnitTestCase
{
    public function testWorkflowRoutesExposeCreationPhaseBAndPdfSurfaces(): void
    {
        $routes = file_get_contents(ROOTPATH . 'app/Config/Routes.php');

        $this->assertNotFalse($routes);
        foreach ([
            "societes/nouveau",
            "dossiers/(:num)",
            "dossiers/(:num)/phase-b",
            "dossiers/(:num)/phase-b/planifier",
            "dossiers/(:num)/phase-b/jour-j",
            "dossiers/(:num)/phase-b/votes",
            "dossiers/(:num)/phase-b/registre",
            "dossiers/(:num)/sarlu/decision",
            "pdf/statuts/(:num)",
            "pdf/convocations/(:num)",
            "pdf/proces-verbaux/(:num)",
            "pdf/constitutifs/(:num)/(:segment)",
        ] as $route) {
            $this->assertStringContainsString($route, $routes);
        }
    }

    public function testSecondConvocationUsesTheRuleRepository(): void
    {
        $source = file_get_contents(ROOTPATH . 'app/Libraries/ReunionService.php');

        $this->assertNotFalse($source);
        $this->assertStringContainsString('trouver((int) $societe[\'type_societe_id\'], $nature, $numeroConvocation)', $source);
        $this->assertStringNotContainsString('$delai = 6;', $source);
    }

    public function testStructuredPhaseBFieldsAreAcceptedByControllers(): void
    {
        $source = file_get_contents(ROOTPATH . 'app/Controllers/Dossiers.php');

        $this->assertNotFalse($source);
        $this->assertStringContainsString("getPost('participants_rows')", $source);
        $this->assertStringContainsString("getPost('resolutions_rows')", $source);
        $this->assertStringContainsString('name="reunion_precedente_id"', file_get_contents(ROOTPATH . 'app/Views/dossier/phase_b_planifier.php'));
    }
}
