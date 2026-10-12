<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<div class="page-heading"><div><p class="help">Vie juridique</p><h1>Réunions et assemblées</h1><p class="help">Planifiez, tenez et documentez les décisions de la société.</p></div></div>
<div class="tabs" role="tablist"><a class="active" href="<?= site_url('dossiers/' . $societeId . '/phase-b') ?>">Vue d’ensemble</a><a href="<?= site_url('dossiers/' . $societeId . '/phase-b/planifier') ?>">Planifier</a><a href="<?= site_url('dossiers/' . $societeId . '/phase-b/jour-j') ?>">Jour J</a><a href="<?= site_url('dossiers/' . $societeId . '/phase-b/votes') ?>">Votes</a><a href="<?= site_url('dossiers/' . $societeId . '/phase-b/registre') ?>">Registre</a></div>
<div class="alert alert-info" role="note">Les calculs de délai, quorum et majorité restent réalisés par les services métier.</div>
<p><a class="button secondary" href="<?= site_url('dossiers/' . $societeId . '/sarlu/decision') ?>">Décision SARLU</a></p>

<div class="grid">
    <section class="card">
        <h2>1. Planifier une réunion</h2>
        <p>Nature AGO, AGE, CA, date, lieu, ordre du jour, date limite de convocation.</p>
        <div class="actions">
            <a class="button" href="<?= site_url('dossiers/' . $societeId . '/phase-b/planifier') ?>">Ouvrir</a>
        </div>
    </section>

    <section class="card">
        <h2>2. Jour J</h2>
        <p>Présents, absents, représentés, président de séance, secrétaire, quorum et 2e convocation.</p>
        <div class="actions">
            <a class="button" href="<?= site_url('dossiers/' . $societeId . '/phase-b/jour-j') ?>">Ouvrir</a>
        </div>
    </section>

    <section class="card">
        <h2>3. Résolutions</h2>
        <p>Voix pour, contre, abstention, blanc, vote du président et décision finale.</p>
        <div class="actions">
            <a class="button" href="<?= site_url('dossiers/' . $societeId . '/phase-b/votes') ?>">Ouvrir</a>
        </div>
    </section>

    <section class="card">
        <h2>4. Registre des assemblées</h2>
        <p>Historique des réunions tenues, quorum, statut, et détails des PV.</p>
        <div class="actions">
            <a class="button" href="<?= site_url('dossiers/' . $societeId . '/phase-b/registre') ?>">Ouvrir</a>
        </div>
    </section>
</div>

<div class="card">
    <h2>Réunions déjà enregistrées</h2>
    <?php if (empty($reunions)): ?>
        <p>Aucune réunion n’a encore été planifiée.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Nature</th>
                    <th>Date</th>
                    <th>Lieu</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reunions as $reunion): ?>
                    <tr>
                        <td><?= esc($reunion['nature_code']) ?></td>
                        <td><?= esc($reunion['date_reunion']) ?></td>
                        <td><?= esc($reunion['lieu']) ?></td>
                        <td><?= esc($reunion['statut']) ?></td>
                        <td>
                            <a class="button secondary" href="<?= site_url('dossiers/' . $societeId . '/phase-b/jour-j?reunion_id=' . $reunion['id']) ?>">Jour J</a>
                            <a class="button secondary" href="<?= site_url('pdf/convocations/' . $reunion['id']) ?>">Convocation PDF</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Assemblées déjà tenues</h2>
    <?php if (empty($assemblees)): ?>
        <p>Aucune assemblée n’a encore été tenue.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Nature</th>
                    <th>Date</th>
                    <th>Quorum</th>
                    <th>Président</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assemblees as $assemblee): ?>
                    <tr>
                        <td><?= esc($assemblee['nature_code']) ?></td>
                        <td><?= esc($assemblee['date_reunion']) ?></td>
                        <td><?= ((int) ($assemblee['quorum_atteint'] ?? 0) === 1) ? 'Atteint' : 'Manquant' ?></td>
                        <td><?= esc($assemblee['president_seance'] ?? '—') ?></td>
                        <td>
                            <a class="button secondary" href="<?= site_url('dossiers/' . $societeId . '/phase-b/votes/' . $assemblee['id']) ?>">Résolutions</a>
                            <a class="button secondary" href="<?= site_url('pdf/proces-verbaux/' . $assemblee['id']) ?>">PV PDF</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
