<h1><?= htmlspecialchars((string) $titre, ENT_QUOTES, 'UTF-8') ?></h1>
<p>
    <strong>Société :</strong> <?= htmlspecialchars((string) $societe, ENT_QUOTES, 'UTF-8') ?><br>
    <strong>Réunion :</strong> <?= htmlspecialchars((string) $nature, ENT_QUOTES, 'UTF-8') ?>,
    convocation n°<?= (int) $numero_convocation ?><br>
    <strong>Date de tenue :</strong> <?= htmlspecialchars((string) ($date_tenue ?? 'Non renseignée'), ENT_QUOTES, 'UTF-8') ?><br>
    <strong>Lieu :</strong> <?= htmlspecialchars((string) $lieu, ENT_QUOTES, 'UTF-8') ?>
</p>

<table>
    <tr><th>Président de séance</th><td><?= htmlspecialchars((string) ($president_seance ?? 'Non renseigné'), ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><th>Secrétaire</th><td><?= htmlspecialchars((string) ($secretaire ?? 'Non renseigné'), ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><th>Quorum</th><td><?= htmlspecialchars((string) ($quorum_requis ?? 'Non renseigné'), ENT_QUOTES, 'UTF-8') ?> — <?= ! empty($quorum_atteint) ? 'atteint' : 'non atteint' ?></td></tr>
</table>

<?php if (! empty($motif_quorum)): ?>
    <div class="notice"><strong>Justification du quorum :</strong> <?= htmlspecialchars($motif_quorum, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<h2>Présences</h2>
<table>
    <thead><tr><th>Nom</th><th>Statut</th><th>Représenté par</th><th>Voix</th></tr></thead>
    <tbody>
    <?php foreach (($participants ?? []) as $participant): ?>
        <tr>
            <td><?= htmlspecialchars((string) $participant['nom'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) $participant['statut'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) ($participant['par'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= (int) ($participant['voix'] ?? 0) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<h2>Ordre du jour</h2>
<ol>
<?php foreach (($ordre_du_jour ?? []) as $point): ?>
    <li><?= htmlspecialchars((string) $point, ENT_QUOTES, 'UTF-8') ?></li>
<?php endforeach; ?>
</ol>

<h2>Déroulement de la séance</h2>
<?php if (empty($interventions)): ?>
    <p>Aucune intervention n’a été consignée.</p>
<?php else: ?>
    <ol>
        <?php foreach ($interventions as $intervention): ?>
            <li><strong><?= htmlspecialchars((string) $intervention['auteur'], ENT_QUOTES, 'UTF-8') ?> :</strong>
                <?= nl2br(htmlspecialchars((string) $intervention['texte'], ENT_QUOTES, 'UTF-8')) ?></li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>

<h2>Résolutions et votes</h2>
<?php if (empty($resolutions)): ?>
    <p class="notice">Aucune résolution n’est enregistrée.</p>
<?php else: ?>
    <?php foreach ($resolutions as $resolution): ?>
        <table>
            <tr><th>Résolution</th><td><?= htmlspecialchars((string) $resolution['texte'], ENT_QUOTES, 'UTF-8') ?></td></tr>
            <tr><th>Vote</th><td>Pour : <?= (int) $resolution['pour'] ?> — Contre : <?= (int) $resolution['contre'] ?></td></tr>
            <tr><th>Décision</th><td><?= $resolution['adoptee'] === null ? 'En attente' : ($resolution['adoptee'] ? 'Adoptée' : 'Rejetée') ?></td></tr>
            <tr><th>Source / statut</th><td><?= htmlspecialchars((string) ($resolution['source'] ?? 'Non renseignée'), ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars((string) ($resolution['statut_regle'] ?? 'Non renseigné'), ENT_QUOTES, 'UTF-8') ?></td></tr>
        </table>
        <?php if (! empty($resolution['mention_pv'])): ?>
            <div class="notice"><?= htmlspecialchars($resolution['mention_pv'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if (! empty($resolution['motif'])): ?>
            <p><strong>Justification :</strong> <?= htmlspecialchars($resolution['motif'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>
