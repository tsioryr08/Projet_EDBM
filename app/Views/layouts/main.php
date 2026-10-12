<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($this->renderSection('title') ?: ($title ?? 'Legaltech EDBM')) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<?php $current = uri_string(); ?>
<header>
    <nav class="site-nav" aria-label="Navigation principale">
        <a class="brand" href="<?= site_url('/') ?>">Legaltech EDBM</a>
        <a href="<?= site_url('/') ?>" <?= $current === '' ? 'aria-current="page"' : '' ?>>Accueil</a>
        <a href="<?= site_url('societes/type') ?>" <?= str_starts_with($current, 'societes') ? 'aria-current="page"' : '' ?>>Créer une société</a>
        <?php if (! empty($societeId)): ?>
            <a href="<?= site_url('dossiers/' . $societeId) ?>" <?= str_starts_with($current, 'dossiers/' . $societeId) && ! str_contains($current, 'phase-b') ? 'aria-current="page"' : '' ?>>Dossier</a>
            <a href="<?= site_url('dossiers/' . $societeId . '/phase-b') ?>" <?= str_contains($current, 'phase-b') ? 'aria-current="page"' : '' ?>>Assemblées</a>
            <a href="<?= site_url('dossiers/' . $societeId) . '#documents' ?>">Documents PDF</a>
        <?php endif; ?>
    </nav>
</header>
<main>
    <?php if (! empty($breadcrumb)): ?><nav class="breadcrumb" aria-label="Fil d’Ariane"><?= $breadcrumb ?></nav><?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success" role="status"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>
    <?php $messages = session()->getFlashdata('errors') ?? []; ?>
    <?php if (! empty($messages)): ?><div class="alert alert-error" role="alert"><strong>Vérifiez les informations</strong><ul><?php foreach (($messages['bloquants'] ?? (is_array($messages) ? $messages : [])) as $message): ?><li><?= esc($message) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <?= $this->renderSection('content') ?>
</main>
<footer>Legaltech EDBM · Les règles affichées proviennent des données métier et restent signalées lorsqu’elles sont à confirmer.</footer>
<script src="<?= base_url('assets/js/app.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
