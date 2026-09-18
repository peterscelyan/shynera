<?php
/* ==========================================================================
   SHYNERA — connexion a la base de donnees
   --------------------------------------------------------------------------
   En ligne : MySQL, avec les identifiants de config.php.
   Sur l'ordinateur pendant les tests : un simple fichier SQLite, active par
   la variable d'environnement SHYNERA_TEST. Le reste du code est identique.
   ========================================================================== */

declare(strict_types=1);

date_default_timezone_set('Europe/Brussels');

function bdd(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    if (getenv('SHYNERA_TEST') === '1') {
        $fichier = getenv('SHYNERA_TEST_BDD') ?: __DIR__ . '/../../test.sqlite';
        $pdo = new PDO('sqlite:' . $fichier, null, null, $options);
        $pdo->exec('PRAGMA foreign_keys = ON');
        return $pdo;
    }

    $chemin = __DIR__ . '/config.php';
    if (!is_file($chemin)) {
        throw new RuntimeException('Le fichier espace/config.php est manquant. Copie config-exemple.php et remplis le mot de passe.');
    }
    $config = require $chemin;

    $dsn = 'mysql:host=' . $config['hote'] . ';dbname=' . $config['nom'] . ';charset=utf8mb4';
    $pdo = new PDO($dsn, $config['utilisateur'], $config['mot_de_passe'], $options);
    $pdo->exec("SET time_zone = '+01:00'");
    return $pdo;
}

/* "mysql" en ligne, "sqlite" pendant les tests. */
function pilote(): string
{
    return bdd()->getAttribute(PDO::ATTR_DRIVER_NAME);
}

/* Les tables, ecrites pour MySQL et pour SQLite. */
function tables_sql(): array
{
    $auto = pilote() === 'sqlite'
        ? 'INTEGER PRIMARY KEY AUTOINCREMENT'
        : 'INT AUTO_INCREMENT PRIMARY KEY';

    return [
        "CREATE TABLE IF NOT EXISTS travailleurs (
            id $auto,
            nom VARCHAR(80) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            mot_de_passe VARCHAR(255) NOT NULL,
            actif INT NOT NULL DEFAULT 1,
            cree_le DATETIME NOT NULL
        )",

        /* Horaires qui reviennent chaque semaine. jour : 1 = lundi ... 7 = dimanche. */
        "CREATE TABLE IF NOT EXISTS disponibilites (
            id $auto,
            travailleur_id INT NOT NULL,
            jour INT NOT NULL,
            debut TIME NOT NULL,
            fin TIME NOT NULL
        )",

        /* Conges ("ferme") et journees ajoutees exceptionnellement ("ouvert"). */
        "CREATE TABLE IF NOT EXISTS exceptions (
            id $auto,
            travailleur_id INT NOT NULL,
            jour_date DATE NOT NULL,
            genre VARCHAR(10) NOT NULL,
            debut TIME NULL,
            fin TIME NULL,
            motif VARCHAR(120) NULL
        )",

        /* Rendez-vous confirmes (utilisee a partir de l'etape suivante). */
        "CREATE TABLE IF NOT EXISTS reservations (
            id $auto,
            travailleur_id INT NOT NULL,
            debut DATETIME NOT NULL,
            fin DATETIME NOT NULL,
            prestation VARCHAR(60) NOT NULL,
            vehicule VARCHAR(40) NOT NULL,
            supplements VARCHAR(255) NULL,
            prix INT NOT NULL,
            code_postal VARCHAR(10) NOT NULL,
            adresse VARCHAR(255) NULL,
            client_nom VARCHAR(120) NOT NULL,
            client_email VARCHAR(190) NOT NULL,
            client_telephone VARCHAR(40) NULL,
            remarque TEXT NULL,
            statut VARCHAR(20) NOT NULL DEFAULT 'confirmee',
            jeton VARCHAR(64) NOT NULL,
            cree_le DATETIME NOT NULL
        )",
    ];
}

/* Colonnes ajoutees apres coup. On essaie de les creer : si elles existent
   deja, la base renvoie une erreur qu'on ignore. Cela evite d'avoir a
   toucher a la base a la main apres une mise a jour du site. */
function mettre_a_jour_tables(): void
{
    $ajouts = [
        'ALTER TABLE reservations ADD COLUMN paye INT NOT NULL DEFAULT 0',
    ];
    foreach ($ajouts as $sql) {
        try {
            bdd()->exec($sql);
        } catch (PDOException $e) {
            /* colonne deja presente */
        }
    }
}

function creer_tables(): void
{
    foreach (tables_sql() as $sql) {
        bdd()->exec($sql);
    }
    mettre_a_jour_tables();
    /* Index : retrouver vite les lignes d'un travailleur. MySQL n'accepte pas
       "IF NOT EXISTS" ici, donc on ignore l'erreur si l'index existe deja. */
    $index = [
        'CREATE INDEX idx_reservations_travailleur ON reservations (travailleur_id, debut)',
        'CREATE INDEX idx_disponibilites_travailleur ON disponibilites (travailleur_id, jour)',
        'CREATE INDEX idx_exceptions_travailleur ON exceptions (travailleur_id, jour_date)',
    ];
    foreach ($index as $sql) {
        try {
            bdd()->exec($sql);
        } catch (PDOException $e) {
            /* index deja present : rien a faire */
        }
    }
}
