<?php
session_start();
session_unset();
session_destroy();
header("Location: login.php"); // Redirect to login page or homepage after logout
exit();
?>
