<?php
/* ==========================================================================
   SHYNERA — calcul des creneaux libres
   --------------------------------------------------------------------------
   Pour chaque travailleur, on part de ses horaires habituels, on enleve ses
   conges, on ajoute ses journees exceptionnelles, puis on retire les
   rendez-vous deja pris et le temps de trajet autour.

   Les reglages se changent ici, dans reglages().
   ========================================================================== */

declare(strict_types=1);

require_once __DIR__ . '/bdd.php';

function reglages(): array
{
    return [
        'pas'             => 30,  /* un creneau propose toutes les 30 minutes */
        'marge_trajet'    => 30,  /* minutes libres avant et apres chaque rendez-vous */
        'delai_minimum'   => 24,  /* heures : on ne reserve pas pour tout de suite */
        'horizon_jours'   => 56,  /* on ouvre la reservation sur 8 semaines */
        'annulation'      => 24,  /* heures avant le rendez-vous */
        'ouverture'       => '08:00', /* premiere heure ou l'equipe peut travailler */
        'fermeture'       => '20:00', /* derniere heure */
    ];
}

/* Les heures proposees dans l'espace pro : de l'ouverture a la fermeture,
   toutes les 30 minutes. */
function heures_possibles(): array
{
    $r = reglages();
    $heures = [];
    $heure = strtotime($r['ouverture']);
    $fin   = strtotime($r['fermeture']);
    while ($heure <= $fin) {
        $heures[] = date('H:i', $heure);
        $heure += $r['pas'] * 60;
    }
    return $heures;
}

function heure_dans_ouverture(string $heure): bool
{
    return in_array($heure, heures_possibles(), true);
}

/* Une date en francais : "lundi 21 septembre 2026 a 8 h 30". */
function strftime_fr(DateTimeInterface $date, bool $avecHeure = true, bool $avecAnnee = false): string
{
    $jours = ['Sunday' => 'dimanche', 'Monday' => 'lundi', 'Tuesday' => 'mardi', 'Wednesday' => 'mercredi',
              'Thursday' => 'jeudi', 'Friday' => 'vendredi', 'Saturday' => 'samedi'];
    $mois  = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
              'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

    $texte = $jours[$date->format('l')] . ' ' . (int) $date->format('j') . ' ' . $mois[(int) $date->format('n')];
    if ($avecAnnee) {
        $texte .= ' ' . $date->format('Y');
    }
    if ($avecHeure) {
        $texte .= ' à ' . heure_fr($date);
    }
    return $texte;
}

/* Le mois en toutes lettres : "septembre". */
function mois_fr(DateTimeInterface $date): string
{
    $mois = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
             'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    return $mois[(int) $date->format('n')];
}

/* "8 h 30", sans zero devant. */
function heure_fr(DateTimeInterface $date): string
{
    return (int) $date->format('G') . ' h ' . $date->format('i');
}

/* Une duree en minutes : "1 h 30", "45 min". */
function duree_fr(int $minutes): string
{
    if ($minutes < 60) {
        return $minutes . ' min';
    }
    $heures = intdiv($minutes, 60);
    $reste  = $minutes % 60;
    return $heures . ' h' . ($reste ? ' ' . str_pad((string) $reste, 2, '0', STR_PAD_LEFT) : '');
}

/* Le nom de la commune correspondant a un code postal. */
function commune_du_code(string $codePostal): ?string
{
    static $zones = null;
    if ($zones === null) {
        $zones = require __DIR__ . '/../zones.php';
    }
    foreach ($zones as $commune => $codes) {
        if (in_array((int) $codePostal, $codes, true)) {
            return $commune;
        }
    }
    return null;
}

/* Les travailleurs qui peuvent recevoir des rendez-vous. */
function travailleurs_actifs(): array
{
    return bdd()->query('SELECT id, nom FROM travailleurs WHERE actif = 1 ORDER BY nom')->fetchAll();
}

/* Horaires habituels : [travailleur_id][jour de la semaine] = liste de plages. */
function horaires_habituels(): array
{
    $par = [];
    foreach (bdd()->query('SELECT travailleur_id, jour, debut, fin FROM disponibilites')->fetchAll() as $ligne) {
        $par[(int) $ligne['travailleur_id']][(int) $ligne['jour']][] = [$ligne['debut'], $ligne['fin']];
    }
    return $par;
}

/* Conges et journees ajoutees, entre deux dates. */
function exceptions_entre(string $du, string $au): array
{
    $requete = bdd()->prepare('SELECT travailleur_id, jour_date, genre, debut, fin FROM exceptions WHERE jour_date BETWEEN ? AND ?');
    $requete->execute([$du, $au]);
    $par = [];
    foreach ($requete->fetchAll() as $ligne) {
        $par[(int) $ligne['travailleur_id']][$ligne['jour_date']][] = $ligne;
    }
    return $par;
}

/* Rendez-vous confirmes, entre deux dates. */
function reservations_entre(string $du, string $au): array
{
    $requete = bdd()->prepare("SELECT travailleur_id, debut, fin FROM reservations WHERE statut = 'confirmee' AND debut <= ? AND fin >= ?");
    $requete->execute([$au . ' 23:59:59', $du . ' 00:00:00']);
    $par = [];
    foreach ($requete->fetchAll() as $ligne) {
        $par[(int) $ligne['travailleur_id']][] = [strtotime($ligne['debut']), strtotime($ligne['fin'])];
    }
    return $par;
}

/* Les plages de travail d'un travailleur pour une journee donnee.
   REGLE : ce qui est ecrit sur une date precise remplace les horaires
   habituels de ce jour-la. Sans rien sur la date, ce sont les horaires
   habituels qui s'appliquent. */
function plages_du_jour(int $travailleurId, DateTimeImmutable $jour, array $horaires, array $exceptions): array
{
    $duJour = $exceptions[$travailleurId][$jour->format('Y-m-d')] ?? [];

    if ($duJour) {
        $plages = [];
        foreach ($duJour as $exception) {
            if ($exception['genre'] === 'ferme') {
                return [];   /* journee entiere retiree */
            }
            if ($exception['debut'] && $exception['fin']) {
                $plages[] = [$exception['debut'], $exception['fin']];
            }
        }
        return $plages;
    }

    return $horaires[$travailleurId][(int) $jour->format('N')] ?? [];
}

/* Les plages d'un travailleur pour une date, telles qu'affichees dans
   l'espace pro (avec l'origine : habituel ou propre a cette date). */
function plages_affichees(int $travailleurId, DateTimeImmutable $jour): array
{
    $exceptions = exceptions_entre($jour->format('Y-m-d'), $jour->format('Y-m-d'));
    $duJour = $exceptions[$travailleurId][$jour->format('Y-m-d')] ?? [];
    $plages = plages_du_jour($travailleurId, $jour, horaires_habituels(), $exceptions);

    return ['plages' => $plages, 'sur_mesure' => (bool) $duJour];
}

/* Un rendez-vous est possible si, trajet compris, il ne touche aucun autre. */
function creneau_libre(int $debut, int $fin, array $rendezVous, int $margeSecondes): bool
{
    foreach ($rendezVous as [$occupeDebut, $occupeFin]) {
        if ($debut < $occupeFin + $margeSecondes && $fin + $margeSecondes > $occupeDebut) {
            return false;
        }
    }
    return true;
}

/* --------------------------------------------------------------------------
   Protection des rendez-vous deja pris
   -------------------------------------------------------------------------- */

/* Les rendez-vous confirmes d'un travailleur, un jour donne. */
function reservations_du_jour(int $travailleurId, string $date): array
{
    $requete = bdd()->prepare(
        "SELECT * FROM reservations
         WHERE travailleur_id = ? AND statut = 'confirmee' AND debut >= ? AND debut <= ?
         ORDER BY debut"
    );
    $requete->execute([$travailleurId, $date . ' 00:00:00', $date . ' 23:59:59']);
    return $requete->fetchAll();
}

/* Le rendez-vous tient-il entierement dans une des plages proposees ? */
function reservation_couverte(array $reservation, array $plages): bool
{
    $debut = substr($reservation['debut'], 11, 5);
    $fin   = substr($reservation['fin'], 11, 5);
    foreach ($plages as [$d, $f]) {
        if (substr((string) $d, 0, 5) <= $debut && $fin <= substr((string) $f, 0, 5)) {
            return true;
        }
    }
    return false;
}

/* Un autre travailleur pourrait-il prendre ce rendez-vous ? Renvoie son id. */
function remplacant_possible(array $reservation, int $exclure): ?int
{
    $jour       = new DateTimeImmutable(substr($reservation['debut'], 0, 10));
    $horaires   = horaires_habituels();
    $exceptions = exceptions_entre($jour->format('Y-m-d'), $jour->format('Y-m-d'));
    $marge      = reglages()['marge_trajet'] * 60;
    $debut      = strtotime($reservation['debut']);
    $fin        = strtotime($reservation['fin']);

    foreach (travailleurs_actifs() as $travailleur) {
        $id = (int) $travailleur['id'];
        if ($id === $exclure) {
            continue;
        }

        /* Le creneau tient-il dans ses heures de travail ce jour-la ? */
        $dansSesHeures = false;
        foreach (plages_du_jour($id, $jour, $horaires, $exceptions) as [$d, $f]) {
            $ouverture = strtotime($jour->format('Y-m-d') . ' ' . $d);
            $fermeture = strtotime($jour->format('Y-m-d') . ' ' . $f);
            if ($debut >= $ouverture && $fin <= $fermeture) {
                $dansSesHeures = true;
                break;
            }
        }
        if (!$dansSesHeures) {
            continue;
        }

        /* Est-il libre a ce moment-la, trajet compris ? */
        $siens = reservations_entre($jour->format('Y-m-d'), $jour->modify('+1 day')->format('Y-m-d'));
        if (creneau_libre($debut, $fin, $siens[$id] ?? [], $marge)) {
            return $id;
        }
    }
    return null;
}

/**
 * Ce que ce changement d'horaire ferait aux rendez-vous deja pris.
 *
 * @return array liste de ['reservation' => ligne, 'remplacant' => id ou null]
 */
function consequences_changement(int $travailleurId, string $date, array $nouvellesPlages): array
{
    $consequences = [];
    foreach (reservations_du_jour($travailleurId, $date) as $reservation) {
        if (reservation_couverte($reservation, $nouvellesPlages)) {
            continue;
        }
        $consequences[] = [
            'reservation' => $reservation,
            'remplacant'  => remplacant_possible($reservation, $travailleurId),
        ];
    }
    return $consequences;
}

/**
 * Creneaux libres, jour par jour.
 *
 * @param int $duree      duree de la prestation, en minutes
 * @param string $depuis  premier jour regarde (AAAA-MM-JJ)
 * @param int $nbJours    nombre de jours regardes
 * @return array liste de jours : ['date' => ..., 'creneaux' => [['heure' => '08:00', 'travailleurs' => [id, ...]]]]
 */
function creneaux_libres(int $duree, string $depuis, int $nbJours): array
{
    $r = reglages();
    $pas   = $r['pas'] * 60;
    $marge = $r['marge_trajet'] * 60;
    $duree = max(15, $duree) * 60;

    $premier = new DateTimeImmutable($depuis . ' 00:00:00');
    $dernier = $premier->modify('+' . ($nbJours - 1) . ' days');
    $plusTot = (new DateTimeImmutable())->modify('+' . $r['delai_minimum'] . ' hours')->getTimestamp();
    $limite  = (new DateTimeImmutable())->modify('+' . $r['horizon_jours'] . ' days')->getTimestamp();

    $travailleurs = travailleurs_actifs();
    $horaires     = horaires_habituels();
    $exceptions   = exceptions_entre($premier->format('Y-m-d'), $dernier->format('Y-m-d'));
    $rendezVous   = reservations_entre($premier->format('Y-m-d'), $dernier->modify('+1 day')->format('Y-m-d'));

    $jours = [];

    for ($i = 0; $i < $nbJours; $i++) {
        $jour = $premier->modify('+' . $i . ' days');
        $parHeure = [];

        foreach ($travailleurs as $travailleur) {
            $id = (int) $travailleur['id'];
            foreach (plages_du_jour($id, $jour, $horaires, $exceptions) as [$debutPlage, $finPlage]) {
                $ouverture  = strtotime($jour->format('Y-m-d') . ' ' . $debutPlage);
                $fermeture  = strtotime($jour->format('Y-m-d') . ' ' . $finPlage);

                for ($debut = $ouverture; $debut + $duree <= $fermeture; $debut += $pas) {
                    if ($debut < $plusTot || $debut > $limite) {
                        continue;
                    }
                    if (!creneau_libre($debut, $debut + $duree, $rendezVous[$id] ?? [], $marge)) {
                        continue;
                    }
                    $parHeure[date('H:i', $debut)][] = $id;
                }
            }
        }

        if (!$parHeure) {
            continue;
        }

        ksort($parHeure);
        $creneaux = [];
        foreach ($parHeure as $heure => $ids) {
            $creneaux[] = ['heure' => $heure, 'travailleurs' => array_values(array_unique($ids))];
        }
        $jours[] = ['date' => $jour->format('Y-m-d'), 'creneaux' => $creneaux];
    }

    return $jours;
}
