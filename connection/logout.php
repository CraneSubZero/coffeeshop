<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to signin.php
header("Location: signin.php");
exit();
?>
