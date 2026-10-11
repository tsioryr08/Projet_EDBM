<?php

namespace App\Commands;

use App\Libraries\ReunionService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * php spark demo:deuxieme-convocation
 *
 * Démo T7 : seconde convocation liée à la première, avec délai de 6 jours
 * et quorum de deuxième convocation.
 */
class DemoDeuxiemeConvocation extends BaseCommand
{
    protected $group       = 'Demo';
    protected $name        = 'demo:deuxieme-convocation';
    protected $description = 'Démo T7 : seconde convocation, délai 6 jours et lien avec la première réunion.';

    public function run(array $params)
    {
        $db = db_connect();
        $societe = $db->table('societe')->where('denomination', 'EXEMPLE SA')->get()->getRowArray();
        if (! $societe) {
            CLI::error("EXEMPLE SA n'existe pas. Lancez d'abord : php spark db:seed DemoSeeder");
            return;
        }

        $service = new ReunionService();

        $premiere = $service->planifier(
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

        $seconde = $service->planifier(
            (int) $societe['id'],
            'AGO',
            '2026-12-22',
            '09:00',
            'Siège social',
            ['Approbation des comptes'],
            2,
            'franc',
            null,
            true,
            (int) $premiere['reunion_id']
        );

        CLI::write('=== T7 - Deuxième convocation ===');
        CLI::write('Première réunion id : ' . $premiere['reunion_id']);
        CLI::write('Deuxième réunion id : ' . $seconde['reunion_id']);
        CLI::write('Réunion précédente : ' . $seconde['reunion_precedente_id']);
        CLI::write('Numéro de convocation : ' . $seconde['numero_convocation']);
        CLI::write('Délai appliqué : ' . $seconde['delai_jours'] . ' jours');
        CLI::write('Date limite de convocation : ' . $seconde['date_convocation']);
        CLI::write('Justification : ' . $seconde['justification']['motif']);
        CLI::write('Source : ' . $seconde['source']);
    }
}
