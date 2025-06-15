<?php
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Belum login']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Terima data JSON dari body
$input = json_decode(file_get_contents('php://input'), true);
$keluhan_id = intval($input['id'] ?? 0);

if ($keluhan_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID keluhan tidak valid']);
    exit;
}

// Cek apakah user sudah like sebelumnya
$stmt = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND keluhan_id = ?");
$stmt->bind_param("ii", $user_id, $keluhan_id);
$stmt->execute();
$cek = $stmt->get_result();

if ($cek->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Anda sudah memberikan like']);
    exit;
}

// Insert like baru
$stmt = $conn->prepare("INSERT INTO likes (user_id, keluhan_id) VALUES (?, ?)");
$stmt->bind_param("ii", $user_id, $keluhan_id);
$stmt->execute();

// Update jumlah like di tabel keluhan
$stmt = $conn->prepare("UPDATE keluhan SET likes = likes + 1 WHERE id = ?");
$stmt->bind_param("i", $keluhan_id);
$stmt->execute();

// Ambil jumlah like terbaru
$stmt = $conn->prepare("SELECT likes FROM keluhan WHERE id = ?");
$stmt->bind_param("i", $keluhan_id);
$stmt->execute();
$res = $stmt->get_result();
$likes = $res->fetch_assoc()['likes'];

echo json_encode(['success' => true, 'likes' => $likes]);
