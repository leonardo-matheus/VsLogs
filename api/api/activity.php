<?php
/**
 * Activity API Endpoint
 * Recebe dados de atividade da extensão VS Code
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../database.php';

try {
    $db = Database::getInstance();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Receber dados de atividade
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Invalid JSON input');
        }

        $userId = $input['user_id'] ?? '';
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

        // Criar chave única: user_id + workspace + date
        $uniqueKey = $userId . '_' . md5($workspace) . '_' . $date;

        // Inserir ou atualizar atividade (agrupado por user+workspace+date)
        $stmt = $db->prepare('
            INSERT INTO activities 
            (user_id, session_id, active_time, afk_time, is_active, workspace, timestamp, date, lines_typed, languages, hourly_activity)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON CONFLICT(session_id) DO UPDATE SET
                active_time = MAX(activities.active_time, excluded.active_time),
                afk_time = MAX(activities.afk_time, excluded.afk_time),
                is_active = excluded.is_active,
                timestamp = excluded.timestamp,
                lines_typed = MAX(activities.lines_typed, excluded.lines_typed),
                languages = excluded.languages,
                hourly_activity = excluded.hourly_activity
        ');
        $stmt->execute([$userId, $uniqueKey, $activeTime, $afkTime, $isActive, $workspace, $timestamp, $date, $linesTyped, $languages, $hourlyActivity]);

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
        // Retornar atividade atual
        $userId = $_GET['user_id'] ?? null;
        
        if ($userId) {
            $stmt = $db->prepare('
                SELECT * FROM activities 
                WHERE user_id = ? 
                ORDER BY timestamp DESC 
                LIMIT 1
            ');
            $stmt->execute([$userId]);
            $activity = $stmt->fetch();
        } else {
            $stmt = $db->query('
                SELECT * FROM activities 
                ORDER BY timestamp DESC 
                LIMIT 10
            ');
            $activity = $stmt->fetchAll();
        }

        echo json_encode([
            'success' => true,
            'data' => $activity
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
