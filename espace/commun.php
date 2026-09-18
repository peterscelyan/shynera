<?php
/* ==========================================================================
   SHYNERA — outils communs a l'espace pro
   Session, securite des formulaires, et habillage des pages.
   ========================================================================== */

declare(strict_types=1);

require_once __DIR__ . '/bdd.php';
require_once __DIR__ . '/prix.php';   /* pour version() : numero de version des fichiers */

/* --- Session -------------------------------------------------------------- */

function demarrer_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => $https,
        'samesite' => 'Lax',
    ]);
    session_name('shynera_espace');
    session_start();
}

function connecte(): ?array
{
    demarrer_session();
    if (empty($_SESSION['travailleur_id'])) {
        return null;
    }
    $requete = bdd()->prepare('SELECT * FROM travailleurs WHERE id = ? AND actif = 1');
    $requete->execute([$_SESSION['travailleur_id']]);
    $travailleur = $requete->fetch();
    return $travailleur ?: null;
}

function exiger_connexion(): array
{
    $travailleur = connecte();
    if (!$travailleur) {
        header('Location: connexion.php');
        exit;
    }
    return $travailleur;
}

/* --- Securite ------------------------------------------------------------- */

/* Jeton anti-CSRF : empeche un autre site de soumettre un formulaire a ta place. */
function jeton(): string
{
    demarrer_session();
    if (empty($_SESSION['jeton'])) {
        $_SESSION['jeton'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['jeton'];
}

function champ_jeton(): string
{
    return '<input type="hidden" name="jeton" value="' . h(jeton()) . '">';
}

function verifier_jeton(): void
{
    demarrer_session();
    $envoye = $_POST['jeton'] ?? '';
    if (!is_string($envoye) || !hash_equals($_SESSION['jeton'] ?? '', $envoye)) {
        http_response_code(400);
        exit('Formulaire expiré. Reviens en arrière et recommence.');
    }
}

/* Echappe le texte avant de l'afficher : protege contre le code injecte. */
function h(?string $texte): string
{
    return htmlspecialchars((string) $texte, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* --- Petits utilitaires --------------------------------------------------- */

function JOURS(): array
{
    return [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'];
}

function heure_valide(string $heure): bool
{
    return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $heure);
}

function date_valide(string $date): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function message(string $texte, string $genre = 'ok'): void
{
    demarrer_session();
    $_SESSION['message'] = ['texte' => $texte, 'genre' => $genre];
}

function afficher_message(): string
{
    demarrer_session();
    if (empty($_SESSION['message'])) {
        return '';
    }
    $m = $_SESSION['message'];
    unset($_SESSION['message']);
    return '<p class="espace__message espace__message--' . h($m['genre']) . '">' . h($m['texte']) . '</p>';
}

/* --- Habillage des pages -------------------------------------------------- */

function entete(string $titre, ?array $travailleur = null): void
{
    ?><!doctype html>
<html lang="fr-BE">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= h($titre) ?> — Espace Shynera</title>
<link rel="icon" type="image/svg+xml" href="../favicon.svg">
<link rel="stylesheet" href="<?= version('style.css') ?>">
<link rel="stylesheet" href="<?= version('espace/espace.css') ?>">
</head>
<body class="espace">
<header class="espace__entete">
  <div class="enveloppe espace__entete-interieur">
    <a href="index.php" class="marque"><span class="marque__mot">Shynera</span><span class="marque__trait" aria-hidden="true"></span></a>
    <?php if ($travailleur): ?>
      <nav class="espace__nav" aria-label="Espace pro">
        <a href="index.php">Tableau de bord</a>
        <a href="disponibilites.php">Mes disponibilités</a>
        <span class="espace__qui"><?= h($travailleur['nom']) ?></span>
        <a href="deconnexion.php" class="espace__deconnexion">Se déconnecter</a>
      </nav>
    <?php endif; ?>
  </div>
</header>
<main class="enveloppe espace__contenu">
<?= afficher_message() ?>
<?php
}

function pied(): void
{
    ?>
</main>
</body>
</html>
<?php
}
