<?php
session_start();
require_once '../../config/db.php';

header('Content-Type: application/json');

// Cek autentikasi admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Validasi method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metode tidak diizinkan']);
    exit;
}

$judul = $_POST['judul'] ?? '';
$isi = $_POST['isi'] ?? '';

if (!$judul || !$isi) {
    http_response_code(400);
    echo json_encode(['error' => 'Judul dan isi wajib diisi']);
    exit;
}

// Upload foto jika ada
$foto = null;
if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['foto']['tmp_name'];
    $fileName = basename($_FILES['foto']['name']);
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($ext, $allowed_ext)) {
        $newFileName = uniqid() . '.' . $ext;
        $uploadDir = '../../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $dest_path = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $dest_path)) {
            $foto = $newFileName;
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Gagal mengunggah foto']);
            exit;
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Ekstensi file tidak diizinkan']);
        exit;
    }
}

// Simpan ke database
$stmt = $conn->prepare("INSERT INTO berita (judul, isi, foto) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $judul, $isi, $foto);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Berita berhasil ditambahkan']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal menambahkan berita']);
}
?>
