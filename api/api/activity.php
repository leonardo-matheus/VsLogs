<?php
/**
 * Activity API Endpoint
 * Recebe dados de atividade da extensão VS Code
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
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Invalid JSON input');
        }

        // Autenticar por token
        $token = $input['token'] ?? '';
        $user = null;
        
        if ($token) {
            $stmt = $db->prepare('SELECT id, public_id, name FROM users WHERE token = ?');
            $stmt->execute([$token]);
            $user = $stmt->fetch();
        }

        $userId = $user ? $user['public_id'] : ($input['user_id'] ?? 'anonymous');
        $sessionId = $input['session_id'] ?? '';
        $activeTime = (int)($input['active_time'] ?? 0);
        $afkTime = (int)($input['afk_time'] ?? 0);
        $isActive = (int)($input['is_active'] ?? 1);
        $workspace = $input['workspace'] ?? '';
        $timestamp = $input['timestamp'] ?? date('c');
        $date = date('Y-m-d');
        $linesTyped = (int)($input['lines_typed'] ?? 0);
        $languages = json_encode($input['languages'] ?? []);
        $hourlyActivity = json_encode($input['hourly_activity'] ?? []);

        // Inserir ou atualizar atividade
        $stmt = $db->prepare('
            INSERT INTO activities 
            (user_id, session_id, active_time, afk_time, is_active, workspace, timestamp, date, lines_typed, languages, hourly_activity)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON CONFLICT(session_id) DO UPDATE SET
                active_time = excluded.active_time,
                afk_time = excluded.afk_time,
                is_active = excluded.is_active,
                timestamp = excluded.timestamp,
                lines_typed = excluded.lines_typed,
                languages = excluded.languages,
                hourly_activity = excluded.hourly_activity
        ');
        $stmt->execute([$userId, $sessionId, $activeTime, $afkTime, $isActive, $workspace, $timestamp, $date, $linesTyped, $languages, $hourlyActivity]);

        // Atualizar resumo diário
        $stmt = $db->prepare('
            INSERT INTO daily_summary (user_id, date, total_active_time, total_afk_time, total_lines_typed, languages, hourly_activity, sessions_count, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, datetime("now"))
            ON CONFLICT(user_id, date) DO UPDATE SET
                total_active_time = MAX(total_active_time, excluded.total_active_time),
                total_afk_time = MAX(total_afk_time, excluded.total_afk_time),
                total_lines_typed = MAX(total_lines_typed, excluded.total_lines_typed),
                languages = excluded.languages,
                hourly_activity = excluded.hourly_activity,
                updated_at = datetime("now")
        ');
        $stmt->execute([$userId, $date, $activeTime, $afkTime, $linesTyped, $languages, $hourlyActivity]);

        echo json_encode([
            'success' => true,
            'message' => 'Activity recorded',
            'data' => [
                'user_id' => $userId,
                'session_id' => $sessionId,
                'active_time' => $activeTime,
                'afk_time' => $afkTime,
                'lines_typed' => $linesTyped
            ]
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $publicId = $_GET['id'] ?? $_GET['user_id'] ?? null;
        
        if ($publicId) {
            $stmt = $db->prepare('
                SELECT * FROM activities 
                WHERE user_id = ? 
                ORDER BY timestamp DESC 
                LIMIT 10
            ');
            $stmt->execute([$publicId]);
            $activities = $stmt->fetchAll();
            
            // Buscar info do usuário
            $stmt = $db->prepare('SELECT name, public_id FROM users WHERE public_id = ?');
            $stmt->execute([$publicId]);
            $userInfo = $stmt->fetch();
        } else {
            $stmt = $db->query('
                SELECT * FROM activities 
                ORDER BY timestamp DESC 
                LIMIT 10
            ');
            $activities = $stmt->fetchAll();
            $userInfo = null;
        }

        echo json_encode([
            'success' => true,
            'user' => $userInfo,
            'data' => $activities
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
