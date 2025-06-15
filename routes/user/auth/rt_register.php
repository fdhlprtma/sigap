<?php
session_start();
require_once __DIR__ . '/../../../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Belum login']);
    exit;
}

$user_id   = $_SESSION['user_id'];
$provinsi  = $_POST['provinsi'] ?? '';
$kota      = $_POST['kota'] ?? '';
$kecamatan = $_POST['kecamatan'] ?? '';
$kelurahan = $_POST['kelurahan'] ?? '';
$rt        = $_POST['rt'] ?? '';
$rw        = $_POST['rw'] ?? '';

// Validasi
if (!$provinsi || !$kota || !$kecamatan || !$kelurahan || !$rt || !$rw) {
    echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi']);
    exit;
}

// Simpan ke tabel pengajuan_rt
$stmt = $conn->prepare("INSERT INTO pengajuan_rt (user_id, provinsi, kota, kecamatan, kelurahan, rt, rw, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
$stmt->bind_param("issssss", $user_id, $provinsi, $kota, $kecamatan, $kelurahan, $rt, $rw);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Pengajuan RT berhasil dikirim']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan pengajuan: ' . $stmt->error]);
}
?>
