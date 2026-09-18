<?php
/* ==========================================================================
   SHYNERA — reglages de la base de donnees
   --------------------------------------------------------------------------
   MODE D'EMPLOI (a faire directement sur le serveur, dans le gestionnaire
   de fichiers de hPanel, dossier public_html/espace/) :
   1. Copie ce fichier et renomme la copie "config.php" (sans "-exemple").
   2. Remplace les trois marqueurs par les informations de ta base, que tu
      retrouves dans hPanel > Bases de donnees > Gestion.

   NE METS JAMAIS config.php SUR GITHUB : le depot est public, ton mot de
   passe le deviendrait aussi. Il est deja exclu par le fichier .gitignore.
   ========================================================================== */

return [
    'hote'         => 'localhost',
    'nom'          => '{{NOM_BASE}}',
    'utilisateur'  => '{{UTILISATEUR_BASE}}',
    'mot_de_passe' => '{{MOT_DE_PASSE_BASE}}',
];
