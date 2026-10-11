<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<h1>A1 — Choisir la forme de la société</h1>
<p class="notice">Le choix de la forme détermine les documents et les règles qui seront recherchés en base. Si une règle manque, elle sera signalée plutôt qu’inventée.</p>
<form method="get" action="<?= site_url('societes/nouveau') ?>">
    <label class="field">Forme sociale
        <select name="type_societe_id" required>
            <option value="">Sélectionner</option>
            <?php foreach ($types as $type): ?>
                <option value="<?= (int) $type['id'] ?>"><?= esc($type['code']) ?> — <?= esc($type['libelle']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit">Continuer vers le formulaire</button>
</form>
<?= $this->endSection() ?>
