<?php
session_start();
require_once __DIR__ . '/../../../config/db.php';
header('Content-Type: application/json');

// Pastikan user login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Belum login']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil wilayah user dari tabel user_wilayah
$stmt = $conn->prepare("SELECT provinsi, kota, kecamatan, kelurahan, rt, rw FROM user_wilayah WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Wilayah user tidak ditemukan']);
    exit;
}

$wilayah = $res->fetch_assoc();

// Hitung jumlah laporan dari wilayah yang sama
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM keluhan 
    WHERE provinsi = ? AND kota = ? AND kecamatan = ? AND kelurahan = ? AND rt = ? AND rw = ?
");
$stmt->bind_param("ssssss", 
    $wilayah['provinsi'], 
    $wilayah['kota'], 
    $wilayah['kecamatan'], 
    $wilayah['kelurahan'], 
    $wilayah['rt'], 
    $wilayah['rw']
);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

echo json_encode([
    'success' => true,
    'wilayah' => $wilayah,
    'total_keluhan' => (int) $data['total']
]);
