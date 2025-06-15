<?php
session_start();

header('Content-Type: application/json');


if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

require_once '../../config/db.php'; // koneksi DB

$user_id = $_SESSION['user_id'];
$sql = "SELECT provinsi, kabupaten, kecamatan, kelurahan, rt, rw
        FROM pengajuan_wilayah_user
        WHERE user_id = ? AND status = 'disetujui' LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
if ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
