<?php
/* ==========================================================================
   SHYNERA — tableau de bord de l'espace pro
   ========================================================================== */

declare(strict_types=1);
require_once __DIR__ . '/commun.php';

$travailleur = exiger_connexion();

/* Heures declarees chaque semaine. */
$requete = bdd()->prepare('SELECT jour, debut, fin FROM disponibilites WHERE travailleur_id = ? ORDER BY jour, debut');
$requete->execute([$travailleur['id']]);
$plages = $requete->fetchAll();

$minutes = 0;
foreach ($plages as $plage) {
    $minutes += (strtotime($plage['fin']) - strtotime($plage['debut'])) / 60;
}
$heures = round($minutes / 60, 1);

/* Prochains conges. */
$requete = bdd()->prepare('SELECT * FROM exceptions WHERE travailleur_id = ? AND jour_date >= ? ORDER BY jour_date LIMIT 5');
$requete->execute([$travailleur['id'], date('Y-m-d')]);
$exceptions = $requete->fetchAll();

/* Prochains rendez-vous (vide tant que la reservation client n'est pas en place). */
$requete = bdd()->prepare('SELECT * FROM reservations WHERE travailleur_id = ? AND debut >= ? AND statut = ? ORDER BY debut LIMIT 10');
$requete->execute([$travailleur['id'], date('Y-m-d 00:00:00'), 'confirmee']);
$reservations = $requete->fetchAll();

entete('Tableau de bord', $travailleur);
?>
<h1>Bonjour <?= h($travailleur['nom']) ?></h1>

<div class="espace__cartes">
  <div class="espace__carte">
    <p class="espace__chiffre"><?= h((string) $heures) ?> h</p>
    <p>déclarées par semaine</p>
    <p><a class="lien-souligne" href="disponibilites.php">Modifier mes disponibilités</a></p>
  </div>
  <div class="espace__carte">
    <p class="espace__chiffre"><?= count($reservations) ?></p>
    <p>rendez-vous à venir</p>
  </div>
</div>

<h2>Prochains rendez-vous</h2>
<?php if (!$reservations): ?>
  <p class="espace__aide">Aucun rendez-vous pour l'instant. Les réservations des clients apparaîtront ici.</p>
<?php else: ?>
  <table class="espace__tableau">
    <thead><tr><th>Quand</th><th>Prestation</th><th>Client</th><th>Où</th><th>Prix</th></tr></thead>
    <tbody>
    <?php foreach ($reservations as $r): ?>
      <tr>
        <td><?= h(date('d/m/Y H:i', strtotime($r['debut']))) ?></td>
        <td><?= h($r['prestation']) ?><br><small><?= h($r['vehicule']) ?></small></td>
        <td><?= h($r['client_nom']) ?><br><small><?= h($r['client_telephone']) ?></small></td>
        <td><?= h($r['code_postal']) ?></td>
        <td><?= h((string) $r['prix']) ?> €</td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<h2>Prochains congés</h2>
<?php if (!$exceptions): ?>
  <p class="espace__aide">Aucun congé prévu. <a class="lien-souligne" href="disponibilites.php#exceptions">En ajouter un</a>.</p>
<?php else: ?>
  <ul class="puces">
    <?php foreach ($exceptions as $e): ?>
      <li>
        <?= h(date('d/m/Y', strtotime($e['jour_date']))) ?> —
        <?= $e['genre'] === 'ferme' ? 'indisponible' : 'disponible ' . h(substr((string) $e['debut'], 0, 5)) . ' – ' . h(substr((string) $e['fin'], 0, 5)) ?>
        <?= $e['motif'] ? '(' . h($e['motif']) . ')' : '' ?>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
<?php pied(); ?>
