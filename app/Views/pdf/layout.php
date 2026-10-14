<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <style><?= htmlspecialchars((string) file_get_contents(FCPATH . 'assets/css/pdf.css'), ENT_QUOTES, 'UTF-8') ?></style>
</head>
<body>
    <?php if (empty($is_pv)): ?>
        <header>
            <div class="brand">Projet EDBM — Legaltech</div>
            <div class="subtitle">Document généré électroniquement</div>
        </header>
    <?php endif; ?>

    <?= $content ?>

    <?php if (empty($is_pv)): ?>
        <footer>
            Page <span class="page-number"></span> / <span class="page-count"></span>
        </footer>
    <?php endif; ?>
</body>
</html>
