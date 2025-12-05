<?php
/**
 * Telegram Account Manager
 * Handles account operations with proper session management
 */

use danog\MadelineProto\API;
use danog\MadelineProto\Exception;
use danog\MadelineProto\RPCErrorException;

class TelegramAccount {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Add new account
     */
    public function addAccount(string $phone, string $label, int $apiId, string $apiHash): array {
        $phone = formatPhone($phone);
        
        // Check if account exists
        $existing = $this->db->fetchOne(
            "SELECT id FROM telegram_accounts WHERE phone = ?",
            [$phone]
        );
        
        if ($existing) {
            return ['success' => false, 'message' => 'Account with this phone number already exists'];
        }
        
        // Generate session path (absolute .madeline file)
        $sessionPath = getSessionPath($phone);
        
        // Insert account
        $success = $this->db->execute(
            "INSERT INTO telegram_accounts (phone, label, api_id, api_hash, session_path, status) 
             VALUES (?, ?, ?, ?, ?, 'pending')",
            [$phone, $label, $apiId, $apiHash, $sessionPath]
        );
        
        if (!$success) {
            return ['success' => false, 'message' => 'Failed to add account'];
        }
        
        return ['success' => true, 'message' => 'Account added successfully'];
    }

    /**
     * Send authentication code
     */
    public function sendCode(int $accountId): array {
        $account = $this->db->fetchOne(
            "SELECT * FROM telegram_accounts WHERE id = ?",
            [$accountId]
        );
        
        if (!$account) {
            return ['success' => false, 'message' => 'Account not found'];
        }
        
        try {
            $sessionPath = $account['session_path'];
            $settings = buildMadelineSettings((int)$account['api_id'], $account['api_hash']);
            $api = new API($sessionPath, $settings);

            try {
                $me = $api->getSelf();
                if ($me) {
                    $this->db->execute(
                        "UPDATE telegram_accounts SET status = 'active' WHERE id = ?",
                        [$accountId]
                    );
                    return ['success' => true, 'message' => 'Account is already authenticated', 'already_logged_in' => true];
                }
            } catch (\Throwable $ignored) {
                // Not logged in, continue to send code
            }
            
            $sent = $api->phoneLogin($account['phone']);
            
            // Store phone_code_hash in session
            if (!isset($_SESSION['tg_auth'])) {
                $_SESSION['tg_auth'] = [];
            }
            
            $_SESSION['tg_auth'][$accountId] = [
                'phone' => $account['phone'],
                'phone_code_hash' => $sent['phone_code_hash']
            ];
            
            return [
                'success' => true,
                'message' => 'Code sent successfully',
                'phone_code_hash' => $sent['phone_code_hash']
            ];
            
        } catch (Exception $e) {
            logError("Send code error for account {$accountId}: " . $e->getMessage());
            
            // Check if already logged in error
            if (strpos($e->getMessage(), 'already logged in') !== false || 
                strpos($e->getMessage(), 'already authorized') !== false) {
                $this->db->execute(
                    "UPDATE telegram_accounts SET status = 'active' WHERE id = ?",
                    [$accountId]
                );
                return ['success' => true, 'message' => 'Account is already authenticated', 'already_logged_in' => true];
            }
            
            return ['success' => false, 'message' => 'Failed to send code: ' . $e->getMessage()];
        } catch (\Throwable $e) {
            logError("Send code error for account {$accountId}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send code: ' . $e->getMessage()];
        }
    }

    /**
     * Complete phone login with code
     */
    public function completeLogin(int $accountId, string $code): array {
        $account = $this->db->fetchOne(
            "SELECT * FROM telegram_accounts WHERE id = ?",
            [$accountId]
        );
        
        if (!$account) {
            return ['success' => false, 'message' => 'Account not found'];
        }
        
        // Check session data
        if (!isset($_SESSION['tg_auth'][$accountId])) {
            return ['success' => false, 'message' => 'No active authentication session. Please request a new code.'];
        }
        
        $authData = $_SESSION['tg_auth'][$accountId];
        
        if (!isset($authData['phone_code_hash'])) {
            return ['success' => false, 'message' => 'Invalid authentication session. Please request a new code.'];
        }
        
        try {
            $sessionPath = $account['session_path'];
            $settings = buildMadelineSettings((int)$account['api_id'], $account['api_hash']);
            $api = new API($sessionPath, $settings);

            try {
                $result = $api->completePhoneLogin($code);
            } catch (RPCErrorException $rpcError) {
                if ($rpcError->rpc === 'SESSION_PASSWORD_NEEDED') {
                    unset($_SESSION['tg_auth'][$accountId]);
                    return ['success' => false, 'message' => '2FA password required. Please disable two-step verification for this account.'];
                }
                throw $rpcError;
            }
            
            // Success - update account status
            $this->db->execute(
                "UPDATE telegram_accounts SET status = 'active' WHERE id = ?",
                [$accountId]
            );
            
            // Clear auth session
            unset($_SESSION['tg_auth'][$accountId]);
            
            // Log activity
            $this->db->execute(
                "INSERT INTO activity_logs (account_id, action, message, status) 
                 VALUES (?, 'login', 'Account authenticated successfully', 'success')",
                [$accountId]
            );
            
            return ['success' => true, 'message' => 'Account authenticated successfully'];
            
        } catch (Exception $e) {
            logError("Complete login error for account {$accountId}: " . $e->getMessage());
            
            $errorMsg = $e->getMessage();
            
            // Handle specific errors
            if (strpos($errorMsg, 'PHONE_CODE_INVALID') !== false || 
                strpos($errorMsg, 'invalid') !== false) {
                return ['success' => false, 'message' => 'Invalid verification code. Please try again.'];
            }
            
            if (strpos($errorMsg, 'not waiting') !== false || strpos($errorMsg, 'PHONE_CODE_EXPIRED') !== false) {
                unset($_SESSION['tg_auth'][$accountId]);
                return ['success' => false, 'message' => 'Code expired. Please request a new code.'];
            }
            
            return ['success' => false, 'message' => 'Authentication failed: ' . $errorMsg];
        } catch (\Throwable $e) {
            logError("Complete login error for account {$accountId}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Authentication failed: ' . $e->getMessage()];
        }
    }

    /**
     * Get all accounts
     */
    public function getAllAccounts(): array {
        return $this->db->fetchAll("SELECT * FROM telegram_accounts ORDER BY created_at DESC");
    }

    /**
     * Get account by ID
     */
    public function getAccount(int $id): ?array {
        return $this->db->fetchOne("SELECT * FROM telegram_accounts WHERE id = ?", [$id]);
    }

    /**
     * Update account limits
     */
    public function updateLimits(int $accountId, int $dailyLimit, int $perRunLimit, int $delayMin, int $delayMax): bool {
        return $this->db->execute(
            "UPDATE telegram_accounts 
             SET daily_limit = ?, per_run_limit = ?, delay_min = ?, delay_max = ?, updated_at = CURRENT_TIMESTAMP
             WHERE id = ?",
            [$dailyLimit, $perRunLimit, $delayMin, $delayMax, $accountId]
        );
    }

    /**
     * Delete account
     */
    public function deleteAccount(int $accountId): bool {
        $account = $this->getAccount($accountId);
        if (!$account) {
            return false;
        }
        
        $sessionPath = $account['session_path'];
        if (is_file($sessionPath)) {
            @unlink($sessionPath);
        }
        if (is_file($sessionPath . '.lock')) {
            @unlink($sessionPath . '.lock');
        }
        
        // Delete from database
        return $this->db->execute("DELETE FROM telegram_accounts WHERE id = ?", [$accountId]);
    }

    /**
     * Check account status
     */
    public function checkStatus(int $accountId): array {
        $account = $this->getAccount($accountId);
        if (!$account) {
            return ['success' => false, 'message' => 'Account not found'];
        }
        
        try {
            $sessionPath = $account['session_path'];
            $settings = buildMadelineSettings((int)$account['api_id'], $account['api_hash']);
            $api = new API($sessionPath, $settings);

            try {
                $me = $api->getSelf();
                $this->db->execute(
                    "UPDATE telegram_accounts SET status = 'active' WHERE id = ?",
                    [$accountId]
                );
                return [
                    'success' => true,
                    'status' => 'active',
                    'user' => $me
                ];
            } catch (\Throwable $e) {
                $this->db->execute(
                    "UPDATE telegram_accounts SET status = 'inactive' WHERE id = ?",
                    [$accountId]
                );
                return [
                    'success' => true,
                    'status' => 'inactive'
                ];
            }
        } catch (\Throwable $e) {
            logError("Status check error for account {$accountId}: " . $e->getMessage());
            $this->db->execute(
                "UPDATE telegram_accounts SET status = 'inactive' WHERE id = ?",
                [$accountId]
            );
            return [
                'success' => true,
                'status' => 'inactive',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Reset daily limits
     */
    public function resetDailyLimits(): void {
        $this->db->execute(
            "UPDATE telegram_accounts 
             SET messages_sent_today = 0, last_reset_date = CURRENT_DATE 
             WHERE last_reset_date < CURRENT_DATE"
        );
    }

    /**
     * Delete directory recursively
     */
    private function deleteDirectory(string $dir): bool {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        return rmdir($dir);
    }
}

