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

// Ambil input JSON dari body request
$input = json_decode(file_get_contents('php://input'), true);

$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Email dan password harus diisi']);
    exit;
}

if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Koneksi database gagal']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Prepare statement gagal']);
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();

    if (password_verify($password, $row['password'])) {
        // Set session
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = $row['role'];

        echo json_encode([
            'success' => true,
            'message' => 'Login berhasil',
            'user' => [
                'id' => $row['id'],
                'username' => $row['username'],
                'role' => $row['role']
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Password salah']);
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Akun tidak ditemukan']);
}

$stmt->close();
$conn->close();
