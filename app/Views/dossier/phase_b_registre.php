<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<div class="page-heading"><div><p class="help">Phase B · Étape 4</p><h1>Registre des assemblées</h1><p class="help"><?= esc($societe['denomination']) ?></p></div></div>
<div class="tabs"><a href="<?= site_url('dossiers/' . $societeId . '/phase-b/planifier') ?>">Planifier</a><a href="<?= site_url('dossiers/' . $societeId . '/phase-b/jour-j') ?>">Jour J</a><a href="<?= site_url('dossiers/' . $societeId . '/phase-b/votes') ?>">Votes</a><a class="active" href="<?= site_url('dossiers/' . $societeId . '/phase-b/registre') ?>">Registre</a></div>
<p><a href="<?= site_url('dossiers/' . $societeId . '/phase-b') ?>">← Retour à la phase B</a></p>

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
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registre as $ligne): ?>
                    <tr>
                        <td><?= esc((string) ($ligne['nature'] ?? '—')) ?></td>
                        <td><?= (int) ($ligne['numero_convocation'] ?? 1) ?></td>
                        <td><?= esc((string) ($ligne['date_reunion'] ?? '—')) ?></td>
                        <td><span class="status-badge <?= ! empty($ligne['quorum_atteint']) ? 'success' : 'error' ?>"><?= ! empty($ligne['quorum_atteint']) ? 'Atteint' : 'Non atteint' ?></span></td>
                        <td><?= esc((string) ($ligne['president'] ?? '—')) ?></td>
                        <td>
                            <?php if (! empty($ligne['assemblee_id'])): ?>
                                <a class="button secondary" href="<?= site_url('pdf/proces-verbaux/' . $ligne['assemblee_id']) ?>">PV PDF</a>
                            <?php else: ?>
                                <span>Pas de PV</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
