<h2>Article 4 — Associés et apports déclarés</h2>
<?php if (empty($associes)): ?>
    <p class="notice">Aucun associé n’est renseigné dans le dossier.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Catégorie</th>
                <th>Apport</th>
                <th>Titres</th>
                <th>Qualité</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($associes as $associe): ?>
            <tr>
                <td><?= htmlspecialchars((string) $associe['nom'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) $associe['categorie'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) $associe['apport'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) $associe['nb_titres'], ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <?= array_key_exists('est_commandite', $associe) && $associe['est_commandite'] !== null
                        ? ($associe['est_commandite'] ? 'commandité' : 'commanditaire')
                        : '—' ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
