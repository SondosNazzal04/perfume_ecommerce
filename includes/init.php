<?php

date_default_timezone_set('Asia/Amman');

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/EmailVerification.php';
require_once __DIR__ . '/../classes/MailService.php';
require_once __DIR__ . '/../classes/GoogleAuth.php';
require_once __DIR__ . '/../classes/PasswordReset.php';
require_once __DIR__ . '/../classes/RateLimiter.php';

$database = new Database();
$conn = $database->connect();

$userModel = new User($conn);
$emailVerification = new EmailVerification($conn);
$mailService = new MailService();
$passwordReset = new PasswordReset($conn);
$rateLimiter = new RateLimiter($conn);