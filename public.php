<?php
//Start a new session
if (!isset($_SESSION['timeout'])) {
    $timeout = 3600;
} else {
    $timeout = $_SESSION['timeout'];
}
error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set('session.gc_maxlifetime', $timeout);
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '100');
session_start();
//Check the session is expired or not
if (isset($_SESSION['start']) && (time() - $_SESSION['start'] > $timeout)) {
    //Unset the session variables
    session_unset();
    //Destroy the session
    session_destroy();
} else {
    //session_unset();
    if (!isset($_SESSION['start'])) {
        $_SESSION['start'] = time();
    }
    $_SESSION['timeout'] = $timeout;
}

require __DIR__ . '/proxy/app.php';
//require __DIR__ . '/proxy/app.php';
//$app->run();
?>