<?php
session_start();
require_once __DIR__ . '/../../../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

header('Content-Type: application/json');

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Ambil input JSON dari body
$input = json_decode(file_get_contents('php://input'), true);

$nama = htmlspecialchars(trim($input['nama'] ?? ''));
$username = htmlspecialchars(trim($input['username'] ?? ''));
$email = htmlspecialchars(trim($input['email'] ?? ''));
$password = $input['password'] ?? '';

if (!$nama || !$username || !$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Nama, username, email, dan password harus diisi']);
    exit;
}

// Cek apakah email sudah digunakan
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    http_response_code(409); // Conflict
    echo json_encode(['error' => 'Email sudah terdaftar']);
    exit;
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Simpan user baru (dengan nama)
$stmt = $conn->prepare("INSERT INTO users (nama, username, email, password) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $nama, $username, $email, $hashed_password);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Registrasi berhasil, silakan login']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Terjadi kesalahan saat registrasi']);
}

$stmt->close();
$conn->close();
