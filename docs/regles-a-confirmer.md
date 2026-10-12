# Règles juridiques à confirmer

Les valeurs ci-dessous sont volontairement marquées `verifie = 0`. Elles ne
doivent pas être présentées comme des conclusions juridiques définitives.

## Délais

- Formes SARL, SNC, SCS, SCA, SC et SARLU : délais de convocation et de seconde convocation non sourcés.
- Fichier : `app/Database/Seeds/RegleDelaiSeeder.php`.

## Quorums et majorités

- SARL, SNC, SCS, SCA, SC et SARLU : quorums et majorités des AGO/AGE, y compris les secondes convocations.
- Fichier : `app/Database/Seeds/RegleQuorumSeeder.php`.

## Validations de création

- Parité du nombre d’administrateurs SA : avertissement à confirmer.
- Les règles SA, SNC et durée maximale sont seedées avec une source identifiée, mais doivent être relues par un juriste avant production.
- Fichier : `app/Database/Seeds/RegleValidationSeeder.php`.

## Statuts

- Le gabarit SCA porte un bandeau « à confirmer ».
- Les mentions d’article 10 restent affichées avec le statut fourni par le service.
- Fichiers : `app/Views/pdf/statuts/sca.php`, `app/Libraries/StatutsService.php`.
