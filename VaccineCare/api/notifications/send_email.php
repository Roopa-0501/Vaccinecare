<?php
// notifications.php

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load Composer autoload (PHPMailer)
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load config
$config = require __DIR__ . '/config.php';

// ---------------------
// 1. Send SMS via Fast2SMS
// ---------------------
function sendSMS($number, $message) {
    global $config; // use config variables

    $apiKey = $config['FAST2SMS_API_KEY'];
    $senderId = $config['FAST2SMS_SENDER_ID'];
    $msg = urlencode($message);

    $url = "https://www.fast2sms.com/dev/bulkV2?authorization=$apiKey&sender_id=$senderId&message=$msg&route=v3&numbers=$number";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "authorization: $apiKey",
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

// ---------------------
// 2. Send Email via Brevo (PHPMailer)
// ---------------------
function sendEmail($to, $subject, $body, $fromEmail = null, $fromName = 'VaccineCare') {
    global $config;

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp-relay.brevo.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['BREVO_SMTP_USER'];
        $mail->Password   = $config['BREVO_SMTP_KEY'];
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Sender
        $mail->setFrom($fromEmail ?? $config['FROM_EMAIL'], $fromName);

        // Recipient
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return "Email sent successfully to $to";
    } catch (Exception $e) {
        return "Email could not be sent. Error: {$mail->ErrorInfo}";
    }
}
?>
