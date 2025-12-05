<?php
/**
 * Broadcast Manager
 * Handles broadcast job creation and management
 */

class BroadcastManager {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create broadcast job
     */
    public function createJob(string $name, int $templateId, array $accountIds, array $userIds): array {
        // Validate template
        $template = $this->db->fetchOne(
            "SELECT * FROM message_templates WHERE id = ?",
            [$templateId]
        );
        
        if (!$template) {
            return ['success' => false, 'message' => 'Template not found'];
        }
        
        // Validate accounts
        $placeholders = implode(',', array_fill(0, count($accountIds), '?'));
        $accounts = $this->db->fetchAll(
            "SELECT id FROM telegram_accounts WHERE id IN ({$placeholders}) AND status = 'active'",
            $accountIds
        );
        
        if (empty($accounts)) {
            return ['success' => false, 'message' => 'No active accounts selected'];
        }
        
        // Validate users
        $userPlaceholders = implode(',', array_fill(0, count($userIds), '?'));
        $users = $this->db->fetchAll(
            "SELECT * FROM scraped_users WHERE id IN ({$userPlaceholders})",
            $userIds
        );
        
        if (empty($users)) {
            return ['success' => false, 'message' => 'No users selected'];
        }
        
        // Create job
        $this->db->execute(
            "INSERT INTO broadcast_jobs (name, template_id, account_ids, user_list, total_users, status)
             VALUES (?, ?, ?, ?, ?, 'pending')",
            [
                $name,
                $templateId,
                json_encode($accountIds),
                json_encode($userIds),
                count($users)
            ]
        );
        
        $jobId = $this->db->lastInsertId();
        
        // Create queue items
        $this->createQueueItems($jobId, $accountIds, $users, $template['content']);
        
        return ['success' => true, 'job_id' => $jobId, 'message' => 'Broadcast job created successfully'];
    }

    /**
     * Create queue items for job
     */
    private function createQueueItems(int $jobId, array $accountIds, array $users, string $template): void {
        foreach ($users as $user) {
            // Replace template variables
            $message = $this->replaceTemplateVariables($template, $user);
            
            // Distribute across accounts (round-robin)
            $accountId = $accountIds[array_rand($accountIds)];
            
            $this->db->execute(
                "INSERT INTO message_queue (job_id, account_id, user_id, message, status)
                 VALUES (?, ?, ?, ?, 'pending')",
                [$jobId, $accountId, $user['user_id'], $message]
            );
        }
    }

    /**
     * Replace template variables
     */
    private function replaceTemplateVariables(string $template, array $user): string {
        $replacements = [
            '{firstname}' => $user['first_name'] ?? '',
            '{lastname}' => $user['last_name'] ?? '',
            '{username}' => $user['username'] ?? '',
            '{fullname}' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Get all jobs
     */
    public function getAllJobs(): array {
        return $this->db->fetchAll(
            "SELECT bj.*, mt.name as template_name
             FROM broadcast_jobs bj
             LEFT JOIN message_templates mt ON bj.template_id = mt.id
             ORDER BY bj.created_at DESC"
        );
    }

    /**
     * Get job by ID
     */
    public function getJob(int $id): ?array {
        $job = $this->db->fetchOne(
            "SELECT bj.*, mt.name as template_name, mt.content as template_content
             FROM broadcast_jobs bj
             LEFT JOIN message_templates mt ON bj.template_id = mt.id
             WHERE bj.id = ?",
            [$id]
        );
        
        if ($job) {
            $job['account_ids'] = json_decode($job['account_ids'], true);
            $job['user_list'] = json_decode($job['user_list'], true);
        }
        
        return $job;
    }

    /**
     * Update job status
     */
    public function updateJobStatus(int $jobId, string $status): bool {
        $updates = ['status' => $status];
        
        if ($status === 'running') {
            $updates['started_at'] = 'CURRENT_TIMESTAMP';
        } elseif ($status === 'completed' || $status === 'failed') {
            $updates['completed_at'] = 'CURRENT_TIMESTAMP';
        }
        
        $setClause = [];
        $params = [];
        foreach ($updates as $key => $value) {
            if ($value === 'CURRENT_TIMESTAMP') {
                $setClause[] = "{$key} = CURRENT_TIMESTAMP";
            } else {
                $setClause[] = "{$key} = ?";
                $params[] = $value;
            }
        }
        $params[] = $jobId;
        
        return $this->db->execute(
            "UPDATE broadcast_jobs SET " . implode(', ', $setClause) . " WHERE id = ?",
            $params
        );
    }

    /**
     * Get job statistics
     */
    public function getJobStats(int $jobId): array {
        $stats = $this->db->fetchOne(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
             FROM message_queue
             WHERE job_id = ?",
            [$jobId]
        );
        
        return $stats ?: ['total' => 0, 'sent' => 0, 'failed' => 0, 'pending' => 0];
    }
}

