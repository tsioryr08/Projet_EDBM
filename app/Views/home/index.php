<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<div class="page-heading"><div><p class="help">Portail juridique</p><h1>Création et vie de la société</h1><p class="help">Un parcours guidé pour constituer, documenter et suivre votre société.</p></div></div>
<div class="alert alert-info" role="note"><strong>Avant de commencer :</strong> vérifiez la disponibilité de la dénomination auprès de l’EDBM. L’application ne réserve pas le nom.</div>
<div class="actions">
    <a class="button success" href="<?= site_url('societes/type') ?>">Créer une société</a>
    <a class="button secondary" href="<?= site_url('pdf/demo/convocation') ?>">Aperçu PDF exemple</a>
</div>
<div class="grid">
    <section class="card"><h2>Créer une société</h2><p>Choisissez la forme, renseignez les associés et dirigeants, puis constituez votre dossier.</p><a class="button" href="<?= site_url('societes/type') ?>">Démarrer</a></section>
    <section class="card"><h2>Gérer la vie juridique</h2><p>Planifiez vos assemblées, suivez les votes et générez vos procès-verbaux.</p><p class="help">Disponible depuis le dossier d’une société.</p></section>
</div>
<div class="page-heading"><h2>Mes sociétés</h2><a class="button link" href="<?= site_url('societes/type') ?>">Nouvelle société</a></div>
<?php if (empty($societes)): ?>
    <div class="empty-state"><p>Aucune société enregistrée.</p><a class="button" href="<?= site_url('societes/type') ?>">Commencer le questionnaire</a></div>
<?php else: ?>
    <div class="grid">
    <?php foreach ($societes as $societe): ?>
        <section class="card">
            <h3><?= esc($societe['denomination']) ?></h3>
            <p class="help">Identifiant #<?= (int) $societe['id'] ?></p>
            <span class="status-badge muted">Dossier à consulter</span>
            <div class="actions">
                <a class="button" href="<?= site_url('dossiers/' . $societe['id']) ?>">Ouvrir le dossier</a>
                <a class="button secondary" href="<?= site_url('pdf/statuts/' . $societe['id']) ?>">Télécharger les statuts</a>
            </div>
        </section>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
