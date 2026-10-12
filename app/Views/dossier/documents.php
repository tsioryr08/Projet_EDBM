<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<div class="page-heading">
    <div>
        <p class="help">Dossier de société</p>
        <h1>Documents — <?= esc($societe['denomination']) ?></h1>
    </div>
</div>

<?= view('dossier/_nav', ['societeId' => $societe['id'], 'actif' => 'documents']) ?>

<div class="card">
    <h2>Convocations</h2>
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
</div>

<div class="card">
    <h2>Procès-verbaux</h2>
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

<?= view('dossier/bail_link', ['bailUrl' => getenv('BAIL_GENERATOR_URL') ?: null]) ?>
<?= $this->endSection() ?>