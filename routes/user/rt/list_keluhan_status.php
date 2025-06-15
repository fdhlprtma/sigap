<?php
session_start();
require_once __DIR__ . '/../../../config/db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Belum login']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($method === 'GET') {
    // Ambil wilayah RT dari pengajuan yang disetujui
    $stmt = $conn->prepare("
        SELECT provinsi, kota, kecamatan, kelurahan, rt, rw 
        FROM pengajuan_rt 
        WHERE user_id = ? AND status = 'disetujui' 
        LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Anda belum terdaftar sebagai RT atau belum disetujui']);
        exit;
    }

    $wilayah = $result->fetch_assoc();

    // Ambil semua keluhan sesuai wilayah RT
    $stmt = $conn->prepare("
        SELECT id, judul, deskripsi, gambar, provinsi, kota, kecamatan, kelurahan, rt, rw, status, created_at 
        FROM keluhan 
        WHERE provinsi = ? AND kota = ? AND kecamatan = ? AND kelurahan = ? AND rt = ? AND rw = ?
        ORDER BY created_at DESC
    ");
    $stmt->bind_param(
        "ssssss",
        $wilayah['provinsi'],
        $wilayah['kota'],
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

    echo json_encode(['success' => true, 'data' => $keluhan]);
    exit;
}

elseif ($method === 'POST') {
    // Ubah status keluhan
    $input = json_decode(file_get_contents('php://input'), true);
    $keluhan_id = intval($input['keluhan_id'] ?? 0);
    $status = strtolower(trim($input['status'] ?? ''));

    $valid_status = ['terselesaikan', 'belum terselesaikan'];
    if (!in_array($status, $valid_status)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
        exit;
    }

    // Cek apakah keluhan ada
    $stmt = $conn->prepare("SELECT id FROM keluhan WHERE id = ?");
    $stmt->bind_param("i", $keluhan_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Keluhan tidak ditemukan']);
        exit;
    }

    // Update status keluhan
    $stmt = $conn->prepare("UPDATE keluhan SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $keluhan_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Status keluhan berhasil diperbarui']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status']);
    }

    $stmt->close();
    $conn->close();
    exit;
}

else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan']);
    exit;
}
?>
