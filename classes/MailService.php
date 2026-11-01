<?php

use PHPMailer\PHPMailer\PHPMailer;

class MailService
{
    private array $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../config/mail.php';
    }

    public function sendOtp(string $email, string $otp): bool
    {
        if (!class_exists(PHPMailer::class)) {
            error_log('OTP email was not sent: install the project Composer dependencies first.');
            return false;
        }

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

            $mail->CharSet = 'UTF-8';
            $mail->setFrom($this->config['username'], 'VELORA');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'VELORA | Verify Your Email';

            // Use only the OTP supplied by the existing verification system.
            $safeOtp = htmlspecialchars($otp, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            // Inline CSS and table layout for broad email-client compatibility.
            // No external template, images, or additional dependencies are needed.
            $html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VELORA Email Verification</title>
</head>
<body style="margin:0;padding:0;background-color:#0B0A09;font-family:Arial,Helvetica,sans-serif;color:#F2E8D9;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;background-color:#0B0A09;">
        <tr>
            <td align="center" style="padding:36px 16px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="560" style="width:100%;max-width:560px;background-color:#17130F;border:1px solid #51402B;">
                    <tr>
                        <td align="center" style="padding:38px 24px 22px;">
                            <div style="font-family:Georgia,'Times New Roman',serif;font-size:34px;letter-spacing:7px;color:#E6C18A;font-weight:normal;">VELORA</div>
                            <div style="margin-top:11px;font-size:10px;letter-spacing:3px;color:#C4A77E;">THE ART OF FRAGRANCE</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="border-top:1px solid #51402B;padding:34px 30px 12px;" align="center">
                            <h1 style="margin:0 0 16px;font-family:Georgia,'Times New Roman',serif;font-size:26px;font-weight:normal;line-height:1.3;color:#F2E8D9;">Verify Your Email</h1>
                            <p style="margin:0;font-size:15px;line-height:1.8;color:#D2C5B3;">Welcome to VELORA.<br>Enter the verification code below to complete your registration.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:25px 30px 12px;" align="center">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;background-color:#241D15;border:1px solid #B18A56;">
                                <tr>
                                    <td align="center" style="padding:23px 12px;">
                                        <div style="font-family:Arial,Helvetica,sans-serif;font-size:34px;font-weight:bold;letter-spacing:7px;line-height:1.3;color:#F1C98C;">{{OTP_CODE}}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:12px 30px 33px;">
                            <p style="margin:0 0 15px;font-size:13px;color:#D6B68A;">This code expires in 5 minutes.</p>
                            <p style="margin:0;font-size:13px;line-height:1.8;color:#B8AA98;">If you did not create a VELORA account, you can safely ignore this email. Never share your code with anyone.</p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:21px 20px;border-top:1px solid #51402B;background-color:#120F0C;">
                            <p style="margin:0;color:#AA9578;font-size:11px;letter-spacing:1px;">VELORA &middot; A PRESENCE BEYOND FRAGRANCE</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

            $mail->Body = str_replace('{{OTP_CODE}}', $safeOtp, $html);

            $mail->AltBody = "VELORA verification code: {$otp}. Expires in 5 minutes. If you did not request this code, ignore this email.";

            return $mail->send();

        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log('Mail sending failed: ' . $e->getMessage());
            return false;
        }
    }

    public function sendPasswordReset(string $email, string $resetLink): bool
    {
        if (!class_exists(PHPMailer::class)) {
            error_log('Password reset email was not sent: install the project Composer dependencies first.');
            return false;
        }

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
        if (!class_exists(PHPMailer::class)) {
            error_log('Order confirmation email was not sent: install the project Composer dependencies first.');
            return false;
        }

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