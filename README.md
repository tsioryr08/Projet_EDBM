# Legaltech EDBM — Création et vie juridique des sociétés

Application web CodeIgniter 4 / PHP destinée à accompagner la création d’une société à Madagascar et le suivi de sa vie juridique :

- création de société et choix de la forme sociale ;
- associés, dirigeants, siège et capital ;
- checklist des pièces à déposer à l’EDBM ;
- génération des statuts et documents constitutifs en PDF ;
- planification des assemblées et conseils d’administration ;
- convocations, quorum, votes et procès-verbaux ;
- suivi du dossier : brouillon, soumis, validé, immatriculé.

> **État documenté le 12 octobre 2026.** Les lots 0 à 4 sont committés sur `feature-oli`.
> Ce README intègre l’état des lieux réalisé en lecture seule. Les éléments marqués « partiel » ou « absent » ne doivent pas être considérés comme terminés.

## Principes métier

1. Les délais, quorums, majorités et documents requis doivent provenir de la base.
2. Une règle manquante doit produire un message explicite ; elle ne doit pas être inventée.
3. Chaque règle affichée dans un document doit indiquer sa source et son statut : vérifié, à confirmer ou déduction.
4. Les services métier sont la source de vérité pour les dates, quorums, majorités et résultats de vote.
5. Les PDF doivent être produits depuis les données persistées ou depuis un JSON d’exemple clairement identifié.

## Installation et lancement

### Prérequis

- PHP 8.2 ou supérieur ;
- extensions PHP `intl`, `mbstring`, `json`, `mysqlnd` ;
- MariaDB/MySQL ;
- Composer ;
- Dompdf.

### Dépendances principales

Le projet utilise :

- CodeIgniter 4.7.2 ;
- PHP 8.2 ;
- Dompdf 3.1.6, requis dans [`composer.json`](composer.json) par `dompdf/dompdf: ^3.0` ;
- PHPUnit 11.5.57.

Commandes usuelles :

```bash
composer install
php spark serve
php spark routes
php spark migrate:status
```

Les migrations historiques observées sont appliquées :

1. `CreateReferentiels`
2. `CreateRegles`
3. `CreateSociete`
4. `CreateReunions`
5. `AjouterJustificationEtVoixPreponderante`

Migrations ajoutées par la réparation, à appliquer sur la base de test puis sur les environnements validés :

6. `2026-10-12-100006_CreateDossierStatutHistorique`
7. `2026-10-12-100007_CreateRegleValidation`

> Ne pas lancer de migration ou de seeder sur un environnement existant sans validation préalable.

## Parcours utilisateur

### Phase A — Création de la société

| Étape | État | Routes / fichiers |
|---|---|---|
| A0 — Dénomination | **Fait sous conditions** | [`Societes.php`](app/Controllers/Societes.php), [`form.php`](app/Views/societe/form.php) |
| A1 — Forme et formulaire | **Fait sous conditions** | [`Societes.php`](app/Controllers/Societes.php), [`societe/`](app/Views/societe/) |
| A2 — Validations | **Fait après migration/seeder** | [`DossierCreationService.php`](app/Libraries/DossierCreationService.php), `regle_validation` |
| A3 — Checklist EDBM | **Fait après migration/seeder** | [`DossierService.php`](app/Libraries/DossierService.php), [`dossier/index.php`](app/Views/dossier/index.php) |
| A4 — Statuts PDF | **Fait sous conditions** | [`Statuts.php`](app/Controllers/Statuts.php), [`StatutsService.php`](app/Libraries/StatutsService.php) |
| A5 — Documents constitutifs | **Fait sous conditions** | [`Constituants.php`](app/Controllers/Constituants.php), [`pdf/constitutifs/`](app/Views/pdf/constitutifs/) |
| A6 — Suivi du dossier | **Fait après migration** | [`Dossiers.php`](app/Controllers/Dossiers.php), [`SuiviDossierService.php`](app/Libraries/SuiviDossierService.php) |

Routes principales :

```text
GET  /societes/type
GET  /societes/nouveau
POST /societes
GET  /dossiers/{societeId}
POST /dossiers/{societeId}/pieces/{documentId}
POST /dossiers/{societeId}/statut
GET  /pdf/statuts/{societeId}
GET  /pdf/constitutifs/{societeId}/{document}
```

### Phase B — Vie juridique

| Étape | État | Routes / fichiers |
|---|---|---|
| B1 — Planification | **Partiel** | [`phase_b_planifier.php`](app/Views/dossier/phase_b_planifier.php) |
| B2 — Convocation PDF | **Fonctionne sous conditions** | [`Convocations.php`](app/Controllers/Convocations.php) |
| B3 — Jour J | **Partiel** | [`phase_b_jour_j.php`](app/Views/dossier/phase_b_jour_j.php) |
| B4 — Deuxième convocation | **Fait sous conditions** | [`ReunionService.php`](app/Libraries/ReunionService.php), [`phase_b_jour_j.php`](app/Views/dossier/phase_b_jour_j.php) |
| B5 — PV PDF | **Fonctionne sous conditions** | [`ProcesVerbaux.php`](app/Controllers/ProcesVerbaux.php) |
| B6 — Registre | **Partiel** | [`phase_b_registre.php`](app/Views/dossier/phase_b_registre.php) |
| B7 — Justification juridique | **Partiel** | [`Justification.php`](app/Libraries/Justification.php), vues PDF |
| B8 — SARLU | **Fait sous conditions** | [`DecisionAssocieUniqueService.php`](app/Libraries/DecisionAssocieUniqueService.php), route `/dossiers/{id}/sarlu/decision` |

Routes principales :

```text
GET  /dossiers/{societeId}/phase-b
GET  /dossiers/{societeId}/phase-b/planifier
POST /dossiers/{societeId}/phase-b/planifier
GET  /dossiers/{societeId}/phase-b/jour-j
POST /dossiers/{societeId}/phase-b/tenir
GET  /dossiers/{societeId}/phase-b/votes
POST /dossiers/{societeId}/phase-b/voter
GET  /dossiers/{societeId}/phase-b/registre
GET  /pdf/convocations/{reunionId}
GET  /pdf/proces-verbaux/{assembleeId}
```

## Arborescence fonctionnelle

### Contrôleurs

| Fichier | Rôle |
|---|---|
| [`Home.php`](app/Controllers/Home.php) | Accueil. |
| [`Societes.php`](app/Controllers/Societes.php) | Choix de forme et création. |
| [`Dossiers.php`](app/Controllers/Dossiers.php) | Dossier, checklist, suivi et Phase B. |
| [`Statuts.php`](app/Controllers/Statuts.php) | Statuts PDF. |
| [`Constituants.php`](app/Controllers/Constituants.php) | Documents constitutifs PDF. |
| [`Convocations.php`](app/Controllers/Convocations.php) | Convocations PDF. |
| [`ProcesVerbaux.php`](app/Controllers/ProcesVerbaux.php) | PV PDF. |
| [`PdfDemo.php`](app/Controllers/PdfDemo.php) | PDF de démonstration depuis un JSON. |

### Modèles

Les modèles de [`app/Models/`](app/Models/) couvrent les sociétés, formes sociales, associés, dirigeants, réunions, assemblées, participants, pouvoirs, résolutions, règles, dossiers, pièces, étapes et documents générés.

Le répertoire [`app/Entities/`](app/Entities/) ne contient pas d’entité métier.

### Services

| Service | Responsabilité |
|---|---|
| [`ReunionService.php`](app/Libraries/ReunionService.php) | Planification, délais, dates de convocation et convoqués. |
| [`AssembleeService.php`](app/Libraries/AssembleeService.php) | Tenue, quorum, votes et départage. |
| [`SocieteService.php`](app/Libraries/SocieteService.php) | Objet `societe` du contrat d’interface. |
| [`DossierService.php`](app/Libraries/DossierService.php) | Checklist issue de la base et pièces fournies. |
| [`DocumentChecklistService.php`](app/Libraries/DocumentChecklistService.php) | Checklist alternative avec templates PHP. |
| [`DossierCreationService.php`](app/Libraries/DossierCreationService.php) | Contrôles de validité de création. |
| [`SuiviDossierService.php`](app/Libraries/SuiviDossierService.php) | Statut du dossier. |
| [`PhaseBService.php`](app/Libraries/PhaseBService.php) | Façade des écrans Phase B. |
| [`CalculRegles.php`](app/Libraries/CalculRegles.php) | Calculs génériques. |
| [`Justification.php`](app/Libraries/Justification.php) | Motifs, sources et statuts juridiques. |
| [`PdfService.php`](app/Libraries/PdfService.php) | HTML vers PDF et enregistrement. |
| [`StatutsService.php`](app/Libraries/StatutsService.php) | Sélection des gabarits de statuts. |
| [`ConvocationService.php`](app/Libraries/ConvocationService.php) | Données de convocation. |
| [`ProcesVerbalService.php`](app/Libraries/ProcesVerbalService.php) | Données de PV depuis `versJson()`. |

### Commandes de démonstration

Les commandes sont dans [`app/Commands/`](app/Commands/) :

`DemoSociete`, `DemoDossier`, `DemoChecklist`, `DemoConvocation`, `DemoDeuxiemeConvocation`, `DemoAssemblee`, `DemoPv`, `DemoPhaseB` et `DemoSuiviDossier`.

## Base de données

### Tables réellement créées

Les migrations créent les tables métier suivantes :

| Groupe | Tables |
|---|---|
| Référentiels | `type_societe`, `nature_reunion`, `jour_ferie`, `etape_creation` |
| Règles | `regle_delai`, `regle_quorum`, `document_requis`, `document_type_societe` |
| Création | `societe`, `associe`, `dirigeant`, `commissaire_aux_comptes`, `dossier`, `piece_fournie` |
| Phase B | `reunion`, `convoque`, `assemblee`, `participant`, `pouvoir`, `resolution`, `vote` |
| Documents | `document_genere` |

La table technique `migrations` est également présente. Le plan annonçait 22 tables métier ; la table `vote` est bien créée dans la migration Phase B.

Migrations :

- [`CreateReferentiels.php`](app/Database/Migrations/2026-10-11-100001_CreateReferentiels.php)
- [`CreateRegles.php`](app/Database/Migrations/2026-10-11-100002_CreateRegles.php)
- [`CreateSociete.php`](app/Database/Migrations/2026-10-11-100003_CreateSociete.php)
- [`CreateReunions.php`](app/Database/Migrations/2026-10-11-100004_CreateReunions.php)
- [`AjouterJustificationEtVoixPreponderante.php`](app/Database/Migrations/2026-10-11-100005_AjouterJustificationEtVoixPreponderante.php)

### Seeders

- [`TypeSocieteSeeder.php`](app/Database/Seeds/TypeSocieteSeeder.php) : SA, SARL, SARLU, SCS, SCA, SNC, SC.
- [`NatureReunionSeeder.php`](app/Database/Seeds/NatureReunionSeeder.php) : AGC, AGO, AGE, CA.
- [`EtapeCreationSeeder.php`](app/Database/Seeds/EtapeCreationSeeder.php) : étapes EDBM.
- [`RegleDelaiSeeder.php`](app/Database/Seeds/RegleDelaiSeeder.php) : 18 règles ; couverture complète non démontrée pour toutes les formes et convocations.
- [`RegleQuorumSeeder.php`](app/Database/Seeds/RegleQuorumSeeder.php) : 19 règles ; SA vérifiée, autres formes principalement à confirmer.
- [`DocumentRequisSeeder.php`](app/Database/Seeds/DocumentRequisSeeder.php) : 33 documents requis et 129 associations forme/document.

Les règles SARL, SARLU, SNC, SCS, SCA et SC contiennent encore des valeurs à confirmer ou à chercher. Cela est préférable à l’invention d’une règle, mais empêche de considérer ces parcours comme complets.

## Contrat d’interface métier

### `ReunionService::planifier()`

Signature :

```php
planifier(
    int $societeId,
    string $nature,
    string $dateReunion,
    string $heure,
    string $lieu,
    array $ordreDuJour = [],
    int $numeroConvocation = 1,
    ?string $modeCalcul = null,
    ?int $delaiStatuts = null,
    bool $enregistrer = true,
    ?int $reunionPrecedenteId = null
): array
```

Champs renvoyés :

`societe`, `type`, `nature`, `numero_convocation`, `reunion_precedente_id`, `date_reunion`, `heure`, `lieu`, `ordre_du_jour`, `delai_jours`, `mode_calcul`, `date_convocation`, `forme_envoi`, `source`, `verifie`, `justification`, `en_retard`, puis `reunion_id` et `convoques` lorsque la réunion est enregistrée.

### `AssembleeService::versJson()`

Renvoie la société, la nature, la convocation, la date et le lieu, l’ordre du jour, la tenue, le président, le secrétaire, les voix, le quorum, la majorité, les participants et les résolutions.

Chaque résolution peut contenir :

- `pour`, `contre`, `adoptee` ;
- vote du président ;
- voix prépondérante ;
- motif ;
- source ;
- statut de la règle ;
- mention à insérer dans le PV.

### Objet `societe`

[`SocieteService::versJson()`](app/Libraries/SocieteService.php) renvoie :

- forme sociale et mode d’administration ;
- dénomination, siège, objet, capital et durée ;
- activité ;
- associés, apports, titres et pourcentages ;
- dirigeants et mandats ;
- commissaires aux comptes ;
- paramètres de la forme sociale ;
- statut du dossier.

### Checklist

[`DossierService::afficher()`](app/Libraries/DossierService.php) renvoie la société, le dossier, les documents requis, les pièces associées, leur état (`manquante`, `fournie`, `périmée`) et les étapes EDBM.

## Génération PDF

### Moteur

[`PdfService.php`](app/Libraries/PdfService.php) utilise Dompdf, enregistre les fichiers dans `writable/documents` et crée une ligne dans `document_genere`.

### Gabarits disponibles

| Type | Gabarits |
|---|---|
| Statuts | SARL/SARLU, SA administrateur général, SA conseil d’administration, SCS/SNC, SCA, SC |
| Convocations | AG et CA, gabarit commun |
| PV | AGO, AGE, AGC et CA via gabarits assemblée/CA/constitutif |
| Constitutifs | PV constitutif, souscription, déclaration, nomination et décision SARLU via gabarit commun |

Le gabarit SCA est disponible et porte un bandeau « à confirmer » dans [`sca.php`](app/Views/pdf/statuts/sca.php).

Le PDF de démonstration utilise [`writable/exemples/convocation.json`](writable/exemples/convocation.json). Les autres documents sont normalement alimentés par les services et les données de la base.

## Règles juridiques externalisées et points à vérifier

Les emplacements principaux sont :

### [`DossierCreationService.php`](app/Libraries/DossierCreationService.php)

Les contrôles de création sont désormais lus depuis `regle_validation`, créée par
`2026-10-12-100007_CreateRegleValidation` et alimentée par
[`RegleValidationSeeder.php`](app/Database/Seeds/RegleValidationSeeder.php).

### [`ReunionService.php`](app/Libraries/ReunionService.php)

Le délai de deuxième convocation est lu depuis `regle_delai`. Une règle absente
produit une erreur explicite.

### [`DocumentChecklistService.php`](app/Libraries/DocumentChecklistService.php)

Les documents, durées de validité et conditions sont lus depuis `document_requis`
et `document_type_societe`.

### [`CreateSociete.php`](app/Database/Migrations/2026-10-11-100003_CreateSociete.php)

Le schéma historique conserve un commentaire de compatibilité ; le contrôle
applicatif est lu depuis `regle_validation`.

## Problèmes connus

### Points restant à vérifier en base réelle

- appliquer `dossier_statut_historique` avant d’utiliser l’historique ;
- appliquer `regle_validation` et relancer les seeders ;
- exécuter les parcours HTTP et les téléchargements PDF sur une base de test peuplée.

## Tests

Tests présents :

- [`DocumentChecklistServiceTest.php`](tests/unit/DocumentChecklistServiceTest.php)
- [`PvServiceTest.php`](tests/unit/PvServiceTest.php)
- [`HealthTest.php`](tests/unit/HealthTest.php)
- [`ExampleDatabaseTest.php`](tests/database/ExampleDatabaseTest.php)
- [`ExampleSessionTest.php`](tests/session/ExampleSessionTest.php)
- [`WorkflowSurfaceTest.php`](tests/unit/WorkflowSurfaceTest.php)

Résultat observé :

- 12 tests exécutés ;
- 42 assertions ;
- `composer test` passe avec `--no-coverage`.

La couverture actuelle ne valide pas encore suffisamment :

- le parcours HTTP A0-A6 ;
- les téléchargements PDF ;
- les transitions du dossier ;
- la deuxième convocation ;
- le lien registre/PV ;
- les parcours SARL, SA, SARLU et SCA ;
- la cohérence avec une base réellement peuplée.

## État des tâches T1-T10 et O1-O8

| Tâche | État |
|---|---|
| T1 — ordre du jour et séance | Fait |
| T2 — objet `societe` | Fait, à vérifier sur tous les champs |
| T3 — contrôles de validité | Fait après migration/seeder `regle_validation` |
| T4 — checklist | Fait, source unique DB via `DossierService`/adaptateur |
| T5 — justifications juridiques | Fait sous conditions |
| T6 — deuxième convocation | Fait sous conditions, règle 2e convocation requise |
| T7 — suivi de dossier | Fait après migration historique |
| T8 — écrans Phase B | Fait sous conditions, calculs dans les services |
| T9 — voix prépondérante du CA | Fait sous conditions |
| T10 — règles restantes | Fait en partie, éléments listés dans `docs/regles-a-confirmer.md` |
| O1 — moteur PDF | Fait |
| O2 — statuts PDF | Fait sous conditions, SCA signalée à confirmer |
| O3 — convocations PDF | Fait sous conditions |
| O4 — PV PDF | Fait sous conditions |
| O5 — écrans Phase A | Fait sous conditions |
| O6 — suivi du dossier | Fait après migration historique |
| O7 — documents constitutifs | Fait sous conditions |
| O8 — générateur de bail | Partiel : lien externe optionnel uniquement |

Les commits visibles permettent d’identifier Tsiory comme auteur de la majorité de la logique métier et Oli comme auteur d’au moins un commit d’interface, mais ne permettent pas d’attribuer avec certitude chaque tâche individuellement.

## Git et sécurité

État observé :

- branche courante : `feature-oli` ;
- branche distante : `origin/feature-oli` ;
- quatre commits de réparation locale sont présents après l’état initial ;
- `.env` n’est plus suivi par Git et les fichiers runtime sont ignorés ;
- les secrets ayant existé dans l’historique doivent être renouvelés par l’opérateur.

Avant toute publication :

1. remplacer les secrets historiques ;
2. appliquer les deux migrations ajoutées puis les seeders ;
3. ne pas commiter les PDFs générés, sessions ou logs ;
4. pousser avec :

```bash
git push --set-upstream origin feature-oli
```

`feature-oli` doit être proposée vers `dev`, jamais vers `main`.

## Priorités avant mise en production

1. Appliquer les deux migrations et les seeders sur une base de test dédiée.
2. Exécuter les parcours HTTP et PDF avec un exemple SA puis SARL.
3. Vérifier les règles `à confirmer` avec un juriste.
4. Ajouter une vraie suite d’intégration avec MariaDB de test.
5. Renouveler les secrets qui ont existé dans l’historique Git.
6. Relire le diff des quatre commits de réparation.
7. Préparer la pull request `feature-oli` vers `dev`.

## Conclusion

Le socle technique, Dompdf, les services métier et les écrans principaux sont
présents. La validation sur une base MariaDB peuplée reste indispensable avant
de considérer le workflow prêt pour la production.
