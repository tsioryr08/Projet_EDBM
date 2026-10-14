<?php
$e = static fn ($valeur): string => htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
$blanc = '_________';

$estCa = $nature === 'CA';
$titreLettre = $estCa
    ? 'CONVOCATION AU CONSEIL D’ADMINISTRATION'
    : 'CONVOCATION À L’ASSEMBLÉE GÉNÉRALE ' . strtoupper((string) $qualificatif);
$reunionLibelle = $estCa
    ? 'la réunion du conseil d’administration'
    : 'l’assemblée générale ' . $qualificatif;
$membres = $estCa ? 'des administrateurs' : 'des membres';
$ordinal = (int) $numero_convocation === 1 ? '1re' : (int) $numero_convocation . 'e';

$destinataires = empty($convoques) ? [null] : array_values($convoques);
$dernier = array_key_last($destinataires);
?>
<?php foreach ($destinataires as $index => $convoque): ?>
    <div<?= $index !== $dernier ? ' style="page-break-after: always;"' : '' ?>>
        <h1 style="text-align: center;"><?= $e($titreLettre) ?></h1>

        <p>
            <strong><?= $e($societe) ?></strong><br>
            <?= $siege !== '' ? $e($siege) : $blanc ?>
        </p>

        <p style="text-align: right;">Le <?= $e($date_emission) ?></p>
        <p><strong>Référence :</strong> <?= $e($reference ?? '') ?></p>

        <p><?= $e($convoque['nom_affiche'] ?? $blanc) ?></p>

        <p><strong>Objet :</strong> Convocation à <?= $e($reunionLibelle) ?> du <?= $e($date_reunion_fr) ?></p>

        <p>Madame, Monsieur,</p>

        <p>
            Nous vous invitons à participer à <?= $e($reunionLibelle) ?> de la société
            <?= $e($societe) ?>. Elle aura lieu le <?= $e($date_reunion_fr) ?>
            à <?= $e($heure_fr) ?>, au <?= $e($lieu) ?>.
            Il s’agit de la <?= $e($ordinal) ?> convocation.
        </p>

        <p>Les délibérations porteront sur l’ordre du jour suivant :</p>
        <?php if (empty($ordre_du_jour)): ?>
            <p><?= $blanc ?></p>
        <?php else: ?>
            <ol>
            <?php foreach ($ordre_du_jour as $point): ?>
                <li><?= $e($point) ?></li>
            <?php endforeach; ?>
            </ol>
        <?php endif; ?>

        <p>
            Conformément aux dispositions des statuts, une délibération en
            <?= $e($reunionLibelle) ?> est possible à condition que
            <?= ! empty($quorum) ? $e($quorum) : $blanc ?> <?= $e($membres) ?> soient présents.
        </p>

        <p>
            Vous remerciant pour l’attention portée à ce courrier, nous vous prions d’agréer,
            Madame, Monsieur, l’expression de notre considération distinguée.
        </p>

        <p style="text-align: right;"><?= ! empty($signataire) ? $e($signataire) : $blanc ?></p>
    </div>
<?php endforeach; ?>
