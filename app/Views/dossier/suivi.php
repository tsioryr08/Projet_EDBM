<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<div class="page-heading">
    <div>
        <p class="help">Dossier de société</p>
        <h1>Suivi — <?= esc($societe['denomination']) ?></h1>
    </div>
    <span class="status-badge <?= ($dossier['statut'] ?? '') === 'immatricule' ? 'success' : 'warning' ?>"><?= esc($dossier['statut'] ?? 'brouillon') ?></span>
</div>

<?= view('dossier/_nav', ['societeId' => $societe['id'], 'actif' => 'suivi']) ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="notice"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php $messages = session()->getFlashdata('errors') ?? []; ?>
<?php if (! empty($messages['bloquants'])): ?>
    <div class="error"><strong>Erreurs bloquantes</strong><ul><?php foreach ($messages['bloquants'] as $message): ?><li><?= esc($message) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if ($dossier): ?>
    <div class="card">
        <h2>A6 — Mettre à jour le suivi</h2>
        <form method="post" action="<?= site_url('dossiers/' . $societe['id'] . '/statut') ?>">
            <?= csrf_field() ?>
            <div class="grid">
                <label class="field">Statut
                    <select name="statut">
                        <?php foreach (array_unique(array_merge([$suivi['statut']], $suivi['transitions_possibles'] ?? [])) as $statut): ?>
                            <option value="<?= esc($statut) ?>" <?= $dossier['statut'] === $statut ? 'selected' : '' ?>><?= esc($statut) ?></option>
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
            <div class="table-wrap"><table>
                <tr><th>Date</th><th>Ancien statut</th><th>Nouveau statut</th><th>Auteur</th></tr>
                <?php foreach ($suivi['historique'] as $changement): ?>
                    <tr>
                        <td><?= esc($changement['date_changement']) ?></td>
                        <td><?= esc($changement['ancien_statut'] ?? '—') ?></td>
                        <td><?= esc($changement['nouveau_statut']) ?></td>
                        <td><?= esc($changement['auteur'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </table></div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <p class="notice">Aucun dossier n’est encore créé.</p>
<?php endif; ?>
<?= $this->endSection() ?>