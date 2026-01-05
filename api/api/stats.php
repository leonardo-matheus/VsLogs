<?php
/**
 * Stats API Endpoint
 * Retorna estatísticas para o dashboard
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../database.php';

try {
    $db = Database::getInstance();
    
    $period = $_GET['period'] ?? 'today';
    $userId = $_GET['user_id'] ?? null;

    switch ($period) {
        case 'today':
            $data = getTodayStats($db, $userId);
            break;
        case 'week':
            $data = getWeekStats($db, $userId);
            break;
        case 'month':
            $data = getMonthStats($db, $userId);
            break;
        case 'realtime':
            $data = getRealtimeStats($db);
            break;
        case 'languages':
            $data = getLanguageStats($db, $userId);
            break;
        case 'projects':
            $data = getProjectStats($db, $userId);
            break;
        default:
            $data = getTodayStats($db, $userId);
    }

    echo json_encode([
        'success' => true,
        'period' => $period,
        'data' => $data
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function getTodayStats(PDO $db, ?string $userId): array {
    $date = date('Y-m-d');
    
    $whereClause = $userId ? 'AND user_id = ?' : '';
    $params = $userId ? [$date, $userId] : [$date];
    
    // Buscar atividades de hoje agrupadas por workspace (pegar o maior valor)
    $stmt = $db->prepare("
        SELECT 
            workspace,
            MAX(active_time) as active_time,
            MAX(afk_time) as afk_time,
            MAX(lines_typed) as lines_typed,
            (SELECT languages FROM activities a2 
             WHERE a2.workspace = activities.workspace 
             AND a2.date = activities.date 
             " . ($userId ? "AND a2.user_id = ?" : "") . "
             ORDER BY a2.timestamp DESC LIMIT 1) as languages,
            (SELECT hourly_activity FROM activities a3 
             WHERE a3.workspace = activities.workspace 
             AND a3.date = activities.date 
             " . ($userId ? "AND a3.user_id = ?" : "") . "
             ORDER BY a3.timestamp DESC LIMIT 1) as hourly_activity
        FROM activities 
        WHERE date = ? $whereClause
        GROUP BY workspace
    ");
    
    // Ajustar parâmetros para as subqueries
    if ($userId) {
        $stmt->execute([$userId, $userId, $date, $userId]);
    } else {
        $stmt->execute([$date]);
    }
    $activities = $stmt->fetchAll();

    // Agregar dados
    $totalActiveTime = 0;
    $totalAfkTime = 0;
    $totalLines = 0;
    $allLanguages = [];
    $allHourly = [];
    $projects = [];

    foreach ($activities as $activity) {
        $totalActiveTime += $activity['active_time'];
        $totalAfkTime += $activity['afk_time'];
        $totalLines += $activity['lines_typed'];
        
        // Agregar linguagens
        $langs = json_decode($activity['languages'], true) ?: [];
        foreach ($langs as $lang => $count) {
            $allLanguages[$lang] = ($allLanguages[$lang] ?? 0) + $count;
        }

        // Agregar atividade por hora
        $hourly = json_decode($activity['hourly_activity'], true) ?: [];
        foreach ($hourly as $hour => $count) {
            $allHourly[$hour] = ($allHourly[$hour] ?? 0) + $count;
        }

        // Projetos
        $projects[] = [
            'name' => $activity['workspace'],
            'active_time' => $activity['active_time'],
            'lines_typed' => $activity['lines_typed'],
            'languages' => $langs
        ];
    }

    // Ordenar horas
    ksort($allHourly);

    return [
        'summary' => [
            'total_active_time' => $totalActiveTime,
            'total_afk_time' => $totalAfkTime,
            'total_lines_typed' => $totalLines,
            'users_count' => count(array_unique(array_column($activities, 'user_id') ?: [1]))
        ],
        'hourly' => array_map(function($hour, $count) {
            return ['hour' => $hour, 'activity_count' => $count];
        }, array_keys($allHourly), array_values($allHourly)),
        'languages' => $allLanguages,
        'projects' => $projects,
        'date' => $date
    ];
}

function getWeekStats(PDO $db, ?string $userId): array {
    $startDate = date('Y-m-d', strtotime('-6 days'));
    $endDate = date('Y-m-d');
    
    $whereClause = $userId ? 'AND user_id = ?' : '';
    $params = $userId ? [$startDate, $endDate, $userId] : [$startDate, $endDate];
    
    $stmt = $db->prepare("
        SELECT 
            date,
            SUM(total_active_time) as total_active_time,
            SUM(total_afk_time) as total_afk_time,
            SUM(total_lines_typed) as total_lines_typed,
            languages
        FROM daily_summary 
        WHERE date BETWEEN ? AND ? $whereClause
        GROUP BY date
        ORDER BY date
    ");
    $stmt->execute($params);
    $dailyData = $stmt->fetchAll();

    // Totais e linguagens
    $totalActiveTime = 0;
    $totalAfkTime = 0;
    $totalLines = 0;
    $allLanguages = [];

    foreach ($dailyData as $day) {
        $totalActiveTime += $day['total_active_time'];
        $totalAfkTime += $day['total_afk_time'];
        $totalLines += $day['total_lines_typed'];
        
        $langs = json_decode($day['languages'], true) ?: [];
        foreach ($langs as $lang => $count) {
            $allLanguages[$lang] = ($allLanguages[$lang] ?? 0) + $count;
        }
    }

    return [
        'summary' => [
            'total_active_time' => $totalActiveTime,
            'total_afk_time' => $totalAfkTime,
            'total_lines_typed' => $totalLines
        ],
        'daily' => $dailyData,
        'languages' => $allLanguages,
        'start_date' => $startDate,
        'end_date' => $endDate
    ];
}

function getMonthStats(PDO $db, ?string $userId): array {
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-d');
    
    $whereClause = $userId ? 'AND user_id = ?' : '';
    $params = $userId ? [$startDate, $endDate, $userId] : [$startDate, $endDate];
    
    $stmt = $db->prepare("
        SELECT 
            date,
            SUM(total_active_time) as total_active_time,
            SUM(total_afk_time) as total_afk_time,
            SUM(total_lines_typed) as total_lines_typed,
            languages
        FROM daily_summary 
        WHERE date BETWEEN ? AND ? $whereClause
        GROUP BY date
        ORDER BY date
    ");
    $stmt->execute($params);
    $dailyData = $stmt->fetchAll();

    // Totais e linguagens
    $totalActiveTime = 0;
    $totalAfkTime = 0;
    $totalLines = 0;
    $allLanguages = [];

    foreach ($dailyData as $day) {
        $totalActiveTime += $day['total_active_time'];
        $totalAfkTime += $day['total_afk_time'];
        $totalLines += $day['total_lines_typed'];
        
        $langs = json_decode($day['languages'], true) ?: [];
        foreach ($langs as $lang => $count) {
            $allLanguages[$lang] = ($allLanguages[$lang] ?? 0) + $count;
        }
    }

    return [
        'summary' => [
            'total_active_time' => $totalActiveTime,
            'total_afk_time' => $totalAfkTime,
            'total_lines_typed' => $totalLines
        ],
        'daily' => $dailyData,
        'languages' => $allLanguages,
        'start_date' => $startDate,
        'end_date' => $endDate
    ];
}

function getRealtimeStats(PDO $db): array {
    // Atividades nos últimos 5 minutos
    $stmt = $db->query("
        SELECT 
            user_id,
            session_id,
            active_time,
            afk_time,
            is_active,
            workspace,
            lines_typed,
            languages,
            timestamp
        FROM activities 
        WHERE timestamp >= datetime('now', '-5 minutes')
        ORDER BY timestamp DESC
    ");
    $recentActivities = $stmt->fetchAll();

    // Processar atividades
    foreach ($recentActivities as &$activity) {
        $activity['languages'] = json_decode($activity['languages'], true) ?: [];
    }

    // Usuários ativos
    $stmt = $db->query("
        SELECT COUNT(DISTINCT user_id) as active_users
        FROM activities 
        WHERE timestamp >= datetime('now', '-5 minutes')
        AND is_active = 1
    ");
    $activeUsers = $stmt->fetch();

    return [
        'active_users' => $activeUsers['active_users'] ?? 0,
        'recent_activities' => $recentActivities,
        'timestamp' => date('c')
    ];
}

function getLanguageStats(PDO $db, ?string $userId): array {
    $whereClause = $userId ? 'WHERE user_id = ?' : '';
    $params = $userId ? [$userId] : [];
    
    $stmt = $db->prepare("
        SELECT languages, workspace, lines_typed
        FROM activities 
        $whereClause
    ");
    $stmt->execute($params);
    $activities = $stmt->fetchAll();

    $allLanguages = [];
    $languagesByProject = [];

    foreach ($activities as $activity) {
        $langs = json_decode($activity['languages'], true) ?: [];
        $workspace = $activity['workspace'];
        
        foreach ($langs as $lang => $count) {
            $allLanguages[$lang] = ($allLanguages[$lang] ?? 0) + $count;
            
            if (!isset($languagesByProject[$workspace])) {
                $languagesByProject[$workspace] = [];
            }
            $languagesByProject[$workspace][$lang] = ($languagesByProject[$workspace][$lang] ?? 0) + $count;
        }
    }

    // Ordenar por uso
    arsort($allLanguages);

    return [
        'total' => $allLanguages,
        'by_project' => $languagesByProject
    ];
}

function getProjectStats(PDO $db, ?string $userId): array {
    $whereClause = $userId ? 'WHERE user_id = ?' : '';
    $params = $userId ? [$userId] : [];
    
    $stmt = $db->prepare("
        SELECT 
            workspace,
            SUM(active_time) as total_active_time,
            SUM(afk_time) as total_afk_time,
            SUM(lines_typed) as total_lines_typed,
            GROUP_CONCAT(languages) as all_languages
        FROM activities 
        $whereClause
        GROUP BY workspace
        ORDER BY total_active_time DESC
    ");
    $stmt->execute($params);
    $projects = $stmt->fetchAll();

    // Processar linguagens por projeto
    foreach ($projects as &$project) {
        $allLangs = [];
        $langStrings = explode(',', $project['all_languages'] ?? '');
        foreach ($langStrings as $langJson) {
            $langs = json_decode($langJson, true) ?: [];
            foreach ($langs as $lang => $count) {
                $allLangs[$lang] = ($allLangs[$lang] ?? 0) + $count;
            }
        }
        arsort($allLangs);
        $project['languages'] = $allLangs;
        unset($project['all_languages']);
    }

    return [
        'projects' => $projects
    ];
}
