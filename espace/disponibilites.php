<?php
/* ==========================================================================
   SHYNERA — mes disponibilites
   --------------------------------------------------------------------------
   Deux facons de travailler, au choix :

   1. LE CALENDRIER (en haut), mois par mois : tu cliques sur une date et tu
      dis quand tu es disponible ce jour-la. Pratique quand les horaires
      changent tout le temps (etudiant, flexi-job). Ce que tu mets sur une
      date REMPLACE tes horaires habituels pour cette date.

   2. LES HORAIRES HABITUELS (en bas) : les memes heures chaque semaine. Ils
      servent de base pour toutes les dates que tu n'as pas reglees a la main.

   Un horaire ne peut pas etre reduit si un rendez-vous s'y trouve deja, sauf
   si un autre travailleur peut le reprendre : dans ce cas le rendez-vous lui
   est confie et tout le monde est prevenu par e-mail.
   ========================================================================== */

declare(strict_types=1);
require_once __DIR__ . '/commun.php';
require_once __DIR__ . '/planning.php';
require_once __DIR__ . '/courriel.php';

$travailleur = exiger_connexion();
$id = (int) $travailleur['id'];

/* Supprime tout ce qui a ete regle a la main sur une date. */
function vider_date(int $id, string $date): void
{
    $requete = bdd()->prepare('DELETE FROM exceptions WHERE travailleur_id = ? AND jour_date = ?');
    $requete->execute([$id, $date]);
}

/* Les dates visees : celle demandee, plus les memes jours de la semaine
   jusqu'a la fin du mois si "repeter" est coche. */
function dates_visees(string $date, bool $repeter): array
{
    $dates = [$date];
    if ($repeter) {
        $jour = new DateTimeImmutable($date);
        $mois = $jour->format('m');
        while (true) {
            $jour = $jour->modify('+1 week');
            if ($jour->format('m') !== $mois) {
                break;
            }
            $dates[] = $jour->format('Y-m-d');
        }
    }
    return $dates;
}

/* Confie un rendez-vous a un autre travailleur et previent tout le monde. */
function confier_reservation(array $reservation, int $nouveauId, string $ancienNom): void
{
    $requete = bdd()->prepare('UPDATE reservations SET travailleur_id = ? WHERE id = ?');
    $requete->execute([$nouveauId, $reservation['id']]);

    $requete = bdd()->prepare('SELECT nom, email FROM travailleurs WHERE id = ?');
    $requete->execute([$nouveauId]);
    $nouveau = $requete->fetch();
    if (!$nouveau) {
        return;
    }

    $quand = strftime_fr(new DateTimeImmutable($reservation['debut']), true, true);

    envoyer_courriel(
        $reservation['client_email'],
        'Votre rendez-vous Shynera : changement de personne',
        "Bonjour " . $reservation['client_nom'] . ",\n\n"
        . "Votre rendez-vous du " . $quand . " est maintenu, mais c'est " . $nouveau['nom']
        . " qui viendra, et non " . $ancienNom . ".\n\n"
        . "Tout le reste ne change pas : " . $reservation['prestation'] . ", " . $reservation['prix'] . " €.\n\n"
        . "À bientôt,\nShynera\n",
        $nouveau['email'],
        $nouveau['email']
    );

    envoyer_courriel(
        $nouveau['email'],
        'Rendez-vous qui te revient : ' . $reservation['client_nom'] . ' le ' . (new DateTimeImmutable($reservation['debut']))->format('d/m à H:i'),
        "Ce rendez-vous t'a été confié.\n\n"
        . $quand . "\n" . $reservation['prestation'] . " — " . $reservation['vehicule'] . "\n"
        . $reservation['client_nom'] . " — " . ($reservation['client_telephone'] ?: '-') . "\n"
        . ($reservation['adresse'] ? $reservation['adresse'] . ', ' : '') . $reservation['code_postal'] . "\n",
        $reservation['client_email'],
        $nouveau['email']
    );
}

/**
 * Applique un changement d'horaire sur une liste de dates, en protegeant les
 * rendez-vous. Renvoie [succes, message].
 */
function appliquer_changement(int $id, array $dates, array $nouvellesPlages, string $nomTravailleur): array
{
    $bloquants = [];
    $aConfier  = [];

    foreach ($dates as $date) {
        foreach (consequences_changement($id, $date, $nouvellesPlages) as $consequence) {
            if ($consequence['remplacant'] === null) {
                $bloquants[] = $consequence['reservation'];
            } else {
                $aConfier[] = $consequence;
            }
        }
    }

    if ($bloquants) {
        $details = [];
        foreach ($bloquants as $r) {
            $details[] = strftime_fr(new DateTimeImmutable($r['debut']), true) . ' (' . $r['client_nom'] . ')';
        }
        return [false, "Impossible : un rendez-vous est déjà pris à ce moment-là et personne d'autre n'est disponible — "
            . implode(', ', $details) . ". Contacte le client pour déplacer le rendez-vous, puis reviens ici."];
    }

    foreach ($aConfier as $consequence) {
        confier_reservation($consequence['reservation'], (int) $consequence['remplacant'], $nomTravailleur);
    }

    return [true, $aConfier ? count($aConfier) . ' rendez-vous confié(s) à un collègue, tout le monde a été prévenu.' : ''];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifier_jeton();
    $action  = (string) ($_POST['action'] ?? '');
    $date    = (string) ($_POST['jour_date'] ?? '');
    $mois    = (string) ($_POST['mois'] ?? '');
    $repeter = !empty($_POST['repeter']);
    $retour  = 'disponibilites.php?mois=' . urlencode($mois) . ($date !== '' ? '&jour=' . urlencode($date) : '') . '#jour';

    /* --- Calendrier : je travaille ce jour-la, de X a Y --- */
    if ($action === 'jour_travaille') {
        $debut = (string) ($_POST['debut'] ?? '');
        $fin   = (string) ($_POST['fin'] ?? '');

        if (!date_valide($date) || !heure_dans_ouverture($debut) || !heure_dans_ouverture($fin)) {
            message('Vérifie la date et les heures.', 'erreur');
        } elseif ($debut >= $fin) {
            message("L'heure de fin doit être après l'heure de début.", 'erreur');
        } else {
            $dates = dates_visees($date, $repeter);
            [$ok, $note] = appliquer_changement($id, $dates, [[$debut . ':00', $fin . ':00']], $travailleur['nom']);

            if (!$ok) {
                message($note, 'erreur');
            } else {
                $ajout  = bdd()->prepare('INSERT INTO exceptions (travailleur_id, jour_date, genre, debut, fin, motif) VALUES (?, ?, ?, ?, ?, NULL)');
                foreach ($dates as $jour) {
                    vider_date($id, $jour);
                    $ajout->execute([$id, $jour, 'ouvert', $debut . ':00', $fin . ':00']);
                }
                message(trim((count($dates) > 1 ? 'Enregistré pour ' . count($dates) . ' dates. ' : 'Enregistré. ') . $note));
            }
        }
    }

    /* --- Calendrier : je ne travaille pas ce jour-la --- */
    if ($action === 'jour_repos') {
        if (!date_valide($date)) {
            message('Date invalide.', 'erreur');
        } else {
            $dates = dates_visees($date, $repeter);
            [$ok, $note] = appliquer_changement($id, $dates, [], $travailleur['nom']);

            if (!$ok) {
                message($note, 'erreur');
            } else {
                $ajout = bdd()->prepare('INSERT INTO exceptions (travailleur_id, jour_date, genre, debut, fin, motif) VALUES (?, ?, ?, NULL, NULL, NULL)');
                foreach ($dates as $jour) {
                    vider_date($id, $jour);
                    $ajout->execute([$id, $jour, 'ferme']);
                }
                message(trim('Journée(s) marquée(s) comme non travaillée(s). ' . $note));
            }
        }
    }

    /* --- Calendrier : revenir aux horaires habituels --- */
    if ($action === 'jour_habituel') {
        $horaires = horaires_habituels();
        $habituel = $horaires[$id][(int) (new DateTimeImmutable($date))->format('N')] ?? [];
        [$ok, $note] = appliquer_changement($id, [$date], $habituel, $travailleur['nom']);

        if (!$ok) {
            message($note, 'erreur');
        } else {
            vider_date($id, $date);
            message(trim('Retour aux horaires habituels. ' . $note));
        }
    }

    /* --- Horaires habituels : ajouter une plage --- */
    if ($action === 'ajouter_plage') {
        $jour   = (int) ($_POST['jour'] ?? 0);
        $debut  = (string) ($_POST['debut'] ?? '');
        $fin    = (string) ($_POST['fin'] ?? '');
        $retour = 'disponibilites.php?mois=' . urlencode($mois) . '#habituels';

        if ($jour < 1 || $jour > 7 || !heure_dans_ouverture($debut) || !heure_dans_ouverture($fin)) {
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
                /* On garde les memes heures et on passe au jour suivant :
                   remplir la semaine se fait en cliquant "Ajouter" sept fois. */
                $_SESSION['habituel_suite'] = [
                    'jour'  => $jour === 7 ? 1 : $jour + 1,
                    'debut' => $debut,
                    'fin'   => $fin,
                ];
            }
        }
    }

    /* --- Horaires habituels : supprimer une plage --- */
    if ($action === 'supprimer_plage') {
        $requete = bdd()->prepare('DELETE FROM disponibilites WHERE id = ? AND travailleur_id = ?');
        $requete->execute([(int) ($_POST['plage'] ?? 0), $id]);
        message('Horaire habituel supprimé. Les dates réglées à la main ne changent pas.');
        $retour = 'disponibilites.php?mois=' . urlencode($mois) . '#habituels';
    }

    header('Location: ' . $retour);
    exit;
}

/* ---------------------------------------------------------------- Affichage */

$aujourdhui = new DateTimeImmutable('today');

/* Le mois affiche. */
$moisDemande = (string) ($_GET['mois'] ?? '');
$premierDuMois = preg_match('/^\d{4}-\d{2}$/', $moisDemande)
    ? new DateTimeImmutable($moisDemande . '-01')
    : $aujourdhui->modify('first day of this month');
$dernierDuMois = $premierDuMois->modify('last day of this month');

/* La grille commence au lundi precedent et finit au dimanche suivant. */
$debutGrille = $premierDuMois->modify('monday this week');
if ($debutGrille > $premierDuMois) {
    $debutGrille = $debutGrille->modify('-1 week');
}
$finGrille = $dernierDuMois->modify('sunday this week');
if ($finGrille < $dernierDuMois) {
    $finGrille = $finGrille->modify('+1 week');
}
$nbCases = (int) $debutGrille->diff($finGrille)->days + 1;

$horaires   = horaires_habituels();
$exceptions = exceptions_entre($debutGrille->format('Y-m-d'), $finGrille->format('Y-m-d'));

/* Rendez-vous du mois affiche, pour les montrer sur le calendrier. */
$requete = bdd()->prepare("SELECT debut FROM reservations WHERE travailleur_id = ? AND statut = 'confirmee' AND debut BETWEEN ? AND ?");
$requete->execute([$id, $debutGrille->format('Y-m-d 00:00:00'), $finGrille->format('Y-m-d 23:59:59')]);
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
$dateChoisie  = new DateTimeImmutable($jourChoisi);
$duJourChoisi = exceptions_entre($jourChoisi, $jourChoisi)[$id][$jourChoisi] ?? [];
$plagesChoisies = plages_du_jour($id, $dateChoisie, $horaires, [$id => [$jourChoisi => $duJourChoisi]]);
$surMesure      = (bool) $duJourChoisi;
$repos          = $surMesure && $duJourChoisi[0]['genre'] === 'ferme';
$rdvDuJour      = reservations_du_jour($id, $jourChoisi);

/* Plages habituelles, rangees par jour de la semaine. */
$requete = bdd()->prepare('SELECT * FROM disponibilites WHERE travailleur_id = ? ORDER BY jour, debut');
$requete->execute([$id]);
$parJour = array_fill_keys(array_keys(JOURS()), []);
foreach ($requete->fetchAll() as $plage) {
    $parJour[(int) $plage['jour']][] = $plage;
}

$heures = heures_possibles();
$moisCourant = $premierDuMois->format('Y-m');

/* Le formulaire des horaires habituels reprend la ou on s'est arrete. */
$suite = $_SESSION['habituel_suite'] ?? ['jour' => 1, 'debut' => '08:00', 'fin' => '18:00'];

/* Liste deroulante d'heures, par pas de 30 minutes. */
function choix_heure(string $nom, string $identifiant, string $valeur, array $heures): void
{
    echo '<select id="' . h($identifiant) . '" name="' . h($nom) . '">';
    foreach ($heures as $heure) {
        echo '<option value="' . h($heure) . '"' . ($heure === $valeur ? ' selected' : '') . '>' . h(str_replace(':', ' h ', $heure)) . '</option>';
    }
    echo '</select>';
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
  <a class="bouton bouton--creux bouton--petit"
     href="?mois=<?= $premierDuMois->modify('-1 month')->format('Y-m') ?>&amp;jour=<?= h($jourChoisi) ?>">← <?= h(mois_fr($premierDuMois->modify('-1 month'))) ?></a>
  <p class="calendrier__periode"><?= h(mois_fr($premierDuMois)) ?> <?= $premierDuMois->format('Y') ?></p>
  <a class="bouton bouton--creux bouton--petit"
     href="?mois=<?= $premierDuMois->modify('+1 month')->format('Y-m') ?>&amp;jour=<?= h($jourChoisi) ?>"><?= h(mois_fr($premierDuMois->modify('+1 month'))) ?> →</a>
</div>

<div class="calendrier">
  <?php foreach (JOURS() as $nomJour): ?>
    <div class="calendrier__entete"><?= h(mb_substr($nomJour, 0, 3)) ?>.</div>
  <?php endforeach; ?>

  <?php for ($i = 0; $i < $nbCases; $i++): ?>
    <?php
    $jour   = $debutGrille->modify('+' . $i . ' days');
    $cle    = $jour->format('Y-m-d');
    $plages = plages_du_jour($id, $jour, $horaires, $exceptions);
    $perso  = !empty($exceptions[$id][$cle]);

    $classes = 'calendrier__jour';
    if ($plages) $classes .= ' calendrier__jour--dispo';
    if ($perso)  $classes .= ' calendrier__jour--perso';
    if ($cle < $aujourdhui->format('Y-m-d')) $classes .= ' calendrier__jour--passe';
    if ($jour->format('Y-m') !== $moisCourant) $classes .= ' calendrier__jour--hors';
    if ($cle === $jourChoisi) $classes .= ' calendrier__jour--choisi';
    if ($cle === $aujourdhui->format('Y-m-d')) $classes .= ' calendrier__jour--aujourdhui';
    ?>
    <a class="<?= $classes ?>" href="?mois=<?= h($moisCourant) ?>&amp;jour=<?= $cle ?>#jour">
      <span class="calendrier__numero"><?= (int) $jour->format('j') ?></span>
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

  <?php if ($rdvDuJour): ?>
    <p class="espace__aide espace__aide--rdv">
      <?= count($rdvDuJour) ?> rendez-vous ce jour-là :
      <?php foreach ($rdvDuJour as $rang => $r): ?>
        <?= $rang ? ', ' : '' ?><strong><?= h(heure_fr(new DateTimeImmutable($r['debut']))) ?> – <?= h(heure_fr(new DateTimeImmutable($r['fin']))) ?></strong> (<?= h($r['client_nom']) ?>)<?= $rang === count($rdvDuJour) - 1 ? '.' : '' ?>
      <?php endforeach; ?>
      Tu ne peux pas retirer ces heures tant que le rendez-vous est confirmé.
    </p>
  <?php endif; ?>

  <form method="post" class="formulaire espace__ligne">
    <?= champ_jeton() ?>
    <input type="hidden" name="action" value="jour_travaille">
    <input type="hidden" name="jour_date" value="<?= h($jourChoisi) ?>">
    <input type="hidden" name="mois" value="<?= h($moisCourant) ?>">
    <div class="champ">
      <label for="debut">Je travaille de</label>
      <?php choix_heure('debut', 'debut', $plagesChoisies ? substr($plagesChoisies[0][0], 0, 5) : '08:00', $heures); ?>
    </div>
    <div class="champ">
      <label for="fin">à</label>
      <?php choix_heure('fin', 'fin', $plagesChoisies ? substr($plagesChoisies[0][1], 0, 5) : '18:00', $heures); ?>
    </div>
    <div class="champ champ--case">
      <label><input type="checkbox" name="repeter" value="1"> Aussi les <?= h(mb_strtolower(JOURS()[(int) $dateChoisie->format('N')])) ?>s suivants du mois</label>
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
      <input type="hidden" name="mois" value="<?= h($moisCourant) ?>">
      <button type="submit" class="bouton bouton--creux bouton--petit">Je ne travaille pas ce jour</button>
    </form>

    <?php if ($surMesure): ?>
      <form method="post">
        <?= champ_jeton() ?>
        <input type="hidden" name="action" value="jour_habituel">
        <input type="hidden" name="jour_date" value="<?= h($jourChoisi) ?>">
        <input type="hidden" name="mois" value="<?= h($moisCourant) ?>">
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
                <input type="hidden" name="mois" value="<?= h($moisCourant) ?>">
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
  <input type="hidden" name="mois" value="<?= h($moisCourant) ?>">
  <div class="champ">
    <label for="jour">Jour</label>
    <select id="jour" name="jour">
      <?php foreach (JOURS() as $numero => $nom): ?>
        <option value="<?= $numero ?>"<?= $numero === (int) $suite['jour'] ? ' selected' : '' ?>><?= h($nom) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="champ">
    <label for="habituel-debut">De</label>
    <?php choix_heure('debut', 'habituel-debut', (string) $suite['debut'], $heures); ?>
  </div>
  <div class="champ">
    <label for="habituel-fin">À</label>
    <?php choix_heure('fin', 'habituel-fin', (string) $suite['fin'], $heures); ?>
  </div>
  <div class="formulaire__pied">
    <button type="submit" class="bouton bouton--creux">Ajouter</button>
  </div>
</form>
<?php pied(); ?>
