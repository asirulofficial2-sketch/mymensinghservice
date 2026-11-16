<?php
session_start();
session_destroy();
header("Location: work_login.php");
exit;
?>