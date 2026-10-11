<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<h1>Création et vie de la société</h1>
<p class="notice">Avant de choisir une dénomination, vérifiez sa disponibilité auprès de l’EDBM. L’application ne remplace pas cette vérification officielle.</p>
<div class="actions">
    <a class="button success" href="<?= site_url('societes/type') ?>">Commencer une création</a>
    <a class="button secondary" href="<?= site_url('pdf/demo/convocation') ?>">Tester O1 — PDF exemple</a>
</div>
<h2>Mes sociétés</h2>
<?php if (empty($societes)): ?>
    <div class="card">Aucune société enregistrée. Commencez par le questionnaire.</div>
<?php else: ?>
    <div class="grid">
    <?php foreach ($societes as $societe): ?>
        <section class="card">
            <h3><?= esc($societe['denomination']) ?></h3>
            <p>Identifiant : <?= (int) $societe['id'] ?></p>
            <div class="actions">
                <a class="button" href="<?= site_url('dossiers/' . $societe['id']) ?>">Ouvrir le dossier</a>
                <a class="button secondary" href="<?= site_url('pdf/statuts/' . $societe['id']) ?>">Télécharger les statuts</a>
            </div>
        </section>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
