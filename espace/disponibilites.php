<?php
/* ==========================================================================
   SHYNERA — mes disponibilites
   --------------------------------------------------------------------------
   Deux facons de travailler, au choix :

   1. LE CALENDRIER (en haut) : tu cliques sur une date et tu dis quand tu es
      disponible ce jour-la. Pratique quand les horaires changent tout le temps
      (etudiant, flexi-job). Ce que tu mets sur une date REMPLACE tes horaires
      habituels pour cette date.

   2. LES HORAIRES HABITUELS (en bas) : les memes heures chaque semaine. Ils
      servent de base pour toutes les dates que tu n'as pas reglees a la main.
   ========================================================================== */

declare(strict_types=1);
require_once __DIR__ . '/commun.php';
require_once __DIR__ . '/planning.php';

$travailleur = exiger_connexion();
$id = (int) $travailleur['id'];

const SEMAINES_AFFICHEES = 4;

/* Supprime tout ce qui a ete regle a la main sur une date. */
function vider_date(int $id, string $date): void
{
    $requete = bdd()->prepare('DELETE FROM exceptions WHERE travailleur_id = ? AND jour_date = ?');
    $requete->execute([$id, $date]);
}

/* Les dates a traiter : celle demandee, plus les memes jours des semaines
   suivantes si le visiteur a coche "repeter". */
function dates_visees(string $date, bool $repeter): array
{
    $dates = [$date];
    if ($repeter) {
        $depart = new DateTimeImmutable($date);
        for ($i = 1; $i < SEMAINES_AFFICHEES; $i++) {
            $dates[] = $depart->modify('+' . $i . ' weeks')->format('Y-m-d');
        }
    }
    return $dates;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_jeton();
    $action  = (string) ($_POST['action'] ?? '');
    $date    = (string) ($_POST['jour_date'] ?? '');
    $repeter = !empty($_POST['repeter']);
    $retour  = 'disponibilites.php' . ($date !== '' ? '?jour=' . urlencode($date) : '');

    /* --- Calendrier : je travaille ce jour-la, de X a Y --- */
    if ($action === 'jour_travaille') {
        $debut = (string) ($_POST['debut'] ?? '');
        $fin   = (string) ($_POST['fin'] ?? '');

        if (!date_valide($date) || !heure_valide($debut) || !heure_valide($fin)) {
            message('Vérifie la date et les heures.', 'erreur');
        } elseif ($debut >= $fin) {
            message("L'heure de fin doit être après l'heure de début.", 'erreur');
        } else {
            $requete = bdd()->prepare('INSERT INTO exceptions (travailleur_id, jour_date, genre, debut, fin, motif) VALUES (?, ?, ?, ?, ?, NULL)');
            foreach (dates_visees($date, $repeter) as $jour) {
                /* Un jour ferme redevient ouvert des qu'on ajoute une plage. */
                $menage = bdd()->prepare("DELETE FROM exceptions WHERE travailleur_id = ? AND jour_date = ? AND genre = 'ferme'");
                $menage->execute([$id, $jour]);
                $requete->execute([$id, $jour, 'ouvert', $debut . ':00', $fin . ':00']);
            }
            message($repeter ? 'Enregistré pour les ' . SEMAINES_AFFICHEES . ' semaines.' : 'Enregistré.');
        }
    }

    /* --- Calendrier : je ne travaille pas ce jour-la --- */
    if ($action === 'jour_repos') {
        if (!date_valide($date)) {
            message('Date invalide.', 'erreur');
        } else {
            $requete = bdd()->prepare('INSERT INTO exceptions (travailleur_id, jour_date, genre, debut, fin, motif) VALUES (?, ?, ?, NULL, NULL, ?)');
            foreach (dates_visees($date, $repeter) as $jour) {
                vider_date($id, $jour);
                $requete->execute([$id, $jour, 'ferme', mb_substr(trim((string) ($_POST['motif'] ?? '')), 0, 120) ?: null]);
            }
            message('Journée marquée comme non travaillée.');
        }
    }

    /* --- Calendrier : revenir aux horaires habituels --- */
    if ($action === 'jour_habituel') {
        vider_date($id, $date);
        message('Retour aux horaires habituels pour cette date.');
    }

    /* --- Calendrier : retirer une plage d'une date --- */
    if ($action === 'plage_date_supprimer') {
        $requete = bdd()->prepare('DELETE FROM exceptions WHERE id = ? AND travailleur_id = ?');
        $requete->execute([(int) ($_POST['exception'] ?? 0), $id]);
        message('Plage retirée.');
    }

    /* --- Horaires habituels : ajouter une plage --- */
    if ($action === 'ajouter_plage') {
        $jour  = (int) ($_POST['jour'] ?? 0);
        $debut = (string) ($_POST['debut'] ?? '');
        $fin   = (string) ($_POST['fin'] ?? '');
        $retour = 'disponibilites.php#habituels';

        if ($jour < 1 || $jour > 7 || !heure_valide($debut) || !heure_valide($fin)) {
            message('Vérifie le jour et les heures.', 'erreur');
        } elseif ($debut >= $fin) {
            message("L'heure de fin doit être après l'heure de début.", 'erreur');
        } else {
            $requete = bdd()->prepare('SELECT COUNT(*) FROM disponibilites WHERE travailleur_id = ? AND jour = ? AND debut < ? AND fin > ?');
            $requete->execute([$id, $jour, $fin, $debut]);
            if ((int) $requete->fetchColumn() > 0) {
                message('Cette plage en chevauche une autre le même jour.', 'erreur');
            } else {
                $requete = bdd()->prepare('INSERT INTO disponibilites (travailleur_id, jour, debut, fin) VALUES (?, ?, ?, ?)');
                $requete->execute([$id, $jour, $debut . ':00', $fin . ':00']);
                message('Horaire habituel ajouté.');
            }
        }
    }

    /* --- Horaires habituels : supprimer une plage --- */
    if ($action === 'supprimer_plage') {
        $requete = bdd()->prepare('DELETE FROM disponibilites WHERE id = ? AND travailleur_id = ?');
        $requete->execute([(int) ($_POST['plage'] ?? 0), $id]);
        message('Horaire habituel supprimé.');
        $retour = 'disponibilites.php#habituels';
    }

    header('Location: ' . $retour);
    exit;
}

/* ---------------------------------------------------------------- Affichage */

$aujourdhui = new DateTimeImmutable('today');
$decalage   = max(0, min(12, (int) ($_GET['semaine'] ?? 0)));
$premier    = $aujourdhui->modify('monday this week')->modify('+' . $decalage . ' weeks');
$dernier    = $premier->modify('+' . (SEMAINES_AFFICHEES * 7 - 1) . ' days');

$horaires   = horaires_habituels();
$exceptions = exceptions_entre($premier->format('Y-m-d'), $dernier->format('Y-m-d'));

/* Rendez-vous de la periode, pour les montrer sur le calendrier. */
$requete = bdd()->prepare("SELECT debut FROM reservations WHERE travailleur_id = ? AND statut = 'confirmee' AND debut BETWEEN ? AND ?");
$requete->execute([$id, $premier->format('Y-m-d 00:00:00'), $dernier->format('Y-m-d 23:59:59')]);
$rendezVous = [];
foreach ($requete->fetchAll() as $r) {
    $jour = substr($r['debut'], 0, 10);
    $rendezVous[$jour] = ($rendezVous[$jour] ?? 0) + 1;
}

/* La date ouverte dans le panneau du bas. */
$jourChoisi = (string) ($_GET['jour'] ?? '');
if (!date_valide($jourChoisi)) {
    $jourChoisi = $aujourdhui->format('Y-m-d');
}
$dateChoisie   = new DateTimeImmutable($jourChoisi);
$duJourChoisi  = $exceptions[$id][$jourChoisi] ?? [];
if (!$duJourChoisi) {
    /* La date choisie peut etre hors de la periode affichee. */
    $autres = exceptions_entre($jourChoisi, $jourChoisi);
    $duJourChoisi = $autres[$id][$jourChoisi] ?? [];
}
$plagesChoisies = plages_du_jour($id, $dateChoisie, $horaires, [$id => [$jourChoisi => $duJourChoisi]]);
$surMesure      = (bool) $duJourChoisi;
$repos          = $surMesure && $duJourChoisi[0]['genre'] === 'ferme';

/* Plages habituelles, rangees par jour de la semaine. */
$requete = bdd()->prepare('SELECT * FROM disponibilites WHERE travailleur_id = ? ORDER BY jour, debut');
$requete->execute([$id]);
$parJour = array_fill_keys(array_keys(JOURS()), []);
foreach ($requete->fetchAll() as $plage) {
    $parJour[(int) $plage['jour']][] = $plage;
}

entete('Mes disponibilités', $travailleur);
?>
<h1>Mes disponibilités</h1>
<p class="espace__aide">
  Clique sur une date pour dire quand tu es disponible ce jour-là. Ce que tu mets sur
  une date remplace tes horaires habituels. Les clients ne voient que ces heures,
  moins les rendez-vous déjà pris et le temps de trajet.
</p>

<div class="calendrier__barre">
  <a class="bouton bouton--creux bouton--petit<?= $decalage === 0 ? ' bouton--inactif' : '' ?>"
     href="?semaine=<?= max(0, $decalage - SEMAINES_AFFICHEES) ?>&amp;jour=<?= h($jourChoisi) ?>">← <?= SEMAINES_AFFICHEES ?> semaines avant</a>
  <p class="calendrier__periode">
    Du <?= h(strftime_fr($premier, false)) ?> au <?= h(strftime_fr($dernier, false, true)) ?>
  </p>
  <a class="bouton bouton--creux bouton--petit"
     href="?semaine=<?= $decalage + SEMAINES_AFFICHEES ?>&amp;jour=<?= h($jourChoisi) ?>"><?= SEMAINES_AFFICHEES ?> semaines après →</a>
</div>

<div class="calendrier">
  <?php foreach (JOURS() as $nomJour): ?>
    <div class="calendrier__entete"><?= h(mb_substr($nomJour, 0, 3)) ?>.</div>
  <?php endforeach; ?>

  <?php for ($i = 0; $i < SEMAINES_AFFICHEES * 7; $i++): ?>
    <?php
    $jour   = $premier->modify('+' . $i . ' days');
    $cle    = $jour->format('Y-m-d');
    $plages = plages_du_jour($id, $jour, $horaires, $exceptions);
    $perso  = !empty($exceptions[$id][$cle]);
    $passe  = $cle < $aujourdhui->format('Y-m-d');

    $classes = 'calendrier__jour';
    if ($plages) $classes .= ' calendrier__jour--dispo';
    if ($perso)  $classes .= ' calendrier__jour--perso';
    if ($passe)  $classes .= ' calendrier__jour--passe';
    if ($cle === $jourChoisi) $classes .= ' calendrier__jour--choisi';
    if ($cle === $aujourdhui->format('Y-m-d')) $classes .= ' calendrier__jour--aujourdhui';
    ?>
    <a class="<?= $classes ?>" href="?semaine=<?= $decalage ?>&amp;jour=<?= $cle ?>#jour">
      <span class="calendrier__numero">
        <?= (int) $jour->format('j') ?><?php if ((int) $jour->format('j') === 1): ?> <?= h(mois_fr($jour)) ?><?php endif; ?>
      </span>
      <span class="calendrier__heures">
        <?php if (!$plages): ?>
          —
        <?php else: foreach ($plages as [$d, $f]): ?>
          <?= h(substr($d, 0, 5)) ?>–<?= h(substr($f, 0, 5)) ?><br>
        <?php endforeach; endif; ?>
      </span>
      <?php if (!empty($rendezVous[$cle])): ?>
        <span class="calendrier__rdv"><?= (int) $rendezVous[$cle] ?> RDV</span>
      <?php endif; ?>
    </a>
  <?php endfor; ?>
</div>

<p class="calendrier__legende">
  <span class="calendrier__puce calendrier__puce--dispo"></span> disponible ·
  <span class="calendrier__puce calendrier__puce--perso"></span> réglé à la main pour cette date ·
  sinon, horaires habituels
</p>

<h2 id="jour"><?= h(strftime_fr($dateChoisie, false, true)) ?></h2>

<div class="espace__panneau">
  <p class="espace__etat-jour">
    <?php if ($repos): ?>
      Tu ne travailles pas ce jour-là.
    <?php elseif (!$plagesChoisies): ?>
      Aucune disponibilité ce jour-là, donc aucun créneau proposé aux clients.
    <?php else: ?>
      Disponible
      <?php foreach ($plagesChoisies as $rang => [$d, $f]): ?>
        <?= $rang ? ' et' : '' ?> de <strong><?= h(substr($d, 0, 5)) ?></strong> à <strong><?= h(substr($f, 0, 5)) ?></strong>
      <?php endforeach; ?>
      <?= $surMesure ? ' (réglé pour cette date)' : ' (horaires habituels)' ?>.
    <?php endif; ?>
  </p>

  <?php if ($surMesure && !$repos): ?>
    <ul class="espace__plages espace__plages--ligne">
      <?php foreach ($duJourChoisi as $exception): if ($exception['genre'] !== 'ouvert') continue; ?>
        <li>
          <span><?= h(substr((string) $exception['debut'], 0, 5)) ?> – <?= h(substr((string) $exception['fin'], 0, 5)) ?></span>
          <form method="post">
            <?= champ_jeton() ?>
            <input type="hidden" name="action" value="plage_date_supprimer">
            <input type="hidden" name="jour_date" value="<?= h($jourChoisi) ?>">
            <input type="hidden" name="exception" value="<?= (int) $exception['id'] ?>">
            <button type="submit" class="espace__retirer" aria-label="Retirer cette plage">×</button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="post" class="formulaire espace__ligne">
    <?= champ_jeton() ?>
    <input type="hidden" name="action" value="jour_travaille">
    <input type="hidden" name="jour_date" value="<?= h($jourChoisi) ?>">
    <div class="champ">
      <label for="debut">Je travaille de</label>
      <input id="debut" name="debut" type="time" value="<?= h($plagesChoisies ? substr($plagesChoisies[0][0], 0, 5) : '08:00') ?>" required>
    </div>
    <div class="champ">
      <label for="fin">à</label>
      <input id="fin" name="fin" type="time" value="<?= h($plagesChoisies ? substr($plagesChoisies[0][1], 0, 5) : '18:00') ?>" required>
    </div>
    <div class="champ champ--case">
      <label><input type="checkbox" name="repeter" value="1"> Aussi les <?= h(mb_strtolower(JOURS()[(int) $dateChoisie->format('N')])) ?>s des <?= SEMAINES_AFFICHEES - 1 ?> semaines suivantes</label>
    </div>
    <div class="formulaire__pied">
      <button type="submit" class="bouton">Enregistrer</button>
    </div>
  </form>

  <div class="espace__actions-jour">
    <form method="post">
      <?= champ_jeton() ?>
      <input type="hidden" name="action" value="jour_repos">
      <input type="hidden" name="jour_date" value="<?= h($jourChoisi) ?>">
      <button type="submit" class="bouton bouton--creux bouton--petit">Je ne travaille pas ce jour</button>
    </form>

    <?php if ($surMesure): ?>
      <form method="post">
        <?= champ_jeton() ?>
        <input type="hidden" name="action" value="jour_habituel">
        <input type="hidden" name="jour_date" value="<?= h($jourChoisi) ?>">
        <button type="submit" class="bouton bouton--creux bouton--petit">Revenir à mes horaires habituels</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<h2 id="habituels">Mes horaires habituels</h2>
<p class="espace__aide">
  Facultatif. Ils servent pour toutes les dates que tu n'as pas réglées à la main.
  Si tes horaires changent toutes les semaines, laisse cette partie vide et utilise
  seulement le calendrier.
</p>

<div class="espace__semaine">
  <?php foreach (JOURS() as $numero => $nom): ?>
    <div class="espace__jour">
      <h3><?= h($nom) ?></h3>
      <?php if (!$parJour[$numero]): ?>
        <p class="espace__aide">—</p>
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
    <label for="habituel-debut">De</label>
    <input id="habituel-debut" name="debut" type="time" value="08:00" required>
  </div>
  <div class="champ">
    <label for="habituel-fin">À</label>
    <input id="habituel-fin" name="fin" type="time" value="18:00" required>
  </div>
  <div class="formulaire__pied">
    <button type="submit" class="bouton bouton--creux">Ajouter</button>
  </div>
</form>
<?php pied(); ?>
