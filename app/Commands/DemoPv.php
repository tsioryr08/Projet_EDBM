<?php

namespace App\Commands;

use App\Libraries\AssembleeService;
use App\Libraries\PvService;
use App\Libraries\ReunionService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DemoPv extends BaseCommand
{
    protected $group = 'Demo';
    protected $name = 'demo:pv';
    protected $description = 'Démontre la génération de procès-verbal pour les cas quorum, annulation et voix prépondérante.';

    public function run(array $params)
    {
        $db = db_connect();
        $societe = $db->table('societe')->where('denomination', 'EXEMPLE SA')->get()->getRowArray();
        if (! $societe) {
            CLI::error("EXEMPLE SA n'existe pas. Lancez d'abord : php spark db:seed DemoSeeder");
            return;
        }

        $reunions = new ReunionService();
        $service = new AssembleeService();
        $pvService = new PvService();
        $htmlMode = CLI::getOption('html') !== null;

        // Cas 1 : AG avec quorum atteint
        $ago = $reunions->planifier((int) $societe['id'], 'AGO', '2026-12-15', '09:00', 'Siège social', ['Approbation des comptes']);
        $qAgo = $service->tenir($ago['reunion_id'], [
            ['nom' => 'RAKOTO Jean', 'statut' => 'present'],
            ['nom' => 'RABE Hanta', 'statut' => 'represente', 'par' => 'RAKOTO Jean'],
            ['nom' => 'ANDRIA Mamy', 'statut' => 'absent'],
        ], 'RAKOTO Jean', 'RABE Hanta');

        $jsonAgo = $service->versJson($qAgo['assemblee_id']);
        $pvAgo = $pvService->generer($jsonAgo);
        CLI::write('=== PV généré pour l’AGO ===');
        CLI::write($htmlMode ? $pvService->exportHtml($jsonAgo) : $pvAgo);
        CLI::newLine();

        // Cas 2 : AGE avec quorum non atteint
        $age = $reunions->planifier((int) $societe['id'], 'AGE', '2026-12-20', '09:00', 'Siège social', ['Modification des statuts']);
        $qAge = $service->tenir($age['reunion_id'], [['nom' => 'RAKOTO Jean', 'statut' => 'present']]);
        $jsonAge = $service->versJson($qAge['assemblee_id']);
        $pvAge = $pvService->generer($jsonAge);
        CLI::write('=== PV de constat pour AGE sans quorum ===');
        CLI::write($htmlMode ? $pvService->exportHtml($jsonAge) : $pvAge);
        CLI::newLine();

        // Cas 3 : CA avec voix prépondérante du président
        $ca = $reunions->planifier((int) $societe['id'], 'CA', '2026-12-10', '10:00', 'Siège social', ['Convention X'], 1, null, 3);
        $qCa = $service->tenir($ca['reunion_id'], [
            ['nom' => 'RAKOTO Jean', 'statut' => 'present'],
            ['nom' => 'RABE Hanta', 'statut' => 'present'],
            ['nom' => 'ANDRIA Mamy', 'statut' => 'present'],
        ], 'RAKOTO Jean');

        $service->voter($qCa['assemblee_id'], [
            ['texte' => 'Convention X', 'pour' => 1, 'contre' => 1, 'abstention' => 0],
        ]);
        $service->departager($qCa['assemblee_id'], 1, 'pour');

        $jsonCa = $service->versJson($qCa['assemblee_id']);
        $pvCa = $pvService->generer($jsonCa);
        CLI::write('=== PV généré pour le CA ===');
        CLI::write($htmlMode ? $pvService->exportHtml($jsonCa) : $pvCa);
    }
}
