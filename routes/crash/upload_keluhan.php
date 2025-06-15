<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Anda belum login.']);
    exit;
}

require_once 'config.php';

$user_id = $_SESSION['user_id'];
$judul = $_POST['judul'] ?? '';
$deskripsi = $_POST['deskripsi'] ?? '';

$provinsi = $_POST['provinsi'] ?? '';
$kabupaten = $_POST['kabupaten'] ?? '';
$kecamatan = $_POST['kecamatan'] ?? '';
$kelurahan = $_POST['kelurahan'] ?? '';
$rt = $_POST['rt'] ?? '';
$rw = $_POST['rw'] ?? '';

// Validasi wilayah user sesuai database
$sql = "SELECT * FROM pengajuan_wilayah_user 
        WHERE user_id = ? AND status = 'disetujui' AND 
              provinsi = ? AND kabupaten = ? AND kecamatan = ? 
              AND kelurahan = ? AND rt = ? AND rw = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("issssss", $user_id, $provinsi, $kabupaten, $kecamatan, $kelurahan, $rt, $rw);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Anda tidak diizinkan membuat keluhan di wilayah ini.']);
    exit;
}

// Upload gambar jika ada
$gambarPath = null;
if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK) {
    $targetDir = 'uploads/';
    if (!is_dir($targetDir)) mkdir($targetDir);
    $gambarPath = $targetDir . uniqid() . "_" . basename($_FILES["gambar"]["name"]);
    move_uploaded_file($_FILES["gambar"]["tmp_name"], $gambarPath);
}

// Simpan keluhan
$sql = "INSERT INTO keluhan (user_id, judul, deskripsi, gambar, provinsi, kabupaten, kecamatan, kelurahan, rt, rw)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("isssssssss", $user_id, $judul, $deskripsi, $gambarPath, $provinsi, $kabupaten, $kecamatan, $kelurahan, $rt, $rw);
$stmt->execute();

echo json_encode(['message' => 'Keluhan berhasil dikirim.']);
