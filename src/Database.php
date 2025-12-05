<?php
/**
 * Database Class
 * Handles SQLite and MySQL connections
 */

class Database {
    private static ?Database $instance = null;
    private $connection;
    private string $type;

    private function __construct() {
        $this->type = DB_TYPE;
        
        if ($this->type === 'sqlite') {
            $this->initSQLite();
        } else {
            $this->initMySQL();
        }
        
        $this->createTables();
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initSQLite(): void {
        $dbPath = DB_PATH;
        $dbDir = dirname($dbPath);
        
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        
        try {
            $this->connection = new PDO("sqlite:{$dbPath}");
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception("SQLite connection failed: " . $e->getMessage());
        }
    }

    private function initMySQL(): void {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $this->connection = new PDO($dsn, DB_USER, DB_PASS);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception("MySQL connection failed: " . $e->getMessage());
        }
    }

    private function createTables(): void {
        $queries = [
            // Telegram accounts
            "CREATE TABLE IF NOT EXISTS telegram_accounts (
                id INTEGER PRIMARY KEY " . ($this->type === 'sqlite' ? 'AUTOINCREMENT' : 'AUTO_INCREMENT') . ",
                phone VARCHAR(20) NOT NULL UNIQUE,
                label VARCHAR(255),
                api_id INTEGER NOT NULL,
                api_hash VARCHAR(255) NOT NULL,
                session_path TEXT NOT NULL,
                status VARCHAR(20) DEFAULT 'pending',
                daily_limit INTEGER DEFAULT 100,
                per_run_limit INTEGER DEFAULT 10,
                delay_min INTEGER DEFAULT 2,
                delay_max INTEGER DEFAULT 5,
                messages_sent_today INTEGER DEFAULT 0,
                last_reset_date DATE" . ($this->type === 'sqlite' ? " DEFAULT CURRENT_DATE" : "") . ",
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            // Message templates
            "CREATE TABLE IF NOT EXISTS message_templates (
                id INTEGER PRIMARY KEY " . ($this->type === 'sqlite' ? 'AUTOINCREMENT' : 'AUTO_INCREMENT') . ",
                name VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )",
            
            // Scraped users
            "CREATE TABLE IF NOT EXISTS scraped_users (
                id INTEGER PRIMARY KEY " . ($this->type === 'sqlite' ? 'AUTOINCREMENT' : 'AUTO_INCREMENT') . ",
                user_id BIGINT NOT NULL,
                username VARCHAR(255),
                first_name VARCHAR(255),
                last_name VARCHAR(255),
                phone VARCHAR(20),
                source_group VARCHAR(255),
                scraped_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(user_id)
            )",
            
            // Broadcast jobs
            "CREATE TABLE IF NOT EXISTS broadcast_jobs (
                id INTEGER PRIMARY KEY " . ($this->type === 'sqlite' ? 'AUTOINCREMENT' : 'AUTO_INCREMENT') . ",
                name VARCHAR(255) NOT NULL,
                template_id INTEGER NOT NULL,
                account_ids TEXT NOT NULL,
                user_list TEXT NOT NULL,
                status VARCHAR(20) DEFAULT 'pending',
                total_users INTEGER DEFAULT 0,
                sent_count INTEGER DEFAULT 0,
                failed_count INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                started_at DATETIME,
                completed_at DATETIME,
                FOREIGN KEY (template_id) REFERENCES message_templates(id)
            )",
            
            // Message queue
            "CREATE TABLE IF NOT EXISTS message_queue (
                id INTEGER PRIMARY KEY " . ($this->type === 'sqlite' ? 'AUTOINCREMENT' : 'AUTO_INCREMENT') . ",
                job_id INTEGER NOT NULL,
                account_id INTEGER NOT NULL,
                user_id BIGINT NOT NULL,
                message TEXT NOT NULL,
                status VARCHAR(20) DEFAULT 'pending',
                attempts INTEGER DEFAULT 0,
                error_message TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                sent_at DATETIME,
                FOREIGN KEY (job_id) REFERENCES broadcast_jobs(id),
                FOREIGN KEY (account_id) REFERENCES telegram_accounts(id)
            )",
            
            // Activity logs
            "CREATE TABLE IF NOT EXISTS activity_logs (
                id INTEGER PRIMARY KEY " . ($this->type === 'sqlite' ? 'AUTOINCREMENT' : 'AUTO_INCREMENT') . ",
                account_id INTEGER,
                action VARCHAR(100) NOT NULL,
                message TEXT,
                status VARCHAR(20),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (account_id) REFERENCES telegram_accounts(id)
            )"
        ];

        foreach ($queries as $query) {
            try {
                $this->connection->exec($query);
            } catch (PDOException $e) {
                // Ignore "table already exists" errors
                if (strpos($e->getMessage(), 'already exists') === false) {
                    logError("Table creation error: " . $e->getMessage());
                }
            }
        }
    }

    public function getConnection(): PDO {
        return $this->connection;
    }

    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchOne(string $sql, array $params = []): ?array {
        $result = $this->query($sql, $params)->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function execute(string $sql, array $params = []): bool {
        try {
            $this->query($sql, $params);
            return true;
        } catch (PDOException $e) {
            logError("Database execute error: " . $e->getMessage(), ['sql' => $sql, 'params' => $params]);
            return false;
        }
    }

    public function lastInsertId(): string {
        return $this->connection->lastInsertId();
    }
}

