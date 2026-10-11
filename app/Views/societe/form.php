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
<h2>Premier associé</h2>
<input name="associes[0][nom]" placeholder="Nom">
<select name="associes[0][categorie]"><option value="physique">Personne physique</option><option value="morale_malagasy">Personne morale malagasy</option><option value="morale_etrangere">Personne morale étrangère</option></select>
<input name="associes[0][apport]" placeholder="Apport">
<input name="associes[0][nb_titres]" placeholder="Nombre de titres">
<h2>Premier dirigeant</h2>
<input name="dirigeants[0][nom]" placeholder="Nom">
<select name="dirigeants[0][fonction]"><option value="gerant">Gérant</option><option value="cogerant">Cogérant</option><option value="administrateur_general">Administrateur général</option><option value="administrateur">Administrateur</option><option value="pca">PCA</option><option value="pdg">PDG</option><option value="dg">DG</option><option value="commandite">Commandité</option></select>
<button type="submit">Enregistrer</button>
</form>
<?= $this->endSection() ?>
