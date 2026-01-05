<?php
/**
 * GitHub Webhook Handler - Auto Deploy
 * 
 * Configure no GitHub:
 * Settings > Webhooks > Add webhook
 * - Payload URL: https://191-235-32-212.nip.io/api/webhook.php
 * - Content type: application/json
 * - Secret: seu_secret_aqui
 * - Events: Just the push event
 */

// Configurações
$secret = getenv('WEBHOOK_SECRET') ?: 'seu_secret_aqui'; // Defina um secret seguro!
$branch = 'main';
$deployScript = '/var/www/vslogs-api/api/deploy/deploy.sh';
$logFile = '/var/log/vslogs-webhook.log';

// Headers CORS
header('Content-Type: application/json');

// Função de log
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Ler payload
$payload = file_get_contents('php://input');

// Verificar assinatura do GitHub
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
if ($secret !== 'seu_secret_aqui') { // Só verifica se secret foi configurado
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    if (!hash_equals($expectedSignature, $signature)) {
        http_response_code(403);
        logMessage("ERRO: Assinatura inválida");
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }
}

// Decodificar JSON
$data = json_decode($payload, true);

if (!$data) {
    http_response_code(400);
    logMessage("ERRO: Payload JSON inválido");
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Verificar se é push na branch correta
$ref = $data['ref'] ?? '';
if ($ref !== "refs/heads/$branch") {
    logMessage("INFO: Push ignorado - branch: $ref");
    echo json_encode(['status' => 'ignored', 'message' => "Not $branch branch"]);
    exit;
}

// Executar deploy
logMessage("INFO: Iniciando deploy automático...");

// Executar script de deploy em background
$command = "cd /var/www/vslogs-api && sudo -u www-data bash $deployScript >> $logFile 2>&1 &";
exec($command, $output, $returnCode);

logMessage("INFO: Deploy iniciado com código: $returnCode");

// Responder ao GitHub
http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => 'Deploy triggered',
    'branch' => $branch,
    'commit' => $data['head_commit']['id'] ?? 'unknown'
]);
