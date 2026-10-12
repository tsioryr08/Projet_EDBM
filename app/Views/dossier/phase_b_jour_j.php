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
    <?php if (empty($assemblee)): ?>
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
    <?php elseif (empty($etapeDeroulement)): ?>
        <div class="card">
            <h2>2. Jour J déjà enregistré</h2>
            <p>Cette réunion a déjà été validée. Consulte le récapitulatif, puis ouvre le déroulement pour compléter le procès-verbal.</p>
            <p><strong>Date de tenue :</strong> <?= esc($assemblee['date_tenue'] ?? '—') ?> —
                <strong>Président :</strong> <?= esc($assemblee['president_seance'] ?? '—') ?> —
                <strong>Secrétaire :</strong> <?= esc($assemblee['secretaire'] ?? '—') ?></p>
            <p><strong>Quorum :</strong> <?= ! empty($assemblee['quorum_atteint']) ? 'atteint' : 'non atteint' ?>
                (<?= (int) ($assemblee['voix_presentes_ou_representees'] ?? 0) ?> / <?= (int) ($assemblee['voix_totales'] ?? 0) ?> voix)</p>
            <h3>Participants enregistrés</h3>
            <ul>
                <?php foreach ($participantsAssemblee as $participant): ?>
                    <li><?= esc($participant['nom']) ?> — <?= esc($participant['statut']) ?><?php if ($participant['statut'] === 'present'): ?> (présent dans la salle)<?php endif; ?></li>
                <?php endforeach; ?>
            </ul>
            <a class="button" href="<?= site_url('dossiers/' . $societeId . '/phase-b/jour-j/' . $selectedReunion['id']) ?>">Continuer vers le déroulement</a>
        </div>
    <?php else: ?>
        <div class="card">
            <h2>3. Déroulement de la réunion</h2>
            <p>Le jour J est enregistré. Ajoute les interventions au fur et à mesure : elles sont conservées et reprises dans le PDF du procès-verbal.</p>
            <div class="grid">
                <section class="card">
                    <h3>Présences enregistrées</h3>
                    <p><strong>Quorum :</strong> <?= ! empty($assemblee['quorum_atteint']) ? 'atteint' : 'non atteint' ?>
                        (<?= (int) ($assemblee['voix_presentes_ou_representees'] ?? 0) ?> / <?= (int) ($assemblee['voix_totales'] ?? 0) ?> voix)</p>
                    <ul>
                        <?php foreach ($participantsAssemblee as $participant): ?>
                            <li><?= esc($participant['nom']) ?> — <?= esc($participant['statut']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
                <form class="card" method="post" action="<?= site_url('dossiers/' . $societeId . '/phase-b/interventions') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="assemblee_id" value="<?= (int) $assemblee['id'] ?>">
                    <label class="field">Personne présente
                        <select name="participant_id" required>
                            <option value="">Choisir la personne qui parle</option>
                            <?php foreach ($participantsPresents as $participant): ?>
                                <option value="<?= (int) $participant['id'] ?>"><?= esc($participant['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">Ce qui a été dit
                        <textarea name="texte" rows="6" required placeholder="Résumé fidèle de l’intervention ou des échanges"></textarea>
                    </label>
                    <button type="submit" <?= empty($participantsPresents) || empty($interventionsTableReady) ? 'disabled' : '' ?>>Ajouter au déroulement</button>
                    <?php if (empty($participantsPresents)): ?><p>Aucun participant présent n’a été enregistré pour cette assemblée.</p><?php endif; ?>
                    <?php if (empty($interventionsTableReady)): ?><p>La table des interventions n’existe pas encore. La migration du projet doit être appliquée avant l’enregistrement.</p><?php endif; ?>
                </form>

                <section class="card">
                    <h3>Interventions enregistrées</h3>
                    <?php if (empty($interventions)): ?>
                        <p>Rien n’a encore été ajouté. Chaque intervention apparaîtra ici après son enregistrement.</p>
                    <?php else: ?>
                        <ol>
                            <?php foreach ($interventions as $intervention): ?>
                                <li><strong><?= esc($intervention['auteur']) ?></strong> — <?= nl2br(esc($intervention['texte'])) ?></li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                    <a class="button secondary" href="<?= site_url('pdf/proces-verbaux/' . $assemblee['id']) ?>">Générer / télécharger le PV PDF</a>
                </section>
            </div>
        </div>
    <?php endif; ?>
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
