<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<h1>Créer une société</h1>
<p class="notice"><strong>A0 — Disponibilité :</strong> vérifiez le nom choisi auprès de l’EDBM avant de poursuivre. Cette application ne réserve pas la dénomination.</p>
<?php if ($errors): ?><div class="error"><strong>A2 — Corrigez les erreurs :</strong><ul><?php foreach ($errors as $error): ?><li><?= esc($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" action="<?= site_url('societes') ?>">
<?= csrf_field() ?>
<h2>A1 — Choix de la forme et informations de la société</h2>
<label>Type
<select name="type_societe_id" required>
<?php foreach ($types as $type): ?><option value="<?= (int) $type['id'] ?>" <?= (int) $selectedType === (int) $type['id'] ? 'selected' : '' ?>><?= esc($type['code']) ?> — <?= esc($type['libelle']) ?></option><?php endforeach; ?>
</select></label><br>
<label>Dénomination <input name="denomination" required maxlength="150"></label><br>
<label>Mode d’administration SA
<select name="mode_administration"><option value="">Non applicable</option><option value="AG">Administrateur général</option><option value="CA">Conseil d’administration</option></select></label><br>
<label>Siège <input name="siege" maxlength="255"></label><br>
<label>Objet <textarea name="objet"></textarea></label><br>
<label>Capital <input name="capital" type="number" step="0.01"></label><br>
<label>Durée (années) <input name="duree_annees" type="number" min="0" max="99"></label><br>
<label>Code activité <input name="code_activite"></label><br>
<label><input type="checkbox" name="activite_reglementee" value="1"> Activité réglementée</label>
<h2>Associés</h2>
<div id="associes">
<div class="grid row">
    <input name="associes[0][nom]" placeholder="Nom" required>
    <select name="associes[0][categorie]"><option value="physique">Personne physique</option><option value="morale_malagasy">Personne morale malagasy</option><option value="morale_etrangere">Personne morale étrangère</option></select>
    <input name="associes[0][apport]" placeholder="Apport">
    <input name="associes[0][nb_titres]" placeholder="Nombre de titres">
    <button type="button" class="button secondary remove-row">Supprimer</button>
</div>
</div>
<button type="button" class="button secondary" data-add="associes">Ajouter un associé</button>
<h2>Dirigeants</h2>
<div id="dirigeants">
<div class="grid row">
    <input name="dirigeants[0][nom]" placeholder="Nom" required>
    <select name="dirigeants[0][fonction]"><option value="gerant">Gérant</option><option value="cogerant">Cogérant</option><option value="administrateur_general">Administrateur général</option><option value="administrateur">Administrateur</option><option value="pca">PCA</option><option value="pdg">PDG</option><option value="dg">DG</option><option value="commandite">Commandité</option></select>
    <input name="dirigeants[0][date_nomination]" type="date" aria-label="Date de nomination">
    <input name="dirigeants[0][fin_mandat]" type="date" aria-label="Fin du mandat">
    <button type="button" class="button secondary remove-row">Supprimer</button>
</div>
</div>
<button type="button" class="button secondary" data-add="dirigeants">Ajouter un dirigeant</button>
<button type="submit">Enregistrer</button>
</form>
<script>
document.querySelectorAll('[data-add]').forEach(function (button) {
    button.addEventListener('click', function () {
        const container = document.getElementById(button.dataset.add);
        const index = container.querySelectorAll('.row').length;
        const first = container.querySelector('.row');
        const clone = first.cloneNode(true);
        clone.querySelectorAll('input, select').forEach(function (field) {
            field.value = '';
            field.name = field.name.replace(/\[\d+\]/, '[' + index + ']');
        });
        container.appendChild(clone);
    });
});
document.addEventListener('click', function (event) {
    if (!event.target.classList.contains('remove-row')) return;
    const rows = event.target.closest('.row').parentElement.querySelectorAll('.row');
    if (rows.length > 1) event.target.closest('.row').remove();
});
</script>
<?= $this->endSection() ?>
