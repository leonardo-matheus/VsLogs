<?php
/**
 * Database Manager - SQLite
 */

class Database {
    private static ?PDO $instance = null;
    private static string $dbPath = __DIR__ . '/data/activity.db';

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $dir = dirname(self::$dbPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            self::$instance = new PDO('sqlite:' . self::$dbPath);
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            self::createTables();
        }
        return self::$instance;
    }

    private static function createTables(): void {
        $db = self::$instance;
        
        // Tabela de atividades
        $db->exec('
            CREATE TABLE IF NOT EXISTS activities (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id TEXT NOT NULL,
                session_id TEXT NOT NULL UNIQUE,
                active_time INTEGER DEFAULT 0,
                afk_time INTEGER DEFAULT 0,
                is_active INTEGER DEFAULT 1,
                workspace TEXT,
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                date TEXT,
                lines_typed INTEGER DEFAULT 0,
                languages TEXT DEFAULT "{}",
                hourly_activity TEXT DEFAULT "{}",
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // Tabela de resumo diário
        $db->exec('
            CREATE TABLE IF NOT EXISTS daily_summary (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id TEXT NOT NULL,
                date DATE NOT NULL,
                total_active_time INTEGER DEFAULT 0,
                total_afk_time INTEGER DEFAULT 0,
                total_lines_typed INTEGER DEFAULT 0,
                languages TEXT DEFAULT "{}",
                hourly_activity TEXT DEFAULT "{}",
                sessions_count INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(user_id, date)
            )
        ');

        // Índices para performance
        $db->exec('CREATE INDEX IF NOT EXISTS idx_activities_user_date ON activities(user_id, date)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_activities_timestamp ON activities(timestamp)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_daily_summary_user_date ON daily_summary(user_id, date)');
    }
}
