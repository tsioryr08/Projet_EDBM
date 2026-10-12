<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<h1>Phase B — Jour J</h1>
<p><a href="<?= site_url('dossiers/' . $societeId . '/phase-b') ?>">← Retour à la phase B</a></p>

<?php if (session()->getFlashdata('success')): ?>
    <div class="notice"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>

<div class="card">
    <h2>1. Sélection de la réunion</h2>
    <form method="get" action="<?= site_url('dossiers/' . $societeId . '/phase-b/jour-j') ?>">
        <label class="field">Réunion
            <select name="reunion_id" onchange="this.form.submit()">
                <option value="">Choisir une réunion</option>
                <?php foreach ($reunions as $reunion): ?>
                    <option value="<?= (int) $reunion['id'] ?>" <?= (! empty($selectedReunion) && (int) $selectedReunion['id'] === (int) $reunion['id']) ? 'selected' : '' ?>>
                        <?= esc($reunion['nature_code']) ?> — <?= esc($reunion['date_reunion']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </form>
</div>

<?php if (! empty($selectedReunion)): ?>
    <div class="card">
        <h2>2. Saisie du jour J</h2>
        <form method="post" action="<?= site_url('dossiers/' . $societeId . '/phase-b/tenir') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="reunion_id" value="<?= (int) $selectedReunion['id'] ?>">

            <div class="grid">
                <label class="field">Président de séance
                    <input type="text" name="president" placeholder="Nom du président">
                </label>
                <label class="field">Secrétaire
                    <input type="text" name="secretaire" placeholder="Nom du secrétaire">
                </label>
                <label class="field">Date de tenue
                    <input type="date" name="date_tenue" value="<?= esc($selectedReunion['date_reunion']) ?>">
                </label>
            </div>

            <label class="field">Participants (une ligne par personne : nom;statut;mandataire si représenté)
                <textarea name="participants" rows="12" placeholder="Exemple :&#10;Dupont;present;&#10;Martin;represente;Dupont&#10;Bernard;absent;"></textarea>
            </label>

            <div class="actions">
                <button type="submit">Valider le jour J</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php if (! empty($result)): ?>
    <div class="card">
        <h2>3. Résultat de la tenue</h2>
        <p><strong>Quorum atteint :</strong> <?= ! empty($result['resume']['quorum_atteint']) ? 'Oui' : 'Non' ?></p>
        <p><strong>Voix présentes ou représentées :</strong> <?= (int) ($result['resume']['voix_presentes_ou_representees'] ?? 0) ?></p>
        <p><strong>Voix totales :</strong> <?= (int) ($result['resume']['voix_totales'] ?? 0) ?></p>

        <?php $just = $result['resume']['justification'] ?? []; ?>
        <?= view('dossier/partials/justification_block', [
            'motif' => $just['motif'] ?? null,
            'article' => $just['article'] ?? null,
            'statut' => $just['statut'] ?? 'a_confirmer',
            'source' => $just['source'] ?? null,
            'message' => $just['message'] ?? null,
        ]) ?>

        <?php if (! empty($result['resume']['deuxieme_convocation']['possible'])): ?>
            <div class="notice">
                Une 2e convocation est possible : n°<?= (int) ($result['resume']['deuxieme_convocation']['numero'] ?? 2) ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
