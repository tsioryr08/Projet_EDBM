<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<div class="page-heading"><div><p class="help">Vie de Société · Jour J</p><h1>Quorum non atteint</h1></div></div>
<div class="tabs"><a href="<?= site_url('dossiers/' . $societeId . '/phase-b/planifier') ?>">Planifier</a><a class="active" href="<?= site_url('dossiers/' . $societeId . '/phase-b/jour-j') ?>">Jour J</a><a href="<?= site_url('dossiers/' . $societeId . '/phase-b/votes') ?>">Votes</a><a href="<?= site_url('dossiers/' . $societeId . '/phase-b/registre') ?>">Registre</a></div>
<p><a href="<?= site_url('dossiers/' . $societeId . '/phase-b') ?>">← Retour à la Vie de Société</a></p>

<?php if (session()->getFlashdata('success')): ?><div class="notice"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>
<div class="card">
    <h2><?= esc($selectedReunion['nature_code']) ?> — séance du <?= esc($assemblee['date_tenue'] ?? $selectedReunion['date_reunion']) ?></h2>
    <p>Le quorum requis n’a pas été atteint. La séance est enregistrée comme constat de non-quorum ; aucune résolution, aucun vote ni intervention ne peut être ajouté.</p>
    <p><strong>Quorum requis :</strong> <?= (int) ($assemblee['quorum_requis_num'] ?? 0) ?>/<?= (int) ($assemblee['quorum_requis_den'] ?? 1) ?>.
       <strong>Voix présentes ou représentées :</strong> <?= (int) ($assemblee['voix_presentes_ou_representees'] ?? 0) ?> sur <?= (int) ($assemblee['voix_totales'] ?? 0) ?>.</p>
    <p><strong>Règle et fondement :</strong> <?= esc($baseLegale) ?></p>
    <p><a class="button" href="<?= site_url('pdf/proces-verbaux/' . (int) $assemblee['id']) ?>">Télécharger le PV de constat PDF</a></p>
</div>

<div class="card">
    <h2>Présences enregistrées</h2>
    <ul>
        <?php foreach ($participantsAssemblee as $participant): ?>
            <li><?= esc($participant['nom']) ?> — <?= esc($participant['statut']) ?><?php if ($participant['statut'] === 'represente' && ! empty($mandataireParMandant[(int) $participant['id']])): ?> (mandataire : <?= esc($mandataireParMandant[(int) $participant['id']]) ?>)<?php endif; ?></li>
        <?php endforeach; ?>
    </ul>
</div>

<div class="card">
    <h2>Reporter la séance</h2>
    <?php if ($reconvocationPossible): ?>
        <?php $ordre = json_decode($selectedReunion['ordre_du_jour'] ?? '[]', true); ?>
        <?php $ordre = is_array($ordre) ? implode("\n", $ordre) : ''; ?>
        <?php if (($selectedReunion['nature_code'] ?? '') === 'CA'): ?>
            <p>Pour un CA, la loi ne fixe pas ici un délai général de six jours : l’article 482 renvoie aux statuts pour les règles de convocation. Renseigne le délai prévu dans vos statuts.</p>
        <?php else: ?>
            <p>Délai de convocation enregistré pour l’appel suivant : <?= (int) $regleDelaiSuivante['delai_jours'] ?> jours. Cela désigne le délai minimum de notification avant la nouvelle séance, pas un délai automatique de six jours après la séance manquée. Le projet calcule en jours francs ; la date limite d’envoi affichée ensuite doit être vérifiée au regard de la règle applicable.</p>
        <?php endif; ?>
        <p><strong>Quorum prévu pour l’appel suivant :</strong> <?= (int) $regleQuorumSuivante['quorum_num'] ?>/<?= (int) $regleQuorumSuivante['quorum_den'] ?>.</p>
        <form method="post" action="<?= site_url('dossiers/' . $societeId . '/phase-b/planifier') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="nature" value="<?= esc($selectedReunion['nature_code']) ?>">
            <input type="hidden" name="reunion_precedente_id" value="<?= (int) $selectedReunion['id'] ?>">
            <input type="hidden" name="ordre_du_jour" value="<?= esc($ordre) ?>">
            <?php if (($selectedReunion['nature_code'] ?? '') === 'CA'): ?>
                <label class="field">Délai indiqué par les statuts, en jours
                    <input type="number" name="delai_statuts" min="1" required>
                </label>
            <?php endif; ?>
            <label class="field">Date de la nouvelle séance <input type="date" name="date_reunion" required></label>
            <label class="field">Heure <input type="time" name="heure" value="<?= esc(substr((string) $selectedReunion['heure'], 0, 5)) ?>" required></label>
            <label class="field">Lieu <input type="text" name="lieu" value="<?= esc($selectedReunion['lieu']) ?>" required></label>
            <button type="submit">Planifier la convocation suivante</button>
        </form>
    <?php else: ?>
        <p>Aucune règle de convocation suivante n’est configurée pour cette forme et cette réunion. Pour un CA, vérifie le délai dans les statuts ; pour les autres cas, la règle doit être confirmée avant de planifier un nouvel appel.</p>
        <a class="button secondary" href="<?= site_url('dossiers/' . $societeId . '/phase-b/planifier') ?>">Retourner à la planification</a>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
