<?php
/* ==========================================================================
   SHYNERA — installation, a ouvrir UNE SEULE FOIS
   --------------------------------------------------------------------------
   Cree les tables de la base et ton premier compte.
   Une fois que ca affiche "termine", SUPPRIME ce fichier du serveur.
   ========================================================================== */

declare(strict_types=1);
require_once __DIR__ . '/commun.php';

$erreur = null;
$termine = false;

try {
    creer_tables();
    $nombre = (int) bdd()->query('SELECT COUNT(*) FROM travailleurs')->fetchColumn();
} catch (Throwable $e) {
    entete('Installation');
    echo '<h1>Installation</h1>';
    echo '<p class="espace__message espace__message--erreur">La base de données ne répond pas : ' . h($e->getMessage()) . '</p>';
    echo '<p>Vérifie le fichier <code>espace/config.php</code> : nom de la base, nom d\'utilisateur et mot de passe.</p>';
    pied();
    exit;
}

/* Un compte existe deja : on ne laisse plus personne en creer un ici. */
if ($nombre > 0) {
    entete('Installation');
    echo '<h1>Installation déjà faite</h1>';
    echo '<p class="espace__message espace__message--erreur">Un compte existe déjà. Par sécurité, cette page ne fait plus rien.</p>';
    echo '<p><strong>Supprime le fichier <code>espace/installation.php</code> du serveur</strong>, puis connecte-toi.</p>';
    echo '<p><a class="bouton" href="connexion.php">Aller à la connexion</a></p>';
    pied();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_jeton();
    $nom   = trim((string) ($_POST['nom'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $mdp   = (string) ($_POST['mot_de_passe'] ?? '');

    if ($nom === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Indique ton prénom et une adresse e-mail valide.';
    } elseif (strlen($mdp) < 10) {
        $erreur = 'Le mot de passe doit faire au moins 10 caractères.';
    } else {
        $requete = bdd()->prepare('INSERT INTO travailleurs (nom, email, mot_de_passe, actif, cree_le) VALUES (?, ?, ?, 1, ?)');
        $requete->execute([$nom, $email, password_hash($mdp, PASSWORD_DEFAULT), date('Y-m-d H:i:s')]);
        $termine = true;
    }
}

entete('Installation');
?>
<h1>Installation de l'espace pro</h1>

<?php if ($termine): ?>
  <p class="espace__message espace__message--ok">Compte créé, les tables sont en place.</p>
  <p><strong>Dernière étape, importante : supprime le fichier <code>espace/installation.php</code> du serveur.</strong>
     Tant qu'il est là, quelqu'un pourrait s'en servir si la base était vidée.</p>
  <p><a class="bouton" href="connexion.php">Se connecter</a></p>
<?php else: ?>
  <p class="espace__aide">Les tables ont été créées. Crée maintenant ton compte : c'est celui qui te servira à entrer tes disponibilités.</p>
  <?php if ($erreur): ?><p class="espace__message espace__message--erreur"><?= h($erreur) ?></p><?php endif; ?>
  <form method="post" class="formulaire">
    <?= champ_jeton() ?>
    <div class="champ champ--large">
      <label for="nom">Prénom (celui que verront les clients)</label>
      <input id="nom" name="nom" type="text" required maxlength="80" value="<?= h($_POST['nom'] ?? '') ?>">
    </div>
    <div class="champ champ--large">
      <label for="email">E-mail (sert à se connecter)</label>
      <input id="email" name="email" type="email" required maxlength="190" value="<?= h($_POST['email'] ?? '') ?>">
    </div>
    <div class="champ champ--large">
      <label for="mot_de_passe">Mot de passe <small>(10 caractères minimum)</small></label>
      <input id="mot_de_passe" name="mot_de_passe" type="password" required minlength="10" autocomplete="new-password">
    </div>
    <div class="formulaire__pied">
      <button type="submit" class="bouton">Créer mon compte</button>
    </div>
  </form>
<?php endif; ?>
<?php pied(); ?>
