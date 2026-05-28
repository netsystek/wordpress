<?php
/**
 * Plugin Name: SMTP Mailpit (Dev)
 * Description: Configure WordPress pour envoyer les emails via Mailpit en développement.
 * Version: 1.0
 *
 * Must-use plugin : placé dans wp-content/mu-plugins/, chargé automatiquement
 * par WordPress AVANT les plugins normaux. Impossible à désactiver depuis l'admin.
 *
 * Parallèle Symfony : équivalent à la configuration du Mailer dans .env.local
 * (MAILER_DSN=smtp://mailpit:1025) — une config d'infrastructure, pas applicative.
 */

add_action( 'phpmailer_init', function ( PHPMailer\PHPMailer\PHPMailer $phpmailer ) {
    $phpmailer->isSMTP();
    $phpmailer->Host       = 'mailpit';   // Nom du service Docker
    $phpmailer->Port       = 1025;
    $phpmailer->SMTPAuth   = false;       // Mailpit n'a pas d'authentification
    $phpmailer->SMTPSecure = '';
} );
