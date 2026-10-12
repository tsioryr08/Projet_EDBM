<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<h1>Suivi du dossier — <?= esc($societe['denomination']) ?></h1>
<p class="notice">Les dates de réunion, délais, quorums et majorités ne sont pas recalculés par l’interface : ils sont affichés depuis les données métier enregistrées.</p>
<?php if ($dossier): ?>
    <p><strong>Statut :</strong> <?= esc($dossier['statut']) ?></p>
    <p>Soumission : <?= esc($dossier['date_soumission'] ?? 'Non renseignée') ?> —
        Validation : <?= esc($dossier['date_validation'] ?? 'Non renseignée') ?> —
        Immatriculation : <?= esc($dossier['date_immatriculation'] ?? 'Non renseignée') ?></p>

    <div class="card">
        <h2>Réunions et assemblées</h2>
        <p>Accédez au workflow de la phase B pour planifier les réunions, tenir l’assemblée, enregistrer les votes et consulter le registre.</p>
        <div class="actions">
            <a class="button" href="<?= site_url('dossiers/' . $societe['id'] . '/phase-b') ?>">Ouvrir la phase B</a>
        </div>
    </div>

    <div class="card">
        <h2>A6 — Mettre à jour le suivi</h2>
        <form method="post" action="<?= site_url('dossiers/' . $societe['id'] . '/statut') ?>">
            <?= csrf_field() ?>
            <div class="grid">
                <label class="field">Statut
                    <select name="statut">
                        <?php foreach (['brouillon', 'soumis', 'valide', 'immatricule'] as $statut): ?>
                            <option value="<?= $statut ?>" <?= $dossier['statut'] === $statut ? 'selected' : '' ?>><?= $statut ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field">Date de soumission
                    <input type="date" name="date_soumission" value="<?= esc($dossier['date_soumission'] ?? '') ?>">
                </label>
                <label class="field">Date prévue de dépôt
                    <input type="date" name="date_depot_prevue" value="<?= esc($dossier['date_depot_prevue'] ?? '') ?>">
                </label>
                <label class="field">Date de validation
                    <input type="date" name="date_validation" value="<?= esc($dossier['date_validation'] ?? '') ?>">
                </label>
                <label class="field">Date d’immatriculation
                    <input type="date" name="date_immatriculation" value="<?= esc($dossier['date_immatriculation'] ?? '') ?>">
                </label>
            </div>
            <button type="submit">Enregistrer le suivi</button>
        </form>
    </div>
<?php else: ?>
    <p class="notice">Aucun dossier n’est encore créé.</p>
<?php endif; ?>
<div class="card">
    <h2>Documents de la société</h2>
    <div class="actions">
        <a class="button" href="<?= site_url('pdf/statuts/' . $societe['id']) ?>">A4 — Statuts PDF</a>
        <?php foreach (['pv_ag_constitutive' => 'PV constitutif', 'bulletin_souscription' => 'Bulletin de souscription', 'declaration_souscription' => 'Déclaration de souscription', 'pv_nomination_gerants' => 'PV nomination gérants', 'declaration_dirigeants' => 'Déclaration dirigeants', 'decision_associe_unique' => 'Décision associé unique'] as $code => $label): ?>
            <a class="button secondary" href="<?= site_url('pdf/constitutifs/' . $societe['id'] . '/' . $code) ?>"><?= esc($label) ?></a>
        <?php endforeach; ?>
    </div>
</div>
<h2>Pièces requises</h2>
<table border="1" cellpadding="5">
<tr><th>Catégorie</th><th>Document</th><th>État</th><th>Source / note</th><th>Action</th></tr>
<?php foreach ($documents as $document): ?>
<tr>
    <td><?= esc($document['categorie']) ?></td>
    <td><?= esc($document['libelle']) ?></td>
    <td><?= esc($document['etat']) ?></td>
    <td><?= esc($document['note'] ?? '') ?></td>
    <td>
        <form method="post" action="<?= site_url('dossiers/' . $societe['id'] . '/pieces/' . $document['id']) ?>">
            <?= csrf_field() ?>
            <input type="date" name="date_piece" value="<?= esc($document['piece']['date_piece'] ?? '') ?>">
            <input type="text" name="fichier" placeholder="Chemin du fichier" value="<?= esc($document['piece']['fichier'] ?? '') ?>">
            <button type="submit">Enregistrer</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</table>
<h2>Étapes EDBM</h2>
<ol><?php foreach ($etapes as $etape): ?><li><?= esc($etape['libelle']) ?> — <?= esc($etape['source'] ?? '') ?></li><?php endforeach; ?></ol>
<?= view('dossier/bail_link', ['bailUrl' => getenv('BAIL_GENERATOR_URL') ?: null]) ?>
<div class="card">
    <h2>Phase B — documents issus des réunions</h2>
    <p class="notice">La planification, le jour J et le registre restent fournis par le bloc métier de Tsiory. Utilisez ses écrans ou commandes pour créer les réunions, puis téléchargez ici les documents.</p>
    <h3>Convocations</h3>
    <?php if (empty($reunions)): ?>
        <p>Aucune réunion enregistrée.</p>
    <?php else: ?>
        <ul>
        <?php foreach ($reunions as $reunion): ?>
            <li>
                <?= esc($reunion['nature_code']) ?> — <?= esc($reunion['date_reunion']) ?>
                <a href="<?= site_url('pdf/convocations/' . $reunion['id']) ?>">Télécharger la convocation PDF</a>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <h3>Procès-verbaux</h3>
    <?php if (empty($assemblees)): ?>
        <p>Aucune assemblée enregistrée.</p>
    <?php else: ?>
        <ul>
        <?php foreach ($assemblees as $assemblee): ?>
            <li>
                <?= esc($assemblee['nature_code']) ?> — <?= esc($assemblee['date_reunion']) ?>
                <a href="<?= site_url('pdf/proces-verbaux/' . $assemblee['id']) ?>">Télécharger le PV PDF</a>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
