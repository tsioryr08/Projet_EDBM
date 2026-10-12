<?= $this->extend('_layout') ?>
<?= $this->section('content') ?>
<div class="page-heading"><div><p class="help">Étape 2 sur 3</p><h1>Créer une société</h1><p class="help">Renseignez les informations disponibles ; les règles manquantes seront signalées.</p></div></div>
<div class="stepper"><div class="step done">1 · Forme sociale</div><div class="step active">2 · Informations</div><div class="step">3 · Dossier</div></div>
<div class="alert alert-info" role="note"><strong>A0 — Disponibilité :</strong> vérifiez le nom auprès de l’EDBM avant de poursuivre.</div>
<?php if ($errors): ?><div class="alert alert-error" role="alert"><strong>A2 — Corrigez les erreurs :</strong><ul><?php foreach ($errors as $error): ?><li><?= esc($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" action="<?= site_url('societes') ?>">
<?= csrf_field() ?>
<div class="panel"><h2>1. Dénomination et forme</h2>
<label class="field">Forme sociale
<select name="type_societe_id" required aria-describedby="type-help">
<?php foreach ($types as $type): ?><option value="<?= (int) $type['id'] ?>" <?= (int) $selectedType === (int) $type['id'] ? 'selected' : '' ?>><?= esc($type['code']) ?> — <?= esc($type['libelle']) ?></option><?php endforeach; ?>
</select><span class="help" id="type-help">Les documents et contrôles sont déterminés par cette forme.</span></label>
<label class="field">Dénomination <input name="denomination" required maxlength="150"><span class="help">Nom à faire vérifier auprès de l’EDBM.</span></label>
<label class="field">Mode d’administration SA
<select name="mode_administration"><option value="">Non applicable</option><option value="AG">Administrateur général</option><option value="CA">Conseil d’administration</option></select></label><br>
<div class="grid"><label class="field">Siège <input name="siege" maxlength="255"></label><label class="field">Objet <textarea name="objet"></textarea></label><label class="field">Capital <input name="capital" type="number" step="0.01"></label><label class="field">Durée (années) <input name="duree_annees" type="number" min="0" max="99"></label><label class="field">Code activité <input name="code_activite"></label></div>
<label><input type="checkbox" name="activite_reglementee" value="1"> Activité réglementée</label></div>
<div class="panel"><h2>2. Associés</h2>
<div id="associes">
<div class="grid row" data-row>
    <input name="associes[0][nom]" placeholder="Nom" required>
    <select name="associes[0][categorie]"><option value="physique">Personne physique</option><option value="morale_malagasy">Personne morale malagasy</option><option value="morale_etrangere">Personne morale étrangère</option></select>
    <input name="associes[0][apport]" placeholder="Apport">
    <input name="associes[0][nb_titres]" placeholder="Nombre de titres">
    <button type="button" class="button secondary" data-remove>Supprimer</button>
</div>
</div>
<button type="button" class="button secondary" data-add="associes">Ajouter un associé</button></div>
<div class="panel"><h2>3. Dirigeants</h2>
<div id="dirigeants">
<div class="grid row" data-row>
    <input name="dirigeants[0][nom]" placeholder="Nom" required>
    <select name="dirigeants[0][fonction]"><option value="gerant">Gérant</option><option value="cogerant">Cogérant</option><option value="administrateur_general">Administrateur général</option><option value="administrateur">Administrateur</option><option value="pca">PCA</option><option value="pdg">PDG</option><option value="dg">DG</option><option value="commandite">Commandité</option></select>
    <input name="dirigeants[0][date_nomination]" type="date" aria-label="Date de nomination">
    <input name="dirigeants[0][fin_mandat]" type="date" aria-label="Fin du mandat">
    <button type="button" class="button secondary" data-remove>Supprimer</button>
</div>
</div>
<button type="button" class="button secondary" data-add="dirigeants">Ajouter un dirigeant</button></div>
<div class="actions"><button type="submit">Enregistrer et ouvrir le dossier</button></div>
</form>
<?= $this->endSection() ?>
