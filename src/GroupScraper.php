<?php
/**
 * Group Scraper
 * Handles joining groups and scraping members
 */

use danog\MadelineProto\API;
use danog\MadelineProto\Exception;

class GroupScraper {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Join group by username
     */
    public function joinGroup(int $accountId, string $username): array {
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
        
        try {
            $sessionPath = $account['session_path'];
            $settings = buildMadelineSettings((int)$account['api_id'], $account['api_hash']);

            $api = new API($sessionPath, $settings);

            try {
                $api->getSelf();
            } catch (\Throwable $e) {
                return ['success' => false, 'message' => 'Account is not logged in'];
            }
            
            // Remove @ if present
            $username = ltrim($username, '@');
            
            $resolved = $api->contacts->resolveUsername($username);
            $peer = $resolved['peer'] ?? null;
            $chatId = is_array($peer) ? ($peer['channel_id'] ?? $peer['chat_id'] ?? null) : null;

            if (!$peer) {
                return ['success' => false, 'message' => 'Invalid group username'];
            }

            $api->channels->joinChannel(['channel' => $peer]);
            
            return [
                'success' => true,
                'message' => 'Successfully joined group',
                'chat_id' => $chatId
            ];
            
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            logError("Join group error for account {$accountId}: " . $errorMsg);
            
            if (strpos($errorMsg, 'already') !== false) {
                return ['success' => true, 'message' => 'Already a member of this group'];
            }
            
            return ['success' => false, 'message' => 'Failed to join: ' . $errorMsg];
        } catch (\Throwable $e) {
            logError("Join group error for account {$accountId}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to join: ' . $e->getMessage()];
        }
    }

    /**
     * Scrape group members
     */
    public function scrapeMembers(int $accountId, string $username, string $sourceGroup = ''): array {
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
                $api->getSelf();
            } catch (\Throwable $e) {
                return ['success' => false, 'message' => 'Account is not logged in'];
            }
            
            // Remove @ if present
            $username = ltrim($username, '@');
            
            // Resolve username
            $resolved = $api->contacts->resolveUsername($username);
            $peer = $resolved['peer'] ?? null;
            $chats = $resolved['chats'] ?? [];
            $chat = $chats[0] ?? null;

            if (!$peer || !$chat) {
                return ['success' => false, 'message' => 'Invalid group username'];
            }

            $chatId = $chat['id'];
            
            // Get participants
            $participants = [];
            $offset = 0;
            $limit = 200;
            $maxMembers = 10000; // Limit to prevent timeout
            
            do {
                $result = $api->channels->getParticipants([
                    'channel' => $peer,
                    'filter' => ['_' => 'channelParticipantsRecent'],
                    'offset' => $offset,
                    'limit' => $limit,
                ]);

                $usersIndex = [];
                if (!empty($result['users'])) {
                    foreach ($result['users'] as $user) {
                        $usersIndex[$user['id']] = $user;
                    }
                }

                if (isset($result['participants'])) {
                    foreach ($result['participants'] as $participant) {
                        $userId = $participant['user_id'] ?? null;
                        if (!$userId) {
                            continue;
                        }

                        $user = $usersIndex[$userId] ?? null;
                        if (!$user) {
                            continue;
                        }

                        try {
                            $this->saveUser([
                                'user_id' => $userId,
                                'username' => $user['username'] ?? null,
                                'first_name' => $user['first_name'] ?? null,
                                'last_name' => $user['last_name'] ?? null,
                                'phone' => $user['phone'] ?? null,
                                'source_group' => $sourceGroup ?: $username,
                            ]);
                            $participants[] = $userId;
                        } catch (\Throwable $e) {
                            logError('Scraper save error: ' . $e->getMessage());
                        }
                    }
                }
                
                $offset += $limit;
                
                // Safety limit
                if (count($participants) >= $maxMembers) {
                    break;
                }
                
                // Small delay to prevent rate limiting
                usleep(500000); // 0.5 seconds
                
            } while (isset($result['participants']) && count($result['participants']) > 0);
            
            return [
                'success' => true,
                'message' => 'Scraping completed',
                'count' => count($participants)
            ];
            
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            logError("Scrape members error for account {$accountId}: " . $errorMsg);
            return ['success' => false, 'message' => 'Failed to scrape: ' . $errorMsg];
        } catch (\Throwable $e) {
            logError("Scrape members error for account {$accountId}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to scrape: ' . $e->getMessage()];
        }
    }

    /**
     * Save user to database
     */
    private function saveUser(array $userData): void {
        try {
            $dbType = defined('DB_TYPE') ? DB_TYPE : 'sqlite';
            
            // Use database-agnostic INSERT IGNORE syntax
            if ($dbType === 'sqlite') {
                $sql = "INSERT OR IGNORE INTO scraped_users (user_id, username, first_name, last_name, phone, source_group)
                        VALUES (?, ?, ?, ?, ?, ?)";
            } else {
                $sql = "INSERT IGNORE INTO scraped_users (user_id, username, first_name, last_name, phone, source_group)
                        VALUES (?, ?, ?, ?, ?, ?)";
            }
            
            $this->db->execute($sql, [
                $userData['user_id'],
                $userData['username'],
                $userData['first_name'],
                $userData['last_name'],
                $userData['phone'],
                $userData['source_group'],
            ]);
        } catch (\Throwable $e) {
            // Ignore duplicate errors
            if (strpos($e->getMessage(), 'Duplicate') === false && strpos($e->getMessage(), 'UNIQUE') === false) {
                logError("Save user error: " . $e->getMessage());
            }
        }
    }

    /**
     * Get all scraped users
     */
    public function getAllUsers(int $limit = 1000, int $offset = 0): array {
        return $this->db->fetchAll(
            "SELECT * FROM scraped_users ORDER BY scraped_at DESC LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    /**
     * Get user count
     */
    public function getUserCount(): int {
        $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM scraped_users");
        return (int)($result['count'] ?? 0);
    }
}

