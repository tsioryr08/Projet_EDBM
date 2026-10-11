# Lots 1 et 2 — migrations, seeders et calculs (CodeIgniter 4)

Copier dans le projet CI4 :
- `app/Database/Migrations/*`  → `app/Database/Migrations/`
- `app/Database/Seeds/*`       → `app/Database/Seeds/`
- `app/Libraries/CalculRegles.php` → `app/Libraries/`
- `tests/unit/CalculReglesTest.php` → `tests/unit/` (PHPUnit)

Créer la base (une fois) : `CREATE DATABASE legaltech_societe CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
Renseigner `.env` (database.default.*), puis :

    php spark migrate
    php spark db:seed DatabaseSeeder

Repartir de zéro : `php spark migrate:refresh` puis `php spark db:seed DatabaseSeeder`.
Ajouter une règle trouvée plus tard : nouvelle ligne dans RegleDelaiSeeder / RegleQuorumSeeder (ou un nouveau seeder), sans toucher au code.

Prérequis : CI4 ≥ 4.2 (RawSql), MySQL ≥ 8.0.16 ou MariaDB ≥ 10.2.1 (contrainte CHECK).
Ces fichiers n'ont pas pu être exécutés ici (pas de PHP dans mon environnement) : lance `php spark migrate` et PHPUnit chez toi.
