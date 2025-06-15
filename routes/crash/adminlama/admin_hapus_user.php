<?php
session_start();
require_once '../../config/db.php';

header('Content-Type: application/json');

// Pastikan request method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan.']);
    exit;
}

// Hanya admin yang bisa hapus
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Tidak memiliki akses.']);
    exit;
}

// Validasi ID
$id = $_POST['id'] ?? null;
if (!$id || !is_numeric($id)) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid.']);
    exit;
}

// Hapus semua keluhan user
$stmt1 = $conn->prepare("DELETE FROM keluhan WHERE user_id = ?");
$stmt1->bind_param("i", $id);
$stmt1->execute();

// Hapus user
$stmt2 = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt2->bind_param("i", $id);

if ($stmt2->execute()) {
    echo json_encode(['success' => true, 'message' => 'User berhasil dihapus.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menghapus user.']);
}
?>
