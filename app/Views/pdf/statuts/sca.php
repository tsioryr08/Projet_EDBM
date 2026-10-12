<?= view('pdf/statuts/_identite', ['societe' => $societe, 'type' => $type, 'forme' => $forme]) ?>
<div class="notice"><strong>À CONFIRMER :</strong> les règles propres à la SCA ne sont pas toutes sourcées dans la base. Vérifiez ce document avant dépôt.</div>
<?= view('pdf/statuts/_article10', ['mentions' => $article_10]) ?>
<?= view('pdf/statuts/_associes', ['associes' => $associes]) ?>
<?= view('pdf/statuts/_dirigeants', ['dirigeants' => $dirigeants]) ?>
<h2>Gouvernance</h2>
<p><?= esc($gouvernance) ?></p>
