<?php
/**
 * Authentication API - Register, Login, Token Management
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../database.php';

try {
    $db = Database::getInstance();
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    
    switch ($action) {
        case 'register':
            handleRegister($db, $input);
            break;
        case 'login':
            handleLogin($db, $input);
            break;
        case 'verify':
            handleVerify($db, $input);
            break;
        case 'profile':
            handleProfile($db, $input);
            break;
        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function generateToken(): string {
    return bin2hex(random_bytes(32));
}

function generatePublicId(): string {
    // ID público curto e amigável para URLs
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $id = '';
    for ($i = 0; $i < 8; $i++) {
        $id .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $id;
}

function handleRegister(PDO $db, array $input): void {
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $name = trim($input['name'] ?? '');

    if (empty($email) || empty($password)) {
        throw new Exception('Email e senha são obrigatórios');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email inválido');
    }

    if (strlen($password) < 6) {
        throw new Exception('Senha deve ter pelo menos 6 caracteres');
    }

    // Verificar se email já existe
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception('Email já cadastrado');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $token = generateToken();
    $publicId = generatePublicId();

    $stmt = $db->prepare('
        INSERT INTO users (email, password_hash, name, token, public_id, created_at)
        VALUES (?, ?, ?, ?, ?, datetime("now"))
    ');
    $stmt->execute([$email, $passwordHash, $name, $token, $publicId]);

    $userId = $db->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Conta criada com sucesso!',
        'data' => [
            'user_id' => $userId,
            'token' => $token,
            'public_id' => $publicId,
            'email' => $email,
            'name' => $name
        ]
    ]);
}

function handleLogin(PDO $db, array $input): void {
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        throw new Exception('Email e senha são obrigatórios');
    }

    $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        throw new Exception('Email ou senha incorretos');
    }

    // Gerar novo token
    $newToken = generateToken();
    $stmt = $db->prepare('UPDATE users SET token = ?, last_login = datetime("now") WHERE id = ?');
    $stmt->execute([$newToken, $user['id']]);

    echo json_encode([
        'success' => true,
        'message' => 'Login realizado com sucesso!',
        'data' => [
            'user_id' => $user['id'],
            'token' => $newToken,
            'public_id' => $user['public_id'],
            'email' => $user['email'],
            'name' => $user['name']
        ]
    ]);
}

function handleVerify(PDO $db, array $input): void {
    $token = $input['token'] ?? '';

    if (empty($token)) {
        throw new Exception('Token não fornecido');
    }

    $stmt = $db->prepare('SELECT id, email, name, public_id FROM users WHERE token = ?');
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('Token inválido');
    }

    echo json_encode([
        'success' => true,
        'data' => $user
    ]);
}

function handleProfile(PDO $db, array $input): void {
    $token = $input['token'] ?? '';
    $name = trim($input['name'] ?? '');

    if (empty($token)) {
        throw new Exception('Token não fornecido');
    }

    $stmt = $db->prepare('SELECT id FROM users WHERE token = ?');
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('Token inválido');
    }

    if (!empty($name)) {
        $stmt = $db->prepare('UPDATE users SET name = ? WHERE id = ?');
        $stmt->execute([$name, $user['id']]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Perfil atualizado'
    ]);
}
