<?php

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';

class MailService
{
    private array $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../config/mail.php';
    }

    public function sendOtp(string $email, string $otp): bool
    {
        $mail = new PHPMailer(true);
        $mail->Timeout = 10;


        try {
            $mail->isSMTP();
            $mail->Host = $this->config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['username'];
            $mail->Password = $this->config['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $this->config['port'];

            $mail->setFrom($this->config['username'], 'Perfume Store');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Email Verification Code';
            $mail->Body = "
                <h2>Verify Your Email</h2>
                <p>Your verification code is:</p>
                <h1>{$otp}</h1>
                <p>This code expires in 5 minutes.</p>
            ";

            $mail->AltBody = "Your verification code is: {$otp}. Expires in 5 minutes.";

            return $mail->send();
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log('Mail sending failed: ' . $e->getMessage());
            return false;
        }
    }
    public function sendPasswordReset(string $email, string $resetLink): bool
    {
        $mail = new PHPMailer(true);
        $mail->Timeout = 10;

        try {
            $mail->isSMTP();
            $mail->Host = $this->config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['username'];
            $mail->Password = $this->config['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $this->config['port'];

            $mail->setFrom($this->config['username'], 'Perfume Store');
            $mail->addAddress($email);

            $safeLink = htmlspecialchars(
                $resetLink,
                ENT_QUOTES,
                'UTF-8'
            );

            $mail->isHTML(true);
            $mail->Subject = 'Reset Your Password';

            $mail->Body = "
            <h2>Reset Your Password</h2>
            <p>We received a request to reset your password.</p>
            <p>
                <a href='{$safeLink}'>Reset Password</a>
            </p>
            <p>This link expires in 15 minutes.</p>
            <p>If you didn't request this, ignore this email.</p>
        ";

            $mail->AltBody =
                "Reset your password using this link:\n"
                . $resetLink
                . "\nThis link expires in 15 minutes.";

            return $mail->send();
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log('Password reset email failed: ' . $e->getMessage());
            return false;
        }
    }
    public function sendOrderConfirmation(
        string $email,
        int $orderId,
        string $amount
    ): bool {
        try {
            $mail = new PHPMailer(true);
            $mail->Timeout = 10;
            $mail->CharSet = 'UTF-8';

            $mail->isSMTP();
            $mail->Host = $this->config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['username'];
            $mail->Password = $this->config['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $this->config['port'];

            $mail->setFrom($this->config['username'], 'Perfume Store');
            $mail->addAddress($email);

            $safeAmount = htmlspecialchars(
                $amount,
                ENT_QUOTES,
                'UTF-8'
            );

            $mail->isHTML(true);
            $mail->Subject = "Payment Confirmation - Order #{$orderId}";

            $mail->Body = "
            <h2>Payment Received</h2>
            <p>Thank you for your purchase!</p>
            <p>Order number: <strong>#{$orderId}</strong></p>
            <p>Amount paid: <strong>{$safeAmount} USD</strong></p>
            <p>Your payment was completed successfully.</p>
        ";

            $mail->AltBody =
                "Payment received for order #{$orderId}.\n"
                . "Amount paid: {$amount} USD.\n"
                . "Thank you for your purchase!";

            return $mail->send();
        } catch (Throwable $e) {
            error_log(
                'Order confirmation email failed: ' . $e->getMessage()
            );

            return false;
        }
    }
}
