<?php
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json');

// Ambil filter dari query string (jika ada)
$provinsi   = $_GET['provinsi']   ?? null;
$kota       = $_GET['kota']       ?? null;
$kecamatan  = $_GET['kecamatan']  ?? null;
$kelurahan  = $_GET['kelurahan']  ?? null;

// Bangun query dasar
$query = "
    SELECT 
        u.nama, 
        COUNT(k.id) AS jumlah_laporan,
        ANY_VALUE(k.rt) AS rt,
        ANY_VALUE(k.rw) AS rw,
        ANY_VALUE(k.kelurahan) AS kelurahan,
        ANY_VALUE(k.kecamatan) AS kecamatan,
        ANY_VALUE(k.kota) AS kota,
        ANY_VALUE(k.provinsi) AS provinsi
    FROM keluhan k
    JOIN users u ON k.user_id = u.id
    WHERE 1=1
";


// Tambahkan filter jika ada
$params = [];
$types = "";

if ($provinsi) {
    $query .= " AND k.provinsi = ?";
    $params[] = $provinsi;
    $types .= "s";
}
if ($kota) {
    $query .= " AND k.kota = ?";
    $params[] = $kota;
    $types .= "s";
}
if ($kecamatan) {
    $query .= " AND k.kecamatan = ?";
    $params[] = $kecamatan;
    $types .= "s";
}
if ($kelurahan) {
    $query .= " AND k.kelurahan = ?";
    $params[] = $kelurahan;
    $types .= "s";
}

$query .= "
    GROUP BY k.user_id
    ORDER BY jumlah_laporan DESC
    LIMIT 10
";

$stmt = $conn->prepare($query);

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$leaderboard = [];
$rank = 1;
while ($row = $result->fetch_assoc()) {
    $row['rank'] = $rank++;
    $leaderboard[] = $row;
}

echo json_encode($leaderboard, JSON_PRETTY_PRINT);
