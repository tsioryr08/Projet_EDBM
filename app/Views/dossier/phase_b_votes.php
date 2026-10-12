<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<h1>Phase B — Résolutions</h1>
<p><a href="<?= site_url('dossiers/' . $societeId . '/phase-b') ?>">← Retour à la phase B</a></p>

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
    <div class="card">
        <h2>1. Saisir les votes</h2>
        <form method="post" action="<?= site_url('dossiers/' . $societeId . '/phase-b/voter') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="assemblee_id" value="<?= (int) $selectedAssemblee['id'] ?>">
            <label class="field">Résolutions (une ligne = texte|pour|contre|abstention|blanc|vote du président)
                <textarea name="resolutions" rows="10" placeholder="Exemple :&#10;Approbation du rapport annuel|8|1|0|0|&#10;Nomination du gérant|7|2|1|0|&#10;Vote CA à égalité|3|3|0|0|pour"></textarea>
            </label>
            <p>Pour une égalité au CA, indique le vote du président à la fin : <code>pour</code>, <code>contre</code> ou <code>abstention</code>. Laisse vide s’il n’est pas encore connu.</p>
            <div class="actions">
                <button type="submit">Enregistrer les votes</button>
            </div>
        </form>
    </div>
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
