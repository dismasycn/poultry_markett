<?php
session_start();
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'poultry_market');
define('SITE_URL', 'http://localhost/poultry_markett/');
define('ADMIN_EMAIL', 'admin@poultrymarket.com');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
date_default_timezone_set('Africa/Dar_es_Salaam');

// PHPMailer autoload – adjust path if you install via Composer
//require_once __DIR__ . '/phpmailer/PHPMailer.php';
//require_once __DIR__ . '/phpmailer/SMTP.php';
//require_once __DIR__ . '/phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
?>