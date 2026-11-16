<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host   = "sql311.infinityfree.com";
$user   = "if0_43082059";
$pass   = "LfUHorM42gzOOE";
$dbname = "if0_43082059_mymensingh_db";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("ডাটাবেস কানেকশন ব্যর্থ: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>