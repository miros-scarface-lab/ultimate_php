<?php

session_start();

/* Remove admin session */
unset($_SESSION['admin_username']);

/* Go to Admin Login */
header("Location: admin-login.php");
exit();

?>