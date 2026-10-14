<?php
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$blanc = '_________';

$nature = (string) ($nature ?? '');
$estCa = $nature === 'CA';
$typeLibelle = match ($nature) {
    'AGO' => 'assemblée générale ordinaire',
    'AGE' => 'assemblée générale extraordinaire',
    'AGC' => 'assemblée générale constitutive',
    'CA'  => 'conseil d’administration',
    default => 'assemblée générale',
};
$titrePv = $estCa
    ? 'Procès-verbal de la réunion du conseil d’administration'
    : 'Procès-verbal de l’' . $typeLibelle;
$reunionLibelle = $estCa ? 'le conseil d’administration' : 'l’' . $typeLibelle;

$typeCode = strtoupper((string) ($type_code ?? ''));
[$membreSing, $membrePlur] = $estCa
    ? ['administrateur', 'administrateurs']
    : ($typeCode === 'SA' ? ['actionnaire', 'actionnaires'] : ['associé', 'associés']);

$participants = $participants ?? [];
$total = count($participants);
$presents = count(array_filter(
    $participants,
    static fn (array $p): bool => in_array($p['statut'] ?? null, ['present', 'represente'], true)
));
$avecMandataire = (bool) array_filter($participants, static fn (array $p): bool => ! empty($p['par']));
$libelleStatut = ['present' => 'Présent', 'represente' => 'Représenté', 'absent' => 'Absent'];

$quorumAtteint = ! empty($quorum_atteint);
$dateAff = ! empty($date_fr) ? $date_fr : $blanc;
$lieuAff = ! empty($lieu) ? $lieu : $blanc;
$president = ! empty($president_seance) ? $president_seance : $blanc;
$secretaireNom = ! empty($secretaire) ? $secretaire : $blanc;
$reference = $reference ?? '';
?>
<style>
    @page { margin: 2.2cm 2cm 2.6cm 2cm; }
    .pvx { font-size: 11pt; line-height: 1.55; }
    .pvx h1 { text-align: center; font-size: 16pt; margin: 18px 0 6px 0; }
    .pvx h2 { font-size: 12pt; margin: 18px 0 6px 0; text-decoration: underline; }
    .pvx-ref { text-align: right; font-size: 10pt; margin: 0 0 4px 0; }
    .pvx-id { margin: 0; }
    .pvx-lieu-date { text-align: right; margin: 10px 0 0 0; }
    .pvx-centre { text-align: center; }
    .pvx-presence { width: 100%; border-collapse: collapse; margin: 8px 0; }
    .pvx-presence th, .pvx-presence td { border: 1px solid #444; padding: 4px 6px; text-align: left; font-size: 10pt; }
    .pvx-motif { font-size: 9.5pt; font-style: italic; margin: 0 0 8px 0; }
    .pvx-sign { margin-top: 28px; }
    .pvx-sign-col { display: inline-block; width: 48%; text-align: center; vertical-align: top; height: 110px; }
    .pvx-footer { position: fixed; bottom: -1.6cm; left: 0; right: 0; text-align: center; font-size: 9pt; color: #555; }
    .pvx-page:before { content: counter(page); }
</style>

<div class="pvx-footer">Référence : <?= $e($reference) ?> — Page <span class="pvx-page"></span></div>

<div class="pvx">
    <p class="pvx-ref"><strong>Référence : <?= $e($reference) ?></strong></p>

    <p class="pvx-id">
        <strong><?= ! empty($societe) ? $e($societe) : $blanc ?></strong><br>
        <?= ! empty($siege) ? $e($siege) : $blanc ?>
    </p>
    <p class="pvx-lieu-date"><?= $e($lieuAff) ?>, le <?= $e($dateAff) ?></p>

    <h1><?= $e($titrePv) ?></h1>
    <p class="pvx-centre">en date du <?= $e($dateAff) ?></p>

    <p>
        Le <?= $e($dateAff) ?><?= ! empty($heure_fr) ? ' à ' . $e($heure_fr) : '' ?>,
        les <?= $e($membrePlur) ?> de la société <strong><?= $e($societe ?? $blanc) ?></strong>
        se sont réunis en <?= $e($estCa ? 'conseil d’administration' : $typeLibelle) ?>
        à <?= $e($lieuAff) ?>, sur convocation écrite
        (<?= (int) ($numero_convocation ?? 1) === 1 ? '1re' : (int) $numero_convocation . 'e' ?> convocation).
    </p>

    <p>
        La séance est présidée par <?= $e($president) ?>, assisté(e) de <?= $e($secretaireNom) ?>,
        en sa qualité de secrétaire de séance.
    </p>

    <p>
        Il a été établi une feuille de présence, reproduite ci-dessous et annexée au présent procès-verbal.
    </p>

    <?php if ($total > 0): ?>
        <table class="pvx-presence">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Statut</th>
                    <?php if ($avecMandataire): ?><th>Représenté par</th><?php endif; ?>
                    <th>Voix</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($participants as $participant): ?>
                <tr>
                    <td><?= $e($participant['nom'] ?? '') ?></td>
                    <td><?= $e($libelleStatut[$participant['statut'] ?? ''] ?? ($participant['statut'] ?? '')) ?></td>
                    <?php if ($avecMandataire): ?><td><?= $e($participant['par'] ?? '') ?></td><?php endif; ?>
                    <td><?= (int) ($participant['voix'] ?? 0) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p>
        Ladite feuille de présence permet de constater que <?= $presents ?> <?= $e($total > 1 ? $membrePlur : $membreSing) ?>
        sur <?= $total ?> sont présents ou représentés<?php if (isset($voix_presentes_ou_representees, $voix_totales)): ?>,
        soit <?= (int) $voix_presentes_ou_representees ?> voix sur <?= (int) $voix_totales ?><?php endif; ?>.
        <?php if ($quorumAtteint): ?>
            En conséquence, le quorum est atteint<?= ! empty($quorum_requis) ? ' (quorum requis : ' . $e($quorum_requis) . ')' : '' ?>
            et <?= $e($reunionLibelle) ?> peut valablement délibérer sur l’ordre du jour suivant :
        <?php else: ?>
            Il est constaté que le quorum n’est pas atteint<?= ! empty($quorum_requis) ? ' (quorum requis : ' . $e($quorum_requis) . ')' : '' ?>.
            Aucune délibération ne peut être prise et aucun vote ne peut être validé.
            L’ordre du jour de la convocation était le suivant :
        <?php endif; ?>
    </p>

    <?php if (empty($ordre_du_jour)): ?>
        <p><?= $blanc ?></p>
    <?php else: ?>
        <ol>
            <?php foreach ($ordre_du_jour as $point): ?>
                <li><?= nl2br($e($point)) ?></li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>

    <?php if (! $quorumAtteint): ?>
        <?php if (! empty($motif_quorum)): ?><p><?= $e($motif_quorum) ?></p><?php endif; ?>
        <?php if (! empty($base_legale_non_quorum)): ?><p><?= $e($base_legale_non_quorum) ?></p><?php endif; ?>
        <?php if (! empty($pv_constat['message'])): ?><p><?= $e($pv_constat['message']) ?></p><?php endif; ?>
        <p>La séance est levée faute de quorum.</p>
    <?php else: ?>

        <?php if (! empty($interventions)): ?>
            <h2>Discussion</h2>
            <p>La discussion est déclarée ouverte. Prennent successivement la parole :</p>
            <?php foreach ($interventions as $intervention): ?>
                <p><strong><?= $e($intervention['auteur'] ?? '') ?></strong> : <?= nl2br($e($intervention['texte'] ?? '')) ?></p>
            <?php endforeach; ?>
        <?php endif; ?>

        <h2>Résolutions et votes</h2>
        <?php if (empty($resolutions)): ?>
            <p>Aucune résolution n’a été soumise à la délibération.</p>
        <?php else: ?>
            <?php foreach ($resolutions as $index => $resolution): ?>
                <?php
                $adoptee = $resolution['adoptee'] ?? null;
                $etat = $adoptee === true ? 'adoptée' : ($adoptee === false ? 'rejetée' : null);
                ?>
                <p>
                    <strong>Résolution n°<?= $index + 1 ?></strong> — <?= nl2br($e($resolution['texte'] ?? '')) ?>
                </p>
                <p>
                    Mise aux voix, cette résolution <?= $etat !== null ? 'est <strong>' . $etat . '</strong> par' : 'recueille' ?>
                    <?= (int) ($resolution['pour'] ?? 0) ?> voix pour,
                    <?= (int) ($resolution['contre'] ?? 0) ?> voix contre<?php if (array_key_exists('abstention', $resolution)): ?>
                    et <?= (int) $resolution['abstention'] ?> abstention<?= (int) $resolution['abstention'] > 1 ? 's' : '' ?><?php endif; ?>.
                    <?php if (($resolution['sens_vote_president'] ?? null) !== null): ?>
                        Vote du président de séance : <?= $e($resolution['sens_vote_president']) ?>.
                    <?php endif; ?>
                </p>
                <?php if (! empty($resolution['mention_pv'])): ?><p><?= $e($resolution['mention_pv']) ?></p><?php endif; ?>
                <?php if (! empty($resolution['motif'])): ?><p class="pvx-motif"><?= $e($resolution['motif']) ?></p><?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <h2>Clôture</h2>
        <p>
            L’ordre du jour étant épuisé et personne ne demandant plus la parole,
            <?= $e($president) ?> déclare la séance levée.
        </p>
    <?php endif; ?>

    <p>Fait à <?= $e($lieuAff) ?>, le <?= $e($dateAff) ?>.</p>

    <div class="pvx-sign">
        <div class="pvx-sign-col">Le président<br><br><br><?= $e($president) ?></div>
        <div class="pvx-sign-col">Le secrétaire<br><br><br><?= $e($secretaireNom) ?></div>
    </div>
</div>