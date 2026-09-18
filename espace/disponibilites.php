<?php
/* ==========================================================================
   SHYNERA — mes disponibilites
   Deux choses : les horaires de chaque semaine, et les exceptions
   (conges, ou journees ajoutees exceptionnellement).
   ========================================================================== */

declare(strict_types=1);
require_once __DIR__ . '/commun.php';

$travailleur = exiger_connexion();
$id = (int) $travailleur['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_jeton();
    $action = (string) ($_POST['action'] ?? '');

    /* --- Ajouter une plage hebdomadaire --- */
    if ($action === 'ajouter_plage') {
        $jour  = (int) ($_POST['jour'] ?? 0);
        $debut = (string) ($_POST['debut'] ?? '');
        $fin   = (string) ($_POST['fin'] ?? '');

        if ($jour < 1 || $jour > 7 || !heure_valide($debut) || !heure_valide($fin)) {
            message('Vérifie le jour et les heures.', 'erreur');
        } elseif ($debut >= $fin) {
            message('L\'heure de fin doit être après l\'heure de début.', 'erreur');
        } else {
            /* Chevauchement avec une plage deja enregistree le meme jour ? */
            $requete = bdd()->prepare('SELECT COUNT(*) FROM disponibilites WHERE travailleur_id = ? AND jour = ? AND debut < ? AND fin > ?');
            $requete->execute([$id, $jour, $fin, $debut]);
            if ((int) $requete->fetchColumn() > 0) {
                message('Cette plage en chevauche une autre le même jour.', 'erreur');
            } else {
                $requete = bdd()->prepare('INSERT INTO disponibilites (travailleur_id, jour, debut, fin) VALUES (?, ?, ?, ?)');
                $requete->execute([$id, $jour, $debut . ':00', $fin . ':00']);
                message('Plage ajoutée.');
            }
        }
    }

    /* --- Supprimer une plage --- */
    if ($action === 'supprimer_plage') {
        $requete = bdd()->prepare('DELETE FROM disponibilites WHERE id = ? AND travailleur_id = ?');
        $requete->execute([(int) ($_POST['plage'] ?? 0), $id]);
        message('Plage supprimée.');
    }

    /* --- Ajouter une exception --- */
    if ($action === 'ajouter_exception') {
        $jourDate = (string) ($_POST['jour_date'] ?? '');
        $genre    = ($_POST['genre'] ?? 'ferme') === 'ouvert' ? 'ouvert' : 'ferme';
        $debut    = (string) ($_POST['debut'] ?? '');
        $fin      = (string) ($_POST['fin'] ?? '');
        $motif    = trim((string) ($_POST['motif'] ?? ''));

        if (!date_valide($jourDate)) {
            message('La date n\'est pas valide.', 'erreur');
        } elseif ($genre === 'ouvert' && (!heure_valide($debut) || !heure_valide($fin) || $debut >= $fin)) {
            message('Pour une journée ajoutée, indique une heure de début et de fin cohérentes.', 'erreur');
        } else {
            $requete = bdd()->prepare('INSERT INTO exceptions (travailleur_id, jour_date, genre, debut, fin, motif) VALUES (?, ?, ?, ?, ?, ?)');
            $requete->execute([
                $id,
                $jourDate,
                $genre,
                $genre === 'ouvert' ? $debut . ':00' : null,
                $genre === 'ouvert' ? $fin . ':00' : null,
                $motif !== '' ? mb_substr($motif, 0, 120) : null,
            ]);
            message('Exception ajoutée.');
        }
    }

    /* --- Supprimer une exception --- */
    if ($action === 'supprimer_exception') {
        $requete = bdd()->prepare('DELETE FROM exceptions WHERE id = ? AND travailleur_id = ?');
        $requete->execute([(int) ($_POST['exception'] ?? 0), $id]);
        message('Exception supprimée.');
    }

    header('Location: disponibilites.php');
    exit;
}

/* Plages rangees par jour. */
$requete = bdd()->prepare('SELECT * FROM disponibilites WHERE travailleur_id = ? ORDER BY jour, debut');
$requete->execute([$id]);
$parJour = array_fill_keys(array_keys(JOURS()), []);
foreach ($requete->fetchAll() as $plage) {
    $parJour[(int) $plage['jour']][] = $plage;
}

$requete = bdd()->prepare('SELECT * FROM exceptions WHERE travailleur_id = ? AND jour_date >= ? ORDER BY jour_date');
$requete->execute([$id, date('Y-m-d')]);
$exceptions = $requete->fetchAll();

entete('Mes disponibilités', $travailleur);
?>
<h1>Mes disponibilités</h1>
<p class="espace__aide">
  Ces horaires reviennent chaque semaine. Les clients ne verront que des créneaux
  compris dedans, une fois déduits les rendez-vous déjà pris et le temps de trajet.
</p>

<div class="espace__semaine">
  <?php foreach (JOURS() as $numero => $nom): ?>
    <div class="espace__jour">
      <h2><?= h($nom) ?></h2>
      <?php if (!$parJour[$numero]): ?>
        <p class="espace__aide">Pas de travail</p>
      <?php else: ?>
        <ul class="espace__plages">
          <?php foreach ($parJour[$numero] as $plage): ?>
            <li>
              <span><?= h(substr($plage['debut'], 0, 5)) ?> – <?= h(substr($plage['fin'], 0, 5)) ?></span>
              <form method="post">
                <?= champ_jeton() ?>
                <input type="hidden" name="action" value="supprimer_plage">
                <input type="hidden" name="plage" value="<?= (int) $plage['id'] ?>">
                <button type="submit" class="espace__retirer" aria-label="Supprimer cette plage">×</button>
              </form>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>

<h2>Ajouter une plage</h2>
<form method="post" class="formulaire espace__ligne">
  <?= champ_jeton() ?>
  <input type="hidden" name="action" value="ajouter_plage">
  <div class="champ">
    <label for="jour">Jour</label>
    <select id="jour" name="jour">
      <?php foreach (JOURS() as $numero => $nom): ?>
        <option value="<?= $numero ?>"><?= h($nom) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="champ">
    <label for="debut">De</label>
    <input id="debut" name="debut" type="time" value="08:00" required>
  </div>
  <div class="champ">
    <label for="fin">À</label>
    <input id="fin" name="fin" type="time" value="18:00" required>
  </div>
  <div class="formulaire__pied">
    <button type="submit" class="bouton">Ajouter</button>
  </div>
</form>

<h2 id="exceptions">Congés et exceptions</h2>
<p class="espace__aide">
  Un congé retire une journée entière, même si elle fait partie de tes horaires habituels.
  Une journée ajoutée ouvre au contraire un créneau en dehors de tes horaires.
</p>

<?php if ($exceptions): ?>
  <table class="espace__tableau">
    <thead><tr><th>Date</th><th>Quoi</th><th>Motif</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($exceptions as $e): ?>
        <tr>
          <td><?= h(date('d/m/Y', strtotime($e['jour_date']))) ?></td>
          <td><?= $e['genre'] === 'ferme'
                ? 'Congé, indisponible'
                : 'Ajout, disponible ' . h(substr((string) $e['debut'], 0, 5)) . ' – ' . h(substr((string) $e['fin'], 0, 5)) ?></td>
          <td><?= h($e['motif'] ?? '') ?></td>
          <td>
            <form method="post">
              <?= champ_jeton() ?>
              <input type="hidden" name="action" value="supprimer_exception">
              <input type="hidden" name="exception" value="<?= (int) $e['id'] ?>">
              <button type="submit" class="espace__retirer" aria-label="Supprimer">×</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php else: ?>
  <p class="espace__aide">Aucune exception prévue.</p>
<?php endif; ?>

<form method="post" class="formulaire espace__ligne">
  <?= champ_jeton() ?>
  <input type="hidden" name="action" value="ajouter_exception">
  <div class="champ">
    <label for="jour_date">Date</label>
    <input id="jour_date" name="jour_date" type="date" min="<?= date('Y-m-d') ?>" required>
  </div>
  <div class="champ">
    <label for="genre">Quoi</label>
    <select id="genre" name="genre">
      <option value="ferme">Congé, je ne travaille pas</option>
      <option value="ouvert">J'ajoute une disponibilité</option>
    </select>
  </div>
  <div class="champ">
    <label for="exception_debut">De</label>
    <input id="exception_debut" name="debut" type="time" value="08:00">
  </div>
  <div class="champ">
    <label for="exception_fin">À</label>
    <input id="exception_fin" name="fin" type="time" value="18:00">
  </div>
  <div class="champ">
    <label for="motif">Motif <small>(facultatif)</small></label>
    <input id="motif" name="motif" type="text" maxlength="120">
  </div>
  <div class="formulaire__pied">
    <button type="submit" class="bouton">Ajouter</button>
  </div>
</form>
<?php pied(); ?>
