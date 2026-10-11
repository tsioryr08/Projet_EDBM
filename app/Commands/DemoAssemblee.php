<?php

namespace App\Commands;

use App\Libraries\AssembleeService;
use App\Libraries\ReunionService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * php spark demo:assemblee          -> joue la démo puis ANNULE tout (rien en base)
 * php spark demo:assemblee --save   -> garde les données en base
 */
class DemoAssemblee extends BaseCommand
{
    protected $group       = 'Demo';
    protected $name        = 'demo:assemblee';
    protected $description = "Démo du jour J : quorum atteint (AGO), puis quorum manqué (AGE) avec proposition de 2e convocation.";

    public function run(array $params)
    {
        $db      = db_connect();
        $societe = $db->table('societe')->where('denomination', 'EXEMPLE SA')->get()->getRowArray();
        if (! $societe) {
            CLI::error("EXEMPLE SA n'existe pas. Lancez d'abord : php spark db:seed DemoSeeder");
            return;
        }
        $garder = CLI::getOption('save') !== null;

        $reunions = new ReunionService();
        $service  = new AssembleeService();

        $db->transBegin();
        try {
            // ---------- Scénario A : AGO, quorum atteint ----------
            CLI::write('=== A. AGO du 15/12/2026 : quorum d\'un quart ===', 'yellow');
            $ago = $reunions->planifier((int) $societe['id'], 'AGO', '2026-12-15', '09:00', 'Siège social', ['Approbation des comptes']);
            $q = $service->tenir($ago['reunion_id'], [
                ['nom' => 'RAKOTO Jean',    'statut' => 'present'],
                ['nom' => 'RABE Hanta',     'statut' => 'represente', 'par' => 'RAKOTO Jean'],
                ['nom' => 'ANDRIA Mamy',    'statut' => 'absent'],
                // RASOA Voahangy non listée : ajoutée comme absente
            ], 'RAKOTO Jean', 'RABE Hanta');
            $this->afficherQuorum($q);

            $res = $service->voter($q['assemblee_id'], [
                ['texte' => 'Approbation des comptes', 'pour' => 150, 'contre' => 0],
                ['texte' => 'Distribution de dividendes', 'pour' => 50, 'contre' => 100],
            ]);
            foreach ($res as $r) {
                CLI::write("Résolution {$r['numero']} : {$r['texte']} -> pour {$r['pour']} / contre {$r['contre']} (majorité > {$r['majorite_requise']}) : "
                    . ($r['adoptee'] ? 'ADOPTÉE' : 'REJETÉE'), $r['adoptee'] ? 'green' : 'red');
            }
            CLI::newLine();
            CLI::write("JSON pour le générateur de PV :", 'cyan');
            CLI::write(json_encode($service->versJson($q['assemblee_id']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            CLI::newLine();

            // ---------- Scénario B : AGE, quorum manqué ----------
            CLI::write('=== B. AGE du 20/12/2026 : quorum de la moitié ===', 'yellow');
            $age = $reunions->planifier((int) $societe['id'], 'AGE', '2026-12-20', '09:00', 'Siège social', ['Modification des statuts']);
            $q2 = $service->tenir($age['reunion_id'], [
                ['nom' => 'RAKOTO Jean', 'statut' => 'present'],
            ]);
            $this->afficherQuorum($q2);

            if ($garder) {
                $db->transCommit();
                CLI::write('Données enregistrées en base.', 'green');
            } else {
                $db->transRollback();
                CLI::write("Démo annulée : rien n'a été enregistré (ajoutez --save pour garder).");
            }
        } catch (\Throwable $e) {
            $db->transRollback();
            CLI::error('Erreur : ' . $e->getMessage());
        }
    }

    private function afficherQuorum(array $q): void
    {
        CLI::write("Voix présentes ou représentées : {$q['voix_presentes_ou_representees']} / {$q['voix_totales']}  (quorum requis {$q['quorum_requis']}, {$q['source']})");
        CLI::write($q['quorum_atteint'] ? 'Quorum ATTEINT' : 'Quorum NON atteint', $q['quorum_atteint'] ? 'green' : 'red');
        if (isset($q['deuxieme_convocation'])) {
            CLI::write($q['deuxieme_convocation']['message'] . ($q['deuxieme_convocation']['quorum_requis'] ? " (quorum requis : {$q['deuxieme_convocation']['quorum_requis']})" : ''), 'yellow');
        }
        CLI::newLine();
    }
}
