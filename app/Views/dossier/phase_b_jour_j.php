<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<div class="page-heading"><div><p class="help">Vie de Société · Étape 2</p><h1>Jour J</h1></div></div>
<div class="tabs"><a href="<?= site_url('dossiers/' . $societeId . '/phase-b/planifier') ?>">Planifier</a><a class="active" href="<?= site_url('dossiers/' . $societeId . '/phase-b/jour-j') ?>">Jour J</a><a href="<?= site_url('dossiers/' . $societeId . '/phase-b/votes') ?>">Votes</a><a href="<?= site_url('dossiers/' . $societeId . '/phase-b/registre') ?>">Registre</a></div>
<p><a href="<?= site_url('dossiers/' . $societeId . '/phase-b') ?>">← Retour à la Vie de Société</a></p>

<?php if (session()->getFlashdata('success')): ?>
    <div class="notice"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="notice"><?= esc(session()->getFlashdata('error')) ?></div>
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

                <section class="card">
                    <h3>Présence et représentation</h3>
                    <p>Choisis le statut de chaque personne convoquée. Le mandataire est demandé uniquement si elle est représentée.</p>
                    <?php if (empty($convoques)): ?>
                        <p>Aucune personne convoquée n’est enregistrée pour cette réunion.</p>
                    <?php else: ?>
                        <?php foreach ($convoques as $index => $convoque): ?>
                            <div class="grid participant-row" data-participant-row>
                                <input type="hidden" name="participants_rows[<?= $index ?>][nom]" value="<?= esc($convoque['nom_affiche']) ?>">
                                <strong><?= esc($convoque['nom_affiche']) ?></strong>
                                <label class="field">Statut
                                    <select name="participants_rows[<?= $index ?>][statut]" data-statut>
                                        <option value="present">Présent</option>
                                        <option value="represente">Absent, représenté</option>
                                        <option value="absent">Absent, non représenté</option>
                                    </select>
                                </label>
                                <label class="field" data-mandataire-field hidden>Mandataire
                                    <select name="participants_rows[<?= $index ?>][par]" data-mandataire>
                                        <option value="">Choisir une personne présente</option>
                                        <?php foreach ($convoques as $mandataire): ?><option value="<?= esc($mandataire['nom_affiche']) ?>"><?= esc($mandataire['nom_affiche']) ?></option><?php endforeach; ?>
                                        <?php if (($selectedReunion['nature_code'] ?? '') !== 'CA'): ?><option value="__autre__">Autre personne</option><?php endif; ?>
                                    </select>
                                    <input type="text" name="participants_rows[<?= $index ?>][autre_nom]" data-autre-nom placeholder="Nom du mandataire" hidden disabled>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>

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
            <p><strong>Seuil appliqué :</strong> <?= (int) ($assemblee['quorum_requis_num'] ?? 0) ?>/<?= (int) ($assemblee['quorum_requis_den'] ?? 1) ?></p>
            <?php if (empty($assemblee['quorum_atteint'])): ?>
                <div class="notice">Le quorum n’est pas atteint. Les présences sont enregistrées, mais le déroulement et les interventions ne sont pas accessibles. Prépare une nouvelle convocation si une règle correspondante est disponible.</div>
            <?php endif; ?>
            <h3>Participants enregistrés</h3>
            <ul>
                <?php foreach ($participantsAssemblee as $participant): ?>
                    <li><?= esc($participant['nom']) ?> — <?= esc($participant['statut']) ?><?php if ($participant['statut'] === 'present'): ?> (présent dans la salle)<?php endif; ?></li>
                <?php endforeach; ?>
            </ul>
            <?php if (! empty($assemblee['quorum_atteint'])): ?>
                <a class="button" href="<?= site_url('dossiers/' . $societeId . '/phase-b/jour-j/' . $selectedReunion['id']) ?>">Continuer vers le déroulement</a>
            <?php endif; ?>
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
                            <li><?= esc($participant['nom']) ?> — <?= esc($participant['statut']) ?><?php if ($participant['statut'] === 'represente' && ! empty($participant['mandataire_nom'])): ?> (mandataire : <?= esc($participant['mandataire_nom']) ?>)<?php endif; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
                <form class="card" method="post" action="<?= site_url('dossiers/' . $societeId . '/phase-b/interventions') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="assemblee_id" value="<?= (int) $assemblee['id'] ?>">
                    <label class="field">Personne qui parle
                        <select name="auteur_selection" required>
                            <option value="">Choisir la personne qui parle</option>
                            <?php foreach ($intervenants as $intervenant): ?>
                                <option value="<?= esc($intervenant['value']) ?>" <?= empty($intervenant['disponible']) ? 'disabled' : '' ?>><?= esc($intervenant['nom']) ?> — <?= esc($intervenant['precision']) ?><?= empty($intervenant['disponible']) ? ' (migration des interventions requise)' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">Ce qui a été dit
                        <textarea name="texte" rows="6" required placeholder="Résumé fidèle de l’intervention ou des échanges"></textarea>
                    </label>
                    <button type="submit" <?= empty($intervenants) || empty($interventionsTableReady) ? 'disabled' : '' ?>>Ajouter au déroulement</button>
                    <?php if (empty($participantsPresents)): ?><p>Aucun participant présent n’a été enregistré pour cette assemblée.</p><?php endif; ?>
                    <?php if (empty($interventionsTableReady)): ?><p>La table des interventions n’existe pas encore. La migration du projet doit être appliquée avant l’enregistrement.</p><?php endif; ?>
                    <?php if (! empty($mandataireExternePresent) && empty($interventionAuteurReady)): ?><p>Le nom du mandataire apparaît dans la liste, mais la migration qui permet d’enregistrer son intervention n’est pas détectée par l’application.</p><?php endif; ?>
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

<script>
document.querySelectorAll('[data-participant-row]').forEach((row) => {
    const statut = row.querySelector('[data-statut]');
    const field = row.querySelector('[data-mandataire-field]');
    const select = row.querySelector('[data-mandataire]');
    const autreNom = row.querySelector('[data-autre-nom]');
    const refresh = () => {
        const represente = statut.value === 'represente';
        field.hidden = !represente;
        select.required = represente;
        select.disabled = !represente;
        const autre = represente && select.value === '__autre__';
        autreNom.hidden = !autre;
        autreNom.disabled = !autre;
        autreNom.required = autre;
    };
    statut.addEventListener('change', refresh);
    select.addEventListener('change', refresh);
    refresh();
});
</script>

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
                <form method="post" action="<?= site_url('dossiers/' . $societeId . '/phase-b/planifier') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="nature" value="<?= esc($selectedReunion['nature_code'] ?? '') ?>">
                    <input type="hidden" name="reunion_precedente_id" value="<?= (int) ($selectedReunion['id'] ?? 0) ?>">
                    <label class="field">Nouvelle date <input type="date" name="date_reunion" required></label>
                    <label class="field">Heure <input type="time" name="heure" value="09:00" required></label>
                    <label class="field">Lieu <input type="text" name="lieu" value="<?= esc($selectedReunion['lieu'] ?? '') ?>" required></label>
                    <input type="hidden" name="ordre_du_jour" value="">
                    <button type="submit">Convoquer à nouveau</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
