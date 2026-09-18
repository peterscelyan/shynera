<?php
/* ==========================================================================
   SHYNERA — creneaux libres, lus par la fenetre de reservation
   --------------------------------------------------------------------------
   Appel : creneaux.php?prestation=interieur&depuis=2026-09-21&jours=14
   Reponse : la liste des jours qui ont au moins un creneau, avec pour chaque
   heure les travailleurs disponibles.
   ========================================================================== */

declare(strict_types=1);

require_once __DIR__ . '/../espace/planning.php';
require_once __DIR__ . '/../espace/prix.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function repondre(array $donnees, int $code = 200): never
{
    http_response_code($code);
    echo json_encode($donnees, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $codePrestation = (string) ($_GET['prestation'] ?? '');
    $prestation = prestation($codePrestation);

    if (!$prestation || !$prestation['disponible']) {
        repondre(['erreur' => 'Prestation inconnue.'], 400);
    }

    $depuis = (string) ($_GET['depuis'] ?? date('Y-m-d'));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $depuis)) {
        $depuis = date('Y-m-d');
    }
    /* On ne regarde jamais avant aujourd'hui. */
    if ($depuis < date('Y-m-d')) {
        $depuis = date('Y-m-d');
    }

    $jours = min(31, max(1, (int) ($_GET['jours'] ?? 14)));

    $noms = [];
    foreach (travailleurs_actifs() as $travailleur) {
        $noms[(int) $travailleur['id']] = $travailleur['nom'];
    }

    $reglages = reglages();

    repondre([
        'travailleurs' => $noms,
        'jours'        => creneaux_libres((int) $prestation['duree'], $depuis, $jours),
        'horizon'      => (new DateTimeImmutable())->modify('+' . $reglages['horizon_jours'] . ' days')->format('Y-m-d'),
    ]);
} catch (Throwable $e) {
    error_log('Shynera creneaux : ' . $e->getMessage());
    repondre(['erreur' => "Les créneaux n'ont pas pu être chargés."], 500);
}
