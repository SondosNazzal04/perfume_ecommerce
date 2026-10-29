<?php

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/classes/Brand.php';

$database = new Database();
$pdo = $database->connect();

$brand = new Brand($pdo);

// 1) اعرض الماركات الموجودة
var_dump($brand->getAll());


$testName = 'Test Brand ' . uniqid();

$added = $brand->addBrand($testName, 'Temporary test brand');
var_dump($added);

var_dump($brand->getAll());