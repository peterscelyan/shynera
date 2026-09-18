<?php
/* ==========================================================================
   SHYNERA — connexion a l'espace pro
   ========================================================================== */

declare(strict_types=1);
require_once __DIR__ . '/commun.php';

demarrer_session();
if (connecte()) {
    header('Location: index.php');
    exit;
}

$erreur = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_jeton();

    /* Freine les essais a la chaine : 5 essais, puis une minute d'attente. */
    $essais = $_SESSION['essais'] ?? ['nombre' => 0, 'jusqu_a' => 0];
    if ($essais['nombre'] >= 5 && time() < $essais['jusqu_a']) {
        $erreur = 'Trop d\'essais. Réessaie dans une minute.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $mdp   = (string) ($_POST['mot_de_passe'] ?? '');

        $requete = bdd()->prepare('SELECT * FROM travailleurs WHERE email = ? AND actif = 1');
        $requete->execute([$email]);
        $travailleur = $requete->fetch();

        if ($travailleur && password_verify($mdp, $travailleur['mot_de_passe'])) {
            session_regenerate_id(true);
            $_SESSION['travailleur_id'] = (int) $travailleur['id'];
            unset($_SESSION['essais']);
            header('Location: index.php');
            exit;
        }

        /* Meme message dans tous les cas : on ne dit pas si l'e-mail existe. */
        usleep(300000);
        $essais = ['nombre' => $essais['nombre'] + 1, 'jusqu_a' => time() + 60];
        $_SESSION['essais'] = $essais;
        $erreur = 'E-mail ou mot de passe incorrect.';
    }
}

entete('Connexion');
?>
<h1>Espace pro</h1>
<p class="espace__aide">Réservé à l'équipe Shynera.</p>

<?php if ($erreur): ?><p class="espace__message espace__message--erreur"><?= h($erreur) ?></p><?php endif; ?>

<form method="post" class="formulaire espace__formulaire-court">
  <?= champ_jeton() ?>
  <div class="champ champ--large">
    <label for="email">E-mail</label>
    <input id="email" name="email" type="email" required autocomplete="username" value="<?= h($_POST['email'] ?? '') ?>">
  </div>
  <div class="champ champ--large">
    <label for="mot_de_passe">Mot de passe</label>
    <input id="mot_de_passe" name="mot_de_passe" type="password" required autocomplete="current-password">
  </div>
  <div class="formulaire__pied">
    <button type="submit" class="bouton">Se connecter</button>
  </div>
</form>
<?php pied(); ?>
