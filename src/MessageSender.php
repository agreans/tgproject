<?php
/**
 * Message Sender
 * Handles sending messages via Telegram accounts
 */

use danog\MadelineProto\API;
use danog\MadelineProto\Exception;
use danog\MadelineProto\RPCErrorException;

class MessageSender {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Send message to user
     */
    public function sendMessage(int $accountId, int $userId, string $message): array {
        $account = $this->db->fetchOne(
            "SELECT * FROM telegram_accounts WHERE id = ?",
            [$accountId]
        );
        
        if (!$account) {
            return ['success' => false, 'message' => 'Account not found'];
        }
        
        if ($account['status'] !== 'active') {
            return ['success' => false, 'message' => 'Account is not active'];
        }
        
        // Check daily limit
        if ($account['messages_sent_today'] >= $account['daily_limit']) {
            return ['success' => false, 'message' => 'Daily limit reached'];
        }
        
        try {
            $sessionPath = $account['session_path'];
            $settings = buildMadelineSettings((int)$account['api_id'], $account['api_hash']);

            $api = new API($sessionPath, $settings);

            try {
                $api->getSelf();
            } catch (\Throwable $e) {
                $this->db->execute(
                    "UPDATE telegram_accounts SET status = 'inactive' WHERE id = ?",
                    [$accountId]
                );
                return ['success' => false, 'message' => 'Account is not logged in'];
            }
            
            // Send message
            $result = $api->messages->sendMessage([
                'peer' => $userId,
                'message' => $message,
            ]);
            
            // Update counters
            $this->db->execute(
                "UPDATE telegram_accounts 
                 SET messages_sent_today = messages_sent_today + 1 
                 WHERE id = ?",
                [$accountId]
            );
            
            return [
                'success' => true,
                'message' => 'Message sent successfully',
                'message_id' => $result['id'] ?? null
            ];
            
        } catch (RPCErrorException $e) {
            $errorMsg = $e->getMessage();
            logError("Send message error for account {$accountId}: " . $errorMsg);

            if (strpos($errorMsg, 'FLOOD_WAIT') !== false || strpos($errorMsg, 'PEER_FLOOD') !== false) {
                return ['success' => false, 'message' => 'Rate limited: ' . $errorMsg];
            }

            if (strpos($errorMsg, 'USER_PRIVACY') !== false) {
                return ['success' => false, 'message' => 'User privacy settings prevent messaging.'];
            }

            return ['success' => false, 'message' => 'Failed to send: ' . $errorMsg];
        } catch (Exception $e) {
            logError("Send message error for account {$accountId}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send: ' . $e->getMessage()];
        }
    }

    /**
     * Process message queue item
     */
    public function processQueueItem(int $queueId): array {
        $item = $this->db->fetchOne(
            "SELECT mq.*, ta.* 
             FROM message_queue mq
             JOIN telegram_accounts ta ON mq.account_id = ta.id
             WHERE mq.id = ? AND mq.status = 'pending'",
            [$queueId]
        );
        
        if (!$item) {
            return ['success' => false, 'message' => 'Queue item not found or already processed'];
        }
        
        // Check account limits
        if ($item['messages_sent_today'] >= $item['daily_limit']) {
            $this->db->execute(
                "UPDATE message_queue SET status = 'failed', error_message = 'Daily limit reached' WHERE id = ?",
                [$queueId]
            );
            return ['success' => false, 'message' => 'Daily limit reached'];
        }
        
        // Send message
        $result = $this->sendMessage(
            $item['account_id'],
            $item['user_id'],
            $item['message']
        );
        
        if ($result['success']) {
            $this->db->execute(
                "UPDATE message_queue SET status = 'sent', sent_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$queueId]
            );
        } else {
            $attempts = $item['attempts'] + 1;
            $status = $attempts >= 3 ? 'failed' : 'pending';
            $this->db->execute(
                "UPDATE message_queue 
                 SET attempts = ?, status = ?, error_message = ? 
                 WHERE id = ?",
                [$attempts, $status, $result['message'], $queueId]
            );
        }
        
        return $result;
    }
}

