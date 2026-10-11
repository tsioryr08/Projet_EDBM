<h2>Traçabilité des mentions statutaires</h2>
<p class="notice">
    Les valeurs ci-dessous proviennent du dossier. Le générateur ne complète pas
    les informations manquantes et n’interprète pas les règles juridiques.
</p>
<?php if (empty($article_10)): ?>
    <p>Aucune mention disponible dans les données fournies.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Mention</th>
                <th>Valeur</th>
                <th>Source</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($article_10 as $mention): ?>
            <tr>
                <td><?= htmlspecialchars($mention['libelle'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($mention['valeur'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($mention['source'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($mention['statut'], ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
