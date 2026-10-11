<?php if (! empty($bailUrl)): ?>
<p><a href="<?= esc($bailUrl) ?>">Ouvrir le générateur de contrat de bail</a></p>
<?php else: ?>
<p class="notice">Aucun générateur de bail n’est configuré dans ce projet.</p>
<?php endif; ?>
