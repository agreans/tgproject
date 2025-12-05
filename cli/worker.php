<?php
/**
 * Cron Worker
 * Runs every minute to process message queue
 *
 * Cron command:
 * * * * * php /home/USER/domains/DOMAIN/public_html/cli/worker.php
 */

// Prevent web access
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from command line");
}

// Load bootstrap
require_once __DIR__ . '/../src/bootstrap.php';

// Set execution time limit after configuration is loaded
set_time_limit(defined('MAX_EXECUTION_TIME') ? MAX_EXECUTION_TIME : 25);

require_once __DIR__ . '/../src/TelegramAccount.php';
require_once __DIR__ . '/../src/MessageSender.php';
require_once __DIR__ . '/../src/BroadcastManager.php';

$db = Database::getInstance();
$accountManager = new TelegramAccount();
$messageSender = new MessageSender();
$broadcastManager = new BroadcastManager();

// Reset daily limits if needed
$accountManager->resetDailyLimits();

// Get pending jobs
$pendingJobs = $db->fetchAll(
    "SELECT * FROM broadcast_jobs WHERE status = 'pending' OR status = 'running' ORDER BY created_at ASC LIMIT 1"
);

foreach ($pendingJobs as $job) {
    // Update job status to running
    if ($job['status'] === 'pending') {
        $broadcastManager->updateJobStatus($job['id'], 'running');
    }
    
    // Get pending queue items for this job
    $queueItems = $db->fetchAll(
        "SELECT mq.*, ta.*
         FROM message_queue mq
         JOIN telegram_accounts ta ON mq.account_id = ta.id
         WHERE mq.job_id = ? AND mq.status = 'pending'
         ORDER BY mq.id ASC
         LIMIT 50",
        [$job['id']]
    );
    
    if (empty($queueItems)) {
        // No more items, check if job is complete
        $remaining = $db->fetchOne(
            "SELECT COUNT(*) as count FROM message_queue WHERE job_id = ? AND status = 'pending'",
            [$job['id']]
        );
        
        if (($remaining['count'] ?? 0) === 0) {
            // Job complete
            $broadcastManager->updateJobStatus($job['id'], 'completed');
            
            // Update job stats
            $stats = $broadcastManager->getJobStats($job['id']);
            $db->execute(
                "UPDATE broadcast_jobs SET sent_count = ?, failed_count = ? WHERE id = ?",
                [$stats['sent'], $stats['failed'], $job['id']]
            );
        }
        continue;
    }
    
    // Process queue items
    $processed = 0;
    $startTime = time();
    
    foreach ($queueItems as $item) {
        // Check execution time
        $maxTime = defined('MAX_EXECUTION_TIME') ? MAX_EXECUTION_TIME : 25;
        if (time() - $startTime >= $maxTime - 2) {
            break; // Stop before timeout
        }
        
        // Check account limits
        $account = $db->fetchOne(
            "SELECT * FROM telegram_accounts WHERE id = ?",
            [$item['account_id']]
        );
        
        if (!$account || $account['status'] !== 'active') {
            $db->execute(
                "UPDATE message_queue SET status = 'failed', error_message = 'Account not active' WHERE id = ?",
                [$item['id']]
            );
            continue;
        }
        
        // Check daily limit
        if ($account['messages_sent_today'] >= $account['daily_limit']) {
            continue; // Skip this account, try next item
        }
        
        // Check per-run limit (count messages sent in this run for this account)
        // Use database-agnostic timestamp comparison
        $oneMinuteAgo = date('Y-m-d H:i:s', strtotime('-1 minute'));
        $sentThisRun = $db->fetchOne(
            "SELECT COUNT(*) as count FROM message_queue 
             WHERE account_id = ? AND status = 'sent' AND sent_at > ?",
            [$item['account_id'], $oneMinuteAgo]
        );
        
        if (($sentThisRun['count'] ?? 0) >= $account['per_run_limit']) {
            continue; // Skip, limit reached
        }
        
        // Process message
        $result = $messageSender->processQueueItem($item['id']);
        
        $processed++;
        
        // Apply delay (randomized)
        if ($processed < count($queueItems)) {
            $delay = rand($account['delay_min'], $account['delay_max']);
            sleep($delay);
        }
        
        // Check execution time again
        $maxTime = defined('MAX_EXECUTION_TIME') ? MAX_EXECUTION_TIME : 25;
        if (time() - $startTime >= $maxTime - 2) {
            break;
        }
    }
    
    // Log activity
    logInfo("Worker processed {$processed} messages for job {$job['id']}");
}

// Check and update account statuses (mark inactive if logged out)
$accounts = $db->fetchAll("SELECT * FROM telegram_accounts WHERE status = 'active' LIMIT 5");

foreach ($accounts as $account) {
    try {
        $sessionPath = $account['session_path'];
        $settings = buildMadelineSettings((int)$account['api_id'], $account['api_hash']);

        $api = new \danog\MadelineProto\API($sessionPath, $settings);

        try {
            $api->getSelf();
        } catch (\Throwable $e) {
            $db->execute(
                "UPDATE telegram_accounts SET status = 'inactive' WHERE id = ?",
                [$account['id']]
            );
            logInfo("Account {$account['id']} marked as inactive (logged out)");
        }
    } catch (\Throwable $e) {
        // Ignore errors during status check
        logError("Status check error for account {$account['id']}: " . $e->getMessage());
    }
}

echo "Worker completed at " . date('Y-m-d H:i:s') . "\n";

