<?php

namespace App\Commands;

use App\Libraries\PhaseBService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * php spark demo:phase-b
 *
 * Démo T8 : écrans de la phase B : planification, journée de réunion, votes, registre des assemblées.
 */
class DemoPhaseB extends BaseCommand
{
    protected $group       = 'Demo';
    protected $name        = 'demo:phase-b';
    protected $description = 'Démo T8 : planification, tenue, votes et registre des assemblées.';

    public function run(array $params)
    {
        $db = db_connect();
        $societe = $db->table('societe')->where('denomination', 'EXEMPLE SA')->get()->getRowArray();
        if (! $societe) {
            CLI::error("EXEMPLE SA n'existe pas. Lancez d'abord : php spark db:seed DemoSeeder");
            return;
        }

        $service = new PhaseBService();

        CLI::write('=== T8 - Écran 1 : planification ===');
        $planning = $service->ecranPlanification(
            (int) $societe['id'],
            'AGO',
            '2026-12-15',
            '09:00',
            'Siège social',
            ['Approbation des comptes'],
            1,
            'franc',
            null,
            true
        );
        CLI::write('Société : ' . $planning['reunion']['societe']);
        CLI::write('Nature : ' . $planning['reunion']['nature']);
        CLI::write('Date réunion : ' . $planning['reunion']['date_reunion']);
        CLI::write('Date convocation : ' . $planning['reunion']['date_convocation']);
        CLI::write('Délai : ' . $planning['reunion']['delai_jours'] . ' jours');
        CLI::write('Convoqués : ' . implode(', ', $planning['reunion']['convoques']));
        CLI::newLine();

        CLI::write('=== T8 - Écran 2 : jour J ===');
        $jourJ = $service->ecranJourJ(
            (int) $planning['reunion']['reunion_id'],
            [
                ['nom' => 'RAKOTO Jean', 'statut' => 'present'],
                ['nom' => 'RABE Hanta', 'statut' => 'represente', 'par' => 'RAKOTO Jean'],
                ['nom' => 'ANDRIA Mamy', 'statut' => 'absent'],
            ],
            'RAKOTO Jean',
            'RABE Hanta',
            '2026-12-15'
        );
        CLI::write('Assemblée id : ' . $jourJ['resume']['assemblee_id']);
        CLI::write('Quorum atteint : ' . ($jourJ['resume']['quorum_atteint'] ? 'oui' : 'non'));
        CLI::write('Voix présentes : ' . $jourJ['resume']['voix_presentes_ou_representees'] . '/' . $jourJ['resume']['voix_totales']);
        CLI::write('Motif : ' . $jourJ['resume']['justification']['motif']);
        CLI::newLine();

        CLI::write('=== T8 - Écran 3 : votes ===');
        $votes = $service->ecranVotes((int) $jourJ['resume']['assemblee_id'], [
            ['texte' => 'Approbation des comptes', 'pour' => 150, 'contre' => 0],
            ['texte' => 'Distribution de dividendes', 'pour' => 50, 'contre' => 100],
        ]);
        foreach ($votes['resultat'] as $r) {
            CLI::write('- ' . $r['texte'] . ' => ' . ($r['adoptee'] ? 'adoptée' : 'rejetée'));
        }
        CLI::newLine();

        CLI::write('=== T8 - Écran 4 : registre des assemblées ===');
        $registre = $service->registreAssemblies((int) $societe['id']);
        foreach ($registre['registre'] as $item) {
            CLI::write('- ' . $item['nature'] . ' n°' . $item['numero_convocation'] . ' | ' . $item['date_reunion'] . ' | statut=' . $item['statut'] . ' | quorum=' . ($item['quorum_atteint'] !== null ? ($item['quorum_atteint'] ? 'oui' : 'non') : 'n/a'));
        }
    }
}
