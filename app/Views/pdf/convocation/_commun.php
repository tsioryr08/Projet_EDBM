<h1><?= htmlspecialchars($titre, ENT_QUOTES, 'UTF-8') ?></h1>

<p>
    <strong>Société :</strong>
    <?= htmlspecialchars((string) $societe, ENT_QUOTES, 'UTF-8') ?>
    <br>
    <strong>Type :</strong>
    <?= htmlspecialchars((string) $type, ENT_QUOTES, 'UTF-8') ?>
</p>

<table>
    <tr>
        <th>Réunion</th>
        <td><?= htmlspecialchars((string) $nature, ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr>
        <th>Convocation</th>
        <td><?= (int) $numero_convocation ?>e convocation</td>
    </tr>
    <tr>
        <th>Date et heure</th>
        <td>
            <?= htmlspecialchars((string) $date_reunion, ENT_QUOTES, 'UTF-8') ?>
            à <?= htmlspecialchars((string) $heure, ENT_QUOTES, 'UTF-8') ?>
        </td>
    </tr>
    <tr>
        <th>Lieu</th>
        <td><?= htmlspecialchars((string) $lieu, ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr>
        <th>Date d’envoi au plus tard</th>
        <td><?= htmlspecialchars((string) ($date_convocation ?? 'Non renseignée'), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
</table>

<h2>Personnes convoquées</h2>
<?php if (empty($convoques)): ?>
    <p class="notice">Aucune personne convoquée n’est enregistrée pour cette réunion.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Qualité</th>
                <th>Mode d’envoi</th>
                <th>Date d’envoi</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($convoques as $convoque): ?>
            <tr>
                <td><?= htmlspecialchars((string) $convoque['nom_affiche'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) $convoque['role'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) ($convoque['mode_envoi'] ?? 'Selon la règle fournie'), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) ($convoque['date_envoi'] ?? 'Non renseignée'), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2>Ordre du jour</h2>
<?php if (empty($ordre_du_jour)): ?>
    <p class="notice">Aucun point d’ordre du jour n’est renseigné.</p>
<?php else: ?>
    <ol>
    <?php foreach ($ordre_du_jour as $point): ?>
        <li><?= htmlspecialchars((string) $point, ENT_QUOTES, 'UTF-8') ?></li>
    <?php endforeach; ?>
    </ol>
<?php endif; ?>

<h2>Délai et source</h2>
<table>
    <tr>
        <th>Délai appliqué</th>
        <td><?= htmlspecialchars((string) ($delai_jours ?? 'Non renseigné'), ENT_QUOTES, 'UTF-8') ?> jour(s)</td>
    </tr>
    <tr>
        <th>Mode de calcul</th>
        <td><?= htmlspecialchars((string) ($mode_calcul ?? 'Non renseigné'), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr>
        <th>Forme d’envoi</th>
        <td><?= htmlspecialchars((string) ($forme_envoi ?? 'Non renseignée'), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr>
        <th>Source</th>
        <td><?= htmlspecialchars((string) ($source ?? 'Non renseignée'), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr>
        <th>Statut de vérification</th>
        <td>
            <?= $verifie === null ? 'Non fourni' : ($verifie ? 'vérifié' : 'à confirmer') ?>
        </td>
    </tr>
</table>

<?php if (! empty($justification)): ?>
    <div class="notice">
        <strong>Justification :</strong>
        <?= htmlspecialchars((string) ($justification['motif'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>
