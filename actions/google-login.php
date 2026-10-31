<?php



require_once __DIR__ . '/../includes/init.php';

$googleAuth = new GoogleAuth();

header('Location: ' . $googleAuth->getLoginUrl());
exit;
