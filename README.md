# Shynera — site web

Site de Shynera, nettoyage automobile à domicile à Liège.

**Version 0** : site en construction, pas encore publié. L'indexation par les
moteurs de recherche est volontairement bloquée (`robots.txt` et la balise
`noindex` dans `index.html`).

## Contenu

| Dossier / fichier | Rôle |
|---|---|
| `index.html` | La page d'accueil, tout le contenu |
| `style.css`, `script.js` | Mise en page et comportements |
| `images/` | Photos avant/après et illustrations |
| `contact.php` | Envoi du formulaire de contact par e-mail |
| `espace/` | Espace pro : connexion, disponibilités de l'équipe |

## Mise en route

Tout est expliqué dans [LISEZMOI.md](LISEZMOI.md) : marqueurs à remplacer,
prix, zone d'intervention, mise en ligne sur Hostinger.

Le fichier `espace/config.php` (identifiants de la base de données) n'est
volontairement pas dans ce dépôt : il se crée directement sur le serveur.
