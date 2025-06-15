<?php
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

// Cek login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Belum login']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data dari request body (application/json)
$input = json_decode(file_get_contents('php://input'), true);
$provinsi = trim($input['provinsi'] ?? '');
$kota = trim($input['kota'] ?? '');
$kecamatan = trim($input['kecamatan'] ?? '');
$kelurahan = trim($input['kelurahan'] ?? '');
$rt = trim($input['rt'] ?? '');
$rw = trim($input['rw'] ?? '');

// Validasi
if (!$provinsi || !$kota || !$kecamatan || !$kelurahan || !$rt || !$rw) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi']);
    exit;
}

// Cek apakah user sudah punya wilayah
$stmt = $conn->prepare("SELECT user_id FROM user_wilayah WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    // Update jika sudah ada
    $stmt = $conn->prepare("UPDATE user_wilayah SET provinsi = ?, kota = ?, kecamatan = ?, kelurahan = ?, rt = ?, rw = ? WHERE user_id = ?");
    $stmt->bind_param("ssssssi", $provinsi, $kota, $kecamatan, $kelurahan, $rt, $rw, $user_id);
    $stmt->execute();
    $status = "diperbarui";
} else {
    // Insert jika belum ada
    $stmt = $conn->prepare("INSERT INTO user_wilayah (user_id, provinsi, kota, kecamatan, kelurahan, rt, rw) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $user_id, $provinsi, $kota, $kecamatan, $kelurahan, $rt, $rw);
    $stmt->execute();
    $status = "disimpan";
}

echo json_encode([
    'success' => true,
    'message' => "Data wilayah berhasil $status",
    'data' => [
        'user_id' => $user_id,
        'provinsi' => $provinsi,
        'kota' => $kota,
        'kecamatan' => $kecamatan,
        'kelurahan' => $kelurahan,
        'rt' => $rt,
        'rw' => $rw
    ]
]);
