<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <style><?= htmlspecialchars((string) file_get_contents(FCPATH . 'assets/css/pdf.css'), ENT_QUOTES, 'UTF-8') ?></style>
</head>
<body>
    <header>
        <div class="brand">Projet EDBM — Legaltech</div>
        <div class="subtitle">Document généré électroniquement</div>
    </header>

    <?= $content ?>

    <footer>
        Page <span class="page-number"></span> / <span class="page-count"></span>
    </footer>
</body>
</html>
