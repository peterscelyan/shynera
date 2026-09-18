<?php
/* ==========================================================================
   SHYNERA — tableau de bord de l'espace pro
   ========================================================================== */

declare(strict_types=1);
require_once __DIR__ . '/commun.php';
require_once __DIR__ . '/planning.php';

$travailleur = exiger_connexion();
$id = (int) $travailleur['id'];

/* Marquer un rendez-vous paye ou non paye. */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_jeton();
    if (($_POST['action'] ?? '') === 'paiement') {
        $requete = bdd()->prepare('UPDATE reservations SET paye = ? WHERE id = ? AND travailleur_id = ?');
        $requete->execute([(int) ($_POST['paye'] ?? 0), (int) ($_POST['reservation'] ?? 0), $id]);
        message(((int) ($_POST['paye'] ?? 0)) === 1 ? 'Marqué comme payé.' : 'Marqué comme non payé.');
    }
    header('Location: index.php');
    exit;
}

/* Heures declarees chaque semaine. */
$requete = bdd()->prepare('SELECT jour, debut, fin FROM disponibilites WHERE travailleur_id = ? ORDER BY jour, debut');
$requete->execute([$id]);
$plages = $requete->fetchAll();

$minutes = 0;
foreach ($plages as $plage) {
    $minutes += (strtotime($plage['fin']) - strtotime($plage['debut'])) / 60;
}
$heures = round($minutes / 60, 1);

/* Rendez-vous a venir, puis ceux qui restent a encaisser. */
$requete = bdd()->prepare("SELECT * FROM reservations WHERE travailleur_id = ? AND debut >= ? AND statut = 'confirmee' ORDER BY debut");
$requete->execute([$id, date('Y-m-d 00:00:00')]);
$aVenir = $requete->fetchAll();

$requete = bdd()->prepare("SELECT * FROM reservations WHERE travailleur_id = ? AND debut < ? AND statut = 'confirmee' AND paye = 0 ORDER BY debut DESC");
$requete->execute([$id, date('Y-m-d H:i:s')]);
$aEncaisser = $requete->fetchAll();

$aEncaisserTotal = 0;
foreach ($aEncaisser as $r) {
    $aEncaisserTotal += (int) $r['prix'];
}

/* Temps de travail deja prevu : la somme des rendez-vous a venir. */
$minutesPrevues = 0;
foreach ($aVenir as $r) {
    $minutesPrevues += (int) ((strtotime($r['fin']) - strtotime($r['debut'])) / 60);
}

/* Affiche une ligne de rendez-vous. */
function ligne_rendez_vous(array $r): void
{
    $debut = new DateTimeImmutable($r['debut']);
    $fin   = new DateTimeImmutable($r['fin']);
    $duree = (int) (($fin->getTimestamp() - $debut->getTimestamp()) / 60);
    $commune = commune_du_code((string) $r['code_postal']);
    ?>
    <tr>
      <td>
        <strong><?= h(strftime_fr($debut, false, true)) ?></strong><br>
        <?= h(heure_fr($debut)) ?> – <?= h(heure_fr($fin)) ?> <small>(<?= h(duree_fr($duree)) ?>)</small>
      </td>
      <td>
        <?= h($r['prestation']) ?><br>
        <small><?= h($r['vehicule']) ?><?= $r['supplements'] ? ' · ' . h($r['supplements']) : '' ?></small>
      </td>
      <td>
        <?= h($r['client_nom']) ?><br>
        <small>
          <?php if ($r['client_telephone']): ?><a href="tel:<?= h($r['client_telephone']) ?>"><?= h($r['client_telephone']) ?></a><br><?php endif; ?>
          <a href="mailto:<?= h($r['client_email']) ?>"><?= h($r['client_email']) ?></a>
        </small>
      </td>
      <td>
        <?= $r['adresse'] ? h($r['adresse']) . '<br>' : '' ?>
        <?= h($r['code_postal']) ?><?= $commune ? ' ' . h($commune) : '' ?>
        <?php if ($r['adresse']): ?>
          <br><small><a href="https://www.google.com/maps/search/?api=1&amp;query=<?= rawurlencode($r['adresse'] . ', ' . $r['code_postal'] . ' ' . ($commune ?? '')) ?>" target="_blank" rel="noopener">Itinéraire</a></small>
        <?php endif; ?>
      </td>
      <td class="espace__prix"><?= h((string) $r['prix']) ?> €</td>
      <td>
        <?php if ((int) $r['paye'] === 1): ?>
          <span class="espace__etat espace__etat--ok">Payé</span>
          <form method="post">
            <?= champ_jeton() ?>
            <input type="hidden" name="action" value="paiement">
            <input type="hidden" name="reservation" value="<?= (int) $r['id'] ?>">
            <input type="hidden" name="paye" value="0">
            <button type="submit" class="espace__lien-discret">annuler</button>
          </form>
        <?php else: ?>
          <span class="espace__etat">Non payé</span>
          <form method="post">
            <?= champ_jeton() ?>
            <input type="hidden" name="action" value="paiement">
            <input type="hidden" name="reservation" value="<?= (int) $r['id'] ?>">
            <input type="hidden" name="paye" value="1">
            <button type="submit" class="bouton bouton--creux bouton--petit">Marquer payé</button>
          </form>
        <?php endif; ?>
      </td>
      <?php if ($r['remarque']): ?>
    </tr>
    <tr class="espace__remarque">
      <td colspan="6"><strong>Remarque du client :</strong> <?= h($r['remarque']) ?></td>
      <?php endif; ?>
    </tr>
    <?php
}

entete('Tableau de bord', $travailleur);
?>
<h1>Bonjour <?= h($travailleur['nom']) ?></h1>

<div class="espace__cartes">
  <div class="espace__carte">
    <p class="espace__chiffre"><?= count($aVenir) ?></p>
    <p>rendez-vous à venir</p>
    <p><a class="lien-souligne" href="disponibilites.php">Gérer mes disponibilités</a></p>
  </div>
  <div class="espace__carte">
    <p class="espace__chiffre"><?= h((string) $heures) ?> h</p>
    <p>d'horaires habituels par semaine</p>
  </div>
  <div class="espace__carte">
    <p class="espace__chiffre"><?= h($minutesPrevues ? duree_fr($minutesPrevues) : '0 h') ?></p>
    <p>de prestations prévues</p>
  </div>
</div>

<h2>Prochains rendez-vous</h2>
<?php if (!$aVenir): ?>
  <p class="espace__aide">Aucun rendez-vous pour l'instant. Les réservations des clients apparaîtront ici.</p>
<?php else: ?>
  <div class="espace__defile">
    <table class="espace__tableau">
      <thead><tr><th>Quand</th><th>Prestation</th><th>Client</th><th>Où</th><th>Prix</th><th>Paiement</th></tr></thead>
      <tbody><?php foreach ($aVenir as $r) ligne_rendez_vous($r); ?></tbody>
    </table>
  </div>
<?php endif; ?>

<?php if ($aEncaisser): ?>
  <h2>Prestations faites, pas encore payées <span class="espace__a-encaisser"><?= $aEncaisserTotal ?> € à encaisser</span></h2>
  <div class="espace__defile">
    <table class="espace__tableau">
      <thead><tr><th>Quand</th><th>Prestation</th><th>Client</th><th>Où</th><th>Prix</th><th>Paiement</th></tr></thead>
      <tbody><?php foreach ($aEncaisser as $r) ligne_rendez_vous($r); ?></tbody>
    </table>
  </div>
<?php endif; ?>
<?php pied(); ?>
