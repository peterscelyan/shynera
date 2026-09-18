<?php
/* ==========================================================================
   SHYNERA — TES TARIFS
   --------------------------------------------------------------------------
   C'EST LE SEUL FICHIER A MODIFIER POUR LES PRIX.
   La page d'accueil, la fenetre de reservation et le serveur lisent tous
   ici : il ne peut jamais y avoir deux prix differents quelque part.

   - prix    : en euros, pour une citadine
   - duree   : en minutes (sert a calculer les creneaux libres)
   - exterieur : true si la prestation touche a la carrosserie. Dans ce cas
     la reservation demande aussi un acces a l'eau.
   - remise_lancement : pourcentage retire sur tout l'achat. Mets 0 pour
     arreter l'offre : les prix barres et les encarts disparaissent partout.
   ========================================================================== */

return [

    'remise_lancement' => 25,

    /* --- Types de vehicules et supplement de gabarit ---------------------- */
    'vehicules' => [
        'citadine'   => ['nom' => 'Citadine',              'exemples' => 'Clio, 208, Polo…',          'supplement' => 0],
        'berline'    => ['nom' => 'Berline ou break',      'exemples' => 'Golf, Octavia, Passat…',    'supplement' => 10],
        'suv'        => ['nom' => 'SUV ou monospace',      'exemples' => 'Tiguan, 3008, Scénic…',     'supplement' => 15],
        'utilitaire' => ['nom' => 'Utilitaire ou 7 places', 'exemples' => 'Berlingo, Kangoo, Touran…', 'supplement' => 25],
    ],

    /* --- Les prestations a l'unite ---------------------------------------- */
    'prestations' => [

        'interieur' => [
            'titre'       => 'Intérieur essentiel',
            'prix'        => 70,
            'duree'       => 90,
            'duree_texte' => '1 h 30',
            'exterieur'   => false,
            'disponible'  => true,
            'vedette'     => false,
            'marque'      => null,
            'resume'      => "L'entretien régulier, pour une voiture qui sert tous les jours.",
            'inclus'      => [
                'Aspiration complète&nbsp;: sièges, moquettes, coffre, rails',
                'Nettoyage des plastiques et du tableau de bord',
                'Vitres intérieures sans traces',
                'Seuils de portes et joints',
                'Poubelle vidée, tapis brossés',
            ],
        ],

        'interieur-exterieur' => [
            'titre'       => 'Intérieur + extérieur',
            'prix'        => 95,
            'duree'       => 150,
            'duree_texte' => '2 h 30',
            'exterieur'   => true,
            'disponible'  => true,
            'vedette'     => true,
            'marque'      => 'La plus demandée',
            'resume'      => 'La formule complète, intérieur et carrosserie en une seule visite.',
            'inclus'      => [
                "Tout l'intérieur essentiel",
                'Prélavage et lavage à la main, méthode deux seaux',
                'Jantes, passages de roues et bas de caisse',
                'Séchage en microfibre, sans traces de calcaire',
                'Brillance pneus et plastiques extérieurs',
            ],
        ],

        'renovation-interieure' => [
            'titre'       => 'Remise à neuf intérieure',
            'prix'        => 180,
            'duree'       => 240,
            'duree_texte' => '4 h',
            'exterieur'   => false,
            'disponible'  => true,
            'vedette'     => false,
            'marque'      => null,
            'resume'      => "Pour une voiture à revendre, une fin de leasing, ou un intérieur qu'on n'ose plus montrer.",
            'inclus'      => [
                "Tout l'intérieur essentiel",
                'Shampoing des sièges par injection-extraction',
                'Shampoing des moquettes et du coffre',
                'Nettoyage du ciel de toit',
                'Traitement nourrissant des plastiques',
                'Traitement des odeurs à la source',
            ],
        ],

        /* Pas encore proposee : mets 'disponible' => true quand tu as le
           materiel, et complete le prix et la duree. */
        'renovation-complete' => [
            'titre'        => 'Remise à neuf complète',
            'prix'         => 0,
            'duree'        => 360,
            'duree_texte'  => '',
            'exterieur'    => true,
            'disponible'   => false,
            'vedette'      => false,
            'marque'       => 'À venir',
            'texte_prix'   => 'Bientôt disponible',
            'bouton'       => 'Être prévenu du lancement',
            'lien'         => 'mailto:{{EMAIL}}?subject=Pr%C3%A9venez-moi%20du%20lancement%20de%20la%20remise%20%C3%A0%20neuf%20compl%C3%A8te',
            'resume'       => "L'intérieur remis à neuf, et une carrosserie qui retrouve son éclat.",
            'inclus'       => [
                'Toute la remise à neuf intérieure',
                'Décontamination de la carrosserie',
                'Polissage des micro-rayures',
                'Protection longue durée de la peinture',
            ],
        ],
    ],

    /* --- Supplements proposes pendant la reservation ----------------------- */
    'supplements' => [
        ['code' => 'poils',    'nom' => "Poils d'animaux",        'detail' => 'Poils incrustés dans les sièges, la moquette ou le coffre', 'montant' => 15],
        ['code' => 'encrasse', 'nom' => 'Véhicule très encrassé', 'detail' => 'Boue, sable, taches importantes ou déchets',                'montant' => 20],
    ],

    /* Phrase affichee sous les tarifs. */
    'note' => 'Shampoing des sièges seul&nbsp;: 80 €. Les suppléments éventuels vous sont toujours annoncés avant de venir, jamais au moment de payer.',

    /* --- Les abonnements. Ils ne beneficient pas de l'offre de lancement. --- */
    'abonnements' => [
        [
            'titre'     => 'Intérieur mensuel',
            'prix'      => 60,
            'reference' => 70,
            'passages'  => 1,
            'vedette'   => false,
            'marque'    => null,
            'resume'    => 'Un habitacle net toute l\'année, pour la voiture du quotidien.',
            'inclus'    => ['Un intérieur essentiel chaque mois', 'Le même créneau réservé pour vous', 'Rappel par message la veille', 'Priorité sur le planning'],
        ],
        [
            'titre'     => 'Complet mensuel',
            'prix'      => 85,
            'reference' => 95,
            'passages'  => 1,
            'vedette'   => true,
            'marque'    => 'Le meilleur rapport',
            'resume'    => 'Intérieur et carrosserie une fois par mois, en une seule visite.',
            'inclus'    => ['Un intérieur + extérieur chaque mois', 'Le même créneau réservé pour vous', 'Rappel par message la veille', 'Priorité sur le planning'],
        ],
        [
            'titre'     => 'Complet deux fois par mois',
            'prix'      => 160,
            'reference' => 190,
            'passages'  => 2,
            'vedette'   => false,
            'marque'    => null,
            'resume'    => 'Pour une voiture qui doit toujours faire bonne impression.',
            'inclus'    => ['Deux intérieur + extérieur par mois', 'Un passage toutes les deux semaines', 'Rappel par message la veille', 'Priorité sur le planning'],
        ],
    ],
];
