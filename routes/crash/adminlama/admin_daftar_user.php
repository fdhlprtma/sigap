<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/db.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Akses ditolak.']);
    exit;
}

// Ambil semua user (kecuali admin)
$query = "SELECT id, username, email FROM users WHERE role != 'admin' ORDER BY id ASC";
$result = $conn->query($query);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal mengambil data user: ' . $conn->error]);
    exit;
}

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = [
        'id' => (int)$row['id'],
        'username' => $row['username'],
        'email' => $row['email']
    ];
}

echo json_encode(['users' => $users]);
