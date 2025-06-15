<?php

session_start();
require_once __DIR__ . '/../../../config/db.php';

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Cek peran user dari database
$role = 'warga';
$role_query = $conn->prepare("SELECT role FROM users WHERE id = ?");
$role_query->bind_param("i", $user_id);
$role_query->execute();
$role_result = $role_query->get_result();
if ($row = $role_result->fetch_assoc()) {
    $role = $row['role'];
}

if ($method === 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $isi = trim($_POST['isi'] ?? '');
    $anonim = isset($_POST['anonim']) ? (bool) $_POST['anonim'] : false;

    if ($role === 'rt') {
        $anonim = false; // RT tidak bisa anonim
    }

    if (!$judul || !$isi) {
        http_response_code(400);
        echo json_encode(['error' => 'Judul dan isi wajib diisi']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO aspirasi (user_id, judul, isi, anonim, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("issi", $user_id, $judul, $isi, $anonim);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Aspirasi berhasil dikirim']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Gagal menyimpan aspirasi']);
    }
}

elseif ($method === 'GET') {
    $result = $conn->query("SELECT a.id, a.judul, a.isi, a.anonim, a.created_at, IF(a.anonim, 'Anonim', u.nama) as pengirim FROM aspirasi a JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC");
    $aspirasi = [];
    while ($row = $result->fetch_assoc()) {
        $aspirasi[] = $row;
    }
    echo json_encode($aspirasi);
}

elseif ($method === 'PUT') {
    parse_str(file_get_contents("php://input"), $put);
    $id = intval($put['id'] ?? 0);
    $judul = trim($put['judul'] ?? '');
    $isi = trim($put['isi'] ?? '');

    $cek = $conn->prepare("SELECT user_id FROM aspirasi WHERE id = ?");
    $cek->bind_param("i", $id);
    $cek->execute();
    $res = $cek->get_result();
    $data = $res->fetch_assoc();

    if (!$data || $data['user_id'] != $user_id) {
        http_response_code(403);
        echo json_encode(['error' => 'Tidak diizinkan mengedit aspirasi ini']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE aspirasi SET judul = ?, isi = ? WHERE id = ?");
    $stmt->bind_param("ssi", $judul, $isi, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Gagal mengupdate aspirasi']);
    }
}

elseif ($method === 'DELETE') {
    parse_str(file_get_contents("php://input"), $delete);
    $id = intval($delete['id'] ?? 0);

    $cek = $conn->prepare("SELECT user_id FROM aspirasi WHERE id = ?");
    $cek->bind_param("i", $id);
    $cek->execute();
    $res = $cek->get_result();
    $data = $res->fetch_assoc();

    if (!$data || $data['user_id'] != $user_id) {
        http_response_code(403);
        echo json_encode(['error' => 'Tidak diizinkan menghapus aspirasi ini']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM aspirasi WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Gagal menghapus aspirasi']);
    }
}

else {
    http_response_code(405);
    echo json_encode(['error' => 'Method tidak diizinkan']);
}
?>
