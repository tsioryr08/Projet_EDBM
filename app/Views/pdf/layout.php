<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        @page {
            margin: 22mm 18mm 22mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            color: #1f2933;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10pt;
            line-height: 1.45;
        }

        header {
            border-bottom: 1px solid #64748b;
            color: #16324f;
            margin-bottom: 18px;
            padding-bottom: 8px;
        }

        header .brand {
            font-size: 13pt;
            font-weight: bold;
        }

        header .subtitle {
            color: #64748b;
            font-size: 8pt;
            margin-top: 2px;
        }

        footer {
            bottom: -12mm;
            color: #64748b;
            font-size: 8pt;
            left: 0;
            position: fixed;
            right: 0;
            text-align: center;
        }

        .page-number:after {
            content: counter(page);
        }

        .page-count:after {
            content: counter(pages);
        }

        h1, h2, h3 {
            color: #16324f;
        }

        h1 {
            font-size: 17pt;
            margin: 0 0 16px;
            text-align: center;
        }

        h2 {
            border-bottom: 1px solid #cbd5e1;
            font-size: 12pt;
            margin: 18px 0 8px;
            padding-bottom: 3px;
        }

        table {
            border-collapse: collapse;
            margin: 8px 0;
            width: 100%;
        }

        th, td {
            border: 1px solid #cbd5e1;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f1f5f9;
        }

        .notice {
            background: #fffbeb;
            border-left: 3px solid #d97706;
            padding: 8px;
        }
    </style>
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
