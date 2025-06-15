<?php
require_once __DIR__ . '/../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$user_id     = $_SESSION['user_id'];
$komentar_id = $_POST['komentar_id'] ?? null;

if (!$komentar_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Komentar ID wajib diisi']);
    exit;
}

// Cek komentar
$cek = $conn->prepare("SELECT id FROM komentar WHERE id = ?");
$cek->bind_param("i", $komentar_id);
$cek->execute();
$cek->store_result();

if ($cek->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Komentar tidak ditemukan']);
    exit;
}

// Cek apakah user sudah like
$cekLike = $conn->prepare("SELECT id FROM like_komentar WHERE komentar_id = ? AND user_id = ?");
$cekLike->bind_param("ii", $komentar_id, $user_id);
$cekLike->execute();
$cekLike->store_result();

if ($cekLike->num_rows > 0) {
    // Jika sudah like, maka unlike
    $hapus = $conn->prepare("DELETE FROM like_komentar WHERE komentar_id = ? AND user_id = ?");
    $hapus->bind_param("ii", $komentar_id, $user_id);
    $hapus->execute();
    echo json_encode(['message' => 'Komentar tidak disukai (unlike)']);
} else {
    // Jika belum like, maka like
    $like = $conn->prepare("INSERT INTO like_komentar (komentar_id, user_id) VALUES (?, ?)");
    $like->bind_param("ii", $komentar_id, $user_id);
    if ($like->execute()) {
        echo json_encode(['message' => 'Komentar disukai']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Gagal menyukai komentar']);
    }
}
