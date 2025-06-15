<?php
session_start();
require_once __DIR__ . '/../../../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$keluhan_id = $input['id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$keluhan_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Keluhan ID is required']);
    exit;
}

// Cek apakah keluhan milik user
$stmt = $conn->prepare("SELECT gambar, kecamatan FROM keluhan WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $keluhan_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden: Not your complaint']);
    exit;
}

$data = $result->fetch_assoc();
$gambar = $data['gambar'];
$kecamatan = $data['kecamatan'];

// Hapus file gambar jika ada
$uploadPath = __DIR__ . '/../uploads/' . $gambar;
if ($gambar && file_exists($uploadPath)) {
    unlink($uploadPath);
}

// Hapus data dari database
$stmt = $conn->prepare("DELETE FROM keluhan WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $keluhan_id, $user_id);
$stmt->execute();

echo json_encode(['message' => 'Keluhan berhasil dihapus', 'kecamatan' => $kecamatan]);
