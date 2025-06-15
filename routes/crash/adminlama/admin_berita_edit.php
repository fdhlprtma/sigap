<?php
session_start();
require_once __DIR__ . '../../config/db.php';

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

// Pastikan admin yang login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak, bukan admin']);
    exit;
}

// Cek method
if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Ambil ID dari parameter query
$id = $_GET['id'] ?? '';
if (!$id || !is_numeric($id)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID berita tidak valid']);
    exit;
}

// Ambil data berita
$stmt = $conn->prepare("SELECT * FROM berita WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$berita = $result->fetch_assoc();

if (!$berita) {
    http_response_code(404);
    echo json_encode(['error' => 'Berita tidak ditemukan']);
    exit;
}

// Kirim data berita dalam format JSON
echo json_encode([
    'success' => true,
    'berita' => [
        'id' => $berita['id'],
        'judul' => $berita['judul'],
        'isi' => $berita['isi'],
        'foto' => $berita['foto'] ? $berita['foto'] : null
    ]
]);

$stmt->close();
$conn->close();
