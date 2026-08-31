<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}
// Optionally check role for specific pages – called from each file
?>