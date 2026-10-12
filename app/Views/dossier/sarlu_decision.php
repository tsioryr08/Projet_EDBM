<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<h1>Décision de l’associé unique — <?= esc($societe['denomination']) ?></h1>
<p class="notice">SARLU : aucune convocation ni aucun quorum n’est calculé dans ce parcours. Le statut juridique affiché reste « <?= esc($statut_regle) ?> ».</p>
<div class="card">
    <p><strong>Associé(s) enregistré(s) :</strong></p>
    <ul><?php foreach ($associes as $associe): ?><li><?= esc($associe['nom']) ?></li><?php endforeach; ?></ul>
    <a class="button" href="<?= site_url('pdf/constitutifs/' . $societe['id'] . '/decision_associe_unique') ?>">Télécharger la décision PDF</a>
</div>
<?= $this->endSection() ?>
