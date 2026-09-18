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
    if (page !== '' && page !== 'index.php') return;
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
      Tout vient de tarifs.php : le serveur l'envoie dans window.SHYNERA.
      Rien a modifier ici quand tu changes un tarif. */
var TARIFS = window.SHYNERA || { remise: 0, vehicules: [], prestations: [], supplements: [] };
var remise = Number(TARIFS.remise) || 0;

function prestationParCode(code) {
  for (var i = 0; i < TARIFS.prestations.length; i++) {
    if (TARIFS.prestations[i].code === code) return TARIFS.prestations[i];
  }
  return null;
}

function vehiculeParCode(code) {
  for (var i = 0; i < TARIFS.vehicules.length; i++) {
    if (TARIFS.vehicules[i].code === code) return TARIFS.vehicules[i];
  }
  return null;
}

function avecRemise(montant) {
  return remise > 0 ? Math.floor(montant * (100 - remise) / 100) : montant;
}

/* Met a jour les prix affiches sur les cartes quand on change de vehicule. */
function calculerPrix(supplement) {
  document.querySelectorAll('[data-prix-prestation]').forEach(function (element) {
    var p = prestationParCode(element.getAttribute('data-prix-prestation'));
    if (p) element.textContent = avecRemise(p.prix + supplement);
  });
  document.querySelectorAll('[data-prix-normal]').forEach(function (element) {
    var p = prestationParCode(element.getAttribute('data-prix-normal'));
    if (p) element.textContent = p.prix + supplement;
  });
  /* Abonnements : pas d'offre de lancement, mais le supplement compte
     pour chaque passage du mois. */
  var abonnements = TARIFS.abonnements || [];
  document.querySelectorAll('[data-prix-abonnement]').forEach(function (element) {
    var a = abonnements[Number(element.getAttribute('data-prix-abonnement'))];
    if (a) element.textContent = a.prix + supplement * a.passages;
  });
  document.querySelectorAll('[data-prix-reference]').forEach(function (element) {
    var a = abonnements[Number(element.getAttribute('data-prix-reference'))];
    if (a) element.textContent = a.reference + supplement * a.passages;
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
    var v = vehiculeParCode(valeur);
    if (!choix || !v) return;
    var supplement = v.supplement;

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
  var zoneCreneaux    = fenetre.querySelector('[data-creneaux]');
  var listeTravailleurs = fenetre.querySelector('[data-liste-travailleurs]');
  var aideTravailleur = fenetre.querySelector('[data-travailleur-aide]');
  var btnConfirmer    = fenetre.querySelector('[data-reservation-confirmer]');
  var erreurResa      = fenetre.querySelector('[data-erreur-reservation]');
  var confirmation    = fenetre.querySelector('[data-confirmation]');

  var rang = 0;                /* etape affichee */
  var parPointeur = false;     /* dernier choix fait a la souris ou au doigt ? */
  var vehiculeChoisi = false;  /* le visiteur a-t-il choisi un vehicule dans les Tarifs ? */
  var creneau = null;          /* { date, heure, travailleurs: [id] } */
  var nomsTravailleurs = {};   /* id -> nom */
  var creneauxCharges = null;  /* reponse de l'API pour la prestation choisie */
  var prestationChargee = null;

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

  /* Vehicules et prestations : tout vient de tarifs.php (window.SHYNERA). */
  var listeVehicules  = fenetre.querySelector('[data-liste-vehicules]');
  var selecteurTarifs = document.querySelector('#tarifs [data-gabarit]');

  TARIFS.vehicules.forEach(function (v) {
    /* Pas de "+10 €" ici : le supplement est deja compris dans les prix
       affiches a l'etape suivante. */
    listeVehicules.appendChild(creerChoix('radio', 'vehicule', v.code, v.nom, v.exemples).label);
  });

  var prestations = {};
  var listePrestations = fenetre.querySelector('[data-liste-prestations]');
  TARIFS.prestations.forEach(function (p) {
    var choix = creerChoix('radio', 'prestation', p.code, p.titre,
      p.disponible ? p.dureeTexte : 'Bientôt disponible');
    choix.input.disabled = !p.disponible;
    prestations[p.code] = {
      titre:     p.titre,
      base:      p.prix,
      duree:     p.dureeTexte,
      minutes:   p.duree,
      exterieur: p.exterieur,
      dispo:     p.disponible,
      prix:      choix.prix
    };
    listePrestations.appendChild(choix.label);
  });

  function coche(nom) { return formulaireR.querySelector('input[name="' + nom + '"]:checked'); }

  function supplementVehicule() {
    var v = coche('vehicule');
    var trouve = v ? vehiculeParCode(v.value) : null;
    return trouve ? trouve.supplement : 0;
  }

  /* Meme calcul que sur les cartes : base + vehicule, puis offre de lancement. */
  function prixPrestation(id) {
    var normal = prestations[id].base + supplementVehicule();
    return { normal: normal, reduit: avecRemise(normal) };
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
    var net  = avecRemise(brut);
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

  /* --- Creneaux : on demande au serveur les heures reellement libres. ------- */

  var MOIS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin',
              'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
  var JOURS_SEMAINE = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];

  function dateEnFrancais(texte, court) {
    var d = new Date(texte + 'T12:00:00');
    var jour = JOURS_SEMAINE[d.getDay()];
    if (court) jour = jour.slice(0, 3) + '.';
    return jour + ' ' + d.getDate() + ' ' + MOIS[d.getMonth()];
  }

  function chargerCreneaux() {
    var p = coche('prestation');
    if (!p) return;
    if (prestationChargee === p.value && creneauxCharges) {
      afficherCreneaux();
      return;
    }
    zoneCreneaux.innerHTML = '<p class="etape__aide">Recherche des disponibilités…</p>';
    creneauxCharges = null;
    prestationChargee = p.value;

    fetch('api/creneaux.php?prestation=' + encodeURIComponent(p.value) + '&jours=21', { headers: { Accept: 'application/json' } })
      .then(function (reponse) { return reponse.json(); })
      .then(function (donnees) {
        if (!donnees || donnees.erreur) throw new Error('erreur');
        creneauxCharges  = donnees;
        nomsTravailleurs = donnees.travailleurs || {};
        afficherCreneaux();
        majEtat();
      })
      .catch(function () {
        zoneCreneaux.innerHTML = '';
        var erreur = document.createElement('p');
        erreur.className = 'etape__erreur';
        erreur.textContent = "Les créneaux n'ont pas pu être chargés. Réessayez, ou écrivez-moi pour convenir d'un rendez-vous.";
        zoneCreneaux.appendChild(erreur);
      });
  }

  function afficherCreneaux() {
    zoneCreneaux.innerHTML = '';
    var jours = (creneauxCharges && creneauxCharges.jours) || [];

    if (!jours.length) {
      var vide = document.createElement('p');
      vide.className = 'etape__aide';
      vide.textContent = 'Aucun créneau libre dans les trois prochaines semaines. Écrivez-moi, on trouvera une solution.';
      zoneCreneaux.appendChild(vide);
      return;
    }

    /* Si le creneau choisi n'existe plus, on repart de zero. */
    if (creneau && !jours.some(function (j) { return j.date === creneau.date; })) creneau = null;

    var jourActif = (creneau && creneau.date) || jours[0].date;

    var onglets = document.createElement('div');
    onglets.className = 'creneaux__jours';
    jours.forEach(function (jour) {
      var bouton = document.createElement('button');
      bouton.type = 'button';
      bouton.className = 'creneaux__jour' + (jour.date === jourActif ? ' creneaux__jour--actif' : '');
      bouton.setAttribute('aria-pressed', jour.date === jourActif ? 'true' : 'false');
      var nom = document.createElement('strong');
      nom.textContent = dateEnFrancais(jour.date, true);
      var nombre = document.createElement('small');
      nombre.textContent = jour.creneaux.length + (jour.creneaux.length > 1 ? ' créneaux' : ' créneau');
      bouton.appendChild(nom);
      bouton.appendChild(nombre);
      bouton.addEventListener('click', function () {
        jourActif = jour.date;
        afficherHeures(jour);
        onglets.querySelectorAll('.creneaux__jour').forEach(function (b) {
          b.classList.remove('creneaux__jour--actif');
          b.setAttribute('aria-pressed', 'false');
        });
        bouton.classList.add('creneaux__jour--actif');
        bouton.setAttribute('aria-pressed', 'true');
      });
      onglets.appendChild(bouton);
    });

    var heures = document.createElement('div');
    heures.className = 'creneaux__heures';

    zoneCreneaux.appendChild(onglets);
    zoneCreneaux.appendChild(heures);

    function afficherHeures(jour) {
      heures.innerHTML = '';
      var titre = document.createElement('p');
      titre.className = 'creneaux__titre';
      titre.textContent = dateEnFrancais(jour.date, false);
      heures.appendChild(titre);

      var grille = document.createElement('div');
      grille.className = 'creneaux__grille';
      jour.creneaux.forEach(function (c) {
        var bouton = document.createElement('button');
        bouton.type = 'button';
        bouton.className = 'creneaux__heure';
        bouton.textContent = c.heure.replace(/^0/, '').replace(':', ' h ');
        var choisi = creneau && creneau.date === jour.date && creneau.heure === c.heure;
        if (choisi) bouton.classList.add('creneaux__heure--actif');
        bouton.setAttribute('aria-pressed', choisi ? 'true' : 'false');
        bouton.addEventListener('click', function () {
          creneau = { date: jour.date, heure: c.heure, travailleurs: c.travailleurs };
          grille.querySelectorAll('.creneaux__heure').forEach(function (b) {
            b.classList.remove('creneaux__heure--actif');
            b.setAttribute('aria-pressed', 'false');
          });
          bouton.classList.add('creneaux__heure--actif');
          bouton.setAttribute('aria-pressed', 'true');
          majEtat();
          setTimeout(suivant, 250);
        });
        grille.appendChild(bouton);
      });
      heures.appendChild(grille);
    }

    var jourChoisi = jours.filter(function (j) { return j.date === jourActif; })[0] || jours[0];
    afficherHeures(jourChoisi);
  }

  /* --- Avec qui : on ne pose la question que si plusieurs sont libres. ------ */
  function construireTravailleurs() {
    listeTravailleurs.innerHTML = '';
    if (!creneau) return;
    var ids = creneau.travailleurs || [];

    if (ids.length <= 1) {
      aideTravailleur.textContent = 'Une seule personne est disponible sur ce créneau.';
    } else {
      aideTravailleur.textContent = 'Plusieurs personnes sont disponibles : choisissez.';
    }

    ids.forEach(function (id, rang) {
      var choix = creerChoix('radio', 'travailleur', String(id), nomsTravailleurs[id] || 'Shynera',
        ids.length > 1 ? '' : 'Ce sera avec ' + (nomsTravailleurs[id] || 'moi'));
      if (ids.length === 1 || rang === 0) choix.input.checked = true;
      listeTravailleurs.appendChild(choix.label);
    });
  }

  function accesRefuse() {
    var e = coche('electricite'), o = coche('eau');
    return (e && e.value === 'non') || (prestationExterieure() && o && o.value === 'non');
  }

  function champ(nom) { return formulaireR.querySelector('[name="' + nom + '"]'); }

  function coordonneesCompletes() {
    var email = champ('email').value.trim();
    return champ('nom').value.trim() !== ''
      && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
      && champ('telephone').value.trim() !== ''
      && champ('adresse').value.trim() !== '';
  }

  function etapeValide(nom) {
    if (nom === 'code-postal') { var v = verifierCodePostal(); return !v.erreur && !v.horsZone; }
    if (nom === 'vehicule')    return !!coche('vehicule');
    if (nom === 'prestation')  { var p = coche('prestation'); return !!p && !p.disabled; }
    if (nom === 'acces') {
      var e = coche('electricite'), o = coche('eau');
      return !!e && e.value === 'oui' && (!prestationExterieure() || (!!o && o.value === 'oui'));
    }
    if (nom === 'creneau')     return !!creneau;
    if (nom === 'travailleur') return !!coche('travailleur');
    if (nom === 'coordonnees') return coordonneesCompletes();
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
    var montrerTotal = t !== null && rang >= rangPrestation && nom !== 'coordonnees' && nom !== 'confirmation';
    totalPied.textContent = montrerTotal ? 'Total : ' + euros(t) : '';

    /* Sur la derniere etape, "Continuer" laisse la place a "Confirmer". */
    var surCoordonnees = nom === 'coordonnees';
    btnSuivant.hidden  = surCoordonnees || nom === 'confirmation';
    btnConfirmer.hidden = !surCoordonnees;
    btnConfirmer.disabled = !coordonneesCompletes();
    btnRetour.style.visibility = (rang === 0 || nom === 'confirmation') ? 'hidden' : '';
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
    if (creneau) ligneRecap('Quand', dateEnFrancais(creneau.date, false) + ' à ' + creneau.heure.replace(/^0/, '').replace(':', ' h '), '');
    var qui = coche('travailleur');
    if (qui) ligneRecap('Avec', nomsTravailleurs[qui.value] || 'Shynera', '');
    ligneRecap('Adresse', codePostal() + (commune ? ' · ' + commune : ''), '');
    ligneRecap('Véhicule', v.parentNode.querySelector('strong').textContent, '');
    if (presta.exterieur) ligneRecap('Eau et électricité', 'Disponibles sur place', '');
    else ligneRecap('Électricité', 'Disponible sur place', '');
    ligneRecap('Prestation', presta.titre + (presta.duree ? ' · ' + presta.duree : ''), euros(m.prestation));
    var supplements = supplementsCoches();
    if (supplements.length) {
      supplements.forEach(function (c) {
        var nom = c.closest('label').querySelector('strong').textContent;
        ligneRecap('Supplément', nom, '+' + euros(Number(c.getAttribute('data-montant')) || 0));
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
    if (nomEtape() === 'creneau')      chargerCreneaux();
    if (nomEtape() === 'travailleur')  construireTravailleurs();
    if (nomEtape() === 'coordonnees')  remplirRecap();
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

  /* --- Envoi de la reservation --------------------------------------------- */
  function confirmer() {
    if (!coordonneesCompletes() || !creneau) return;
    var qui = coche('travailleur');
    btnConfirmer.disabled = true;
    erreurResa.textContent = 'Enregistrement en cours…';

    fetch('api/reserver.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({
        prestation:  coche('prestation').value,
        vehicule:    coche('vehicule').value,
        supplements: supplementsCoches().map(function (c) { return c.value; }),
        code_postal: codePostal(),
        debut:       creneau.date + ' ' + creneau.heure,
        travailleur: qui ? Number(qui.value) : 0,
        nom:         champ('nom').value.trim(),
        email:       champ('email').value.trim(),
        telephone:   champ('telephone').value.trim(),
        adresse:     champ('adresse').value.trim(),
        remarque:    champ('remarque').value.trim(),
        site_web:    champ('site_web').value
      })
    })
      .then(function (reponse) {
        return reponse.json().then(function (donnees) { return { statut: reponse.status, donnees: donnees }; });
      })
      .then(function (resultat) {
        if (resultat.donnees && resultat.donnees.ok) {
          erreurResa.textContent = '';
          confirmation.textContent = 'Rendez-vous le ' + resultat.donnees.quand
            + ' avec ' + resultat.donnees.travailleur + ', pour ' + resultat.donnees.total + ' €.';
          afficher(etapes.length - 1, true);
          return;
        }
        /* Creneau pris entre-temps : on renvoie au choix des creneaux. */
        erreurResa.textContent = (resultat.donnees && resultat.donnees.message) || "La réservation n'a pas pu être enregistrée.";
        if (resultat.statut === 409) {
          creneau = null;
          creneauxCharges = null;
          var rangCreneau = etapes.map(function (e) { return e.getAttribute('data-etape'); }).indexOf('creneau');
          afficher(rangCreneau, true);
        }
      })
      .catch(function () {
        erreurResa.textContent = "La réservation n'a pas pu être envoyée. Vérifiez votre connexion et réessayez.";
      })
      .then(function () { btnConfirmer.disabled = false; });
  }

  btnSuivant.addEventListener('click', suivant);
  btnConfirmer.addEventListener('click', confirmer);
  formulaireR.addEventListener('input', function (evenement) {
    if (evenement.target.closest('[data-etape="coordonnees"]')) majEtat();
  });
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
    erreurResa.textContent = '';
    creneau = null;
    creneauxCharges = null;
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

  fenetre.querySelectorAll('[data-reservation-fermer]').forEach(function (bouton) {
    bouton.addEventListener('click', function () { fenetre.close(); });
  });
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