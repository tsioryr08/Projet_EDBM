<h2>Article 1 — Forme, dénomination et siège</h2>
<table>
    <tr>
        <th>Forme</th>
        <td><?= htmlspecialchars((string) ($forme ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr>
        <th>Dénomination</th>
        <td><?= htmlspecialchars((string) ($societe['denomination'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr>
        <th>Siège social</th>
        <td><?= htmlspecialchars((string) ($societe['siege'] ?? 'Non renseigné'), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
</table>

<h2>Article 2 — Objet social</h2>
<p><?= nl2br(htmlspecialchars((string) ($societe['objet'] ?? 'Non renseigné'), ENT_QUOTES, 'UTF-8')) ?></p>

<h2>Article 3 — Capital, durée et activité</h2>
<table>
    <tr>
        <th>Capital</th>
        <td>
            <?= $societe['capital'] !== null && $societe['capital'] !== ''
                ? htmlspecialchars((string) $societe['capital'], ENT_QUOTES, 'UTF-8') . ' ariary'
                : 'Non renseigné' ?>
        </td>
    </tr>
    <tr>
        <th>Durée</th>
        <td>
            <?= $societe['duree_annees'] !== null && $societe['duree_annees'] !== ''
                ? htmlspecialchars((string) $societe['duree_annees'], ENT_QUOTES, 'UTF-8') . ' ans'
                : 'Non renseignée' ?>
        </td>
    </tr>
    <tr>
        <th>Code d’activité</th>
        <td><?= htmlspecialchars((string) ($societe['code_activite'] ?? 'Non renseigné'), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
</table>
