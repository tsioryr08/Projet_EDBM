<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<div class="page-heading"><div><p class="help">Étape 1 sur 2</p><h1>Choisir la forme sociale</h1><p class="help">Cette sélection détermine les pièces et règles recherchées en base.</p></div></div>
<div class="stepper"><div class="step active">1 · Forme sociale</div><div class="step">2 · Informations</div><div class="step">3 · Dossier</div></div>
<div class="alert alert-info" role="note">Si une règle manque, elle sera signalée plutôt qu’inventée.</div>
<form method="get" action="<?= site_url('societes/nouveau') ?>">
    <div class="grid">
    <?php foreach ($types as $type): ?>
        <div class="type-card"><input id="type-<?= (int) $type['id'] ?>" type="radio" name="type_societe_id" value="<?= (int) $type['id'] ?>" required><label for="type-<?= (int) $type['id'] ?>"><strong><?= esc($type['code']) ?></strong><br><span><?= esc($type['libelle']) ?></span><p class="help">Règles et documents associés chargés depuis la base.</p></label></div>
    <?php endforeach; ?>
    </div>
    <div class="actions"><button type="submit">Continuer</button></div>
</form>
<?= $this->endSection() ?>
