<?php
/* ==========================================================================
   SHYNERA — envoi du formulaire de contact
   --------------------------------------------------------------------------
   Recoit le formulaire de la section Contact et t'envoie le message par
   e-mail. Fonctionne uniquement une fois le site en ligne chez Hostinger.

   A REMPLIR : remplace {{EMAIL}} ci-dessous par ton adresse, la meme que
   dans index.php. Elle doit etre une adresse de ton domaine (par exemple
   contact@shynera.be) creee dans hPanel, sinon les e-mails risquent de
   finir en spam ou d'etre refuses.
   ========================================================================== */

$destinataire = '{{EMAIL}}';

/* Repond au script de la page (JSON), ou renvoie vers la page si le
   navigateur n'a pas JavaScript. */
function repondre($reussi)
{
    $accepte = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
    if (strpos($accepte, 'application/json') !== false) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($reussi ? 200 : 400);
        echo json_encode(array('ok' => $reussi));
    } else {
        header('Location: index.php?contact=' . ($reussi ? 'envoye' : 'erreur') . '#contact', true, 303);
    }
    exit;
}

function champ($nom)
{
    return isset($_POST[$nom]) ? trim((string) $_POST[$nom]) : '';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php#contact', true, 303);
    exit;
}

/* Piege a robots : un humain ne voit pas ce champ, donc le laisse vide.
   On fait semblant d'avoir envoye pour ne pas renseigner le robot. */
if (champ('site_web') !== '') {
    repondre(true);
}

$nom       = champ('nom');
$email     = champ('email');
$telephone = champ('telephone');
$message   = champ('message');

/* Verifications : champs obligatoires, adresse valide, longueurs raisonnables. */
if ($nom === '' || $message === ''
    || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || strlen($nom) > 300 || strlen($email) > 300
    || strlen($telephone) > 100 || strlen($message) > 15000) {
    repondre(false);
}

/* Aucun retour a la ligne dans ce qui part dans les en-tetes de l'e-mail :
   c'est ce qui empeche un robot de detourner le formulaire pour envoyer du spam. */
$nom       = str_replace(array("\r", "\n"), ' ', strip_tags($nom));
$telephone = str_replace(array("\r", "\n"), ' ', strip_tags($telephone));
$message   = strip_tags($message);

$sujet = 'Message du site Shynera : ' . $nom;
$corps = "Nom : " . $nom . "\n"
       . "E-mail : " . $email . "\n"
       . "Téléphone : " . ($telephone !== '' ? $telephone : '-') . "\n\n"
       . "Message :\n" . $message . "\n";

$entetes = "From: Site Shynera <" . $destinataire . ">\r\n"
         . "Reply-To: " . $email . "\r\n"
         . "MIME-Version: 1.0\r\n"
         . "Content-Type: text/plain; charset=UTF-8\r\n"
         . "Content-Transfer-Encoding: 8bit";

$envoye = mail(
    $destinataire,
    '=?UTF-8?B?' . base64_encode($sujet) . '?=',
    $corps,
    $entetes
);

repondre($envoye);
