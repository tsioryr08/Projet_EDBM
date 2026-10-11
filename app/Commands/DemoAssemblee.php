<?php

namespace App\Commands;

use App\Libraries\AssembleeService;
use App\Libraries\Justification;
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
    protected $description = "Démo du jour J avec justification juridique : AGO, AGE sans quorum, CA avec partage des voix.";

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
            // ---------- A. AGO : quorum atteint, 3 résolutions dont une égalité ----------
            CLI::write("=== A. AGO du 15/12/2026 ===", 'yellow');
            $ago = $reunions->planifier((int) $societe['id'], 'AGO', '2026-12-15', '09:00', 'Siège social', ['Approbation des comptes']);
            $q = $service->tenir($ago['reunion_id'], [
                ['nom' => 'RAKOTO Jean', 'statut' => 'present'],
                ['nom' => 'RABE Hanta',  'statut' => 'represente', 'par' => 'RAKOTO Jean'],
                ['nom' => 'ANDRIA Mamy', 'statut' => 'absent'],
            ], 'RAKOTO Jean', 'RABE Hanta');
            $this->afficherQuorum($q);

            $res = $service->voter($q['assemblee_id'], [
                ['texte' => 'Approbation des comptes',   'pour' => 150, 'contre' => 0],
                ['texte' => 'Distribution de dividendes', 'pour' => 50,  'contre' => 100],
                ['texte' => 'Questions diverses',         'pour' => 75,  'contre' => 75],
            ]);
            $this->afficherResolutions($res);
            CLI::write('JSON pour le générateur de PV :', 'cyan');
            CLI::write(json_encode($service->versJson($q['assemblee_id']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            CLI::newLine();

            // ---------- B. AGE : quorum manqué ----------
            CLI::write('=== B. AGE du 20/12/2026 ===', 'yellow');
            $age = $reunions->planifier((int) $societe['id'], 'AGE', '2026-12-20', '09:00', 'Siège social', ['Modification des statuts']);
            $q2 = $service->tenir($age['reunion_id'], [['nom' => 'RAKOTO Jean', 'statut' => 'present']]);
            $this->afficherQuorum($q2);

            // ---------- C. CA : partage des voix, voix prépondérante du président de séance ----------
            CLI::write('=== C. Conseil d\'administration du 10/12/2026 (délai fixé par les statuts : 3 jours) ===', 'yellow');
            $ca = $reunions->planifier((int) $societe['id'], 'CA', '2026-12-10', '10:00', 'Siège social', ['Autorisation d\'une convention'], 1, null, 3);
            $q3 = $service->tenir($ca['reunion_id'], [
                ['nom' => 'RAKOTO Jean', 'statut' => 'present'],   // président de séance (pca)
                ['nom' => 'RABE Hanta',  'statut' => 'present'],
                ['nom' => 'ANDRIA Mamy', 'statut' => 'present'],
            ], 'RAKOTO Jean');
            $this->afficherQuorum($q3);

            // 1 pour (RABE), 1 contre (ANDRIA), le président s'abstient : partage des voix, décision en attente
            $res3 = $service->voter($q3['assemblee_id'], [
                ['texte' => 'Convention X',  'pour' => 1, 'contre' => 1, 'abstention' => 1],   // président : pas encore saisi
                ['texte' => 'Convention Y',  'pour' => 1, 'contre' => 1, 'abstention' => 1],
            ]);
            CLI::write('Avant la saisie du vote du président :', 'cyan');
            $this->afficherResolutions($res3);

            CLI::write('Le président de séance vote « pour » sur la convention X, et s\'abstient sur la Y :', 'cyan');
            $this->afficherResolutions([
                $service->departager($q3['assemblee_id'], 1, 'pour') + ['numero' => 1, 'pour' => 1, 'contre' => 1],
                $service->departager($q3['assemblee_id'], 2, 'abstention') + ['numero' => 2, 'pour' => 1, 'contre' => 1],
            ]);

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
        CLI::write("Voix présentes ou représentées : {$q['voix_presentes_ou_representees']} / {$q['voix_totales']}");
        CLI::write($q['quorum_atteint'] ? 'Quorum ATTEINT' : 'Quorum NON atteint', $q['quorum_atteint'] ? 'green' : 'red');
        $this->afficherJustification($q['justification']);
        if (! $q['quorum_atteint'] && isset($q['pv_annulation'])) {
            CLI::write('PV de constat : ' . $q['pv_annulation']['message'], 'yellow');
        }
        if (isset($q['deuxieme_convocation'])) {
            CLI::write($q['deuxieme_convocation']['message'] . ($q['deuxieme_convocation']['quorum_requis'] ? " (quorum requis : {$q['deuxieme_convocation']['quorum_requis']})" : ''), 'yellow');
        }
        CLI::newLine();
    }

    private function afficherResolutions(array $resolutions): void
    {
        foreach ($resolutions as $r) {
            if ($r['adoptee'] === null) {
                $etat = 'EN ATTENTE (vote du président de séance)';
                $couleur = 'yellow';
            } else {
                $etat = $r['adoptee'] ? 'ADOPTÉE' : 'REJETÉE';
                $couleur = $r['adoptee'] ? 'green' : 'red';
            }
            CLI::write("Résolution {$r['numero']} : {$r['texte']} (pour {$r['pour']} / contre {$r['contre']}) -> {$etat}", $couleur);
            $this->afficherJustification($r['justification']);
        }
        CLI::newLine();
    }

    private function afficherJustification(array $j): void
    {
        CLI::write('  Pourquoi : ' . $j['motif']);
        CLI::write('  Source : ' . ($j['source'] ?? 'non renseignée') . ' | ' . Justification::STATUTS[$j['statut']]);
        if ($j['precision']) {
            CLI::write('  Précision : ' . $j['precision']);
        }
        if ($j['avertissement']) {
            CLI::write('  Attention : ' . $j['avertissement'], 'yellow');
        }
        if ($j['texte_officiel']) {
            CLI::write('  Texte : ' . $j['texte_officiel']);
        }
    }
}
