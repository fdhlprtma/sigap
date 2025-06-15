<?php
session_start();
require_once __DIR__ . '/../../config/db.php';  // Sesuaikan path db.php

header('Content-Type: application/json');

// Cek sesi admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak, bukan admin']);
    exit;
}

// Ambil input JSON mentah (jika ada)
$input = json_decode(file_get_contents('php://input'), true);

// Ambil action dari input JSON atau POST biasa (fallback)
$action = $input['action'] ?? ($_POST['action'] ?? '');

if ($action === 'tambah') {
    // Untuk tambah biasanya form data, pakai $_POST biasa
    $judul = $_POST['judul'] ?? '';
    $isi = $_POST['isi'] ?? '';
    $foto = null;

    // Upload foto jika ada
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['foto']['tmp_name'];
        $fileName = basename($_FILES['foto']['name']);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($ext, $allowed_ext)) {
            $newFileName = uniqid() . '.' . $ext;
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $dest_path = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $foto = $newFileName;
            }
        }
    }

    $stmt = $conn->prepare("INSERT INTO berita (judul, isi, foto) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $judul, $isi, $foto);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Berita berhasil ditambahkan']);
    exit;

} elseif ($action === 'edit') {
    $id = $input['id'] ?? ($_POST['id'] ?? '');
    $judul = $_POST['judul'] ?? '';
    $isi = $_POST['isi'] ?? '';

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID berita tidak ditemukan']);
        exit;
    }

    // Ambil data lama
    $stmt = $conn->prepare("SELECT foto FROM berita WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $old = $result->fetch_assoc();

    $foto = $old['foto'];

    // Upload foto baru jika ada
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['foto']['tmp_name'];
        $fileName = basename($_FILES['foto']['name']);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($ext, $allowed_ext)) {
            $newFileName = uniqid() . '.' . $ext;
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $dest_path = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                if ($foto && file_exists($uploadDir . $foto)) {
                    unlink($uploadDir . $foto);
                }
                $foto = $newFileName;
            }
        }
    }

    $stmt = $conn->prepare("UPDATE berita SET judul = ?, isi = ?, foto = ? WHERE id = ?");
    $stmt->bind_param("sssi", $judul, $isi, $foto, $id);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Berita berhasil diperbarui']);
    exit;

} elseif ($action === 'hapus') {
    $id = $input['id'] ?? ($_POST['id'] ?? '');
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID berita tidak ditemukan']);
        exit;
    }

    // Ambil data foto untuk hapus file
    $stmt = $conn->prepare("SELECT foto FROM berita WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $berita = $result->fetch_assoc();

    if ($berita && $berita['foto']) {
        $filePath = __DIR__ . '/../uploads/' . $berita['foto'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    $stmt = $conn->prepare("DELETE FROM berita WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Berita berhasil dihapus']);
    exit;

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Aksi tidak valid']);
    exit;
}
