<?php
require_once __DIR__ . '/../config/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$user_id = $_SESSION['user_id'];
$keluhan_id = $_POST['keluhan_id'] ?? null;
$isi = htmlspecialchars(trim($_POST['isi'] ?? ''));

if (!$keluhan_id || !$isi) {
    http_response_code(400);
    echo json_encode(['error' => 'Keluhan dan isi komentar wajib diisi']);
    exit;
}

$cek = $conn->prepare("SELECT id FROM keluhan WHERE id = ?");
$cek->bind_param("i", $keluhan_id);
$cek->execute();
$cek->store_result();

if ($cek->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Keluhan tidak ditemukan']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO komentar (keluhan_id, user_id, isi) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $keluhan_id, $user_id, $isi);

if ($stmt->execute()) {
    http_response_code(201);
    echo json_encode([
        'message' => 'Komentar berhasil ditambahkan',
        'komentar' => [
            'id' => $stmt->insert_id,
            'keluhan_id' => $keluhan_id,
            'isi' => $isi,
            'user_id' => $user_id
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal menambahkan komentar']);
}
