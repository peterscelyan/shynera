<?php
/* ==========================================================================
   SHYNERA — annulation d'un rendez-vous par le client
   Le lien contenu dans l'e-mail de confirmation mene ici. Le jeton, long et
   tire au hasard, sert de mot de passe : impossible d'annuler le rendez-vous
   de quelqu'un d'autre.
   ========================================================================== */

declare(strict_types=1);

require_once __DIR__ . '/espace/planning.php';
require_once __DIR__ . '/espace/courriel.php';

function texte(?string $t): string
{
    return htmlspecialchars((string) $t, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$jeton = (string) ($_GET['jeton'] ?? $_POST['jeton'] ?? '');
$reservation = null;

if (preg_match('/^[a-f0-9]{32}$/', $jeton)) {
    $requete = bdd()->prepare('SELECT r.*, t.nom AS travailleur_nom, t.email AS travailleur_email
                               FROM reservations r JOIN travailleurs t ON t.id = r.travailleur_id
                               WHERE r.jeton = ?');
    $requete->execute([$jeton]);
    $reservation = $requete->fetch() ?: null;
}

$limite = reglages()['annulation'];
$message = null;
$annule  = false;

if ($reservation) {
    $debut     = new DateTimeImmutable($reservation['debut']);
    $tropTard  = $debut->getTimestamp() - time() < $limite * 3600;
    $dejaAnnule = $reservation['statut'] !== 'confirmee';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$dejaAnnule && !$tropTard) {
        $requete = bdd()->prepare("UPDATE reservations SET statut = 'annulee' WHERE id = ?");
        $requete->execute([$reservation['id']]);
        $annule = true;

        envoyer_courriel(
            $reservation['travailleur_email'],
            'Annulation : ' . $reservation['client_nom'] . ' le ' . $debut->format('d/m à H:i'),
            "Le client a annulé son rendez-vous.\n\n"
            . strftime_fr($debut) . "\n"
            . $reservation['prestation'] . ' — ' . $reservation['client_nom'] . "\n"
            . 'Téléphone : ' . ($reservation['client_telephone'] ?: '-') . "\n",
            $reservation['client_email'],
            $reservation['travailleur_email']
        );
    } elseif ($dejaAnnule) {
        $message = 'Ce rendez-vous est déjà annulé.';
    } elseif ($tropTard) {
        $message = 'Il reste moins de ' . $limite . ' heures avant le rendez-vous : écrivez-moi ou appelez-moi directement.';
    }
}
?><!doctype html>
<html lang="fr-BE">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Annuler un rendez-vous — Shynera</title>
<link rel="icon" type="image/svg+xml" href="favicon.svg">
<link rel="stylesheet" href="<?= version('style.css') ?>">
</head>
<body>
<main class="section">
  <div class="enveloppe" style="max-width: 40rem;">
    <div class="cesure"><h1>Annuler un rendez-vous</h1></div>

    <?php if (!$reservation): ?>
      <p class="intro">Ce lien n'est pas valable. Vérifiez le lien reçu par e-mail, ou écrivez-moi.</p>
      <p><a class="bouton bouton--creux" href="/">Retour au site</a></p>

    <?php elseif ($annule): ?>
      <p class="intro">C'est annulé. Vous recevez la place, je reçois l'information : personne n'a rien à faire de plus.</p>
      <p><a class="bouton" href="/#tarifs">Reprendre un autre créneau</a></p>

    <?php else: ?>
      <dl class="recap">
        <div><dt>Quand</dt><dd><?= texte(strftime_fr(new DateTimeImmutable($reservation['debut']))) ?></dd><dd></dd></div>
        <div><dt>Prestation</dt><dd><?= texte($reservation['prestation']) ?></dd><dd></dd></div>
        <div><dt>Véhicule</dt><dd><?= texte($reservation['vehicule']) ?></dd><dd></dd></div>
        <div><dt>Avec</dt><dd><?= texte($reservation['travailleur_nom']) ?></dd><dd></dd></div>
      </dl>

      <?php if ($message): ?>
        <p class="etape__erreur"><?= texte($message) ?></p>
        <p><a class="bouton bouton--creux" href="/">Retour au site</a></p>
      <?php else: ?>
        <p class="intro">Vous pouvez annuler jusqu'à <?= (int) $limite ?> heures avant le rendez-vous.</p>
        <form method="post">
          <input type="hidden" name="jeton" value="<?= texte($jeton) ?>">
          <button type="submit" class="bouton">Annuler ce rendez-vous</button>
        </form>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</main>
</body>
</html>
