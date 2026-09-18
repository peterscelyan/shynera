# Shynera — site en HTML

**Version 0 : le site n'est pas encore public.** L'indexation par Google est
bloquée dans `robots.txt` et par une balise `noindex` dans `index.html`. Le jour
de la publication (v1), suis les instructions écrites en haut de `robots.txt`.

Aucun outil de compilation. Tu ouvres les fichiers dans Notepad++, tu modifies,
tu enregistres, tu rafraîchis le navigateur. C'est tout.

## Les fichiers

| Fichier | À quoi il sert |
|---|---|
| `index.html` | La page d'accueil : tout le contenu et les textes |
| `style.css` | Toutes les couleurs et la mise en page |
| `script.js` | Le curseur avant/après, le logo, le menu mobile, les prix et l'envoi du formulaire |
| `contact.php` | Reçoit le formulaire de contact et te l'envoie par e-mail |
| `images/` | Tes photos |
| `robots.txt` | Autorise Google à explorer le site |
| `sitemap.xml` | La liste de tes pages, à compléter |
| `favicon.svg` | La petite icône dans l'onglet |

## Étape 1 — remplacer tes informations

Ouvre `index.html` **et** `contact.php` dans Notepad++, fais **Ctrl+H**, coche
« Dans tous les documents ouverts », et remplace ces marqueurs. Coche
« Remplacer tout » à chaque fois.

- `{{NOM_COMPLET}}` — prénom et nom, pour les mentions légales
- `{{TELEPHONE}}` — format lisible, par exemple `+32 493 12 34 56`
- `{{TEL_BRUT}}` — sans espaces ni `+`, par exemple `32493123456`
- `{{EMAIL}}` — par exemple `contact@shynera.be`
- `{{ADRESSE}}` — l'adresse de ton siège, obligatoire légalement
- `{{BCE}}` — ton numéro d'entreprise
- `{{LIEN_RESERVATION}}` — ton lien Cal.com

Pour la section Avis Google :

- `{{NOTE_GOOGLE}}` — ta note moyenne, par exemple `4,9`
- `{{NB_AVIS}}` — le nombre d'avis
- `{{LIEN_AVIS_GOOGLE}}` — le lien vers ta fiche Google
- `{{LIEN_LAISSER_AVIS}}` — le lien « Écrire un avis » fourni par Google
- `{{AVIS_1}}`, `{{AVIS_2}}`, `{{AVIS_3}}` — le texte de trois vrais avis, copiés mot pour mot
- `{{AVIS_1_NOM}}`, `{{AVIS_2_NOM}}`, `{{AVIS_3_NOM}}` — prénom et initiale, par exemple `Julie M.`

N'invente jamais d'avis : c'est interdit, et un seul faux suffit à tout
décrédibiliser.

Astuce : quand tu as fini, cherche `{{` dans le fichier. S'il ne reste rien,
tu n'as rien oublié.

## Changer les prix

Les prix affichés s'adaptent au type de véhicule choisi par le visiteur
(citadine, berline, SUV, utilitaire). Tout se règle dans `index.html` :

- **Prix de base** (citadine) : sur chaque carte, par exemple
  `<span data-prix="70">70</span>`. Change les **deux** chiffres.
- **Suppléments par véhicule** : dans le sélecteur « Votre véhicule » de la
  section Tarifs, l'attribut `data-supplement="20"`. Ils s'appliquent
  automatiquement aux offres et aux abonnements.
- **Offre de lancement** : le pourcentage est dans `data-remise-lancement="25"`
  (section Tarifs). Les prix réduits sont calculés tout seuls, arrondis à
  l'euro inférieur. Pour **arrêter l'offre**, mets `data-remise-lancement="0"` :
  les prix barrés, l'encart et le bandeau du haut de page disparaissent.
- L'offre **Remise à neuf complète** est marquée « À venir ». Quand tu as le
  matériel, suis le commentaire placé juste au-dessus de sa carte.

## Zone d'intervention

La fenêtre de réservation n'accepte que les codes postaux de la liste
« Où j'interviens » (section Contact de `index.html`). Chaque commune porte
ses codes : `<li data-codes-postaux="4430 4431 4432"><span>Ans</span></li>`.
Pour ajouter une commune, ajoute une ligne comme celle-là, et ajoute-la aussi
dans `areaServed` tout en haut du fichier.

## Étape 2 — changer les couleurs

Tout est dans le bloc `:root` en haut de `style.css`. Modifie `--eclat` et
l'accent change partout : boutons, traits, puces, curseur. Tu n'as jamais
besoin de chercher une couleur ailleurs dans le fichier.

## Étape 3 — remplacer les images

Les images actuelles sont des gris de substitution. Mets les tiennes dans
`images/` en gardant les mêmes noms de fichiers.

Une contrainte à ne pas rater : dans une paire avant/après, les deux photos
doivent avoir **exactement le même cadrage et les mêmes dimensions**, sinon le
curseur superpose deux images décalées. Pose ton téléphone au même endroit pour
les deux prises.

Format conseillé : 1200 × 900 px, en `.webp`, sous 200 ko.

**Ajouter une paire avant/après** : dans `index.html`, section Avant / après,
copie un bloc `<figure> … </figure>` entier et colle-le à la suite. Change les
deux noms d'images (`exemple-7-avant.webp`, `exemple-7-apres.webp`), la légende,
et le numéro dans `for="curseur-7"` et `id="curseur-7"`. Les flèches du
carrousel apparaissent toutes seules dès qu'il y a plus de photos que de place,
et tournent en boucle : après la dernière photo, on revient à la première.

Les paires 5 et 6 sont des exemples (images grises et légende « Photo
d'exemple, à remplacer ») : remplace-les par tes propres photos.

**Ajouter un avis Google** : dans la section Avis, copie un bloc
`<blockquote> … </blockquote>` et remplace le texte et le nom. Les avis
défilent tout seuls, lentement, en boucle. La vitesse se règle dans
`script.js`, partie 9 (`VITESSE`, en pixels par seconde).

## Étape 4 — mettre en ligne sur Hostinger

Crée un dépôt GitHub et pousse ces fichiers à la racine (pas dans un
sous-dossier : `index.html` doit être au premier niveau).

Dans hPanel, va dans Site web puis Git, ajoute l'URL de ton dépôt, mets `main`
comme branche et `public_html` comme répertoire. Déploie.

À chaque modification, tu pousses sur GitHub puis tu cliques sur Déployer.
Tu peux aussi activer le déploiement automatique avec le webhook fourni.

Pense à activer le certificat SSL gratuit dans hPanel, et à forcer le HTTPS.

**Formulaire de contact** : il ne fonctionne qu'une fois le site en ligne
(Hostinger fait tourner `contact.php`, ton ordinateur non). Avant de le tester :

1. Crée ton adresse e-mail sur ton domaine dans hPanel (Emails), par exemple
   `contact@shynera.be`.
2. Vérifie que `{{EMAIL}}` est bien remplacé par cette adresse dans `contact.php`.
3. Envoie-toi un message depuis le site. S'il n'arrive pas, regarde dans tes
   spams.

## La réservation en ligne

Le client choisit dans cet ordre : code postal, véhicule, prestation,
suppléments, accès eau et électricité, **créneau**, **avec qui**, puis ses
coordonnées. Le site n'affiche que des créneaux réellement libres.

Un créneau est proposé si, ce jour-là, quelqu'un a déclaré travailler, n'est
pas en congé, n'a pas déjà un rendez-vous, et qu'il reste 30 minutes de trajet
avant et après. Les réglages sont en haut de `espace/planning.php` :

| Réglage | Valeur |
|---|---|
| `pas` | un créneau proposé toutes les 30 minutes |
| `marge_trajet` | 30 minutes entre deux clients |
| `delai_minimum` | 24 heures avant le rendez-vous |
| `horizon_jours` | réservation ouverte sur 8 semaines |
| `annulation` | annulation possible jusqu'à 24 heures avant |

À chaque réservation, le client reçoit une confirmation avec un lien
d'annulation, et tu reçois un e-mail avec ses coordonnées. Les rendez-vous
apparaissent sur ton tableau de bord.

Le prix est **toujours recalculé par le serveur** à partir de `tarifs.php` :
même si quelqu'un bricole la page dans son navigateur, il paiera le bon prix.

## La zone d'intervention

`zones.php` contient les communes et leurs codes postaux. La liste affichée sur
le site et la vérification pendant la réservation viennent de ce fichier.

## L'espace pro (dossier `espace/`)

C'est là que l'équipe entre ses disponibilités. Il fonctionne avec une base de
données MySQL, incluse dans ton hébergement.

Première mise en ligne, dans l'ordre :

1. Dans hPanel, mets PHP en **8.2 ou plus récent**.
2. Crée la base de données (hPanel > Bases de données > Gestion) et note bien
   le mot de passe : Hostinger ne le réaffiche jamais.
3. Envoie les fichiers sur GitHub, puis déploie.
4. Dans le gestionnaire de fichiers, va dans `public_html/espace/`, copie
   `config-exemple.php` en `config.php`, et remplace les trois marqueurs
   `{{NOM_BASE}}`, `{{UTILISATEUR_BASE}}` et `{{MOT_DE_PASSE_BASE}}`.
5. Ouvre `shynera.be/espace/installation.php` et crée ton compte.
6. **Supprime `installation.php` du serveur.**
7. Connecte-toi sur `shynera.be/espace/` et entre tes horaires.

`config.php` ne doit jamais partir sur GitHub : le dépôt est public. Il est
déjà exclu par le fichier `.gitignore`.

## Ce qui est déjà en place

Balises `title`, `description` et `canonical`. Un seul `<h1>`. Données
structurées `AutoWash` et `FAQPage` pour Google. Images avec dimensions
explicites et chargement différé. Menu mobile sans JavaScript. Navigation au
clavier avec focus visible. Animations désactivées pour qui le demande dans son
système.

## Les pages suivantes

Duplique `index.html`, renomme-le, et vide le contenu du `<main>`. L'en-tête,
le pied de page et les styles suivent automatiquement.

À créer dans l'ordre : `mentions-legales.html` et `confidentialite.html`
(obligatoires, déjà liées dans le pied de page), puis une page par commune —
`liege.html`, `ans.html`, `herstal.html`. Écris un texte différent pour chaque
commune : dupliquer le même paragraphe en changeant juste le nom de la ville
est exactement ce que Google pénalise.

Ajoute chaque nouvelle page dans `sitemap.xml`.

## Statistiques

N'installe pas Google Analytics : il t'obligerait à afficher une bannière
cookies. Plausible ou Umami ne posent aucun cookie. Une seule ligne à coller
avant `</head>`.
