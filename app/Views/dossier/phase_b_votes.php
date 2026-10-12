<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<div class="page-heading"><div><p class="help">Vie de Société · Étape 3</p><h1>Votes et résolutions</h1></div></div>
<div class="tabs"><a href="<?= site_url('dossiers/' . $societeId . '/phase-b/planifier') ?>">Planifier</a><a href="<?= site_url('dossiers/' . $societeId . '/phase-b/jour-j') ?>">Jour J</a><a class="active" href="<?= site_url('dossiers/' . $societeId . '/phase-b/votes') ?>">Votes</a><a href="<?= site_url('dossiers/' . $societeId . '/phase-b/registre') ?>">Registre</a></div>
<p><a href="<?= site_url('dossiers/' . $societeId . '/phase-b') ?>">← Retour à la Vie de Société</a></p>

<?php if (session()->getFlashdata('success')): ?>
    <div class="notice"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>

<div class="card">
    <h2>Sélection de l’assemblée</h2>
    <form method="get" action="<?= site_url('dossiers/' . $societeId . '/phase-b/votes') ?>">
        <label class="field">Assemblée
            <select name="assemblee_id" onchange="this.form.submit()">
                <option value="">Choisir une assemblée</option>
                <?php foreach ($assemblees as $assemblee): ?>
                    <option value="<?= (int) $assemblee['id'] ?>" <?= (! empty($selectedAssemblee) && (int) $selectedAssemblee['id'] === (int) $assemblee['id']) ? 'selected' : '' ?>>
                        <?= esc($assemblee['nature_code']) ?> — <?= esc($assemblee['date_reunion']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </form>
</div>

<?php if (! empty($selectedAssemblee)): ?>
    <?php if (empty($selectedAssemblee['quorum_atteint'])): ?>
        <div class="notice">Le quorum n’a pas été atteint pour cette réunion. Les résolutions et les votes ne peuvent pas être enregistrés.</div>
    <?php else: ?>
      <div class="card">
        <h2>1. Saisir les votes</h2>
        <form method="post" action="<?= site_url('dossiers/' . $societeId . '/phase-b/voter') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="assemblee_id" value="<?= (int) $selectedAssemblee['id'] ?>">
            <div id="resolutions">
                <div class="grid resolution-row" data-row>
                    <textarea name="resolutions_rows[0][texte]" rows="2" placeholder="Texte de la résolution" required></textarea>
                    <input name="resolutions_rows[0][pour]" type="number" min="0" value="0" placeholder="Pour">
                    <input name="resolutions_rows[0][contre]" type="number" min="0" value="0" placeholder="Contre">
                    <input name="resolutions_rows[0][abstention]" type="number" min="0" value="0" placeholder="Abstention">
                    <input name="resolutions_rows[0][blanc]" type="number" min="0" value="0" placeholder="Blanc">
                    <select name="resolutions_rows[0][sens_vote_president]"><option value="">Voix prépondérante non utilisée</option><option value="pour">Président : pour</option><option value="contre">Président : contre</option><option value="abstention">Président : abstention</option></select>
                </div>
            </div>
            <button type="button" class="button secondary" data-add="resolutions">Ajouter une résolution</button>
            <p>Le formulaire accepte aussi le format texte si vous préférez saisir les résolutions en une seule zone.</p>
            <div class="actions">
                <button type="submit">Enregistrer les votes</button>
            </div>
        </form>
      </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (! empty($result)): ?>
    <div class="card">
        <h2>2. Résultat des décisions</h2>
        <?php foreach (($result['resultat'] ?? []) as $resolution): ?>
            <div class="card" style="background:#f9fbfc;">
                <h3>Résolution n°<?= (int) ($resolution['numero'] ?? 1) ?></h3>
                <p><strong>Texte :</strong> <?= esc((string) ($resolution['texte'] ?? '—')) ?></p>
                <p><strong>Pour :</strong> <?= (int) ($resolution['pour'] ?? 0) ?> — <strong>Contre :</strong> <?= (int) ($resolution['contre'] ?? 0) ?> — <strong>Abstention :</strong> <?= (int) ($resolution['abstention'] ?? 0) ?> — <strong>Blanc :</strong> <?= (int) ($resolution['blanc'] ?? 0) ?></p>
                <?php if (! empty($resolution['sens_vote_president'])): ?><p><strong>Vote du président :</strong> <?= esc($resolution['sens_vote_president']) ?></p><?php endif; ?>
                <p><strong>Décision :</strong> <?= ($resolution['adoptee'] ?? null) === null ? 'En attente du vote du président' : ($resolution['adoptee'] ? 'Adoptée' : 'Rejetée') ?></p>
                <?php $just = $resolution['justification'] ?? []; ?>
                <?= view('dossier/partials/justification_block', [
                    'motif' => $just['motif'] ?? null,
                    'article' => $just['article'] ?? null,
                    'statut' => $just['statut'] ?? 'a_confirmer',
                    'source' => $just['source'] ?? null,
                    'message' => $just['message'] ?? null,
                ]) ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
