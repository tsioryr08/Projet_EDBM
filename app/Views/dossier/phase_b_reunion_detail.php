<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<div class="page-heading"><div><p class="help">Vie de Société · Détail de réunion</p><h1><?= esc($reunion['nature_code']) ?> — Réunion n°<?= (int) $reunion['numero_convocation'] ?></h1><p class="help"><?= esc($societe['denomination']) ?> · <?= esc($reunion['date_reunion']) ?></p></div></div>
<div class="tabs"><a href="<?= site_url('dossiers/' . $societeId . '/phase-b/planifier') ?>">Planifier</a><a href="<?= site_url('dossiers/' . $societeId . '/phase-b/jour-j') ?>">Jour J</a><a href="<?= site_url('dossiers/' . $societeId . '/phase-b/votes') ?>">Votes</a><a href="<?= site_url('dossiers/' . $societeId . '/phase-b/registre') ?>">Registre</a></div>
<p><a href="<?= site_url('dossiers/' . $societeId . '/phase-b/registre') ?>">← Retour au registre</a> · <a href="<?= site_url('dossiers/' . $societeId . '/phase-b/planifier') ?>">Historique des planifications</a></p>

<div class="card">
    <h2>Informations de planification</h2>
    <div class="grid">
        <p><strong>Statut :</strong> <?= esc($reunion['statut']) ?></p>
        <p><strong>Date et heure :</strong> <?= esc($reunion['date_reunion']) ?> à <?= esc(substr((string) $reunion['heure'], 0, 5)) ?></p>
        <p><strong>Lieu :</strong> <?= esc($reunion['lieu']) ?></p>
        <p><strong>Date limite de convocation :</strong> <?= esc((string) ($reunion['date_convocation'] ?? '—')) ?></p>
        <p><strong>Délai appliqué :</strong> <?= esc((string) ($reunion['delai_applique'] ?? '—')) ?> jours (<?= esc($reunion['mode_calcul_delai']) ?>)</p>
        <p><strong>Convocation :</strong> n°<?= (int) $reunion['numero_convocation'] ?><?= ! empty($reunion['reunion_precedente_id']) ? ' · réunion précédente #' . (int) $reunion['reunion_precedente_id'] : '' ?></p>
    </div>
    <h3>Ordre du jour</h3>
    <?php if (empty($ordreDuJour)): ?><p>Aucun ordre du jour enregistré.</p><?php else: ?><ol><?php foreach ($ordreDuJour as $point): ?><li><?= esc((string) $point) ?></li><?php endforeach; ?></ol><?php endif; ?>
    <h3>Personnes convoquées</h3>
    <?php if (empty($convoques)): ?><p>Aucune personne convoquée enregistrée.</p><?php else: ?><ul><?php foreach ($convoques as $convoque): ?><li><?= esc($convoque['nom_affiche']) ?> — <?= esc($convoque['role']) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <div class="actions"><a class="button secondary" href="<?= site_url('pdf/convocations/' . (int) $reunion['id']) ?>">Convocation PDF</a><a class="button" href="<?= site_url('dossiers/' . $societeId . '/phase-b/jour-j?reunion_id=' . (int) $reunion['id']) ?>">Ouvrir le Jour J</a></div>
</div>

<div class="card">
    <h2>Tenue de la réunion</h2>
    <?php if (! $assemblee): ?>
        <p>Cette réunion n’a pas encore été enregistrée comme tenue.</p>
    <?php else: ?>
        <div class="grid">
            <p><strong>Date de tenue :</strong> <?= esc((string) ($assemblee['date_tenue'] ?? '—')) ?></p>
            <p><strong>Président de séance :</strong> <?= esc((string) ($assemblee['president_seance'] ?? '—')) ?></p>
            <p><strong>Secrétaire :</strong> <?= esc((string) ($assemblee['secretaire'] ?? '—')) ?></p>
            <p><strong>Quorum :</strong> <?= $assemblee['quorum_atteint'] === null ? 'Non calculé' : ((int) $assemblee['quorum_atteint'] === 1 ? 'Atteint' : 'Non atteint') ?></p>
            <p><strong>Voix présentes ou représentées :</strong> <?= esc((string) ($assemblee['voix_presentes_ou_representees'] ?? '—')) ?> / <?= esc((string) ($assemblee['voix_totales'] ?? '—')) ?></p>
        </div>
        <h3>Présences</h3>
        <?php if (empty($participants)): ?><p>Aucune présence enregistrée.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Personne convoquée</th><th>Statut</th><th>Voix</th></tr></thead><tbody><?php foreach ($participants as $participant): ?><tr><td><?= esc($participant['nom']) ?></td><td><?= esc($participant['statut']) ?></td><td><?= (int) $participant['voix'] ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
        <?php if (! empty($pouvoirs)): ?><h3>Procurations</h3><ul><?php foreach ($pouvoirs as $pouvoir): ?><li><?= esc($pouvoir['mandant_nom']) ?> représenté par <?= esc($pouvoir['mandataire_nom']) ?></li><?php endforeach; ?></ul><?php endif; ?>
        <div class="actions"><a class="button secondary" href="<?= site_url('pdf/proces-verbaux/' . (int) $assemblee['id']) ?>">Procès-verbal PDF</a><a class="button" href="<?= site_url('dossiers/' . $societeId . '/phase-b/votes?assemblee_id=' . (int) $assemblee['id']) ?>">Voir / saisir les votes</a></div>
    <?php endif; ?>
</div>

<?php if ($assemblee): ?>
<div class="card"><h2>Résolutions</h2>
    <?php if (empty($resolutions)): ?><p>Aucune résolution enregistrée.</p><?php else: ?><?php foreach ($resolutions as $resolution): ?><div class="card"><h3>Résolution n°<?= (int) $resolution['numero'] ?> — <?= $resolution['adoptee'] === null ? 'En attente' : ((int) $resolution['adoptee'] === 1 ? 'Adoptée' : 'Rejetée') ?></h3><p><?= nl2br(esc($resolution['texte'])) ?></p><p><strong>Pour :</strong> <?= (int) $resolution['voix_pour'] ?> · <strong>Contre :</strong> <?= (int) $resolution['voix_contre'] ?> · <strong>Abstentions :</strong> <?= (int) $resolution['abstentions'] ?> · <strong>Blancs :</strong> <?= (int) $resolution['voix_blanches'] ?></p><?php if (! empty($resolution['motif'])): ?><p><strong>Motif :</strong> <?= esc($resolution['motif']) ?></p><?php endif; ?></div><?php endforeach; ?><?php endif; ?>
</div>
<div class="card"><h2>Déroulement de la réunion</h2>
    <?php if (empty($interventions)): ?><p>Aucune intervention enregistrée.</p><?php else: ?><ol><?php foreach ($interventions as $intervention): ?><li><strong><?= esc((string) ($intervention['auteur'] ?? 'Intervenant')) ?></strong><?= ! empty($intervention['cree_le']) ? ' · ' . esc((string) $intervention['cree_le']) : '' ?><p><?= nl2br(esc($intervention['texte'])) ?></p></li><?php endforeach; ?></ol><?php endif; ?>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
