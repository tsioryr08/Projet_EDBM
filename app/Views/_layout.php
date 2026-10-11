<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Legaltech EDBM') ?></title>
    <style>
        :root { color-scheme: light; font-family: Arial, sans-serif; }
        body { margin: 0; background: #f4f6f8; color: #17202a; }
        header { background: #17324d; color: #fff; }
        nav, main { max-width: 1180px; margin: 0 auto; }
        nav { display: flex; gap: 1rem; align-items: center; padding: .9rem 1rem; flex-wrap: wrap; }
        nav a { color: #fff; text-decoration: none; }
        nav .brand { font-weight: bold; margin-right: auto; }
        main { padding: 1.5rem 1rem 3rem; }
        .card { background: #fff; border: 1px solid #d9e1e8; border-radius: 8px; padding: 1rem; margin: 1rem 0; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem; }
        .actions { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1rem; }
        .button, button { background: #1769aa; color: #fff; border: 0; border-radius: 4px; padding: .55rem .8rem; text-decoration: none; cursor: pointer; }
        .button.secondary { background: #5f6b76; }
        .button.success { background: #237a57; }
        .notice { background: #fff4ce; border-left: 4px solid #d99a00; padding: .75rem; }
        .error { background: #ffe1e1; border-left: 4px solid #b42318; padding: .75rem; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { border: 1px solid #d9e1e8; padding: .55rem; text-align: left; vertical-align: top; }
        label { display: block; margin: .7rem 0; }
        input, select, textarea { box-sizing: border-box; max-width: 100%; padding: .45rem; border: 1px solid #aeb9c4; border-radius: 4px; }
        .field { display: flex; flex-direction: column; gap: .25rem; }
        footer { text-align: center; color: #5f6b76; padding: 1rem; }
    </style>
</head>
<body>
<header>
    <nav>
        <a class="brand" href="<?= site_url('/') ?>">Legaltech EDBM</a>
        <a href="<?= site_url('/') ?>">Accueil</a>
        <a href="<?= site_url('societes/type') ?>">Créer une société</a>
        <?php if (! empty($societeId)): ?>
            <a href="<?= site_url('dossiers/' . $societeId) ?>">Dossier</a>
            <a href="<?= site_url('pdf/statuts/' . $societeId) ?>">Statuts PDF</a>
        <?php endif; ?>
    </nav>
</header>
<main><?= $this->renderSection('content') ?></main>
<footer>Les règles, dates et majorités affichées proviennent des données métier et restent à vérifier lorsque le statut l’indique.</footer>
</body>
</html>
