<?php

define('DB_HOST', 'sql105.infinityfree.com');
define('DB_USER', 'if0_42097182');
define('DB_PASS', 'd8Rv2WaEBk');
define('DB_NAME', 'if0_42097182_db_siber');

$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
    DB_USER,
    DB_PASS,
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
);

session_start();