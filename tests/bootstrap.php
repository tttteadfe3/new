<?php
// tests/bootstrap.php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

define('DB_HOST', $_ENV['DB_HOST']);
define('DB_NAME', $_ENV['DB_DATABASE']);
define('DB_USER', $_ENV['DB_USERNAME']);
define('DB_PASS', $_ENV['DB_PASSWORD']);
define('DB_CHARSET', 'utf8mb4');
