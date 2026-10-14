<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<div class="page-heading"><div><p class="help">Vie de Société · Étape 4</p><h1>Registre des assemblées</h1><p class="help"><?= esc($societe['denomination']) ?></p></div></div>
<div class="tabs"><a href="<?= site_url('dossiers/' . $societeId . '/phase-b/planifier') ?>">Planifier</a><a href="<?= site_url('dossiers/' . $societeId . '/phase-b/jour-j') ?>">Jour J</a><a href="<?= site_url('dossiers/' . $societeId . '/phase-b/votes') ?>">Votes</a><a class="active" href="<?= site_url('dossiers/' . $societeId . '/phase-b/registre') ?>">Registre</a></div>
<p><a href="<?= site_url('dossiers/' . $societeId . '/phase-b') ?>">← Retour à la Vie de Société</a></p>

<div class="card">
    <h2>Historique des assemblées</h2>
    <?php if (empty($registre)): ?>
        <p>Aucune assemblée n’a encore été tenue.</p>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead>
                <tr>
                    <th>Nature</th>
                    <th>Convocation</th>
                    <th>Date réunion</th>
                    <th>Quorum</th>
                    <th>Président</th>
                    <th>Détails</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registre as $ligne): ?>
                    <tr class="reunion-clickable" data-href="<?= site_url('dossiers/' . $societeId . '/phase-b/reunions/' . (int) $ligne['reunion_id']) ?>" tabindex="0" role="link" aria-label="Voir les détails de la réunion <?= esc((string) ($ligne['nature'] ?? '')) ?> du <?= esc((string) ($ligne['date_reunion'] ?? '')) ?>">
                        <td><?= esc((string) ($ligne['nature'] ?? '—')) ?></td>
                        <td><?= (int) ($ligne['numero_convocation'] ?? 1) ?></td>
                        <td><?= esc((string) ($ligne['date_reunion'] ?? '—')) ?></td>
                        <td><span class="status-badge <?= ! empty($ligne['quorum_atteint']) ? 'success' : 'error' ?>"><?= ! empty($ligne['quorum_atteint']) ? 'Atteint' : 'Non atteint' ?></span></td>
                        <td><?= esc((string) ($ligne['president'] ?? '—')) ?></td>
                        <td>
                            <?php if (! empty($ligne['assemblee_id'])): ?>
                                <a class="button secondary" href="<?= site_url('pdf/proces-verbaux/' . $ligne['assemblee_id']) ?>" data-row-action>PV PDF</a>
                            <?php else: ?>
                                Détails
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>
<script>
document.querySelectorAll('.reunion-clickable').forEach((row) => {
    const ouvrir = () => window.location.href = row.dataset.href;
    row.addEventListener('click', (event) => {
        if (!event.target.closest('[data-row-action]')) ouvrir();
    });
    row.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            ouvrir();
        }
    });
});
</script>
<?= $this->endSection() ?>
