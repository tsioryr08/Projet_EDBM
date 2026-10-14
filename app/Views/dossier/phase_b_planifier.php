<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<?php $planningForm = $planningForm ?? []; ?>
<div class="page-heading"><div><p class="help">Vie de Société · Étape 1</p><h1>Planifier une réunion</h1></div></div>
<div class="tabs"><a class="active" href="<?= site_url('dossiers/' . $societeId . '/phase-b/planifier') ?>">Planifier</a><a href="<?= site_url('dossiers/' . $societeId . '/phase-b/jour-j') ?>">Jour J</a><a href="<?= site_url('dossiers/' . $societeId . '/phase-b/votes') ?>">Votes</a><a href="<?= site_url('dossiers/' . $societeId . '/phase-b/registre') ?>">Registre</a></div>
<p><a href="<?= site_url('dossiers/' . $societeId . '/phase-b') ?>">← Retour à la Vie de Société</a></p>

<?php if (session()->getFlashdata('success')): ?>
    <div class="notice"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>

<div class="card">
    <h2>1. Saisie de la réunion</h2>
    <?php if (! empty($planningError)): ?><div class="notice"><?= esc($planningError) ?></div><?php endif; ?>
    <form method="post" action="<?= site_url('dossiers/' . $societeId . '/phase-b/planifier') ?>">
        <?= csrf_field() ?>
        <div class="grid">
            <label class="field">Nature
                <select name="nature" data-nature required>
                    <option value="AGO" <?= ($planningForm['nature'] ?? '') === 'AGO' ? 'selected' : '' ?>>AGO</option>
                    <option value="AGE" <?= ($planningForm['nature'] ?? '') === 'AGE' ? 'selected' : '' ?>>AGE</option>
                    <option value="CA" <?= ($planningForm['nature'] ?? '') === 'CA' ? 'selected' : '' ?>>CA</option>
                </select>
            </label>
            <label class="field">Date de réunion
                <input type="date" name="date_reunion" value="<?= esc($planningForm['date_reunion'] ?? '') ?>" required>
            </label>
            <label class="field">Heure
                <input type="time" name="heure" value="<?= esc($planningForm['heure'] ?? '09:00') ?>" required>
            </label>
            <label class="field">Lieu
                <input type="text" name="lieu" value="<?= esc($planningForm['lieu'] ?? 'Siège social') ?>" required>
            </label>
            <label class="field">Numéro de convocation
                <input type="number" name="numero_convocation" min="1" value="<?= esc((string) ($planningForm['numero_convocation'] ?? 1)) ?>">
            </label>
            <label class="field">Réunion précédente (pour une 2e convocation)
                <select name="reunion_precedente_id">
                    <option value="">Aucune</option>
                    <?php foreach ($reunions as $reunion): ?>
                        <option value="<?= (int) $reunion['id'] ?>" data-nature="<?= esc($reunion['nature_code']) ?>" <?= (int) ($planningForm['reunion_precedente_id'] ?? 0) === (int) $reunion['id'] ? 'selected' : '' ?>>#<?= (int) $reunion['id'] ?> — <?= esc($reunion['nature_code']) ?> — <?= esc($reunion['date_reunion']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php $delaiStatutsAffiche = ($planningForm['nature'] ?? '') === 'CA'; ?>
            <label class="field" data-delai-statuts-field <?= $delaiStatutsAffiche ? '' : 'hidden' ?> style="<?= $delaiStatutsAffiche ? '' : 'display:none' ?>">Délai prévu par les statuts (en jours)
                <input type="number" name="delai_statuts" min="1" value="<?= esc((string) ($planningForm['delai_statuts'] ?? '')) ?>" data-delai-statuts <?= $delaiStatutsAffiche ? 'required' : 'disabled' ?>>
                <small>Pour un CA, vérifie le délai et le mode de calcul dans les statuts. Le projet applique son calcul en jours francs.</small>
            </label>
        </div>

        <label class="field">Ordre du jour
            <textarea name="ordre_du_jour" rows="6" placeholder="Exemple :&#10;1. Lecture et approbation du rapport&#10;2. Adoption des résolutions"><?= esc($planningForm['ordre_du_jour'] ?? '') ?></textarea>
        </label>

        <div class="actions">
            <button type="submit">Planifier la réunion</button>
        </div>
    </form>
</div>

<script>
const natureSelect = document.querySelector('[data-nature]');
const reunionPrecedenteSelect = document.querySelector('[name="reunion_precedente_id"]');
const delaiStatutsField = document.querySelector('[data-delai-statuts-field]');
const delaiStatutsInput = document.querySelector('[data-delai-statuts]');
const actualiserDelaiStatuts = () => {
    const previousNature = reunionPrecedenteSelect.selectedOptions[0]?.dataset.nature ?? '';
    const requis = natureSelect.value === 'CA' || previousNature === 'CA';
    delaiStatutsField.hidden = !requis;
    delaiStatutsField.style.display = requis ? '' : 'none';
    delaiStatutsInput.disabled = !requis;
    delaiStatutsInput.required = requis;
};
natureSelect.addEventListener('change', actualiserDelaiStatuts);
reunionPrecedenteSelect.addEventListener('change', actualiserDelaiStatuts);
actualiserDelaiStatuts();
</script>

<?php if (! empty($preview)): ?>
    <?php $resultatPlanification = $preview['reunion'] ?? $preview; ?>
    <div class="card">
        <h2>2. Résultat calculé</h2>
        <p><strong>Société :</strong> <?= esc($resultatPlanification['societe'] ?? $societe['denomination']) ?></p>
        <p><strong>Type :</strong> <?= esc($resultatPlanification['type'] ?? '—') ?></p>
        <p><strong>Nature :</strong> <?= esc($resultatPlanification['nature'] ?? '—') ?></p>
        <p><strong>Date de réunion :</strong> <?= esc($resultatPlanification['date_reunion'] ?? '—') ?></p>
        <p><strong>Date limite de convocation :</strong> <?= esc($resultatPlanification['date_convocation'] ?? '—') ?></p>
        <p><strong>Délai appliqué :</strong> <?= esc((string) ($resultatPlanification['delai_jours'] ?? '—')) ?> jours</p>
        <p><strong>Mode de calcul :</strong> <?= esc($resultatPlanification['mode_calcul'] ?? '—') ?></p>
        <p><strong>Forme d’envoi :</strong> <?= esc($resultatPlanification['forme_envoi'] ?? '—') ?></p>
        <p><strong>En retard ?</strong> <?= ! empty($resultatPlanification['en_retard']) ? 'Oui' : 'Non' ?></p>

        <?php $just = $resultatPlanification['justification'] ?? []; ?>
        <?= view('dossier/partials/justification_block', [
            'motif' => $just['motif'] ?? null,
            'article' => $just['article'] ?? $just['source'] ?? null,
            'statut' => $just['statut'] ?? 'a_confirmer',
            'source' => $just['source'] ?? null,
            'message' => $just['message'] ?? $just['avertissement'] ?? $just['precision'] ?? null,
        ]) ?>

        <?php $reunionCreee = $resultatPlanification; ?>
        <h3>Personnes convoquées</h3>
        <p>La liste est constituée à partir des membres de la société : les associés/actionnaires pour une AG, les administrateurs pour un CA.</p>
        <?php $convoques = $reunionCreee['convoques'] ?? []; ?>
        <?php if (empty($convoques)): ?>
            <p>Aucun convoqué inscrit pour l’instant.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($convoques as $nom): ?>
                    <li><?= esc((string) $nom) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if (! empty($reunionCreee['reunion_id'])): ?>
            <div class="actions">
                <a class="button" href="<?= site_url('pdf/convocations/' . (int) $reunionCreee['reunion_id']) ?>">Télécharger la convocation PDF</a>
                <a class="button secondary" href="<?= site_url('dossiers/' . $societeId . '/phase-b/jour-j?reunion_id=' . (int) $reunionCreee['reunion_id']) ?>">Passer au Jour J</a>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
<!-- 
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
                    <tr class="reunion-clickable" data-href="<?= site_url('dossiers/' . $societeId . '/phase-b/reunions/' . (int) $reunion['id']) ?>" tabindex="0" role="link" aria-label="Voir les détails de la réunion <?= esc($reunion['nature_code']) ?> du <?= esc($reunion['date_reunion']) ?>">
                        <td><?= esc($reunion['nature_code']) ?></td>
                        <td>#<?= (int) ($reunion['numero_convocation'] ?? 1) ?></td>
                        <td><?= esc($reunion['date_reunion']) ?></td>
                        <td><?= esc((string) ($reunion['delai_applique'] ?? '—')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div> -->
<script>
document.querySelectorAll('.reunion-clickable').forEach((row) => {
    const ouvrir = () => window.location.href = row.dataset.href;
    row.addEventListener('click', ouvrir);
    row.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            ouvrir();
        }
    });
});
</script>
<?= $this->endSection() ?>
