<?php
/**
 * Router simples para o servidor embutido do PHP
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Arquivos estáticos
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    $extension = pathinfo($uri, PATHINFO_EXTENSION);
    
    $mimeTypes = [
        'html' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon'
    ];
    
    if (isset($mimeTypes[$extension])) {
        header('Content-Type: ' . $mimeTypes[$extension]);
    }
    
    return false; // Deixar o PHP servir o arquivo
}

// Redirecionar raiz para dashboard
if ($uri === '/') {
    header('Location: /dashboard.html');
    exit;
}

// API endpoints
if (strpos($uri, '/api/') === 0) {
    $apiFile = __DIR__ . $uri;
    if (file_exists($apiFile)) {
        require $apiFile;
        exit;
    }
}

// 404
http_response_code(404);
echo json_encode(['error' => 'Not Found']);
