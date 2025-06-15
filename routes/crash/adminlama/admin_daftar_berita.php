<?php
session_start();
require_once '../../config/db.php';

header('Content-Type: application/json');

// Autentikasi admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Ambil semua berita dari database
$sql = "SELECT id, judul, isi, foto, created_at FROM berita ORDER BY created_at DESC";
$result = $conn->query($sql);

$berita = [];

while ($row = $result->fetch_assoc()) {
    $berita[] = [
        'id' => $row['id'],
        'judul' => $row['judul'],
        'isi' => $row['isi'],
        'foto' => $row['foto'] ? '/uploads/' . $row['foto'] : null,
        'created_at' => $row['created_at'],
    ];
}

echo json_encode(['success' => true, 'data' => $berita]);
?>
