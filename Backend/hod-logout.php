<?php
session_start();
unset($_SESSION['hod'], $_SESSION['hod_name']);
header("Location: index.php");
exit();
?>
