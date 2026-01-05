<?php
/**
 * VsLogs API - Router Principal
 * 
 * Endpoints:
 * POST /api/activity - Registrar atividade
 * GET  /api/stats    - Obter estatísticas
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Parse URL
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Remove /api prefix se existir
$path = preg_replace('#^/api#', '', $path);

// Roteamento
switch (true) {
    case $path === '/activity' || $path === '/activity.php':
        require __DIR__ . '/api/activity.php';
        break;
    
    case $path === '/stats' || $path === '/stats.php':
        require __DIR__ . '/api/stats.php';
        break;
    
    case $path === '/webhook' || $path === '/webhook.php':
        require __DIR__ . '/webhook.php';
        break;
    
    case $path === '/' || $path === '':
        echo json_encode([
            'name' => 'VsLogs API',
            'version' => '1.0.0',
            'status' => 'running',
            'endpoints' => [
                'POST /api/activity' => 'Register activity',
                'GET /api/stats' => 'Get statistics',
                'GET /dashboard' => 'View dashboard'
            ]
        ]);
        break;
    
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found', 'path' => $path]);
}
