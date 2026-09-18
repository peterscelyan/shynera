<?php
/* ==========================================================================
   SHYNERA — calcul des prix
   Un seul endroit pour la regle : prix de base + supplement du vehicule,
   puis l'offre de lancement sur le total, arrondie a l'euro inferieur.
   Les valeurs viennent de tarifs.php.
   ========================================================================== */

declare(strict_types=1);

/* Ajoute un numero de version a un fichier (style.css, script.js...) :
   le navigateur recharge le fichier des qu'il change, et garde en memoire
   tant qu'il ne change pas. Evite d'avoir a vider son cache. */
function version(string $fichier): string
{
    $fichier = ltrim($fichier, '/');
    $chemin  = __DIR__ . '/../' . $fichier;
    $date    = is_file($chemin) ? filemtime($chemin) : time();
    /* Adresse depuis la racine du site : valable aussi depuis /espace/. */
    return '/' . $fichier . '?v=' . $date;
}

function tarifs(): array
{
    static $tarifs = null;
    if ($tarifs === null) {
        $tarifs = require __DIR__ . '/../tarifs.php';
    }
    return $tarifs;
}

function remise_lancement(): int
{
    return max(0, (int) tarifs()['remise_lancement']);
}

function prestation(string $code): ?array
{
    return tarifs()['prestations'][$code] ?? null;
}

function vehicule(string $code): ?array
{
    return tarifs()['vehicules'][$code] ?? null;
}

/* Prix d'une prestation pour un vehicule, avant l'offre de lancement. */
function prix_prestation(string $codePrestation, string $codeVehicule): int
{
    $p = prestation($codePrestation);
    $v = vehicule($codeVehicule);
    if (!$p || !$v) {
        return 0;
    }
    return (int) $p['prix'] + (int) $v['supplement'];
}

/* Applique l'offre de lancement a un montant. */
function prix_avec_remise(int $montant): int
{
    $remise = remise_lancement();
    return $remise > 0 ? (int) floor($montant * (100 - $remise) / 100) : $montant;
}

/**
 * Detail complet d'un achat.
 *
 * @param string[] $codesSupplements
 * @return array{prestation:int, supplements:int, brut:int, reduction:int, total:int}
 */
function detail_achat(string $codePrestation, string $codeVehicule, array $codesSupplements): array
{
    $prestation = prix_prestation($codePrestation, $codeVehicule);

    $supplements = 0;
    foreach (tarifs()['supplements'] as $supplement) {
        if (in_array($supplement['code'], $codesSupplements, true)) {
            $supplements += (int) $supplement['montant'];
        }
    }

    $brut  = $prestation + $supplements;
    $total = prix_avec_remise($brut);

    return [
        'prestation'  => $prestation,
        'supplements' => $supplements,
        'brut'        => $brut,
        'reduction'   => $brut - $total,
        'total'       => $total,
    ];
}

/* Les donnees dont la fenetre de reservation a besoin dans le navigateur. */
function tarifs_pour_navigateur(): array
{
    $t = tarifs();

    $prestations = [];
    foreach ($t['prestations'] as $code => $p) {
        $prestations[] = [
            'code'       => $code,
            'titre'      => $p['titre'],
            'prix'       => (int) $p['prix'],
            'duree'      => (int) $p['duree'],
            'dureeTexte' => $p['duree_texte'],
            'exterieur'  => (bool) $p['exterieur'],
            'disponible' => (bool) $p['disponible'],
        ];
    }

    $vehicules = [];
    foreach ($t['vehicules'] as $code => $v) {
        $vehicules[] = [
            'code'       => $code,
            'nom'        => $v['nom'],
            'exemples'   => $v['exemples'],
            'supplement' => (int) $v['supplement'],
        ];
    }

    $abonnements = array_map(static fn(array $a): array => [
        'titre'     => $a['titre'],
        'prix'      => (int) $a['prix'],
        'reference' => (int) $a['reference'],
        'passages'  => (int) $a['passages'],
    ], $t['abonnements']);

    return [
        'remise'       => remise_lancement(),
        'vehicules'    => $vehicules,
        'prestations'  => $prestations,
        'abonnements'  => $abonnements,
        'supplements'  => array_map(static fn(array $s): array => [
            'code'    => $s['code'],
            'nom'     => $s['nom'],
            'detail'  => $s['detail'],
            'montant' => (int) $s['montant'],
        ], $t['supplements']),
    ];
}
