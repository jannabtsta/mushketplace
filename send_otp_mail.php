<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

function sendOTPEmail($toEmail, $otp) {
    $mail = new PHPMailer(true);
    try {
        // SMTP Settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'bautistajoana1221@gmail.com'; // your Gmail
        $mail->Password   = 'eius fmab qamh uytr';    // your App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Email content
        $mail->setFrom('bautistajoana1221@gmail.com', 'Mushketplace OTP');
        $mail->addAddress($toEmail);
        $mail->Subject = 'Your One-Time Password (OTP)';
        $mail->Body    = "Your OTP code is: $otp\n\nIt expires in 5 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
