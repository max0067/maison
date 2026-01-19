<?php
session_start();
session_destroy();
header('Location: rss_login.php');
exit;
?>
