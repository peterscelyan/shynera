<?php
/* ==========================================================================
   SHYNERA — envoi des e-mails
   En ligne : la fonction mail() de l'hebergeur.
   Pendant les tests sur ordinateur : rien n'est envoye, tout est ecrit dans
   un fichier courriels-test.txt a cote de la base de test.
   ========================================================================== */

declare(strict_types=1);

function envoyer_courriel(string $destinataire, string $sujet, string $message, string $repondreA, string $expediteur): bool
{
    $sujetEncode = '=?UTF-8?B?' . base64_encode($sujet) . '?=';

    if (getenv('SHYNERA_TEST') === '1') {
        $fichier = (getenv('SHYNERA_TEST_BDD') ?: __DIR__ . '/../../test.sqlite') . '.courriels.txt';
        file_put_contents(
            $fichier,
            "=== " . date('d/m/Y H:i') . " ===\nA : $destinataire\nSujet : $sujet\n\n$message\n\n",
            FILE_APPEND
        );
        return true;
    }

    $entetes = "From: Shynera <" . $expediteur . ">\r\n"
             . "Reply-To: " . $repondreA . "\r\n"
             . "MIME-Version: 1.0\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n"
             . "Content-Transfer-Encoding: 8bit";

    return @mail($destinataire, $sujetEncode, $message, $entetes);
}
