<?php
session_start();
header('Content-Type: application/json');

// Hapus semua data sesi
$_SESSION = [];
session_unset();
session_destroy();

echo json_encode([
    'success' => true,
    'message' => 'Berhasil logout'
]);
