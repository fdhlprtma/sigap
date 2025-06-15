<?php
require_once __DIR__ . '/../../../config/db.php';
header('Content-Type: application/json');

$sql = "
  SELECT k.*, u.nama AS pengirim
  FROM keluhan k
  JOIN users u ON k.user_id = u.id
  ORDER BY k.created_at DESC
";

$result = $conn->query($sql);

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>
