<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$keluhan_id = $_GET['keluhan_id'] ?? 0;

$stmt = $conn->prepare("
  SELECT k.id, k.isi, k.created_at, u.nama
  FROM komentar k
  JOIN users u ON k.user_id = u.id
  WHERE k.keluhan_id = ?
  ORDER BY k.created_at DESC
");

$stmt->bind_param("i", $keluhan_id);
$stmt->execute();
$result = $stmt->get_result();

$komentar = [];

while ($row = $result->fetch_assoc()) {
    $komentar[] = $row;
}

echo json_encode($komentar);
?>
