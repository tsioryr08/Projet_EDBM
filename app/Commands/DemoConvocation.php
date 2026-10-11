<?php

namespace App\Commands;

use App\Libraries\ReunionService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * php spark demo:convocation           -> calcule et affiche, sans rien enregistrer
 * php spark demo:convocation --save    -> enregistre aussi la réunion et les convoqués
 */
class DemoConvocation extends BaseCommand
{
    protected $group       = 'Demo';
    protected $name        = 'demo:convocation';
    protected $description = "Calcule la date de convocation d'une AGO de EXEMPLE SA (démo).";

    public function run(array $params)
    {
        $societe = db_connect()->table('societe')->where('denomination', 'EXEMPLE SA')->get()->getRowArray();
        if (! $societe) {
            CLI::error("EXEMPLE SA n'existe pas. Lancez d'abord : php spark db:seed DemoSeeder");
            return;
        }

        $enregistrer = CLI::getOption('save') !== null;
        $service     = new ReunionService();

        foreach (['calendaire', 'franc'] as $mode) {
            $r = $service->planifier(
                (int) $societe['id'], 'AGO', '2026-12-15', '09:00', 'Siège social',
                ['Approbation des comptes', 'Questions diverses'],
                1, $mode, null,
                $enregistrer && $mode === 'franc' // on n'enregistre que la version « franc »
            );

            CLI::write("--- Mode : {$mode} ---", 'yellow');
            CLI::write("{$r['societe']} ({$r['type']}) - {$r['nature']} le {$r['date_reunion']} à {$r['heure']}, {$r['lieu']}");
            CLI::write("Délai légal : {$r['delai_jours']} jours  ({$r['source']}" . ($r['verifie'] ? ', vérifié' : ', à confirmer') . ')');
            CLI::write("Forme d'envoi : {$r['forme_envoi']}");
            CLI::write('Ordre du jour : ' . implode(' ; ', $r['ordre_du_jour']));
            CLI::write("Date limite de convocation : {$r['date_convocation']}", 'green');
            CLI::write('Pourquoi : ' . $r['justification']['motif']);
            CLI::write('Statut : ' . \App\Libraries\Justification::STATUTS[$r['justification']['statut']]);
            if ($r['justification']['avertissement']) {
                CLI::write('Attention : ' . $r['justification']['avertissement'], 'yellow');
            }
            if (isset($r['convoques'])) {
                CLI::write('Convoqués : ' . implode(', ', $r['convoques']));
            }
            CLI::newLine();
        }

        CLI::write($enregistrer ? 'Réunion (franc) enregistrée en base.' : "Rien enregistré (ajoutez --save pour enregistrer).");
    }
}
