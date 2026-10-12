<?php
$onglets = [
    'index'     => ['Vue d’ensemble', 'dossiers/' . $societeId],
    'suivi'     => ['Suivi', 'dossiers/' . $societeId . '/suivi'],
    'pieces'    => ['Pièces requises', 'dossiers/' . $societeId . '/pieces'],
    'documents' => ['Documents', 'dossiers/' . $societeId . '/documents'],
    'phase-b'   => ['Vie de Société', 'dossiers/' . $societeId . '/phase-b'],
];
?>
<nav class="actions" aria-label="Navigation du dossier">
    <?php foreach ($onglets as $cle => [$libelle, $chemin]): ?>
        <a class="button <?= ($actif ?? '') === $cle ? '' : 'secondary' ?>" href="<?= site_url($chemin) ?>"><?= esc($libelle) ?></a>
    <?php endforeach; ?>
</nav>