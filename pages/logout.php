<?php
// pages/logout.php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

header('Location: connexion.php');
exit();
?>