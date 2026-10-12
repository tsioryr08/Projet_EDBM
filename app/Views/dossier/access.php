<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<div class="page-heading">
    <div>
        <p class="help">Accès sécurisé</p>
        <h1><?= esc($societe['denomination']) ?></h1>
        <p class="help">Entrez le mot de passe créé lors de l’enregistrement de cette société.</p>
    </div>
</div>
<?php if (! empty($errors)): ?>
    <div class="alert alert-error" role="alert">
        <strong>Accès refusé :</strong>
        <ul><?php foreach ($errors as $error): ?><li><?= esc($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>
<div class="card">
    <form method="post" action="<?= site_url('dossiers/' . $societe['id'] . '/acces') ?>">
        <?= csrf_field() ?>
        <label class="field">Mot de passe
            <input name="mot_de_passe" type="password" required autocomplete="current-password">
        </label>
        <div class="actions">
            <button type="submit">Ouvrir le dossier</button>
            <a class="button secondary" href="<?= site_url('/') ?>">Retour</a>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
