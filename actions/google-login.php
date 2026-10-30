<?php

require_once __DIR__ . '/../includes/init.php';

header('Location: ' . $googleAuth->getLoginUrl());
exit;