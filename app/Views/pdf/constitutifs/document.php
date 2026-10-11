<h1><?= esc($titre) ?></h1>
<p><strong>Société :</strong> <?= esc($societe['denomination']) ?></p>
<p><strong>Forme :</strong> <?= esc($type['libelle'] ?? 'Non renseignée') ?></p>
<table>
    <tr><th>Siège</th><td><?= esc($societe['siege'] ?? 'Non renseigné') ?></td></tr>
    <tr><th>Capital</th><td><?= esc($societe['capital'] ?? 'Non renseigné') ?> ariary</td></tr>
    <tr><th>Objet</th><td><?= esc($societe['objet'] ?? 'Non renseigné') ?></td></tr>
</table>
<?php if ($document === 'bulletin_souscription' || $document === 'etat_souscription'): ?>
<h2>Souscripteurs</h2>
<table><tr><th>Nom</th><th>Apport</th><th>Nombre de titres</th></tr>
<?php foreach ($associes as $associe): ?><tr><td><?= esc($associe['nom']) ?></td><td><?= esc($associe['apport']) ?></td><td><?= esc($associe['nb_titres']) ?></td></tr><?php endforeach; ?>
</table>
<?php elseif ($document === 'pv_nomination_gerants' || $document === 'declaration_dirigeants'): ?>
<h2>Dirigeants déclarés</h2>
<table><tr><th>Nom</th><th>Fonction</th><th>Date de nomination</th></tr>
<?php foreach ($dirigeants as $dirigeant): ?><tr><td><?= esc($dirigeant['nom']) ?></td><td><?= esc($dirigeant['fonction']) ?></td><td><?= esc($dirigeant['date_nomination'] ?? 'Non renseignée') ?></td></tr><?php endforeach; ?>
</table>
<?php elseif ($document === 'decision_associe_unique'): ?>
<h2>Décision de l’associé unique</h2>
<p class="notice">Gabarit SARLU : la décision doit être complétée et vérifiée avec les données de l’associé unique et les règles applicables fournies par le service métier.</p>
<?php foreach ($associes as $associe): ?>
    <p><strong>Associé unique :</strong> <?= esc($associe['nom']) ?></p>
<?php endforeach; ?>
<?php else: ?>
<p class="notice">Ce document est un gabarit préparatoire. Les informations juridiques non présentes dans les données sont à compléter et à vérifier.</p>
<?php endif; ?>
<p class="notice">Source juridique et statut : non fournis par le contrat de données pour ce document — à confirmer.</p>
