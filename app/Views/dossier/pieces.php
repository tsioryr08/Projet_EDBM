<?php
$libellesCategories = [
    'etablissement' => 'Établissement',
    'associe'       => 'Associés',
    'dirigeant'     => 'Dirigeants',
    'siege'         => 'Siège social',
    'activite'      => 'Activité réglementée',
    'mandataire'    => 'Mandataire',
];
$groupes = [];
foreach ($documents as $document) {
    $groupes[$document['categorie']][] = $document;
}
?>
<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<div class="page-heading">
    <div>
        <p class="help">Dossier de société</p>
        <h1>Pièces requises — <?= esc($societe['denomination']) ?></h1>
    </div>
    <span class="help"><?= count($documents) ?> document(s) référencé(s)</span>
</div>

<?= view('dossier/_nav', ['societeId' => $societe['id'], 'actif' => 'pieces']) ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="notice"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>

<form method="post" action="<?= site_url('dossiers/' . $societe['id'] . '/pieces') ?>">
    <?= csrf_field() ?>
    <?php foreach ($groupes as $categorie => $pieces): ?>
        <div class="card">
            <h2><?= esc($libellesCategories[$categorie] ?? ucfirst($categorie)) ?></h2>
            <div class="table-wrap"><table>
                <tr><th>Document</th><th>État</th><th>Note</th><th>Date</th><th>Chemin du fichier</th></tr>
                <?php foreach ($pieces as $document): ?>
                    <tr>
                        <td><?= esc($document['libelle']) ?></td>
                        <td><span class="status-badge <?= in_array($document['etat'], ['fournie', 'conforme'], true) ? 'success' : (in_array($document['etat'], ['périmée', 'expiree'], true) ? 'error' : 'warning') ?>"><?= esc($document['etat']) ?></span></td>
                        <td><?= esc($document['note'] ?? '') ?></td>
                        <td><input type="date" name="pieces[<?= (int) $document['id'] ?>][date_piece]" value="<?= esc($document['piece']['date_piece'] ?? '') ?>"></td>
                        <td><input type="text" name="pieces[<?= (int) $document['id'] ?>][fichier]" placeholder="Chemin du fichier" value="<?= esc($document['piece']['fichier'] ?? '') ?>"></td>
                    </tr>
                <?php endforeach; ?>
            </table></div>
        </div>
    <?php endforeach; ?>
    <div class="actions">
        <button type="submit">Enregistrer les pièces</button>
        <a class="button secondary" href="<?= site_url('dossiers/' . $societe['id']) ?>">Retour au dossier</a>
    </div>
</form>
<?= $this->endSection() ?>