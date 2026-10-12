<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<div class="page-heading">
    <div>
        <p class="help">Dossier de société</p>
        <h1><?= esc($societe['denomination']) ?></h1>
        <p class="help">Suivi de la constitution et accès aux documents juridiques.</p>
    </div>
    <span class="status-badge <?= ($dossier['statut'] ?? '') === 'immatricule' ? 'success' : 'warning' ?>"><?= esc($dossier['statut'] ?? 'brouillon') ?></span>
</div>

<?= view('dossier/_nav', ['societeId' => $societe['id'], 'actif' => 'index']) ?>

<div class="alert alert-info" role="note">Les dates, délais, quorums et majorités sont affichés depuis les données métier ; l’interface ne les recalcule pas.</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="notice"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php $messages = session()->getFlashdata('errors') ?? []; ?>
<?php if (! empty($messages['bloquants'])): ?>
    <div class="error"><strong>Erreurs bloquantes</strong><ul><?php foreach ($messages['bloquants'] as $message): ?><li><?= esc($message) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if ($dossier): ?>
    <?php
    $statut = $dossier['statut'];
    $nbPieces = count($documents);
    $nbFournies = count(array_filter($documents, static fn ($d) => in_array($d['etat'], ['fournie', 'conforme'], true)));
    ?>
    <div class="stepper">
        <div class="step <?= $statut === 'brouillon' ? 'active' : 'done' ?>">Brouillon</div>
        <div class="step <?= $statut === 'soumis' ? 'active' : ($statut === 'brouillon' ? '' : 'done') ?>">Soumis</div>
        <div class="step <?= $statut === 'valide' ? 'active' : ($statut === 'immatricule' ? 'done' : '') ?>">Validé</div>
        <div class="step <?= $statut === 'immatricule' ? 'active' : '' ?>">Immatriculé</div>
    </div>
    <p>Soumission : <?= esc($dossier['date_soumission'] ?? 'Non renseignée') ?> —
        Validation : <?= esc($dossier['date_validation'] ?? 'Non renseignée') ?> —
        Immatriculation : <?= esc($dossier['date_immatriculation'] ?? 'Non renseignée') ?></p>

    <div class="grid">
        <div class="card">
            <h2>Suivi du dossier</h2>
            <p>Statut actuel : <strong><?= esc($statut) ?></strong>. Dates et historique des changements.</p>
            <div class="actions"><a class="button" href="<?= site_url('dossiers/' . $societe['id'] . '/suivi') ?>">Gérer le suivi</a></div>
        </div>
        <div class="card">
            <h2>Pièces requises</h2>
            <p><?= $nbFournies ?> / <?= $nbPieces ?> pièce(s) fournie(s).</p>
            <div class="actions"><a class="button" href="<?= site_url('dossiers/' . $societe['id'] . '/pieces') ?>">Gérer les pièces</a></div>
        </div>
        <div class="card">
            <h2>Documents</h2>
            <p>Convocations et procès-verbaux des réunions en PDF.</p>
            <div class="actions"><a class="button" href="<?= site_url('dossiers/' . $societe['id'] . '/documents') ?>">Voir les documents</a></div>
        </div>
        <div class="card" id="documents">
            <h2>Réunions et assemblées</h2>
            <p>Planifier les réunions, tenir l’assemblée, enregistrer les votes et consulter le registre.</p>
            <div class="actions"><a class="button" href="<?= site_url('dossiers/' . $societe['id'] . '/phase-b') ?>">Ouvrir la Vie de Société</a></div>
        </div>
    </div>
<?php else: ?>
    <p class="notice">Aucun dossier n’est encore créé.</p>
<?php endif; ?>

<h2>Étapes EDBM</h2>
<ol><?php foreach ($etapes as $etape): ?><li><?= esc($etape['libelle']) ?> — <?= esc($etape['source'] ?? '') ?></li><?php endforeach; ?></ol>
<?= $this->endSection() ?>