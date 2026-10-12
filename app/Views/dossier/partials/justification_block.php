<?php
/**
 * Bloc réutilisable de justification juridique.
 * Usage attendu : $motif, $article, $statut, $source, $message.
 */
?>
<div class="card">
    <h3>Justification</h3>
    <div class="actions">
        <?php
        $badge = 'secondary';
        if (($statut ?? '') === 'verifie') { $badge = 'success'; }
        if (($statut ?? '') === 'a_confirmer') { $badge = 'warning'; }
        if (($statut ?? '') === 'deduction') { $badge = 'secondary'; }
        ?>
        <span class="button link">
            <?= esc((string) ($statut ?? 'a_confirmer')) ?>
        </span>
    </div>
    <p><strong>Motif :</strong> <?= esc((string) ($motif ?? '—')) ?></p>
    <p><strong>Article :</strong> <?= esc((string) ($article ?? $source ?? '—')) ?></p>
    <p><?= esc((string) ($message ?? 'Aucune justification détaillée n’a encore été fournie.')) ?></p>
</div>
