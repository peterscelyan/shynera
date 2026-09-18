/* ==========================================================================
   SHYNERA — comportements de la page
   --------------------------------------------------------------------------
   1. Le logo ramene en haut de la page d'accueil.
   2. Le menu mobile se referme quand on choisit une section.
   3. La section en cours de lecture est soulignee dans la barre du haut.
   4. Le curseur avant / apres.
   5. Les prix selon le type de vehicule, et l'offre de lancement.
   6. L'envoi du formulaire de contact.
   7. La fenetre de reservation, etape par etape.
   8. Le carrousel des photos avant / apres.
   9. Le defilement des avis.
   Sans JavaScript, tout reste utilisable : ces comportements sont un plus.
   ========================================================================== */

var menuBascule = document.getElementById('menu-bascule');

/* 1. Logo. Sur une autre page, le lien index.html fait son travail normal.
      Sur la page d'accueil, on remonte en douceur au lieu de recharger. */
var logo = document.querySelector('[data-accueil]');
if (logo) {
  logo.addEventListener('click', function (evenement) {
    var page = location.pathname.split('/').pop();
    if (page !== '' && page !== 'index.html') return;
    evenement.preventDefault();
    if (menuBascule) menuBascule.checked = false;
    history.replaceState(null, '', location.pathname);
    window.scrollTo(0, 0);
  });
}

/* 2. Menu mobile. On le referme AVANT de defiler : sinon la page vise
      la section alors que le menu ouvert pousse encore tout vers le bas. */
document.querySelectorAll('.nav a').forEach(function (lien) {
  lien.addEventListener('click', function (evenement) {
    if (!menuBascule || !menuBascule.checked) return;
    menuBascule.checked = false;
    var href = lien.getAttribute('href');
    var cible = href.charAt(0) === '#' && document.getElementById(href.slice(1));
    if (!cible) return;
    evenement.preventDefault();
    history.pushState(null, '', href);
    cible.scrollIntoView(); /* defilement doux via le CSS, sauf si l'utilisateur l'a desactive */
  });
});

/* 3. Section en cours. */
var liensMenu = document.querySelectorAll('.nav__liste a[href^="#"]');
if ('IntersectionObserver' in window && liensMenu.length) {
  var parCible = {};
  liensMenu.forEach(function (lien) { parCible[lien.getAttribute('href').slice(1)] = lien; });

  var observateur = new IntersectionObserver(function (entrees) {
    entrees.forEach(function (entree) {
      if (!entree.isIntersecting) return;
      liensMenu.forEach(function (lien) { lien.removeAttribute('aria-current'); });
      var actif = parCible[entree.target.id];
      if (actif) actif.setAttribute('aria-current', 'true');
    });
  }, { rootMargin: '-45% 0px -50% 0px' });

  Object.keys(parCible).forEach(function (id) {
    var section = document.getElementById(id);
    if (section) observateur.observe(section);
  });
  /* De retour tout en haut, plus rien n'est souligne. */
  var hero = document.querySelector('.hero');
  if (hero) observateur.observe(hero);
}

/* 4. Curseur avant / apres. Il repose sur un <input type="range"> natif,
      donc il fonctionne a la souris, au doigt et au clavier.
      Quand une image arrive a l'ecran, le curseur fait un aller-retour tout
      seul pour montrer qu'on peut le deplacer. Une seule fois par image, et
      jamais si le visiteur a demande a son systeme de reduire les animations. */
var mouvementReduit = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

var PARCOURS_INDICE = [50, 22, 78, 50]; /* positions successives, en % */
var DUREE_ETAPE     = 600;              /* millisecondes par deplacement */

function montrerIndice(bloc, curseur, placer) {
  var interrompu = false;
  function arreter() { interrompu = true; bloc.classList.remove('comparateur--indice'); }
  curseur.addEventListener('pointerdown', arreter, { once: true });
  curseur.addEventListener('keydown', arreter, { once: true });

  bloc.classList.add('comparateur--indice');
  var debut = null;

  function image(temps) {
    if (interrompu) return;
    if (debut === null) debut = temps;
    var ecoule = temps - debut;
    var etape  = Math.floor(ecoule / DUREE_ETAPE);

    if (etape >= PARCOURS_INDICE.length - 1) {
      placer(curseur.value);
      bloc.classList.remove('comparateur--indice');
      return;
    }

    /* Acceleration puis freinage, pour un mouvement naturel. */
    var p = (ecoule % DUREE_ETAPE) / DUREE_ETAPE;
    var doux = p < .5 ? 2 * p * p : 1 - Math.pow(-2 * p + 2, 2) / 2;
    var de = PARCOURS_INDICE[etape], vers = PARCOURS_INDICE[etape + 1];
    placer(de + (vers - de) * doux);
    requestAnimationFrame(image);
  }
  requestAnimationFrame(image);
}

var observateurIndice = null;
if (!mouvementReduit && 'IntersectionObserver' in window) {
  observateurIndice = new IntersectionObserver(function (entrees) {
    /* Les images qui apparaissent en meme temps partent l'une apres l'autre. */
    var ordre = 0;
    entrees.forEach(function (entree) {
      if (!entree.isIntersecting) return;
      observateurIndice.unobserve(entree.target);
      setTimeout(entree.target.lancerIndice, 350 + ordre * 250);
      ordre += 1;
    });
  }, { threshold: 0.6 });
}

document.querySelectorAll('[data-comparateur]').forEach(function (bloc) {
  var curseur = bloc.querySelector('[data-curseur]');
  var volet   = bloc.querySelector('[data-volet]');
  var ligne   = bloc.querySelector('[data-ligne]');

  if (!curseur || !volet || !ligne) return;

  function placer(valeur) {
    var position = valeur + '%';
    volet.style.width = position;
    ligne.style.left  = position;
  }

  curseur.addEventListener('input', function () { placer(curseur.value); });
  placer(curseur.value);

  if (observateurIndice) {
    bloc.lancerIndice = function () { montrerIndice(bloc, curseur, placer); };
    observateurIndice.observe(bloc);
  }
});

/* 5. Prix selon le type de vehicule, et offre de lancement.
      Les prix de base sont dans les attributs data-prix du HTML, les
      supplements dans data-supplement du selecteur de la section Tarifs,
      le pourcentage de l'offre dans data-remise-lancement.
      Rien a modifier ici quand tu changes un tarif. */
var encartLancement = document.querySelector('[data-remise-lancement]');
var remise = encartLancement ? Number(encartLancement.getAttribute('data-remise-lancement')) || 0 : 0;

if (remise > 0) {
  document.querySelectorAll('[data-remise-texte]').forEach(function (texte) { texte.textContent = remise; });
} else {
  /* Offre arretee : on cache l'encart, le bandeau et les prix barres. */
  document.querySelectorAll('[data-lancement]').forEach(function (element) { element.hidden = true; });
}

/* Prix = (prix de base + supplement x nombre de passages), puis la reduction
   de lancement sur les prix marques data-remise, arrondie a l'euro inferieur. */
function calculerPrix(supplement) {
  document.querySelectorAll('[data-prix]').forEach(function (prix) {
    var passages = Number(prix.getAttribute('data-passages')) || 1;
    var montant  = Number(prix.getAttribute('data-prix')) + supplement * passages;
    if (remise > 0 && prix.hasAttribute('data-remise')) {
      montant = Math.floor(montant * (100 - remise) / 100);
    }
    prix.textContent = montant;
  });
}

var selecteur = document.querySelector('[data-gabarit]');
if (!selecteur) calculerPrix(0);
if (selecteur) {

  /* Copie du selecteur dans la section Abonnements. */
  document.querySelectorAll('[data-gabarit-copie]').forEach(function (place, rang) {
    var copie = selecteur.cloneNode(true);
    copie.querySelectorAll('input').forEach(function (champ) { champ.name += '-copie-' + rang; });
    place.replaceWith(copie);
  });

  var annonce = document.querySelector('[data-gabarit-annonce]');

  function appliquer(valeur, annoncer) {
    var choix = selecteur.querySelector('input[value="' + valeur + '"]');
    if (!choix) return;
    var supplement = Number(choix.getAttribute('data-supplement')) || 0;

    /* Tous les selecteurs affichent le meme choix. */
    document.querySelectorAll('[data-gabarit] input[value="' + valeur + '"]').forEach(function (champ) {
      champ.checked = true;
    });

    calculerPrix(supplement);

    if (annoncer && annonce) {
      annonce.textContent = 'Prix mis à jour pour : ' + choix.nextElementSibling.firstChild.textContent;
    }
  }

  document.addEventListener('change', function (evenement) {
    if (evenement.target.closest('[data-gabarit]')) appliquer(evenement.target.value, true);
  });

  var coche = selecteur.querySelector('input:checked');
  if (coche) appliquer(coche.value, false);
}

/* 6. Formulaire de contact. Envoie le message a contact.php sans recharger
      la page, puis affiche une confirmation. Sans JavaScript, le formulaire
      s'envoie quand meme normalement et contact.php renvoie vers la page. */
var formulaire = document.querySelector('[data-formulaire-contact]');
if (formulaire) {
  var etat   = formulaire.querySelector('[data-formulaire-etat]');
  var bouton = formulaire.querySelector('button[type="submit"]');
  var messages = {
    envoi:   'Envoi en cours…',
    envoye:  'Merci, votre message est bien parti. Je vous réponds au plus vite.',
    erreur:  "Le message n'a pas pu être envoyé. Réessayez dans un instant, ou écrivez-moi " +
             "directement grâce aux coordonnées en bas de page."
  };

  function afficher(type) {
    etat.className   = 'formulaire__etat formulaire__etat--' + type;
    etat.textContent = messages[type];
  }

  formulaire.addEventListener('submit', function (evenement) {
    evenement.preventDefault();
    bouton.disabled = true;
    afficher('envoi');

    fetch(formulaire.action, {
      method: 'POST',
      body: new FormData(formulaire),
      headers: { Accept: 'application/json' }
    })
      .then(function (reponse) { return reponse.json(); })
      .then(function (resultat) {
        if (resultat && resultat.ok) { formulaire.reset(); afficher('envoye'); }
        else afficher('erreur');
      })
      .catch(function () { afficher('erreur'); })
      .then(function () { bouton.disabled = false; });
  });

  /* Retour de contact.php quand JavaScript etait desactive. */
  var retour = new URLSearchParams(location.search).get('contact');
  if (retour === 'envoye' || retour === 'erreur') afficher(retour);
}

/* 7. Fenetre de reservation, etape par etape.
      Les vehicules sont lus dans le selecteur de la section Tarifs, les
      prestations et leurs prix dans les cartes de la section Tarifs.
      Sans JavaScript (ou sur un tres vieux navigateur), les boutons Reserver
      gardent leur lien normal. */
(function () {
  var fenetre = document.querySelector('[data-reservation]');
  if (!fenetre || typeof fenetre.showModal !== 'function') return;

  var formulaireR = fenetre.querySelector('[data-reservation-formulaire]');
  var etapes      = Array.prototype.slice.call(fenetre.querySelectorAll('[data-etape]'));
  var btnRetour   = fenetre.querySelector('[data-reservation-retour]');
  var btnSuivant  = fenetre.querySelector('[data-reservation-suivant]');
  var compteur    = fenetre.querySelector('[data-reservation-compteur]');
  var barre       = fenetre.querySelector('[data-reservation-barre]');
  var totalPied   = fenetre.querySelector('[data-reservation-total]');
  var champCP     = fenetre.querySelector('[name="code_postal"]');
  var erreurCP    = fenetre.querySelector('[data-erreur-code-postal]');
  var communeCP   = fenetre.querySelector('[data-commune]');
  var horsZone    = fenetre.querySelector('[data-hors-zone]');
  var blocage     = fenetre.querySelector('[data-blocage]');
  var questionEau = fenetre.querySelector('[data-question-eau]');
  var accesTitre  = fenetre.querySelector('[data-acces-titre]');
  var accesAide   = fenetre.querySelector('[data-acces-aide]');
  var accesBloque = fenetre.querySelector('[data-acces-blocage]');
  var recap       = fenetre.querySelector('[data-recap]');
  var recapTotal  = fenetre.querySelector('[data-recap-total]');

  var rang = 0;                /* etape affichee */
  var parPointeur = false;     /* dernier choix fait a la souris ou au doigt ? */
  var vehiculeChoisi = false;  /* le visiteur a-t-il choisi un vehicule dans les Tarifs ? */

  function euros(montant) { return montant + ' €'; }

  /* Zone d'intervention : codes postaux lus dans la liste "Ou j'interviens"
     de la section Contact. Code postal -> nom de la commune. */
  var communes = {};
  var listeZone = document.querySelectorAll('[data-codes-postaux]');
  listeZone.forEach(function (ligne) {
    ligne.getAttribute('data-codes-postaux').split(/\s+/).forEach(function (code) {
      if (code) communes[code] = ligne.textContent.trim();
    });
  });
  fenetre.querySelectorAll('[data-nombre-communes]').forEach(function (nombre) {
    nombre.textContent = listeZone.length;
  });

  function creerChoix(type, nom, valeur, titre, detail) {
    var label   = document.createElement('label');
    var input   = document.createElement('input');
    var contenu = document.createElement('span');
    var fort    = document.createElement('strong');
    var prix    = document.createElement('span');
    label.className = 'choix';
    input.type = type; input.name = nom; input.value = valeur;
    contenu.className = 'choix__contenu';
    fort.textContent = titre;
    contenu.appendChild(fort);
    if (detail) {
      var petit = document.createElement('small');
      petit.textContent = detail;
      contenu.appendChild(petit);
    }
    prix.className = 'choix__prix';
    contenu.appendChild(prix);
    label.appendChild(input);
    label.appendChild(contenu);
    return { input: input, prix: prix, label: label };
  }

  /* Vehicules, copies du selecteur de la section Tarifs. */
  var listeVehicules = fenetre.querySelector('[data-liste-vehicules]');
  var selecteurTarifs = document.querySelector('#tarifs [data-gabarit]');
  if (selecteurTarifs) {
    selecteurTarifs.querySelectorAll('input').forEach(function (radio) {
      var etiquette  = radio.nextElementSibling;
      var exemples   = etiquette.querySelector('small');
      var supplement = Number(radio.getAttribute('data-supplement')) || 0;
      var choix = creerChoix('radio', 'vehicule', radio.value,
        etiquette.firstChild.textContent.trim(), exemples ? exemples.textContent : '');
      /* Pas de "+10 €" ici : le supplement est deja compris dans les prix
         affiches a l'etape suivante. */
      choix.input.setAttribute('data-supplement', supplement);
      listeVehicules.appendChild(choix.label);
    });
  }

  /* Prestations, copiees des cartes de la section Tarifs. */
  var prestations = {};
  var listePrestations = fenetre.querySelector('[data-liste-prestations]');
  document.querySelectorAll('#tarifs [data-offre]').forEach(function (carte) {
    var id       = carte.getAttribute('data-offre');
    var prixBase = carte.querySelector('[data-prix][data-remise]') || carte.querySelector('[data-prix]');
    var duree    = carte.querySelector('.formule__prix em');
    var dispo    = !!prixBase && !carte.classList.contains('formule--bientot');
    var p = {
      titre:     carte.querySelector('h3').textContent.trim(),
      base:      prixBase ? Number(prixBase.getAttribute('data-prix')) : 0,
      remise:    !!prixBase && prixBase.hasAttribute('data-remise'),
      duree:     duree ? duree.textContent.replace(/^[·\s]+/, '') : '',
      exterieur: carte.hasAttribute('data-exterieur'),
      dispo:     dispo
    };
    var choix = creerChoix('radio', 'prestation', id, p.titre, dispo ? p.duree : 'Bientôt disponible');
    choix.input.disabled = !dispo;
    p.prix = choix.prix;
    prestations[id] = p;
    listePrestations.appendChild(choix.label);
  });

  function coche(nom) { return formulaireR.querySelector('input[name="' + nom + '"]:checked'); }

  function supplementVehicule() {
    var v = coche('vehicule');
    return v ? Number(v.getAttribute('data-supplement')) || 0 : 0;
  }

  /* Meme calcul que sur les cartes : base + vehicule, puis offre de lancement. */
  function prixPrestation(id) {
    var p = prestations[id];
    var normal = p.base + supplementVehicule();
    var reduit = (remise > 0 && p.remise) ? Math.floor(normal * (100 - remise) / 100) : normal;
    return { normal: normal, reduit: reduit };
  }

  function ecrirePrix(element, prix) {
    element.textContent = euros(prix.reduit);
    if (prix.reduit !== prix.normal) {
      var barre = document.createElement('del');
      barre.textContent = euros(prix.normal);
      element.appendChild(barre);
    }
  }

  function majPrixPrestations() {
    Object.keys(prestations).forEach(function (id) {
      if (prestations[id].dispo) ecrirePrix(prestations[id].prix, prixPrestation(id));
    });
  }

  function supplementsCoches() {
    return Array.prototype.slice.call(formulaireR.querySelectorAll('input[name="supplements"]:checked'));
  }

  /* Montants de l'achat : prestation + supplements, puis l'offre de lancement
     sur le tout, arrondie a l'euro inferieur. */
  function montants() {
    var p = coche('prestation');
    if (!p) return null;
    var prestation  = prixPrestation(p.value).normal;
    var supplements = supplementsCoches().reduce(function (somme, c) {
      return somme + (Number(c.getAttribute('data-montant')) || 0);
    }, 0);
    var brut = prestation + supplements;
    var net  = (remise > 0 && prestations[p.value].remise) ? Math.floor(brut * (100 - remise) / 100) : brut;
    return { prestation: prestation, reduction: brut - net, net: net };
  }

  function total() { var m = montants(); return m ? m.net : null; }

  /* Code postal : 4 chiffres, et present dans la liste des communes. */
  function codePostal() { return champCP.value.replace(/\s+/g, '').replace(/^BE?-?/i, ''); }
  function verifierCodePostal() {
    var cp = codePostal();
    if (!/^\d{4}$/.test(cp)) return { erreur: 'Un code postal belge compte 4 chiffres, par exemple 4000.' };
    if (listeZone.length) return communes[cp] ? { commune: communes[cp] } : { horsZone: true };
    /* Sans liste de communes sur la page : toute la province de Liege. */
    return (Number(cp) >= 4000 && Number(cp) <= 4999) ? { commune: '' } : { horsZone: true };
  }

  function prestationExterieure() {
    var p = coche('prestation');
    return !!p && prestations[p.value].exterieur;
  }

  function accesRefuse() {
    var e = coche('electricite'), o = coche('eau');
    return (e && e.value === 'non') || (prestationExterieure() && o && o.value === 'non');
  }

  function etapeValide(nom) {
    if (nom === 'code-postal') { var v = verifierCodePostal(); return !v.erreur && !v.horsZone; }
    if (nom === 'vehicule')    return !!coche('vehicule');
    if (nom === 'prestation')  { var p = coche('prestation'); return !!p && !p.disabled; }
    if (nom === 'acces') {
      var e = coche('electricite'), o = coche('eau');
      return !!e && e.value === 'oui' && (!prestationExterieure() || (!!o && o.value === 'oui'));
    }
    return true;
  }

  function nomEtape() { return etapes[rang].getAttribute('data-etape'); }

  function majEtat() {
    var nom = nomEtape();
    /* Le code postal reste cliquable pour pouvoir afficher l'erreur. */
    btnSuivant.disabled = nom !== 'code-postal' && !etapeValide(nom);
    blocage.hidden = !accesRefuse();

    /* Sans exterieur, on ne demande pas l'eau. */
    var exterieur = prestationExterieure();
    questionEau.hidden = !exterieur;
    accesTitre.textContent  = exterieur ? 'Eau et électricité' : 'Électricité';
    accesAide.textContent   = exterieur
      ? "Pour une prestation extérieure, j'ai besoin d'une prise électrique à 50 m maximum et d'un robinet près du véhicule."
      : "Pour un intérieur, une prise électrique à 50 m maximum du véhicule suffit : pas besoin d'eau.";
    accesBloque.textContent = exterieur
      ? 'Je ne peux malheureusement pas intervenir sans eau et électricité.'
      : 'Je ne peux malheureusement pas intervenir sans électricité.';

    /* Le total s'affiche a partir de l'etape du choix de la prestation. */
    var t = total();
    var rangPrestation = etapes.map(function (e) { return e.getAttribute('data-etape'); }).indexOf('prestation');
    var montrerTotal = t !== null && rang >= rangPrestation && nom !== 'recapitulatif';
    totalPied.textContent = montrerTotal ? 'Total : ' + euros(t) : '';
  }

  function ligneRecap(terme, description, montant) {
    var ligne = document.createElement('div');
    var dt = document.createElement('dt');
    var dd = document.createElement('dd');
    var ddMontant = document.createElement('dd');
    dt.textContent = terme;
    dd.textContent = description;
    ddMontant.className = 'recap__montant';
    if (typeof montant === 'string') ddMontant.textContent = montant;
    else if (montant) ecrirePrix(ddMontant, montant);
    ligne.appendChild(dt); ligne.appendChild(dd); ligne.appendChild(ddMontant);
    recap.appendChild(ligne);
  }

  function remplirRecap() {
    recap.textContent = '';
    var v = coche('vehicule'), p = coche('prestation');
    var presta = prestations[p.value];
    var m = montants();
    var commune = verifierCodePostal().commune;
    ligneRecap('Adresse', codePostal() + (commune ? ' · ' + commune : ''), '');
    ligneRecap('Véhicule', v.parentNode.querySelector('strong').textContent, '');
    if (presta.exterieur) ligneRecap('Eau et électricité', 'Disponibles sur place', '');
    else ligneRecap('Électricité', 'Disponible sur place', '');
    ligneRecap('Prestation', presta.titre + (presta.duree ? ' · ' + presta.duree : ''), euros(m.prestation));
    var supplements = supplementsCoches();
    if (supplements.length) {
      supplements.forEach(function (c) {
        ligneRecap('Supplément', c.value, '+' + euros(Number(c.getAttribute('data-montant')) || 0));
      });
    } else {
      ligneRecap('Suppléments', 'Aucun', '');
    }
    if (m.reduction > 0) ligneRecap('Offre de lancement', '−' + remise + ' % sur le tout', '−' + euros(m.reduction));
    recapTotal.textContent = euros(m.net);
  }

  function afficher(nouveauRang, deplacerFocus) {
    rang = nouveauRang;
    etapes.forEach(function (etape, i) { etape.hidden = i !== rang; });
    compteur.textContent = 'Étape ' + (rang + 1) + ' sur ' + etapes.length;
    barre.style.width = ((rang + 1) / etapes.length * 100) + '%';
    btnRetour.style.visibility  = rang === 0 ? 'hidden' : '';
    btnSuivant.style.visibility = rang === etapes.length - 1 ? 'hidden' : '';
    if (nomEtape() === 'recapitulatif') remplirRecap();
    majEtat();
    if (deplacerFocus) {
      (nomEtape() === 'code-postal' ? champCP : etapes[rang].querySelector('.etape__titre')).focus();
    }
  }

  function suivant() {
    var nom = nomEtape();
    if (nom === 'code-postal') {
      var verif = verifierCodePostal();
      erreurCP.textContent = verif.erreur || '';
      horsZone.hidden = !verif.horsZone;
      champCP.setAttribute('aria-invalid', (verif.erreur || verif.horsZone) ? 'true' : 'false');
      if (verif.erreur || verif.horsZone) { champCP.focus(); return; }
    }
    if (!etapeValide(nom) || rang >= etapes.length - 1) return;
    afficher(rang + 1, true);
  }

  btnSuivant.addEventListener('click', suivant);
  btnRetour.addEventListener('click', function () { if (rang > 0) afficher(rang - 1, true); });
  /* Touche Entree dans le code postal. */
  formulaireR.addEventListener('submit', function (evenement) { evenement.preventDefault(); suivant(); });
  /* Pendant la saisie : on affiche la commune reconnue, ou tout de suite
     le message "hors zone" des que les 4 chiffres sont tapes. */
  champCP.addEventListener('input', function () {
    var verif = verifierCodePostal();
    erreurCP.textContent = '';
    horsZone.hidden = !verif.horsZone;
    communeCP.textContent = verif.commune ? '✓ ' + verif.commune : '';
    if (verif.horsZone) champCP.setAttribute('aria-invalid', 'true');
    else champCP.removeAttribute('aria-invalid');
  });

  formulaireR.addEventListener('pointerdown', function () { parPointeur = true; });
  formulaireR.addEventListener('keydown', function () { parPointeur = false; });
  formulaireR.addEventListener('change', function (evenement) {
    var nom = evenement.target.name;
    if (nom === 'vehicule') majPrixPrestations();
    majEtat();
    /* Un clic sur un vehicule ou une prestation fait passer a la suite.
       Pas au clavier : les fleches changent le choix sans vouloir le valider. */
    if (parPointeur && (nom === 'vehicule' || nom === 'prestation')) {
      var rangDuChoix = rang;
      setTimeout(function () { if (rang === rangDuChoix) suivant(); }, 250);
    }
  });

  /* Si le visiteur a choisi son vehicule dans les Tarifs, on le reprend. */
  document.addEventListener('change', function (evenement) {
    if (evenement.target.closest('[data-gabarit]')) vehiculeChoisi = true;
  });

  function ouvrir(prestationVoulue) {
    if (menuBascule) menuBascule.checked = false;
    if (vehiculeChoisi && selecteurTarifs) {
      var gabarit = selecteurTarifs.querySelector('input:checked');
      var v = gabarit && formulaireR.querySelector('input[name="vehicule"][value="' + gabarit.value + '"]');
      if (v) v.checked = true;
    }
    if (prestationVoulue) {
      var p = formulaireR.querySelector('input[name="prestation"][value="' + prestationVoulue + '"]');
      if (p && !p.disabled) p.checked = true;
    }
    majPrixPrestations();
    erreurCP.textContent = '';
    afficher(0, false);
    fenetre.showModal();
    champCP.focus();
  }

  document.addEventListener('click', function (evenement) {
    var bouton = evenement.target.closest('[data-reserver]');
    if (!bouton) return;
    evenement.preventDefault();
    ouvrir(bouton.getAttribute('data-prestation'));
  });

  fenetre.querySelector('[data-reservation-fermer]').addEventListener('click', function () { fenetre.close(); });
  /* Un clic sur le fond grise ferme la fenetre. */
  fenetre.addEventListener('click', function (evenement) {
    if (evenement.target === fenetre) fenetre.close();
  });
})();

/* 8. Carrousel des photos avant / apres. Les fleches font avancer d'une
      photo, sans fin : apres la derniere on revient a la premiere, et
      inversement. Elles se cachent quand toutes les photos tiennent a
      l'ecran. Au doigt, on peut aussi faire glisser la rangee. */
document.querySelectorAll('[data-carrousel]').forEach(function (piste) {
  var section = piste.closest('section');
  var fleches = section && section.querySelector('[data-carrousel-fleches]');
  if (!fleches) return;
  var precedent = fleches.querySelector('[data-carrousel-precedent]');
  var prochain  = fleches.querySelector('[data-carrousel-suivant]');

  piste.classList.add('galerie--carrousel');

  /* Largeur d'une photo + l'espace entre deux photos. */
  function pas() {
    var photo = piste.firstElementChild;
    if (!photo) return piste.clientWidth;
    return photo.getBoundingClientRect().width + (parseFloat(getComputedStyle(piste).columnGap) || 0);
  }

  function maximum() { return piste.scrollWidth - piste.clientWidth; }

  function majFleches() { fleches.hidden = maximum() <= 2; }

  precedent.addEventListener('click', function () {
    if (piste.scrollLeft <= 2) piste.scrollTo({ left: maximum() });   /* au debut : on file a la fin */
    else piste.scrollBy({ left: -pas() });
  });
  prochain.addEventListener('click', function () {
    if (piste.scrollLeft >= maximum() - 2) piste.scrollTo({ left: 0 }); /* a la fin : retour au debut */
    else piste.scrollBy({ left: pas() });
  });
  window.addEventListener('resize', majFleches);
  majFleches();
});

/* 9. Defilement continu des avis. Les avis sont copies pour remplir la
      largeur puis doubles : la piste glisse de la moitie de sa longueur et
      recommence, sans raccord visible. Pause au survol, au clavier ou avec
      le bouton. Jamais si le visiteur a demande de reduire les animations. */
(function () {
  var VITESSE = 35; /* pixels par seconde : assez lent pour lire un avis */

  var cadre = document.querySelector('[data-avis-defilement]');
  if (!cadre || mouvementReduit) return;
  var piste = cadre.querySelector('.avis__piste');
  var originaux = Array.prototype.slice.call(piste.children);
  var boutonPause = document.querySelector('[data-avis-pause]');
  if (!originaux.length) return;

  function copie(carte) {
    var double = carte.cloneNode(true);
    double.setAttribute('aria-hidden', 'true');
    double.classList.add('avis__carte--copie');
    return double;
  }

  function construire() {
    piste.querySelectorAll('.avis__carte--copie').forEach(function (c) { c.remove(); });
    cadre.classList.add('avis--defile');

    /* 1. Assez d'avis pour couvrir toute la largeur visible. */
    var tours = 0;
    while (piste.getBoundingClientRect().width < cadre.clientWidth && tours < 20) {
      originaux.forEach(function (carte) { piste.appendChild(copie(carte)); });
      tours += 1;
    }
    /* 2. Le tout en double, pour que la boucle soit invisible. */
    Array.prototype.slice.call(piste.children).forEach(function (carte) { piste.appendChild(copie(carte)); });

    var moitie = piste.getBoundingClientRect().width / 2;
    piste.style.setProperty('--duree-defilement', Math.round(moitie / VITESSE) + 's');
  }

  construire();

  /* On reconstruit si la largeur de l'ecran change nettement. */
  var largeur = cadre.clientWidth, attente;
  window.addEventListener('resize', function () {
    clearTimeout(attente);
    attente = setTimeout(function () {
      if (Math.abs(cadre.clientWidth - largeur) > 50) { largeur = cadre.clientWidth; construire(); }
    }, 200);
  });

  if (boutonPause) {
    boutonPause.hidden = false;
    boutonPause.addEventListener('click', function () {
      var enPause = cadre.classList.toggle('avis--pause');
      boutonPause.setAttribute('aria-pressed', enPause ? 'true' : 'false');
      boutonPause.textContent = enPause ? 'Reprendre le défilement' : 'Mettre en pause';
    });
  }
})();