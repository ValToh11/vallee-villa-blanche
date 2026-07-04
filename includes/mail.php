<?php
// Fonction d'envoi d'emails réutilisable, basée sur PHPMailer (installé dans libs/src)

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../config.php';            // constantes MAIL_*
require_once __DIR__ . '/../libs/src/Exception.php';
require_once __DIR__ . '/../libs/src/PHPMailer.php';
require_once __DIR__ . '/../libs/src/SMTP.php';

/**
 * Envoie un email HTML.
 * @return bool  true si l'envoi a réussi, false sinon.
 */
function envoyerEmail(string $destinataire, string $sujet, string $corpsHtml): bool
{
    $mail = new PHPMailer(true);

    try {
        // --- Connexion SMTP (Mailtrap en développement) ---
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        // En développement local, on ne vérifie pas le certificat SSL
        // (À RETIRER en production, où l'on veut la vérification stricte)
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];

        // --- Expéditeur et destinataire ---
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NOM);
        $mail->addAddress($destinataire);

        // --- Contenu ---
        $mail->isHTML(true);
        $mail->Subject = $sujet;
        $mail->Body    = $corpsHtml;
        $mail->AltBody = strip_tags($corpsHtml);   // version texte pour les clients sans HTML

        $mail->send();
        return true;

    } catch (Exception $e) {
        // On journalise l'erreur sans la montrer à l'utilisateur
        error_log("Erreur d'envoi email : " . $mail->ErrorInfo);
        return false;
    }
}
