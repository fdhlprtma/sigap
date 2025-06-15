<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$provinsi  = trim($input['provinsi'] ?? '');
$kabupaten = trim($input['kabupaten'] ?? '');
$kecamatan = trim($input['kecamatan'] ?? '');
$kelurahan = trim($input['kelurahan'] ?? '');
$rt        = trim($input['rt'] ?? '');
$rw        = trim($input['rw'] ?? '');
$pengaju_id = $_SESSION['user_id'] ?? null;

if (!$provinsi || !$kabupaten || !$kecamatan || !$kelurahan || !$rt || !$rw || !$pengaju_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Semua field wajib diisi']);
    exit;
}

// ✅ Validasi 1: wilayah sudah aktif (diterima)
$cekAktif = $conn->prepare("SELECT id FROM wilayah_aktif WHERE provinsi=? AND kabupaten=? AND kecamatan=? AND kelurahan=? AND rt=? AND rw=?");
$cekAktif->bind_param("ssssss", $provinsi, $kabupaten, $kecamatan, $kelurahan, $rt, $rw);
$cekAktif->execute();
$resAktif = $cekAktif->get_result();
if ($resAktif->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'Wilayah sudah aktif/terdaftar. Tidak dapat diajukan kembali.']);
    exit;
}
$cekAktif->close();

// ✅ Validasi 2: wilayah sudah diajukan dan belum diproses
$cekPending = $conn->prepare("SELECT id FROM wilayah_pengajuan WHERE provinsi=? AND kabupaten=? AND kecamatan=? AND kelurahan=? AND rt=? AND rw=? AND status='pending'");
$cekPending->bind_param("ssssss", $provinsi, $kabupaten, $kecamatan, $kelurahan, $rt, $rw);
$cekPending->execute();
$resPending = $cekPending->get_result();
if ($resPending->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'Wilayah sudah diajukan dan masih menunggu konfirmasi']);
    exit;
}
$cekPending->close();

// 💾 Simpan pengajuan
$stmt = $conn->prepare("INSERT INTO wilayah_pengajuan (provinsi, kabupaten, kecamatan, kelurahan, rt, rw, pengaju_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
$stmt->bind_param("ssssssi", $provinsi, $kabupaten, $kecamatan, $kelurahan, $rt, $rw, $pengaju_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Pengajuan wilayah berhasil dikirim. Menunggu konfirmasi.']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal menyimpan pengajuan wilayah']);
}

$stmt->close();
$conn->close();
