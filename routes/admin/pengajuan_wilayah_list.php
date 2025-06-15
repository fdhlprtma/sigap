<?php
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json');

// GET: Tampilkan semua pengajuan wilayah
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "
        SELECT 
            w.id,
            w.provinsi,
            w.kabupaten,
            w.kecamatan,
            w.kelurahan,
            w.rt,
            w.rw,
            w.status,
            w.pengaju_id,
            w.created_at,
            u.username,
            u.nama
        FROM wilayah_pengajuan w
        LEFT JOIN users u ON w.pengaju_id = u.id
        ORDER BY w.created_at DESC
    ";

    $result = $conn->query($sql);
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode($data);
    exit;
}

// POST: Konfirmasi pengajuan wilayah
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? null;
    $aksi = $input['aksi'] ?? ''; // "terima" atau "tolak"

    if (!$id || !in_array($aksi, ['terima', 'tolak'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID dan aksi (terima/tolak) diperlukan']);
        exit;
    }

    // Ambil data wilayah berdasarkan ID dan status pending
    $stmt = $conn->prepare("SELECT * FROM wilayah_pengajuan WHERE id=? AND status='pending'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Pengajuan tidak ditemukan atau sudah diproses']);
        exit;
    }

    $data = $result->fetch_assoc();

    if ($aksi === 'terima') {
        // Masukkan ke tabel wilayah_aktif
        $insert = $conn->prepare("INSERT INTO wilayah_aktif (provinsi, kabupaten, kecamatan, kelurahan, rt, rw) VALUES (?, ?, ?, ?, ?, ?)");
        $insert->bind_param("ssssss", $data['provinsi'], $data['kabupaten'], $data['kecamatan'], $data['kelurahan'], $data['rt'], $data['rw']);
        $insert->execute();
        $insert->close();

        $status = 'disetujui';
    } else {
        $status = 'ditolak';
    }

    // Update status di tabel pengajuan
    $update = $conn->prepare("UPDATE wilayah_pengajuan SET status=? WHERE id=?");
    $update->bind_param("si", $status, $id);
    $update->execute();

    echo json_encode([
        'success' => true,
        'message' => "Wilayah berhasil $status.",
        'updated_id' => $id
    ]);
    exit;
}

// Jika method tidak valid
http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
