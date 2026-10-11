<?php

namespace App\Commands;

use App\Libraries\DocumentChecklistService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * php spark demo:checklist
 *
 * Démo du T5 : checklist documentaire avec pièces obligatoires, pièces expirées et pièces manquantes.
 */
class DemoChecklist extends BaseCommand
{
    protected $group       = 'Demo';
    protected $name        = 'demo:checklist';
    protected $description = 'Démo du T5 : checklist des pièces, validation de la validité et statut du dossier.';

    public function run(array $params)
    {
        $service = new DocumentChecklistService();

        $contexte = [
            'type_societe' => 'SARL',
            'mode_administration' => 'gerance',
            'date_depot_prevue' => '2026-10-11',
            'associe_mineur' => true,
            'sous_location' => true,
            'activite_reglementee' => false,
            'mandataire' => false,
            'type_personne' => 'personne_physique',
            'documents' => [
                'STATUTS_SARL' => ['date_emission' => '2026-01-10'],
                'DIR_ID' => ['date_emission' => '2026-09-02'],
                'ASSOC_ID' => ['date_emission' => '2026-09-15'],
                'DIR_RESID' => ['date_emission' => '2026-08-01'],
                'SIEGE_BAIL' => ['date_emission' => '2026-01-10'],
                'SIEGE_SOUSLOC' => ['date_emission' => '2026-02-01'],
            ],
        ];

        $pieces = $service->buildChecklistForType('SARL', $contexte);
        $resultat = $service->genererChecklist($contexte, $pieces);

        CLI::write('=== T5 - Checklist documentaire ===');
        CLI::write('Statut du dossier : ' . $resultat['dossier_statut']);
        CLI::write('Pièces obligatoires : ' . $resultat['pieces_obligatoires']);
        CLI::write('Pièces valides : ' . $resultat['pieces_valides']);
        CLI::write('Pièces manquantes : ' . $resultat['pieces_manquantes']);
        CLI::write('Pièces expirées : ' . $resultat['pieces_expirees']);
        CLI::newLine();

        foreach ($resultat['pieces'] as $piece) {
            CLI::write('- ' . $piece['libelle'] . ' => ' . $piece['statut']);
        }
    }
}
