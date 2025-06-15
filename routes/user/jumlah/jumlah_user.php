<?php
session_start();
require_once __DIR__ . '/../../../config/db.php';
header('Content-Type: application/json');

// Cek login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Belum login']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil wilayah user dari tabel user_wilayah
$stmt = $conn->prepare("SELECT provinsi, kota, kecamatan, kelurahan FROM user_wilayah WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Wilayah user tidak ditemukan']);
    exit;
}

$userWilayah = $res->fetch_assoc();
$provinsi = $userWilayah['provinsi'];
$kota = $userWilayah['kota'];
$kecamatan = $userWilayah['kecamatan'];
$kelurahan = $userWilayah['kelurahan'];

// Hitung jumlah user lain di wilayah yang sama
$stmt = $conn->prepare("
    SELECT COUNT(*) as total
    FROM user_wilayah
    WHERE provinsi = ? AND kota = ? AND kecamatan = ? AND kelurahan = ?
");
$stmt->bind_param("ssss", $provinsi, $kota, $kecamatan, $kelurahan);
$stmt->execute();
$res = $stmt->get_result();
$count = $res->fetch_assoc()['total'];

echo json_encode([
    'success' => true,
    'total_pengguna_daerah_ini' => intval($count),
    'wilayah' => $userWilayah
]);
