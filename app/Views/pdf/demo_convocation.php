<h1><?= htmlspecialchars($titre ?? 'Convocation', ENT_QUOTES, 'UTF-8') ?></h1>

<p>
    <strong>Société :</strong>
    <?= htmlspecialchars((string) ($societe ?? ''), ENT_QUOTES, 'UTF-8') ?>
</p>

<table>
    <tr>
        <th>Réunion</th>
        <td><?= htmlspecialchars((string) ($nature ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr>
        <th>Convocation</th>
        <td><?= (int) ($numero_convocation ?? 1) ?>e convocation</td>
    </tr>
    <tr>
        <th>Date et heure</th>
        <td>
            <?= htmlspecialchars((string) ($date_reunion ?? ''), ENT_QUOTES, 'UTF-8') ?>
            à <?= htmlspecialchars((string) ($heure ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </td>
    </tr>
    <tr>
        <th>Lieu</th>
        <td><?= htmlspecialchars((string) ($lieu ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr>
        <th>Date limite d'envoi</th>
        <td><?= htmlspecialchars((string) ($date_convocation ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
</table>

<h2>Ordre du jour</h2>
<ol>
    <?php foreach (($ordre_du_jour ?? []) as $point): ?>
        <li><?= htmlspecialchars((string) $point, ENT_QUOTES, 'UTF-8') ?></li>
    <?php endforeach; ?>
</ol>

<h2>Informations juridiques fournies par le service</h2>
<p>
    Délai : <?= htmlspecialchars((string) ($delai_jours ?? ''), ENT_QUOTES, 'UTF-8') ?> jour(s),
    calcul <?= htmlspecialchars((string) ($mode_calcul ?? ''), ENT_QUOTES, 'UTF-8') ?>.
</p>
<p>
    Forme d'envoi :
    <?= htmlspecialchars((string) ($forme_envoi ?? ''), ENT_QUOTES, 'UTF-8') ?>
</p>
<p>
    Source :
    <?= htmlspecialchars((string) ($source ?? 'non renseignée'), ENT_QUOTES, 'UTF-8') ?> —
    statut :
    <?= ! empty($verifie) ? 'vérifié' : 'à confirmer' ?>
</p>

<?php if (! empty($justification)): ?>
    <div class="notice">
        <strong>Justification :</strong>
        <?= htmlspecialchars((string) ($justification['motif'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        <?php if (! empty($justification['avertissement'])): ?>
            <br>
            <strong>Attention :</strong>
            <?= htmlspecialchars((string) $justification['avertissement'], ENT_QUOTES, 'UTF-8') ?>
        <?php endif; ?>
    </div>
<?php endif; ?>
