<?php

namespace App\Commands;

use App\Libraries\SocieteService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/** php spark demo:societe  -> affiche l'objet « societe » (JSON) de EXEMPLE SA */
class DemoSociete extends BaseCommand
{
    protected $group       = 'Demo';
    protected $name        = 'demo:societe';
    protected $description = "Affiche l'objet « societe » en JSON pour EXEMPLE SA (entrée du générateur de statuts).";

    public function run(array $params)
    {
        $societe = db_connect()->table('societe')->where('denomination', 'EXEMPLE SA')->get()->getRowArray();
        if (! $societe) {
            CLI::error("EXEMPLE SA n'existe pas. Lancez d'abord : php spark db:seed DemoSeeder");
            return;
        }
        CLI::write(json_encode((new SocieteService())->versJson((int) $societe['id']),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
