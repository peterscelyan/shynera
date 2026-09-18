<?php
/* Les prix, durees et formules viennent tous de tarifs.php. */
require __DIR__ . '/espace/prix.php';
$tarifs = tarifs();
$remise = remise_lancement();
$zones  = require __DIR__ . '/zones.php';
?>
<!doctype html>
<html lang="fr-BE">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- ==========================================================================
     A REMPLIR AVANT LA MISE EN LIGNE
     --------------------------------------------------------------------------
     Ouvre index.php, contact.php ET tarifs.php dans Notepad++, fais Ctrl+H
     (Remplacer), coche "Dans tous les documents ouverts", et remplace ces
     marqueurs un par un :

       {{NOM_COMPLET}}   prenom + nom        ex. Celyan Dupont
       {{TELEPHONE}}     format lisible      ex. +32 493 12 34 56
       {{TEL_BRUT}}      sans espaces ni +   ex. 32493123456
       {{EMAIL}}         ex. contact@shynera.be
       {{ADRESSE}}       adresse du siege, obligatoire legalement
       {{BCE}}           numero d'entreprise ex. 0123.456.789
       {{LIEN_RESERVATION}}  ton lien Cal.com

     Avis Google (section "Avis") :
       {{NOTE_GOOGLE}}         ta note moyenne     ex. 4,9
       {{NB_AVIS}}             nombre d'avis       ex. 12
       {{LIEN_AVIS_GOOGLE}}    lien vers ta fiche Google (tous les avis)
       {{LIEN_LAISSER_AVIS}}   lien "Ecrire un avis" fourni par Google
       {{AVIS_1}} {{AVIS_2}} {{AVIS_3}}              texte de 3 vrais avis
       {{AVIS_1_NOM}} {{AVIS_2_NOM}} {{AVIS_3_NOM}}  prenom + initiale

     Tant qu'il reste une accolade double dans le fichier, le site n'est pas pret.
     ========================================================================== -->

<title>Nettoyage de voiture à domicile à Liège | Shynera</title>
<meta name="description" content="Nettoyage automobile à domicile à Liège et dans toute la province. Intérieur, extérieur et remise à neuf. Déplacement compris, vous pouvez payer après la prestation.">
<link rel="canonical" href="https://shynera.be/">

<!-- VERSION 0 : le site n'est pas encore public. Supprime la ligne ci-dessous
     le jour de la publication (v1), et remets robots.txt en "Allow: /". -->
<meta name="robots" content="noindex, nofollow">

<meta name="theme-color" content="#16202B">
<link rel="icon" type="image/svg+xml" href="favicon.svg">

<!-- Partage sur Facebook, WhatsApp, LinkedIn -->
<meta property="og:type" content="website">
<meta property="og:locale" content="fr_BE">
<meta property="og:site_name" content="Shynera">
<meta property="og:title" content="Nettoyage de voiture à domicile à Liège | Shynera">
<meta property="og:description" content="Je viens chez vous avec mon matériel. Intérieur, extérieur et remise à neuf, déplacement compris.">
<meta property="og:url" content="https://shynera.be/">
<meta property="og:image" content="https://shynera.be/images/exemple-1-apres.webp">
<meta name="twitter:card" content="summary_large_image">

<link rel="stylesheet" href="style.css">

<!-- Fiche d'entreprise lue par Google. Mets a jour la zone desservie
     et la fourchette de prix si tu changes tes tarifs. -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "AutoWash",
  "name": "Shynera",
  "description": "Nettoyage automobile à domicile à Liège et en province de Liège.",
  "url": "https://shynera.be/",
  "telephone": "{{TELEPHONE}}",
  "email": "{{EMAIL}}",
  "image": "https://shynera.be/images/exemple-1-apres.webp",
  "priceRange": "70 € – 205 €",
  "currenciesAccepted": "EUR",
  "openingHours": "Mo-Sa 08:00-20:00",
  "address": { "@type": "PostalAddress", "addressLocality": "Liège", "addressCountry": "BE" },
  "areaServed": [
    { "@type": "City", "name": "Liège" },
    { "@type": "City", "name": "Ans" },
    { "@type": "City", "name": "Herstal" },
    { "@type": "City", "name": "Seraing" },
    { "@type": "City", "name": "Chaudfontaine" },
    { "@type": "City", "name": "Fléron" },
    { "@type": "City", "name": "Saint-Nicolas" },
    { "@type": "City", "name": "Grâce-Hollogne" },
    { "@type": "City", "name": "Flémalle" },
    { "@type": "City", "name": "Awans" },
    { "@type": "City", "name": "Oupeye" },
    { "@type": "City", "name": "Visé" },
    { "@type": "City", "name": "Beyne-Heusay" },
    { "@type": "City", "name": "Soumagne" },
    { "@type": "City", "name": "Esneux" },
    { "@type": "City", "name": "Neupré" },
    { "@type": "City", "name": "Juprelle" },
    { "@type": "City", "name": "Blegny" }
  ]
}
</script>

<!-- Questions / reponses. Google peut les afficher directement dans ses
     resultats. Si tu modifies une question dans la page, modifie-la ici aussi. -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    { "@type": "Question", "name": "Faut-il que je sois présent pendant la prestation ?",
      "acceptedAnswer": { "@type": "Answer", "text": "Non. Il me faut simplement les clés et un accès au véhicule. Beaucoup de clients me laissent travailler pendant qu'ils sont au bureau ou à la maison. Je vous envoie les photos avant et après par message dans tous les cas." } },
    { "@type": "Question", "name": "Avez-vous besoin d'eau et d'électricité ?",
      "acceptedAnswer": { "@type": "Answer", "text": "Pour une prestation intérieure, une simple prise de courant à 50 mètres maximum du véhicule suffit : pas besoin d'eau. Pour une prestation extérieure, il me faut aussi un robinet accessible près du véhicule." } },
    { "@type": "Question", "name": "Combien de temps avant de pouvoir réutiliser la voiture ?",
      "acceptedAnswer": { "@type": "Answer", "text": "Immédiatement après un nettoyage intérieur classique. Après un shampoing des sièges par injection-extraction, comptez trois à quatre heures de séchage, ou une nuit si le temps est humide." } },
    { "@type": "Question", "name": "Que se passe-t-il s'il pleut le jour du rendez-vous ?",
      "acceptedAnswer": { "@type": "Answer", "text": "L'intérieur se fait par tous les temps, du moment que la voiture est accessible. Pour l'extérieur, je vous propose un report sans frais et je vous contacte la veille si la météo ne le permet pas." } },
    { "@type": "Question", "name": "Intervenez-vous si je n'ai ni garage ni allée ?",
      "acceptedAnswer": { "@type": "Answer", "text": "Oui pour l'intérieur : une place de stationnement devant chez vous suffit. Pour l'extérieur c'est plus délicat, car certaines communes encadrent le lavage sur la voie publique." } },
    { "@type": "Question", "name": "Les taches anciennes partent-elles vraiment ?",
      "acceptedAnswer": { "@type": "Answer", "text": "Souvent, mais pas toujours. Une tache de boisson ou de nourriture part généralement bien. Le gras incrusté, l'encre et les décolorations dues au soleil résistent parfois. Je regarde le véhicule avant de commencer et je vous dis ce que je peux obtenir." } },
    { "@type": "Question", "name": "Et si je ne suis pas satisfait ?",
      "acceptedAnswer": { "@type": "Answer", "text": "Vous me le dites sur place avant que je reparte, et je reprends le travail sans supplément. Vous pouvez payer après la prestation, une fois que vous avez vu le résultat." } },
    { "@type": "Question", "name": "Travaillez-vous avec les sociétés ? Fournissez-vous une facture ?",
      "acceptedAnswer": { "@type": "Answer", "text": "Oui aux deux. Je fournis une facture pour chaque prestation. Pour les indépendants et les sociétés, l'entretien d'un véhicule professionnel est en principe déductible : renseignez-vous auprès de votre comptable." } }
  ]
}
</script>
</head>

<body>

<a href="#contenu" class="cache">Aller au contenu</a>

<!-- ==========================================================================
     EN-TETE — reste colle en haut de l'ecran pendant le defilement.
     Le logo ramene toujours a la page d'accueil.
     Le menu mobile fonctionne sans JavaScript : la case a cocher cachee
     #menu-bascule ouvre et ferme la navigation via le CSS.
     ========================================================================== -->
<header class="entete">
  <div class="enveloppe entete__interieur">

    <a href="/" class="marque" aria-label="Shynera, retour à l'accueil" data-accueil>
      <span class="marque__mot">Shynera</span>
      <span class="marque__trait" aria-hidden="true"></span>
    </a>

    <input type="checkbox" id="menu-bascule" class="cache">
    <label for="menu-bascule" class="menu-bouton"><span aria-hidden="true"></span>Menu</label>

    <nav class="nav" aria-label="Navigation principale">
      <ul class="nav__liste">
        <li><a href="#avant-apres">Avant / après</a></li>
        <li><a href="#avis">Avis</a></li>
        <li><a href="#tarifs">Tarifs</a></li>
        <li><a href="#abonnements">Abonnements</a></li>
        <li><a href="#professionnels">Professionnels</a></li>
        <li><a href="#recrutement">Recrutement</a></li>
        <li><a href="#faq">FAQ</a></li>
        <li><a href="#contact">Contact</a></li>
      </ul>
      <a href="{{LIEN_RESERVATION}}" class="bouton bouton--petit" data-reserver>Réserver</a>
    </nav>

  </div>
</header>

<main id="contenu">

<!-- ==========================================================================
     HERO — la seule zone visible sans faire defiler la page.
     Quatre choses doivent y tenir : le titre avec le mot-cle et la ville,
     la promesse concrete, le bouton, et une preuve visuelle.
     Ne rajoute rien ici : chaque element supplementaire pousse le bouton
     sous la ligne de flottaison.
     ========================================================================== -->
<section class="hero">
  <div class="enveloppe hero__grille">

    <div class="hero__texte">
      <?php if ($remise > 0): ?>
      <a href="#tarifs" class="hero__offre">
        Offre de lancement&nbsp;: <strong>−<?= $remise ?>&nbsp;%</strong> pour mes 20 premiers clients
      </a>
      <?php endif; ?>
      <h1>Nettoyage de voiture à domicile à Liège</h1>
      <p class="hero__accroche">
        Je viens chez vous, avec mon matériel. Vous ne déplacez pas votre voiture,
        vous ne perdez pas votre samedi, et vous pouvez payer après la prestation.
      </p>
      <div class="boutons">
        <a href="{{LIEN_RESERVATION}}" class="bouton" data-reserver>Réserver un créneau</a>
        <a href="https://wa.me/{{TEL_BRUT}}" class="bouton bouton--creux" rel="noopener">Écrire sur WhatsApp</a>
      </div>
      <ul class="hero__garanties">
        <li>Déplacement compris dans le prix</li>
        <li>Vous pouvez payer après la prestation</li>
        <li>Créneaux en soirée et le week-end</li>
      </ul>
    </div>

    <div class="hero__visuel">
      <!-- Comparateur. Les deux images d'une paire DOIVENT avoir exactement
           les memes dimensions et le meme cadrage, sinon le curseur decale. -->
      <figure class="comparateur" data-comparateur>
        <div class="comparateur__scene">
          <img class="comparateur__image" src="images/exemple-1-apres.webp" width="1200" height="900" decoding="async"
               alt="Siège conducteur en tissu après shampoing, Volkswagen Golf, Liège">
          <div class="comparateur__volet" data-volet>
            <img class="comparateur__image" src="images/exemple-1-avant.webp" width="1200" height="900" decoding="async" alt="" aria-hidden="true">
          </div>
          <div class="comparateur__ligne" data-ligne aria-hidden="true"><span></span></div>
          <span class="comparateur__etiquette comparateur__etiquette--avant">Avant</span>
          <span class="comparateur__etiquette comparateur__etiquette--apres">Après</span>
          <label class="cache" for="curseur-hero">Comparer avant et après&nbsp;: siège conducteur</label>
          <input class="comparateur__curseur" data-curseur type="range" min="0" max="100" value="50" step="1" id="curseur-hero">
        </div>
        <figcaption class="comparateur__legende">
          <strong>Siège conducteur en tissu</strong>
          <span>Volkswagen Golf, shampoing par injection-extraction</span>
        </figcaption>
      </figure>
    </div>

  </div>
</section>

<!-- ==========================================================================
     1. AVANT / APRES — le seul argument que le visiteur peut verifier lui-meme.

     AJOUTER UNE PAIRE DE PHOTOS : copie un bloc <figure> ... </figure> entier,
     colle-le a la suite, puis change :
       - les deux noms d'images (exemple-7-apres.webp, exemple-7-avant.webp) ;
       - le texte alt et la legende ;
       - le numero dans for="curseur-7" ET id="curseur-7" (unique par paire).
     Les fleches apparaissent toutes seules des qu'il y a plus de photos que
     de place a l'ecran. Mets tes plus belles paires en premier.
     ========================================================================== -->
<section class="section section--nuit" id="avant-apres">
  <div class="enveloppe">
    <div class="cesure cesure--actions">
      <h2>Le seul argument que vous pouvez vérifier vous-même</h2>
      <div class="carrousel__fleches" data-carrousel-fleches hidden>
        <button type="button" class="carrousel__fleche" data-carrousel-precedent aria-label="Photos précédentes">←</button>
        <button type="button" class="carrousel__fleche" data-carrousel-suivant aria-label="Photos suivantes">→</button>
      </div>
    </div>
    <div class="galerie" data-carrousel aria-label="Photos avant / après">

      <figure class="comparateur" data-comparateur>
        <div class="comparateur__scene">
          <img class="comparateur__image" src="images/exemple-2-apres.webp" width="1200" height="900" loading="lazy" decoding="async"
               alt="Moquettes de Renault Clio après nettoyage en profondeur">
          <div class="comparateur__volet" data-volet>
            <img class="comparateur__image" src="images/exemple-2-avant.webp" width="1200" height="900" loading="lazy" decoding="async" alt="" aria-hidden="true">
          </div>
          <div class="comparateur__ligne" data-ligne aria-hidden="true"><span></span></div>
          <span class="comparateur__etiquette comparateur__etiquette--avant">Avant</span>
          <span class="comparateur__etiquette comparateur__etiquette--apres">Après</span>
          <label class="cache" for="curseur-2">Comparer avant et après&nbsp;: moquettes</label>
          <input class="comparateur__curseur" data-curseur type="range" min="0" max="100" value="50" step="1" id="curseur-2">
        </div>
        <figcaption class="comparateur__legende">
          <strong>Moquettes et tapis</strong>
          <span>Renault Clio, véhicule rendu en fin de leasing</span>
        </figcaption>
      </figure>

      <figure class="comparateur" data-comparateur>
        <div class="comparateur__scene">
          <img class="comparateur__image" src="images/exemple-3-apres.webp" width="1200" height="900" loading="lazy" decoding="async"
               alt="Coffre de break après retrait des poils d'animaux">
          <div class="comparateur__volet" data-volet>
            <img class="comparateur__image" src="images/exemple-3-avant.webp" width="1200" height="900" loading="lazy" decoding="async" alt="" aria-hidden="true">
          </div>
          <div class="comparateur__ligne" data-ligne aria-hidden="true"><span></span></div>
          <span class="comparateur__etiquette comparateur__etiquette--avant">Avant</span>
          <span class="comparateur__etiquette comparateur__etiquette--apres">Après</span>
          <label class="cache" for="curseur-3">Comparer avant et après&nbsp;: coffre</label>
          <input class="comparateur__curseur" data-curseur type="range" min="0" max="100" value="50" step="1" id="curseur-3">
        </div>
        <figcaption class="comparateur__legende">
          <strong>Coffre après transport d'un chien</strong>
          <span>Skoda Octavia break, poils et traitement des odeurs</span>
        </figcaption>
      </figure>

      <figure class="comparateur" data-comparateur>
        <div class="comparateur__scene">
          <img class="comparateur__image" src="images/exemple-4-apres.webp" width="1200" height="900" loading="lazy" decoding="async"
               alt="Tableau de bord de Peugeot 208 après nettoyage détaillé">
          <div class="comparateur__volet" data-volet>
            <img class="comparateur__image" src="images/exemple-4-avant.webp" width="1200" height="900" loading="lazy" decoding="async" alt="" aria-hidden="true">
          </div>
          <div class="comparateur__ligne" data-ligne aria-hidden="true"><span></span></div>
          <span class="comparateur__etiquette comparateur__etiquette--avant">Avant</span>
          <span class="comparateur__etiquette comparateur__etiquette--apres">Après</span>
          <label class="cache" for="curseur-4">Comparer avant et après&nbsp;: tableau de bord</label>
          <input class="comparateur__curseur" data-curseur type="range" min="0" max="100" value="50" step="1" id="curseur-4">
        </div>
        <figcaption class="comparateur__legende">
          <strong>Tableau de bord et console</strong>
          <span>Peugeot 208, nettoyage détaillé des plastiques</span>
        </figcaption>
      </figure>

      <!-- PHOTO D'EXEMPLE : remplace les images exemple-5 et la legende. -->
      <figure class="comparateur" data-comparateur>
        <div class="comparateur__scene">
          <img class="comparateur__image" src="images/exemple-5-apres.webp" width="1200" height="900" loading="lazy" decoding="async"
               alt="Sièges arrière après nettoyage (photo d'exemple)">
          <div class="comparateur__volet" data-volet>
            <img class="comparateur__image" src="images/exemple-5-avant.webp" width="1200" height="900" loading="lazy" decoding="async" alt="" aria-hidden="true">
          </div>
          <div class="comparateur__ligne" data-ligne aria-hidden="true"><span></span></div>
          <span class="comparateur__etiquette comparateur__etiquette--avant">Avant</span>
          <span class="comparateur__etiquette comparateur__etiquette--apres">Après</span>
          <label class="cache" for="curseur-5">Comparer avant et après&nbsp;: sièges arrière</label>
          <input class="comparateur__curseur" data-curseur type="range" min="0" max="100" value="50" step="1" id="curseur-5">
        </div>
        <figcaption class="comparateur__legende">
          <strong>Sièges arrière</strong>
          <span>Photo d'exemple, à remplacer</span>
        </figcaption>
      </figure>

      <!-- PHOTO D'EXEMPLE : remplace les images exemple-6 et la legende. -->
      <figure class="comparateur" data-comparateur>
        <div class="comparateur__scene">
          <img class="comparateur__image" src="images/exemple-6-apres.webp" width="1200" height="900" loading="lazy" decoding="async"
               alt="Volant et commandes après nettoyage (photo d'exemple)">
          <div class="comparateur__volet" data-volet>
            <img class="comparateur__image" src="images/exemple-6-avant.webp" width="1200" height="900" loading="lazy" decoding="async" alt="" aria-hidden="true">
          </div>
          <div class="comparateur__ligne" data-ligne aria-hidden="true"><span></span></div>
          <span class="comparateur__etiquette comparateur__etiquette--avant">Avant</span>
          <span class="comparateur__etiquette comparateur__etiquette--apres">Après</span>
          <label class="cache" for="curseur-6">Comparer avant et après&nbsp;: volant et commandes</label>
          <input class="comparateur__curseur" data-curseur type="range" min="0" max="100" value="50" step="1" id="curseur-6">
        </div>
        <figcaption class="comparateur__legende">
          <strong>Volant et commandes</strong>
          <span>Photo d'exemple, à remplacer</span>
        </figcaption>
      </figure>

    </div>
  </div>
</section>

<!-- ==========================================================================
     2. AVIS GOOGLE
     Copie ici de VRAIS avis depuis ta fiche Google, mot pour mot.
     N'invente jamais de temoignage : un seul faux avis decredibilise tout,
     et c'est une pratique commerciale interdite.
     Ne conditionne jamais une reduction a un avis : Google l'interdit et
     peut supprimer tes avis. Demande-le simplement apres la prestation.
     ========================================================================== -->
<section class="section section--brume" id="avis">
  <div class="enveloppe">
    <div class="cesure">
      <h2>Ce que mes clients en disent</h2>
    </div>

    <div class="avis__resume">
      <span class="avis__note">{{NOTE_GOOGLE}}</span>
      <span class="avis__etoiles" role="img" aria-label="Note de {{NOTE_GOOGLE}} sur 5">★★★★★</span>
      <span class="avis__total">sur {{NB_AVIS}} avis Google</span>
    </div>

    <!-- AJOUTER UN AVIS : copie un bloc <blockquote> ... </blockquote> entier,
         colle-le a la suite, et remplace le texte et le nom. Autant d'avis
         que tu veux : ils defilent tout seuls, lentement, en boucle.
         Si l'avis a moins de 5 etoiles, retire des ★ et change le "5 etoiles". -->
    <div class="avis" data-avis-defilement>
      <div class="avis__piste">
        <blockquote class="avis__carte">
          <span class="avis__etoiles" role="img" aria-label="5 étoiles sur 5">★★★★★</span>
          <p>{{AVIS_1}}</p>
          <cite>{{AVIS_1_NOM}}</cite>
        </blockquote>
        <blockquote class="avis__carte">
          <span class="avis__etoiles" role="img" aria-label="5 étoiles sur 5">★★★★★</span>
          <p>{{AVIS_2}}</p>
          <cite>{{AVIS_2_NOM}}</cite>
        </blockquote>
        <blockquote class="avis__carte">
          <span class="avis__etoiles" role="img" aria-label="5 étoiles sur 5">★★★★★</span>
          <p>{{AVIS_3}}</p>
          <cite>{{AVIS_3_NOM}}</cite>
        </blockquote>
      </div>
    </div>

    <div class="boutons avis__actions">
      <a href="{{LIEN_AVIS_GOOGLE}}" class="bouton bouton--creux" rel="noopener">Voir tous les avis sur Google</a>
      <a href="{{LIEN_LAISSER_AVIS}}" class="lien-souligne" rel="noopener">Laisser un avis</a>
      <button type="button" class="avis__pause" data-avis-pause aria-pressed="false" hidden>Mettre en pause</button>
    </div>
  </div>
</section>

<!-- ==========================================================================
     3. TARIFS — les offres a l'unite
     Tout vient de tarifs.php : prix, durees, contenu des formules, offre de
     lancement. Ne modifie pas les prix ici, modifie tarifs.php.
     ========================================================================== -->
<section class="section" id="tarifs">
  <div class="enveloppe">
    <div class="cesure">
      <h2>Des prix affichés, pas des devis</h2>
    </div>
    <p class="intro">
      Choisissez votre type de véhicule, les prix s'adaptent. Le déplacement est toujours compris,
      et le prix affiché est celui que vous payez.
    </p>

    <?php if ($remise > 0): ?>
    <div class="lancement">
      <p class="lancement__pourcentage">−<?= $remise ?>&nbsp;%</p>
      <div>
        <p class="lancement__titre">Offre de lancement</p>
        <p class="lancement__offre">
          Pour mes 20 premiers clients, sur toutes les prestations à l'unité.
          Les prix ci-dessous tiennent déjà compte de la réduction.
        </p>
        <p class="lancement__condition">
          En échange, j'aimerais simplement pouvoir utiliser les photos avant/après de votre véhicule.
        </p>
      </div>
    </div>
    <?php endif; ?>

    <fieldset class="gabarit" data-gabarit>
      <legend>Votre véhicule</legend>
      <div class="gabarit__choix">
        <?php $premier = true; foreach ($tarifs['vehicules'] as $code => $v): ?>
        <label><input type="radio" name="gabarit" value="<?= $code ?>"<?= $premier ? ' checked' : '' ?>><span><?= $v['nom'] ?><small><?= $v['exemples'] ?></small></span></label>
        <?php $premier = false; endforeach; ?>
      </div>
    </fieldset>
    <p class="cache" aria-live="polite" data-gabarit-annonce></p>

    <div class="formules formules--4">
      <?php foreach ($tarifs['prestations'] as $code => $p): ?>
      <article class="formule<?= $p['vedette'] ? ' formule--vedette' : '' ?><?= $p['disponible'] ? '' : ' formule--bientot' ?>" data-offre="<?= $code ?>">
        <?php if ($p['marque']): ?><p class="formule__marque"><?= $p['marque'] ?></p><?php endif; ?>
        <h3><?= $p['titre'] ?></h3>

        <?php if ($p['disponible']): ?>
          <?php $normal = prix_prestation($code, 'citadine'); $reduit = prix_avec_remise($normal); ?>
          <p class="formule__prix">
            <span data-prix-prestation="<?= $code ?>"><?= $reduit ?></span> €
            <?php if ($reduit !== $normal): ?>
              <del class="formule__barre"><span class="cache">au lieu de </span><span data-prix-normal="<?= $code ?>"><?= $normal ?></span> €</del>
            <?php endif; ?>
            <em>· <?= $p['duree_texte'] ?></em>
          </p>
        <?php else: ?>
          <p class="formule__prix formule__prix--bientot"><?= $p['texte_prix'] ?? 'Bientôt disponible' ?></p>
        <?php endif; ?>

        <p class="formule__resume"><?= $p['resume'] ?></p>
        <ul class="formule__inclus">
          <?php foreach ($p['inclus'] as $ligne): ?><li><?= $ligne ?></li><?php endforeach; ?>
        </ul>

        <?php if ($p['disponible']): ?>
          <a href="{{LIEN_RESERVATION}}" class="bouton <?= $p['vedette'] ? '' : 'bouton--creux ' ?>formule__action" data-reserver data-prestation="<?= $code ?>">Réserver</a>
        <?php else: ?>
          <a href="<?= $p['lien'] ?? '#contact' ?>" class="bouton bouton--creux formule__action"><?= $p['bouton'] ?? 'Être prévenu' ?></a>
        <?php endif; ?>
      </article>
      <?php endforeach; ?>
    </div>

    <p class="formules__note"><?= $tarifs['note'] ?></p>
  </div>
</section>

<!-- ==========================================================================
     4. ABONNEMENTS
     Eux aussi viennent de tarifs.php. Ils ne beneficient pas de l'offre de
     lancement, mais suivent le supplement du vehicule choisi.
     Les boutons ouvrent WhatsApp avec un message deja redige, parce qu'un
     abonnement demande de fixer un creneau ensemble.
     ========================================================================== -->
<section class="section section--brume" id="abonnements">
  <div class="enveloppe">
    <div class="cesure">
      <h2>Une voiture toujours propre, sans y penser</h2>
    </div>
    <p class="intro">
      Je reviens à date fixe, vous n'avez plus rien à organiser. Chaque passage coûte moins cher
      qu'à l'unité, et vous pouvez payer après la prestation.
    </p>

    <!-- Le script place ici une copie du selecteur "Votre vehicule" de la
         section Tarifs : les deux restent synchronises. Rien a modifier ici. -->
    <div data-gabarit-copie></div>

    <div class="formules">
      <?php foreach ($tarifs['abonnements'] as $rang => $a): ?>
      <article class="formule<?= $a['vedette'] ? ' formule--vedette' : '' ?>">
        <?php if ($a['marque']): ?><p class="formule__marque"><?= $a['marque'] ?></p><?php endif; ?>
        <h3><?= $a['titre'] ?></h3>
        <p class="formule__prix"><span data-prix-abonnement="<?= $rang ?>"><?= $a['prix'] ?></span> € <em>/ mois</em></p>
        <p class="formule__economie">Au lieu de <span data-prix-reference="<?= $rang ?>"><?= $a['reference'] ?></span> € à l'unité</p>
        <p class="formule__resume"><?= $a['resume'] ?></p>
        <ul class="formule__inclus">
          <?php foreach ($a['inclus'] as $ligne): ?><li><?= $ligne ?></li><?php endforeach; ?>
        </ul>
        <a href="https://wa.me/{{TEL_BRUT}}?text=<?= rawurlencode("Bonjour, je suis intéressé par l'abonnement " . $a['titre'] . '.') ?>" class="bouton <?= $a['vedette'] ? '' : 'bouton--creux ' ?>formule__action" rel="noopener">Choisir cet abonnement</a>
      </article>
      <?php endforeach; ?>
    </div>

    <p class="formules__note">
      Sans engagement de durée&nbsp;: vous arrêtez quand vous voulez, en me prévenant un mois à l'avance.
    </p>
  </div>
</section>

<!-- ==========================================================================
     5. PROFESSIONNELS — demande de devis
     Le bouton ouvre un e-mail deja structure : tu recois directement
     les informations dont tu as besoin pour chiffrer.
     ========================================================================== -->
<section class="section section--nuit" id="professionnels">
  <div class="enveloppe entreprises">
    <div>
      <div class="cesure">
        <h2>Vos véhicules propres sans immobiliser personne</h2>
      </div>
      <p class="intro">
        J'interviens sur votre parking pendant les heures de bureau, ou directement chez vos
        collaborateurs. Personne ne perd une demi-journée dans une station de lavage.
      </p>
      <p class="entreprises__devis">
        Dites-moi combien de véhicules, lesquels, où et à quelle fréquence&nbsp;:
        je vous envoie un devis détaillé.
      </p>
      <div class="boutons">
        <a href="mailto:{{EMAIL}}?subject=Demande%20de%20devis%20professionnel&amp;body=Bonjour%2C%0D%0A%0D%0ASoci%C3%A9t%C3%A9%20%3A%20%0D%0ANombre%20de%20v%C3%A9hicules%20%3A%20%0D%0AType%20de%20v%C3%A9hicules%20%3A%20%0D%0AAdresse%20du%20site%20%3A%20%0D%0AFr%C3%A9quence%20souhait%C3%A9e%20%3A%20%0D%0A%0D%0AMerci." class="bouton">Demander un devis</a>
        <a href="https://wa.me/{{TEL_BRUT}}" class="bouton bouton--creux" rel="noopener">En parler sur WhatsApp</a>
      </div>
    </div>
    <ul class="arguments">
      <li><strong>Facture systématique</strong>Pour chaque prestation, particulier comme société.</li>
      <li><strong>Tarif dégressif</strong>À partir de trois véhicules traités le même jour sur le même site.</li>
      <li><strong>Fin de leasing</strong>Remise à neuf avant restitution, pour éviter les frais de remise en état.</li>
      <li><strong>Créneaux fixes</strong>Le même jour chaque mois, pour ne plus avoir à y penser.</li>
    </ul>
  </div>
</section>

<!-- ==========================================================================
     6. RECRUTEMENT
     A VALIDER : adapte les conditions (statuts, permis, horaires) a ce que
     tu proposes vraiment.
     ========================================================================== -->
<section class="section" id="recrutement">
  <div class="enveloppe recrutement">
    <div>
      <div class="cesure">
        <h2>Vous aimez le travail bien fait&nbsp;? Rejoignez-moi</h2>
      </div>
      <p class="intro">
        L'activité grandit et je cherche des renforts dans la région liégeoise. Je forme moi-même
        chaque personne à ma méthode&nbsp;: l'expérience n'est pas obligatoire, le soin et la ponctualité, si.
      </p>
      <div class="boutons">
        <a href="mailto:{{EMAIL}}?subject=Candidature%20Shynera&amp;body=Bonjour%2C%0D%0A%0D%0AJe%20m%27appelle%20%3A%20%0D%0AJ%27habite%20%C3%A0%20%3A%20%0D%0AMes%20disponibilit%C3%A9s%20%3A%20%0D%0APourquoi%20Shynera%20%3A%20%0D%0A%0D%0AMerci." class="bouton">Envoyer ma candidature</a>
      </div>
      <p class="recrutement__note">Quelques lignes suffisent, pas besoin de CV.</p>
    </div>
    <div class="recrutement__listes">
      <div>
        <h3>Ce que je cherche</h3>
        <ul class="puces">
          <li>Le souci du détail et de la fiabilité</li>
          <li>Permis B</li>
          <li>Des disponibilités en soirée ou le week-end</li>
          <li>Habiter la région liégeoise</li>
        </ul>
      </div>
      <div>
        <h3>Ce que je propose</h3>
        <ul class="puces">
          <li>Une formation complète à la méthode et au matériel</li>
          <li>Des horaires souples, compatibles avec des études</li>
          <li>Job étudiant, flexi-job ou temps partiel</li>
          <li>Du matériel professionnel fourni</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- ==========================================================================
     7. QUESTIONS FREQUENTES
     Si tu modifies une question ici, modifie-la aussi dans le bloc FAQPage
     tout en haut du fichier, sinon Google verra deux versions differentes.
     ========================================================================== -->
<section class="section section--brume" id="faq">
  <div class="enveloppe">
    <div class="cesure">
      <h2>Ce qu'on me demande avant de réserver</h2>
    </div>
    <div class="faq">

      <details class="faq__item">
        <summary>Faut-il que je sois présent pendant la prestation&nbsp;?</summary>
        <p>Non. Il me faut simplement les clés et un accès au véhicule. Beaucoup de clients me laissent travailler pendant qu'ils sont au bureau ou à la maison. Je vous envoie les photos avant et après par message dans tous les cas.</p>
      </details>

      <details class="faq__item">
        <summary>Avez-vous besoin d'eau et d'électricité&nbsp;?</summary>
        <p>Pour une prestation intérieure, une simple prise de courant à 50&nbsp;mètres maximum du véhicule suffit&nbsp;: pas besoin d'eau. Pour une prestation extérieure, il me faut aussi un robinet accessible près du véhicule. La question vous est posée au moment de réserver.</p>
      </details>

      <details class="faq__item">
        <summary>Combien de temps avant de pouvoir réutiliser la voiture&nbsp;?</summary>
        <p>Immédiatement après un nettoyage intérieur classique. Après un shampoing des sièges par injection-extraction, comptez trois à quatre heures de séchage, ou une nuit si le temps est humide. Je vous préviens toujours à l'avance.</p>
      </details>

      <details class="faq__item">
        <summary>Que se passe-t-il s'il pleut le jour du rendez-vous&nbsp;?</summary>
        <p>L'intérieur se fait par tous les temps, du moment que la voiture est accessible. Pour l'extérieur, je vous propose un report sans frais&nbsp;: je vous contacte la veille si la météo ne le permet pas.</p>
      </details>

      <details class="faq__item">
        <summary>Intervenez-vous si je n'ai ni garage ni allée&nbsp;?</summary>
        <p>Oui pour l'intérieur&nbsp;: une place de stationnement devant chez vous suffit. Pour l'extérieur, c'est plus délicat, car certaines communes encadrent le lavage sur la voie publique. Dites-moi votre adresse et je vous confirme ce qui est possible.</p>
      </details>

      <details class="faq__item">
        <summary>Les taches anciennes partent-elles vraiment&nbsp;?</summary>
        <p>Souvent, mais pas toujours. Une tache de boisson ou de nourriture part généralement bien, même après des mois. Le gras incrusté, l'encre et les décolorations dues au soleil résistent parfois. Je regarde le véhicule avant de commencer et je vous dis honnêtement ce que je peux obtenir.</p>
      </details>

      <details class="faq__item">
        <summary>Et si je ne suis pas satisfait&nbsp;?</summary>
        <p>Vous me le dites sur place, avant que je reparte, et je reprends le travail sans supplément. Vous pouvez payer après la prestation, une fois que vous avez vu le résultat.</p>
      </details>

      <details class="faq__item">
        <summary>Travaillez-vous avec les sociétés&nbsp;? Fournissez-vous une facture&nbsp;?</summary>
        <p>Oui aux deux. Je fournis une facture pour chaque prestation, particulier comme professionnel. Pour les indépendants et les sociétés, l'entretien d'un véhicule professionnel est en principe déductible&nbsp;: renseignez-vous auprès de votre comptable.</p>
      </details>

    </div>
  </div>
</section>

<!-- ==========================================================================
     8. CONTACT ET ZONE D'INTERVENTION
     Le formulaire envoie les messages a contact.php, qui te les transmet
     par e-mail. Il ne fonctionne qu'une fois le site en ligne chez Hostinger
     (pas en ouvrant le fichier sur ton ordinateur).

     Chaque commune citee ici aide sur les recherches locales. Plus tard, cree
     une vraie page par commune (liege.html, ans.html...) avec un texte
     different a chaque fois, jamais un copier-coller.
     ========================================================================== -->
<section class="section contact" id="contact">
  <div class="enveloppe">
    <div class="cesure">
      <h2>Une question&nbsp;? Écrivez-moi</h2>
    </div>

    <form class="formulaire" action="contact.php" method="post" data-formulaire-contact>
      <div class="champ">
        <label for="contact-nom">Nom</label>
        <input id="contact-nom" name="nom" type="text" autocomplete="name" required maxlength="100">
      </div>
      <div class="champ">
        <label for="contact-email">E-mail</label>
        <input id="contact-email" name="email" type="email" autocomplete="email" required maxlength="150">
      </div>
      <div class="champ champ--large">
        <label for="contact-telephone">Téléphone <small>(facultatif)</small></label>
        <input id="contact-telephone" name="telephone" type="tel" autocomplete="tel" maxlength="30">
      </div>
      <div class="champ champ--large">
        <label for="contact-message">Message</label>
        <textarea id="contact-message" name="message" rows="6" required maxlength="5000"></textarea>
      </div>

      <!-- Piege a robots : invisible pour les visiteurs, les robots le remplissent. -->
      <div class="formulaire__piege" aria-hidden="true">
        <label for="contact-site">Ne pas remplir</label>
        <input id="contact-site" name="site_web" type="text" tabindex="-1" autocomplete="off">
      </div>

      <div class="formulaire__pied">
        <button type="submit" class="bouton">Envoyer le message</button>
        <p class="formulaire__mention">
          Vos coordonnées servent uniquement à vous répondre.
          <a href="confidentialite.html">Confidentialité</a>
        </p>
      </div>
      <p class="formulaire__etat" role="status" data-formulaire-etat></p>
    </form>

    <div class="contact__zone">
      <h3>Où j'interviens</h3>
      <p>
        Liège et les communes voisines ci-dessous, sans frais de déplacement.
        Au-delà, écrivez-moi&nbsp;: je me déplace souvent plus loin quand le planning le permet.
      </p>
      <!-- La liste des communes et leurs codes postaux viennent de zones.php.
           C'est ce meme fichier qui sert a verifier le code postal pendant
           la reservation : un seul endroit a modifier. -->
      <ul class="zone">
        <?php foreach ($zones as $commune => $codes): ?>
        <li data-codes-postaux="<?= implode(' ', $codes) ?>"><span><?= $commune ?></span></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>

</main>

<!-- ==========================================================================
     PIED DE PAGE
     Le numero BCE et la mention de franchise TVA sont obligatoires.
     ========================================================================== -->
<footer class="pied">
  <div class="enveloppe pied__grille">

    <div>
      <p class="pied__marque">Shynera</p>
      <p>Nettoyage automobile à domicile à Liège et dans toute la province.</p>
      <p class="pied__contact">
        <a href="tel:+{{TEL_BRUT}}">{{TELEPHONE}}</a><br>
        <a href="mailto:{{EMAIL}}">{{EMAIL}}</a><br>
        Du lundi au samedi, 8h – 20h
      </p>
    </div>

    <div>
      <h2 class="pied__titre">Prestations</h2>
      <ul class="pied__liens">
        <li><a href="#tarifs">Tarifs</a></li>
        <li><a href="#abonnements">Abonnements</a></li>
        <li><a href="#professionnels">Devis professionnels</a></li>
        <li><a href="#avant-apres">Avant / après</a></li>
      </ul>
    </div>

    <div>
      <h2 class="pied__titre">Informations</h2>
      <ul class="pied__liens">
        <li><a href="#avis">Avis clients</a></li>
        <li><a href="#faq">Questions fréquentes</a></li>
        <li><a href="#recrutement">Recrutement</a></li>
        <li><a href="#contact">Contact</a></li>
      </ul>
    </div>

  </div>

  <div class="enveloppe pied__legal">
    <p>
      {{NOM_COMPLET}} — {{ADRESSE}} — Numéro d'entreprise BCE {{BCE}}.<br>
      Petite entreprise soumise au régime de la franchise de taxe. TVA non applicable.
    </p>
    <p class="pied__liens-legaux">
      <a href="mentions-legales.html">Mentions légales</a>
      <a href="confidentialite.html">Confidentialité</a>
      <span>© 2026 Shynera</span>
    </p>
  </div>
</footer>

<!-- ==========================================================================
     FENETRE DE RESERVATION
     S'ouvre sur tous les boutons marques data-reserver. Les etapes se
     suivent dans l'ordre ou elles sont ecrites ci-dessous.
     - Les vehicules viennent du selecteur "Votre vehicule" de la section Tarifs.
     - Les prestations, leurs durees et leurs prix viennent des cartes de la
       section Tarifs. Rien a dupliquer ici.
     - Seuls les supplements sont definis ici (data-montant) : si tu changes
       leur prix, change aussi la petite note sous les tarifs.
     Sans JavaScript, les boutons Reserver menent a {{LIEN_RESERVATION}}.
     ========================================================================== -->
<dialog class="reservation" data-reservation aria-labelledby="reservation-titre">
  <form class="reservation__cadre" method="dialog" data-reservation-formulaire novalidate>

    <header class="reservation__entete">
      <p class="reservation__titre" id="reservation-titre">Réserver une prestation</p>
      <button type="button" class="reservation__fermer" data-reservation-fermer aria-label="Fermer">×</button>
      <div class="reservation__progression" aria-hidden="true"><span data-reservation-barre></span></div>
      <p class="reservation__compteur" data-reservation-compteur></p>
    </header>

    <div class="reservation__corps">

      <section class="etape" data-etape="code-postal">
        <h2 class="etape__titre" tabindex="-1">Où se trouve le véhicule&nbsp;?</h2>
        <p class="etape__aide">J'interviens à Liège et dans les communes voisines, sans frais de déplacement.</p>
        <label class="etape__label" for="reservation-code-postal">Code postal</label>
        <input class="etape__champ" id="reservation-code-postal" name="code_postal" type="text"
               inputmode="numeric" autocomplete="postal-code" maxlength="8" placeholder="4000"
               aria-describedby="reservation-code-postal-erreur reservation-commune">
        <p class="etape__commune" id="reservation-commune" data-commune></p>
        <p class="etape__erreur" id="reservation-code-postal-erreur" role="alert" data-erreur-code-postal></p>
        <div class="etape__blocage" role="alert" data-hors-zone hidden>
          <p><strong>Je n'interviens pas encore dans cette commune.</strong></p>
          <p>Pour l'instant, je me déplace dans <span data-nombre-communes>18</span> communes&nbsp;: Liège et ses environs. Écrivez-moi quand même&nbsp;: je me déplace souvent plus loin quand le planning le permet.</p>
          <a href="https://wa.me/{{TEL_BRUT}}" class="lien-souligne" rel="noopener">M'écrire sur WhatsApp</a>
        </div>
      </section>

      <section class="etape" data-etape="vehicule" hidden>
        <h2 class="etape__titre" tabindex="-1">Quel est votre véhicule&nbsp;?</h2>
        <div class="choix-liste" data-liste-vehicules></div>
      </section>

      <section class="etape" data-etape="prestation" hidden>
        <h2 class="etape__titre" tabindex="-1">Quelle prestation&nbsp;?</h2>
        <?php if ($remise > 0): ?><p class="etape__aide">Offre de lancement&nbsp;: −<?= $remise ?>&nbsp;% déjà déduits.</p><?php endif; ?>
        <div class="choix-liste" data-liste-prestations></div>
      </section>

      <section class="etape" data-etape="supplements" hidden>
        <h2 class="etape__titre" tabindex="-1">Des suppléments&nbsp;?</h2>
        <p class="etape__aide">
          Cochez ce qui correspond à votre véhicule, ou continuez simplement.
          <?php if ($remise > 0): ?>L'offre de lancement s'applique aussi aux suppléments.<?php endif; ?>
        </p>
        <div class="choix-liste">
          <?php foreach ($tarifs['supplements'] as $s): ?>
          <label class="choix">
            <input type="checkbox" name="supplements" value="<?= $s['code'] ?>" data-montant="<?= (int) $s['montant'] ?>">
            <span class="choix__contenu">
              <strong><?= $s['nom'] ?></strong>
              <small><?= $s['detail'] ?></small>
              <span class="choix__prix">+<?= (int) $s['montant'] ?> €</span>
            </span>
          </label>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="etape" data-etape="acces" hidden>
        <!-- Titre et textes remplaces par le script selon la prestation :
             avec exterieur on demande l'eau et l'electricite, sinon l'electricite seule. -->
        <h2 class="etape__titre" tabindex="-1" data-acces-titre>Eau et électricité</h2>
        <p class="etape__aide" data-acces-aide>Pour une prestation extérieure, j'ai besoin d'une prise électrique à 50&nbsp;m maximum et d'un robinet près du véhicule.</p>
        <fieldset class="question">
          <legend>Une prise électrique accessible à 50&nbsp;m maximum du véhicule&nbsp;?</legend>
          <div class="question__choix">
            <label class="choix choix--compact"><input type="radio" name="electricite" value="oui"><span class="choix__contenu"><strong>Oui</strong></span></label>
            <label class="choix choix--compact"><input type="radio" name="electricite" value="non"><span class="choix__contenu"><strong>Non</strong></span></label>
          </div>
        </fieldset>
        <fieldset class="question" data-question-eau>
          <legend>Un robinet d'eau accessible près du véhicule&nbsp;?</legend>
          <div class="question__choix">
            <label class="choix choix--compact"><input type="radio" name="eau" value="oui"><span class="choix__contenu"><strong>Oui</strong></span></label>
            <label class="choix choix--compact"><input type="radio" name="eau" value="non"><span class="choix__contenu"><strong>Non</strong></span></label>
          </div>
        </fieldset>
        <div class="etape__blocage" role="alert" data-blocage hidden>
          <p><strong data-acces-blocage>Je ne peux malheureusement pas intervenir sans eau et électricité.</strong></p>
          <p>Si vous pensez qu'une solution est possible, écrivez-moi et on en parle.</p>
          <a href="https://wa.me/{{TEL_BRUT}}" class="lien-souligne" rel="noopener">M'écrire sur WhatsApp</a>
        </div>
      </section>

      <section class="etape" data-etape="creneau" hidden>
        <h2 class="etape__titre" tabindex="-1">Quel créneau&nbsp;?</h2>
        <p class="etape__aide">Les horaires affichés sont ceux où quelqu'un est réellement disponible.</p>
        <div class="creneaux" data-creneaux>
          <p class="etape__aide" data-creneaux-attente>Recherche des disponibilités…</p>
        </div>
      </section>

      <section class="etape" data-etape="travailleur" hidden>
        <h2 class="etape__titre" tabindex="-1">Avec qui&nbsp;?</h2>
        <p class="etape__aide" data-travailleur-aide></p>
        <div class="choix-liste" data-liste-travailleurs></div>
      </section>

      <section class="etape" data-etape="coordonnees" hidden>
        <h2 class="etape__titre" tabindex="-1">Vos coordonnées</h2>

        <dl class="recap" data-recap></dl>
        <p class="recap__total"><span>Total</span> <strong data-recap-total></strong></p>
        <p class="etape__aide">Déplacement compris. Vous pouvez payer après la prestation, en liquide ou par virement.</p>

        <div class="formulaire">
          <div class="champ">
            <label for="reservation-nom">Nom</label>
            <input id="reservation-nom" name="nom" type="text" autocomplete="name" maxlength="120" required>
          </div>
          <div class="champ">
            <label for="reservation-email">E-mail</label>
            <input id="reservation-email" name="email" type="email" autocomplete="email" maxlength="190" required>
          </div>
          <div class="champ">
            <label for="reservation-telephone">Téléphone</label>
            <input id="reservation-telephone" name="telephone" type="tel" autocomplete="tel" maxlength="40" required>
          </div>
          <div class="champ">
            <label for="reservation-adresse">Adresse <small>(rue et numéro)</small></label>
            <input id="reservation-adresse" name="adresse" type="text" autocomplete="street-address" maxlength="255" required>
          </div>
          <div class="champ champ--large">
            <label for="reservation-remarque">Quelque chose à signaler&nbsp;? <small>(facultatif)</small></label>
            <textarea id="reservation-remarque" name="remarque" rows="3" maxlength="2000"></textarea>
          </div>
          <div class="formulaire__piege" aria-hidden="true">
            <label for="reservation-site">Ne pas remplir</label>
            <input id="reservation-site" name="site_web" type="text" tabindex="-1" autocomplete="off">
          </div>
        </div>

        <p class="etape__aide">
          Vos coordonnées servent uniquement à réaliser la prestation.
          <a href="confidentialite.html">Confidentialité</a>
        </p>
        <p class="etape__erreur" role="alert" data-erreur-reservation></p>
      </section>

      <section class="etape etape--confirmation" data-etape="confirmation" hidden>
        <h2 class="etape__titre" tabindex="-1">C'est réservé</h2>
        <p class="etape__confirme" data-confirmation></p>
        <p class="etape__aide">
          Vous recevez un e-mail de confirmation, avec un lien si vous devez annuler.
          Vérifiez vos spams s'il n'arrive pas.
        </p>
        <button type="button" class="bouton" data-reservation-fermer>Fermer</button>
      </section>

    </div>

    <footer class="reservation__pied">
      <button type="button" class="bouton bouton--creux" data-reservation-retour>Retour</button>
      <p class="reservation__total" data-reservation-total aria-live="polite"></p>
      <button type="button" class="bouton" data-reservation-suivant>Continuer</button>
      <button type="button" class="bouton" data-reservation-confirmer hidden>Confirmer la réservation</button>
    </footer>

  </form>
</dialog>

<!-- Les tarifs, passes au script pour la fenetre de reservation. -->
<script>window.SHYNERA = <?= json_encode(tarifs_pour_navigateur(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="script.js"></script>

</body>
</html>
