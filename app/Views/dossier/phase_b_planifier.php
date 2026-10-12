<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<h1>Phase B — Planifier une réunion</h1>
<p><a href="<?= site_url('dossiers/' . $societeId . '/phase-b') ?>">← Retour à la phase B</a></p>

<?php if (session()->getFlashdata('success')): ?>
    <div class="notice"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>

<div class="card">
    <h2>1. Saisie de la réunion</h2>
    <form method="post" action="<?= site_url('dossiers/' . $societeId . '/phase-b/planifier') ?>">
        <?= csrf_field() ?>
        <div class="grid">
            <label class="field">Nature
                <select name="nature" required>
                    <option value="AGO">AGO</option>
                    <option value="AGE">AGE</option>
                    <option value="CA">CA</option>
                </select>
            </label>
            <label class="field">Date de réunion
                <input type="date" name="date_reunion" required>
            </label>
            <label class="field">Heure
                <input type="time" name="heure" value="09:00" required>
            </label>
            <label class="field">Lieu
                <input type="text" name="lieu" value="Siège social" required>
            </label>
            <label class="field">Numéro de convocation
                <input type="number" name="numero_convocation" min="1" value="1">
            </label>
            <label class="field">Réunion précédente (pour une 2e convocation)
                <select name="reunion_precedente_id">
                    <option value="">Aucune</option>
                    <?php foreach ($reunions as $reunion): ?>
                        <option value="<?= (int) $reunion['id'] ?>">#<?= (int) $reunion['id'] ?> — <?= esc($reunion['nature_code']) ?> — <?= esc($reunion['date_reunion']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <label class="field">Ordre du jour
            <textarea name="ordre_du_jour" rows="6" placeholder="Exemple :&#10;1. Lecture et approbation du rapport&#10;2. Adoption des résolutions"></textarea>
        </label>

        <div class="actions">
            <button type="submit">Planifier la réunion</button>
        </div>
    </form>
</div>

<?php if (! empty($preview)): ?>
    <div class="card">
        <h2>2. Résultat calculé</h2>
        <p><strong>Société :</strong> <?= esc($preview['societe'] ?? $societe['denomination']) ?></p>
        <p><strong>Type :</strong> <?= esc($preview['type'] ?? '—') ?></p>
        <p><strong>Nature :</strong> <?= esc($preview['nature'] ?? '—') ?></p>
        <p><strong>Date de réunion :</strong> <?= esc($preview['date_reunion'] ?? '—') ?></p>
        <p><strong>Date limite de convocation :</strong> <?= esc($preview['date_convocation'] ?? '—') ?></p>
        <p><strong>Délai appliqué :</strong> <?= esc((string) ($preview['delai_jours'] ?? '—')) ?> jours</p>
        <p><strong>Mode de calcul :</strong> <?= esc($preview['mode_calcul'] ?? '—') ?></p>
        <p><strong>Forme d’envoi :</strong> <?= esc($preview['forme_envoi'] ?? '—') ?></p>
        <p><strong>En retard ?</strong> <?= ! empty($preview['en_retard']) ? 'Oui' : 'Non' ?></p>

        <?php $just = $preview['justification'] ?? []; ?>
        <?= view('dossier/partials/justification_block', [
            'motif' => $just['motif'] ?? null,
            'article' => $just['article'] ?? null,
            'statut' => $just['statut'] ?? 'a_confirmer',
            'source' => $just['source'] ?? null,
            'message' => $just['message'] ?? null,
        ]) ?>

        <h3>Convoqués</h3>
        <?php $convoques = $preview['convoques'] ?? []; ?>
        <?php if (empty($convoques)): ?>
            <p>Aucun convoqué inscrit pour l’instant.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($convoques as $nom): ?>
                    <li><?= esc((string) $nom) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="card">
    <h2>Historique/planifications</h2>
    <?php if (empty($reunions)): ?>
        <p>Aucune réunion n’a encore été planifiée.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Nature</th>
                    <th>Numéro</th>
                    <th>Date</th>
                    <th>Délai</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reunions as $reunion): ?>
                    <tr>
                        <td><?= esc($reunion['nature_code']) ?></td>
                        <td><?= (int) ($reunion['numero_convocation'] ?? 1) ?></td>
                        <td><?= esc($reunion['date_reunion']) ?></td>
                        <td><?= esc((string) ($reunion['delai_applique'] ?? '—')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
