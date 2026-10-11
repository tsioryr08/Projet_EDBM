<?php

namespace App\Commands;

use App\Libraries\DossierService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * php spark demo:dossier
 *
 * Démo du T6 : état d’un dossier de société, synthèse finale à partir de la checklist T5.
 */
class DemoDossier extends BaseCommand
{
    protected $group       = 'Demo';
    protected $name        = 'demo:dossier';
    protected $description = 'Démo du T6 : état du dossier et résumé du dossier de société.';

    public function run(array $params)
    {
        $db = db_connect();
        $societe = $db->table('societe')->where('denomination', 'EXEMPLE SA')->get()->getRowArray();
        if (! $societe) {
            CLI::error("EXEMPLE SA n'existe pas. Lancez d'abord : php spark db:seed DemoSeeder");
            return;
        }

        $service = new DossierService();

        $contexte = [
            'type_societe' => 'SARL',
            'mode_administration' => 'gerance',
            'date_depot_prevue' => '2026-10-11',
            'associe_mineur' => true,
            'sous_location' => true,
            'activite_reglementee' => false,
            'documents' => [
                'STATUTS_SARL' => ['date_emission' => '2026-01-10'],
                'DIR_ID' => ['date_emission' => '2026-09-02'],
                'ASSOC_ID' => ['date_emission' => '2026-09-15'],
                'DIR_RESID' => ['date_emission' => '2026-08-01'],
                'SIEGE_BAIL' => ['date_emission' => '2026-01-10'],
                'SIEGE_SOUSLOC' => ['date_emission' => '2026-02-01'],
            ],
        ];

        $etat = $service->evaluerDossier((int) $societe['id'], $contexte);

        CLI::write('=== T6 - État du dossier + validité de création ===');
        CLI::write('Société : ' . ($etat['societe_nom'] ?? '—'));
        CLI::write('Type : ' . ($etat['type_societe'] ?? '—'));
        CLI::write('Mode : ' . ($etat['mode_administration'] ?? '—'));
        CLI::write('Statut du dossier : ' . $etat['dossier_statut']);
        CLI::write('Résumé : ' . $etat['resume']);
        CLI::write('Validation de création : ' . ($etat['validite_creation']['valide'] ? 'OK' : 'À corriger'));
        CLI::write('Message : ' . $etat['validite_creation']['message']);
        CLI::write('Pièces obligatoires : ' . $etat['pieces_obligatoires']);
        CLI::write('Pièces valides : ' . $etat['pieces_valides']);
        CLI::write('Pièces manquantes : ' . $etat['pieces_manquantes']);
        CLI::write('Pièces expirées : ' . $etat['pieces_expirees']);

        if (! empty($etat['validite_creation']['bloquants'])) {
            CLI::write('Blocages :');
            foreach ($etat['validite_creation']['bloquants'] as $blocage) {
                CLI::write('- ' . $blocage);
            }
        }

        if (! empty($etat['validite_creation']['warnings'])) {
            CLI::write('Avertissements :');
            foreach ($etat['validite_creation']['warnings'] as $warning) {
                CLI::write('- ' . $warning);
            }
        }

        CLI::newLine();

        foreach ($etat['pieces'] as $piece) {
            CLI::write('- ' . $piece['libelle'] . ' => ' . $piece['statut']);
        }
    }
}
