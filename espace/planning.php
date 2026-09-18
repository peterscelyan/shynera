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
    ];
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
   Un conge annule la journee entiere ; une journee ajoutee s'ajoute. */
function plages_du_jour(int $travailleurId, DateTimeImmutable $jour, array $horaires, array $exceptions): array
{
    $date = $jour->format('Y-m-d');
    $duJour = $exceptions[$travailleurId][$date] ?? [];

    foreach ($duJour as $exception) {
        if ($exception['genre'] === 'ferme') {
            return [];
        }
    }

    $plages = $horaires[$travailleurId][(int) $jour->format('N')] ?? [];
    foreach ($duJour as $exception) {
        if ($exception['genre'] === 'ouvert' && $exception['debut'] && $exception['fin']) {
            $plages[] = [$exception['debut'], $exception['fin']];
        }
    }
    return $plages;
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
