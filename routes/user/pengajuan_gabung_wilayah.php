<?php
require_once __DIR__ . '/../../config/db.php';
session_start();
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Anda harus login terlebih dahulu']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// Ambil data dari user
$nama = htmlspecialchars(trim($input['nama'] ?? ''));
$provinsi = htmlspecialchars(trim($input['provinsi'] ?? ''));
$kabupaten = htmlspecialchars(trim($input['kabupaten'] ?? ''));
$kecamatan = htmlspecialchars(trim($input['kecamatan'] ?? ''));
$kelurahan = htmlspecialchars(trim($input['kelurahan'] ?? ''));
$rt = htmlspecialchars(trim($input['rt'] ?? ''));
$rw = htmlspecialchars(trim($input['rw'] ?? ''));

// Validasi semua harus diisi
if (!$nama || !$provinsi || !$kabupaten || !$kecamatan || !$kelurahan || !$rt || !$rw) {
    http_response_code(400);
    echo json_encode(['error' => 'Semua field harus diisi']);
    exit;
}

// ✅ Cek apakah wilayah benar-benar terdaftar di `wilayah_aktif`
$stmt = $conn->prepare("SELECT id FROM wilayah_aktif WHERE provinsi = ? AND kabupaten = ? AND kecamatan = ? AND kelurahan = ? AND rt = ? AND rw = ?");
$stmt->bind_param("ssssss", $provinsi, $kabupaten, $kecamatan, $kelurahan, $rt, $rw);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Wilayah tidak ditemukan di sistem. Hubungi RT/developer.']);
    exit;
}

// ✅ Simpan pengajuan user ke tabel `pengajuan_wilayah`
$stmt = $conn->prepare("INSERT INTO pengajuan_wilayah_user (user_id, nama, provinsi, kabupaten, kecamatan, kelurahan, rt, rw) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isssssss", $user_id, $nama, $provinsi, $kabupaten, $kecamatan, $kelurahan, $rt, $rw);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Pengajuan berhasil, menunggu persetujuan RT']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal menyimpan pengajuan']);
}

$stmt->close();
$conn->close();
