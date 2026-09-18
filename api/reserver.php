<?php
/* ==========================================================================
   SHYNERA — enregistrement d'une reservation
   --------------------------------------------------------------------------
   Recoit le choix du client, verifie TOUT de son cote (le navigateur peut
   mentir), enregistre le rendez-vous, puis envoie les deux e-mails.
   ========================================================================== */

declare(strict_types=1);

require_once __DIR__ . '/../espace/planning.php';
require_once __DIR__ . '/../espace/prix.php';
require_once __DIR__ . '/../espace/courriel.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function repondre(array $donnees, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($donnees, JSON_UNESCAPED_UNICODE);
    exit;
}

function refuser(string $message, int $code = 400): never
{
    repondre(['ok' => false, 'message' => $message], $code);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    refuser('Méthode non autorisée.', 405);
}

$corps = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($corps)) {
    refuser('Requête illisible.');
}

/* --- Piege a robots -------------------------------------------------------- */
if (!empty($corps['site_web'])) {
    repondre(['ok' => true]);
}

/* --- Ce qu'a choisi le client ---------------------------------------------- */
$codePrestation = (string) ($corps['prestation'] ?? '');
$codeVehicule   = (string) ($corps['vehicule'] ?? '');
$supplements    = array_values(array_filter((array) ($corps['supplements'] ?? []), 'is_string'));
$codePostal     = preg_replace('/\D/', '', (string) ($corps['code_postal'] ?? ''));
$debutTexte     = (string) ($corps['debut'] ?? '');
$travailleurVoulu = (int) ($corps['travailleur'] ?? 0);

$nom        = trim((string) ($corps['nom'] ?? ''));
$email      = trim((string) ($corps['email'] ?? ''));
$telephone  = trim((string) ($corps['telephone'] ?? ''));
$adresse    = trim((string) ($corps['adresse'] ?? ''));
$remarque   = trim((string) ($corps['remarque'] ?? ''));

$prestation = prestation($codePrestation);
$vehicule   = vehicule($codeVehicule);

if (!$prestation || !$prestation['disponible'] || !$vehicule) {
    refuser('Prestation ou véhicule inconnu.');
}
if ($nom === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    refuser('Indiquez votre nom et une adresse e-mail valide.');
}
if (mb_strlen($nom) > 120 || mb_strlen($telephone) > 40 || mb_strlen($adresse) > 255 || mb_strlen($remarque) > 2000) {
    refuser('Un des champs est trop long.');
}

/* --- Zone d'intervention ---------------------------------------------------- */
$zones = require __DIR__ . '/../zones.php';
$commune = null;
foreach ($zones as $nomCommune => $codes) {
    if (in_array((int) $codePostal, $codes, true)) {
        $commune = $nomCommune;
        break;
    }
}
if ($commune === null) {
    refuser("Je n'interviens pas encore dans cette commune.");
}

/* --- Le creneau demande ------------------------------------------------------ */
if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $debutTexte)) {
    refuser('Créneau illisible.');
}
$debut = DateTimeImmutable::createFromFormat('Y-m-d H:i', $debutTexte);
if (!$debut) {
    refuser('Créneau illisible.');
}
$fin = $debut->modify('+' . (int) $prestation['duree'] . ' minutes');

/* --- Qui est libre a ce moment-la ? ------------------------------------------ */
$jours = creneaux_libres((int) $prestation['duree'], $debut->format('Y-m-d'), 1);
$libres = [];
foreach ($jours as $jour) {
    foreach ($jour['creneaux'] as $creneau) {
        if ($creneau['heure'] === $debut->format('H:i')) {
            $libres = $creneau['travailleurs'];
        }
    }
}
if (!$libres) {
    refuser("Ce créneau vient d'être pris. Choisissez-en un autre.", 409);
}

$travailleurId = ($travailleurVoulu && in_array($travailleurVoulu, $libres, true))
    ? $travailleurVoulu
    : $libres[0];

/* --- Prix calcule ici, jamais celui envoye par le navigateur ----------------- */
$detail = detail_achat($codePrestation, $codeVehicule, $supplements);

$nomsSupplements = [];
foreach (tarifs()['supplements'] as $supplement) {
    if (in_array($supplement['code'], $supplements, true)) {
        $nomsSupplements[] = $supplement['nom'];
    }
}

/* --- Enregistrement ---------------------------------------------------------- */
$jeton = bin2hex(random_bytes(16));

try {
    $pdo = bdd();
    $pdo->beginTransaction();

    /* Dernier controle, au cas ou quelqu'un aurait reserve pendant ce temps. */
    $requete = $pdo->prepare(
        "SELECT COUNT(*) FROM reservations
         WHERE travailleur_id = ? AND statut = 'confirmee' AND debut < ? AND fin > ?"
    );
    $marge = reglages()['marge_trajet'];
    $requete->execute([
        $travailleurId,
        $fin->modify('+' . $marge . ' minutes')->format('Y-m-d H:i:s'),
        $debut->modify('-' . $marge . ' minutes')->format('Y-m-d H:i:s'),
    ]);
    if ((int) $requete->fetchColumn() > 0) {
        $pdo->rollBack();
        refuser("Ce créneau vient d'être pris. Choisissez-en un autre.", 409);
    }

    $requete = $pdo->prepare(
        'INSERT INTO reservations
         (travailleur_id, debut, fin, prestation, vehicule, supplements, prix, code_postal, adresse,
          client_nom, client_email, client_telephone, remarque, statut, jeton, cree_le)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $requete->execute([
        $travailleurId,
        $debut->format('Y-m-d H:i:s'),
        $fin->format('Y-m-d H:i:s'),
        $prestation['titre'],
        $vehicule['nom'],
        $nomsSupplements ? implode(', ', $nomsSupplements) : null,
        $detail['total'],
        $codePostal,
        $adresse !== '' ? $adresse : null,
        $nom,
        $email,
        $telephone !== '' ? $telephone : null,
        $remarque !== '' ? $remarque : null,
        'confirmee',
        $jeton,
        date('Y-m-d H:i:s'),
    ]);

    $pdo->commit();
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Shynera reservation : ' . $e->getMessage());
    refuser("La réservation n'a pas pu être enregistrée. Réessayez dans un instant.", 500);
}

/* --- Les deux e-mails --------------------------------------------------------- */
$requete = bdd()->prepare('SELECT nom, email FROM travailleurs WHERE id = ?');
$requete->execute([$travailleurId]);
$travailleur = $requete->fetch() ?: ['nom' => 'Shynera', 'email' => $email];

$quand = strftime_fr($debut);
$site  = (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'shynera.be');

$resume = "Prestation : " . $prestation['titre'] . "\n"
        . "Véhicule : " . $vehicule['nom'] . "\n"
        . ($nomsSupplements ? "Suppléments : " . implode(', ', $nomsSupplements) . "\n" : '')
        . "Quand : " . $quand . "\n"
        . "Durée : " . $prestation['duree_texte'] . "\n"
        . "Avec : " . $travailleur['nom'] . "\n"
        . "Où : " . ($adresse !== '' ? $adresse . ', ' : '') . $codePostal . ' ' . $commune . "\n"
        . "Total : " . $detail['total'] . " €"
        . ($detail['reduction'] > 0 ? " (offre de lancement : −" . $detail['reduction'] . " €)" : '') . "\n";

/* Au client : sa confirmation, avec le lien d'annulation. */
envoyer_courriel(
    $email,
    'Votre rendez-vous Shynera est confirmé',
    "Bonjour " . $nom . ",\n\nVotre rendez-vous est confirmé.\n\n" . $resume
    . "\nVous pouvez payer après la prestation.\n\n"
    . "Un empêchement ? Annulez ici :\n" . $site . "/annuler.php?jeton=" . $jeton . "\n\n"
    . "À bientôt,\n" . $travailleur['nom'] . " — Shynera\n",
    $travailleur['email'],
    $travailleur['email']
);

/* A toi : la nouvelle reservation, avec les coordonnees du client. */
envoyer_courriel(
    $travailleur['email'],
    'Nouvelle réservation : ' . $nom . ' le ' . $debut->format('d/m à H:i'),
    "Nouvelle réservation.\n\n" . $resume
    . "\nClient : " . $nom . "\nE-mail : " . $email . "\nTéléphone : " . ($telephone !== '' ? $telephone : '-') . "\n"
    . ($remarque !== '' ? "\nRemarque :\n" . $remarque . "\n" : ''),
    $email,
    $travailleur['email']
);

repondre([
    'ok'          => true,
    'quand'       => $quand,
    'travailleur' => $travailleur['nom'],
    'total'       => $detail['total'],
    'email'       => $email,
]);
