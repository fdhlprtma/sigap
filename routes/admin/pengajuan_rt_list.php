<?php
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json');

// Ambil semua pengajuan RT dengan status pending
$sql = "
    SELECT 
        p.id,
        u.username,
        u.email,
        u.nama,
        p.provinsi,
        p.kota,
        p.kecamatan,
        p.kelurahan,
        p.rt,
        p.rw,
        p.status,
        p.created_at
    FROM pengajuan_rt p
    JOIN users u ON p.user_id = u.id
    WHERE p.status = 'pending'
    ORDER BY p.created_at DESC
";

$result = $conn->query($sql);

$data = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal mengambil data']);
}
?>
