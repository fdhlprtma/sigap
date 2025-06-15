<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/db.php';

// Cek apakah user sudah login dan admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Akses ditolak.']);
    exit;
}

// Ambil semua data keluhan dan nama usernya
$query = "
    SELECT k.id, k.deskripsi, k.kecamatan, k.created_at, u.username 
    FROM keluhan k 
    JOIN users u ON k.user_id = u.id 
    ORDER BY k.created_at DESC
";
$result = $conn->query($query);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal mengambil data keluhan: ' . $conn->error]);
    exit;
}

$keluhan = [];
while ($row = $result->fetch_assoc()) {
    $keluhan[] = [
        'id' => (int)$row['id'],
        'deskripsi' => $row['deskripsi'],
        'username' => $row['username'],
        'kecamatan' => $row['kecamatan'],
        'created_at' => $row['created_at'],
        'tanggal_format' => date("d M Y H:i", strtotime($row['created_at']))
    ];
}

echo json_encode(['keluhan' => $keluhan]);
