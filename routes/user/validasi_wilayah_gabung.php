<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
$user_id = $_SESSION['user_id'] ?? null;
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Belum login']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil wilayah dari pengajuan_wilayah_user yang sudah disetujui
$stmt = $conn->prepare("SELECT provinsi, kabupaten, kecamatan, kelurahan, rt, rw FROM pengajuan_wilayah_user WHERE user_id = ? AND status = 'disetujui' ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Wilayah Anda belum disetujui.']);
    exit;
}

$wilayah = $result->fetch_assoc();

// Ambil laporan hanya untuk wilayah user yang disetujui
$stmt = $conn->prepare("SELECT * FROM keluhan WHERE provinsi = ? AND kota = ? AND kecamatan = ? AND kelurahan = ? AND rt = ? AND rw = ? ORDER BY created_at DESC");
$stmt->bind_param(
    "ssssss",
    $wilayah['provinsi'],
    $wilayah['kabupaten'],
    $wilayah['kecamatan'],
    $wilayah['kelurahan'],
    $wilayah['rt'],
    $wilayah['rw']
);
$stmt->execute();
$result = $stmt->get_result();

$keluhan = [];
while ($row = $result->fetch_assoc()) {
    $keluhan[] = $row;
}

echo json_encode([
    'success' => true,
    'wilayah' => $wilayah,
    'laporan' => $keluhan
]);
