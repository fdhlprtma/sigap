<?php
require_once __DIR__ . '/../../../config/db.php';
session_start();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// Cek apakah user login
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// ==== GET: Ambil daftar pengajuan wilayah yang belum dikonfirmasi dan sesuai wilayah RT ====
if ($method === 'GET') {
    // Ambil wilayah RT dari pengajuan yang sudah disetujui oleh user yang login (RT)
    $stmt = $conn->prepare("SELECT provinsi, kota, kecamatan, kelurahan, rt, rw FROM pengajuan_rt WHERE user_id = ? AND status = 'disetujui' LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['error' => 'Wilayah RT tidak ditemukan atau belum disetujui']);
        exit;
    }

    $wilayah = $res->fetch_assoc();

    // Ambil pengajuan warga yang statusnya menunggu dan berada di wilayah RT yang bersangkutan
    $sql = "
        SELECT 
            p.id,
            u.username,
            u.email,
            u.nama,
            p.provinsi,
            p.kabupaten AS kota,
            p.kecamatan,
            p.kelurahan,
            p.rt,
            p.rw,
            p.status,
            p.created_at
        FROM pengajuan_wilayah_user p
        JOIN users u ON p.user_id = u.id
        WHERE p.status = 'menunggu'
          AND p.provinsi = ?
          AND p.kabupaten = ?
          AND p.kecamatan = ?
          AND p.kelurahan = ?
          AND p.rt = ?
          AND p.rw = ?
        ORDER BY p.created_at DESC
    ";

    $stmt2 = $conn->prepare($sql);
    $stmt2->bind_param(
        "ssssss",
        $wilayah['provinsi'],
        $wilayah['kota'],
        $wilayah['kecamatan'],
        $wilayah['kelurahan'],
        $wilayah['rt'],
        $wilayah['rw']
    );
    $stmt2->execute();
    $result = $stmt2->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode($data);
    exit;
}

// ==== POST: Konfirmasi pengajuan wilayah (setujui / tolak) ====
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $pengajuan_id = intval($input['pengajuan_id'] ?? 0);
    $status = strtolower(trim($input['status'] ?? ''));

    // Validasi input
    if (!$pengajuan_id || !$status) {
        http_response_code(400);
        echo json_encode(['error' => 'ID pengajuan dan status wajib diisi']);
        exit;
    }

    // Validasi status hanya boleh disetujui atau ditolak
    $allowed_status = ['disetujui', 'ditolak'];
    if (!in_array($status, $allowed_status)) {
        http_response_code(400);
        echo json_encode(['error' => 'Status tidak valid. Gunakan "disetujui" atau "ditolak"']);
        exit;
    }

    // Query update status
    $stmt = $conn->prepare("UPDATE pengajuan_wilayah_user SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $pengajuan_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => "Pengajuan telah $status"]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Gagal mengubah status. ID mungkin tidak ditemukan atau status sudah sama.']);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// Jika method bukan GET atau POST
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
