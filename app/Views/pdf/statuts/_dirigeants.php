<h2>Administration et direction</h2>
<?php if (empty($dirigeants)): ?>
    <p class="notice">Aucun dirigeant n’est renseigné dans le dossier.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Fonction</th>
                <th>Nommé par les statuts</th>
                <th>Date de nomination</th>
                <th>Fin de mandat</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($dirigeants as $dirigeant): ?>
            <tr>
                <td><?= htmlspecialchars((string) $dirigeant['nom'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) $dirigeant['fonction'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= ! empty($dirigeant['nomme_par_statuts']) ? 'Oui' : 'Non' ?></td>
                <td><?= htmlspecialchars((string) ($dirigeant['date_nomination'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) ($dirigeant['fin_mandat'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
<p><strong>Mode d’administration :</strong> <?= htmlspecialchars($gouvernance, ENT_QUOTES, 'UTF-8') ?></p>
