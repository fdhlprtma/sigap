<?php
$host = 'localhost';
$user = 'root';
$pass = ''; // default kosong jika pakai XAMPP
$db   = 'keluhan_masyarakat';

$conn = new mysqli($host, $user, $pass, $db);

// Periksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
