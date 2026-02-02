<?php
session_start();
require_once '../includes/session.php';
session_destroy();
header("Location: login.php");
exit();
?>
