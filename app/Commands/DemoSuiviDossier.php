<?php

namespace App\Commands;

use App\Libraries\SuiviDossierService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * php spark demo:suivi-dossier
 *
 * Démo T9 : suivi du dossier de société, mise à jour du statut et enregistrement des dates.
 */
class DemoSuiviDossier extends BaseCommand
{
    protected $group       = 'Demo';
    protected $name        = 'demo:suivi-dossier';
    protected $description = 'Démo T9 : statut du dossier, transition brouillon → soumis → validé → immatriculé et dates associées.';

    public function run(array $params)
    {
        $db = db_connect();
        $societe = $db->table('societe')->where('denomination', 'EXEMPLE SA')->get()->getRowArray();
        if (! $societe) {
            CLI::error("EXEMPLE SA n'existe pas. Lancez d'abord : php spark db:seed DemoSeeder");
            return;
        }

        $service = new SuiviDossierService();

        $transitions = [
            ['statut' => 'brouillon', 'date' => '2026-10-01'],
            ['statut' => 'soumis', 'date' => '2026-10-03'],
            ['statut' => 'valide', 'date' => '2026-10-08'],
            ['statut' => 'immatricule', 'date' => '2026-10-15'],
        ];

        CLI::write('=== T9 - Suivi du dossier ===');
        CLI::write('Société : ' . $societe['denomination']);

        foreach ($transitions as $transition) {
            $result = $service->changerStatut((int) $societe['id'], $transition['statut'], $transition['date']);
            CLI::write('- Statut : ' . $result['statut']);
            CLI::write('  Ancien statut : ' . ($result['ancien_statut'] ?? '—'));
            CLI::write('  Date de soumission : ' . ($result['date_soumission'] ?? '—'));
            CLI::write('  Date de validation : ' . ($result['date_validation'] ?? '—'));
            CLI::write('  Date d’immatriculation : ' . ($result['date_immatriculation'] ?? '—'));
            CLI::write('  Message : ' . $result['message']);
            CLI::newLine();
        }

        $etat = $service->etat((int) $societe['id']);
        CLI::write('État final :');
        CLI::write(' - statut = ' . $etat['statut']);
        CLI::write(' - date_soumission = ' . ($etat['date_soumission'] ?? '—'));
        CLI::write(' - date_validation = ' . ($etat['date_validation'] ?? '—'));
        CLI::write(' - date_immatriculation = ' . ($etat['date_immatriculation'] ?? '—'));
    }
}
