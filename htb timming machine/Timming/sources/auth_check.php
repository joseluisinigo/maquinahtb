<?php

//ini_set('display_errors', '1');
//ini_set('display_startup_errors', '1');
//error_reporting(E_ALL);

// session is valid for 1 hour
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);

session_start();
if (!isset($_SESSION['userid']) && strpos($_SERVER['REQUEST_URI'], "login.php") === false) {
    header('Location: ./login.php');
    die();
}
?>
