<?php
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

// Pastikan user login dan berperan sebagai RT
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'rt') {
//     http_response_code(403);
//     echo json_encode(['success' => false, 'message' => 'Akses ditolak, hanya RT yang dapat mengubah status']);
//     exit;
// }

$input = json_decode(file_get_contents('php://input'), true);
$keluhan_id = intval($input['keluhan_id'] ?? 0);
$status = strtolower(trim($input['status'] ?? ''));

// Validasi status
$valid_status = ['terselesaikan', 'belum terselesaikan'];
if (!in_array($status, $valid_status)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
    exit;
}

// Cek apakah keluhan ada
$stmt = $conn->prepare("SELECT id FROM keluhan WHERE id = ?");
$stmt->bind_param("i", $keluhan_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Keluhan tidak ditemukan']);
    exit;
}

// Update status keluhan
$stmt = $conn->prepare("UPDATE keluhan SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $keluhan_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Status keluhan berhasil diperbarui']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status']);
}

$stmt->close();
$conn->close();
?>
