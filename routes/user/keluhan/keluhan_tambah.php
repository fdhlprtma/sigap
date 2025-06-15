<?php
session_start();
require_once __DIR__ . '/../../../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Belum login']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Terima data dari form
$judul = $_POST['judul'] ?? '';
$deskripsi = $_POST['deskripsi'] ?? '';
$provinsi = $_POST['provinsi'] ?? '';
$kabupaten = $_POST['kota'] ?? '';
$kecamatan = $_POST['kecamatan'] ?? '';
$kelurahan = $_POST['kelurahan'] ?? '';
$rt = $_POST['rt'] ?? '';
$rw = $_POST['rw'] ?? '';
$gambar = $_FILES['gambar'] ?? null;

// Validasi wilayah aktif
$cek_stmt = $conn->prepare("SELECT id FROM wilayah_aktif WHERE provinsi = ? AND kabupaten = ? AND kecamatan = ? AND kelurahan = ? AND rt = ? AND rw = ?");
$cek_stmt->bind_param("ssssss", $provinsi, $kabupaten, $kecamatan, $kelurahan, $rt, $rw);
$cek_stmt->execute();
$cek_result = $cek_stmt->get_result();

if ($cek_result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Wilayah belum terdaftar sebagai wilayah aktif, silakan ajukan terlebih dahulu.'
    ]);
    exit;
}

// Lanjut simpan keluhan (logika sebelumnya tetap bisa digunakan di sini)

$filename = null;
if ($gambar && $gambar['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($gambar['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $ext;
    move_uploaded_file($gambar['tmp_name'], __DIR__ . '/../uploads/' . $filename);
}

$stmt = $conn->prepare("INSERT INTO keluhan (user_id, judul, deskripsi, provinsi, kota, kecamatan, kelurahan, rt, rw, gambar, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'belum terselesaikan')");
$stmt->bind_param("isssssssss", $user_id, $judul, $deskripsi, $provinsi, $kabupaten, $kecamatan, $kelurahan, $rt, $rw, $filename);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Keluhan berhasil dikirim']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal mengirim keluhan']);
}
?>
