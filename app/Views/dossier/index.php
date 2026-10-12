<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<div class="page-heading"><div><p class="help">Dossier de société</p><h1><?= esc($societe['denomination']) ?></h1><p class="help">Suivi de la constitution et accès aux documents juridiques.</p></div><span class="status-badge <?= ($dossier['statut'] ?? '') === 'immatricule' ? 'success' : 'warning' ?>"><?= esc($dossier['statut'] ?? 'brouillon') ?></span></div>
<div class="alert alert-info" role="note">Les dates, délais, quorums et majorités sont affichés depuis les données métier ; l’interface ne les recalcule pas.</div>
<?php if (session()->getFlashdata('success')): ?>
    <div class="notice"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php $messages = session()->getFlashdata('errors') ?? []; ?>
<?php if (! empty($messages['bloquants'])): ?>
    <div class="error"><strong>Erreurs bloquantes</strong><ul><?php foreach ($messages['bloquants'] as $message): ?><li><?= esc($message) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<?php if ($dossier): ?>
    <div class="stepper"><div class="step <?= $dossier['statut'] === 'brouillon' ? 'active' : 'done' ?>">Brouillon</div><div class="step <?= $dossier['statut'] === 'soumis' ? 'active' : ($dossier['statut'] === 'brouillon' ? '' : 'done') ?>">Soumis</div><div class="step <?= $dossier['statut'] === 'valide' ? 'active' : (in_array($dossier['statut'], ['immatricule'], true) ? 'done' : '') ?>">Validé</div><div class="step <?= $dossier['statut'] === 'immatricule' ? 'active' : '' ?>">Immatriculé</div></div>
    <p>Soumission : <?= esc($dossier['date_soumission'] ?? 'Non renseignée') ?> —
        Validation : <?= esc($dossier['date_validation'] ?? 'Non renseignée') ?> —
        Immatriculation : <?= esc($dossier['date_immatriculation'] ?? 'Non renseignée') ?></p>

    <div class="card" id="documents">
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
                        <?php foreach (array_unique(array_merge([$suivi['statut']], $suivi['transitions_possibles'] ?? [])) as $statut): ?>
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
    <div class="card">
        <h2>Historique des statuts</h2>
        <?php if (empty($suivi['historique'])): ?>
            <p>Aucun changement enregistré.</p>
        <?php else: ?>
            <table>
                <tr><th>Date</th><th>Ancien statut</th><th>Nouveau statut</th><th>Auteur</th></tr>
                <?php foreach ($suivi['historique'] as $changement): ?>
                    <tr>
                        <td><?= esc($changement['date_changement']) ?></td>
                        <td><?= esc($changement['ancien_statut'] ?? '—') ?></td>
                        <td><?= esc($changement['nouveau_statut']) ?></td>
                        <td><?= esc($changement['auteur'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
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
<div class="page-heading"><h2>Pièces requises</h2><span class="help"><?= count($documents) ?> document(s) référencé(s)</span></div>
<div class="table-wrap"><table>
<tr><th>Catégorie</th><th>Document</th><th>État</th><th>Source / note</th><th>Action</th></tr>
<?php foreach ($documents as $document): ?>
<tr>
    <td><?= esc($document['categorie']) ?></td>
    <td><?= esc($document['libelle']) ?></td>
    <td><span class="status-badge <?= in_array($document['etat'], ['fournie', 'conforme'], true) ? 'success' : (in_array($document['etat'], ['périmée', 'expiree'], true) ? 'error' : 'warning') ?>"><?= esc($document['etat']) ?></span></td>
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
</table></div>
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
