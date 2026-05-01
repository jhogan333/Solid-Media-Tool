<?php

/**
 * ActivityLogService — orchestrates writes to the activity_logs table.
 *
 * SAFETY CONTRACT: every public method on this service catches ALL exceptions
 * internally. A logging failure must NEVER block or break the primary action
 * (login, post create, etc.). The worst case is a silently missed log entry,
 * which is acceptable; the worst case we avoid is a primary feature breaking
 * because the logs table is unavailable or misconfigured.
 *
 * All methods pull the actor from $_SESSION when called from a web request,
 * or accept explicit user_id/name/role arguments for the cron hook path.
 */
class ActivityLogService
{
    /**
     * Primary low-level entry point. Inserts one row. Caller is responsible
     * for passing a client_id but every other field is optional. Returns the
     * new row id on success, or null on any failure (never throws).
     */
    public function log(array $data): ?int
    {
        try {
            $clientId = (int) ($data['client_id'] ?? $GLOBALS['client_id'] ?? 0);
            if ($clientId <= 0) {
                error_log('ActivityLogService::log called without client_id');
                return null;
            }

            $row = [
                'client_id'   => $clientId,
                'user_id'     => $data['user_id'] ?? null,
                'user_name'   => isset($data['user_name']) ? $this->truncate($data['user_name'], 150) : null,
                'user_role'   => $data['user_role'] ?? null,
                'action'      => $this->truncate((string) ($data['action'] ?? 'unknown'), 50),
                'entity_type' => isset($data['entity_type']) ? $this->truncate($data['entity_type'], 50) : null,
                'entity_id'   => isset($data['entity_id']) ? (int) $data['entity_id'] : null,
                'description' => isset($data['description']) ? $this->truncate($data['description'], 500) : null,
                'metadata'    => isset($data['metadata']) && $data['metadata'] !== null
                    ? json_encode($data['metadata'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                    : null,
                'ip_address'  => $this->truncate($this->clientIp(), 45),
                'user_agent'  => $this->truncate($_SERVER['HTTP_USER_AGENT'] ?? '', 500),
            ];

            $model = new ActivityLog();
            return $model->create($row);
        } catch (\Throwable $e) {
            // Silently swallow. Do NOT propagate — logging must never break the caller.
            error_log('ActivityLogService::log failed: ' . $e->getMessage());
            return null;
        }
    }

    // ====================================================================
    // Convenience methods — one per event type. All delegate to log() which
    // is the single try/catch boundary. Each method infers actor from session
    // (except logLogin/logLogout which are passed explicit data since the
    // session state differs across login/logout timing).
    // ====================================================================

    public function logLogin(?int $userId, string $username, ?string $role, bool $success, ?string $reason = null): void
    {
        $this->log([
            'user_id'   => $userId,
            'user_name' => $username,
            'user_role' => $role,
            'action'    => $success ? 'login_success' : 'login_failed',
            'description' => $success
                ? "{$username} signed in"
                : "Failed login attempt for \"{$username}\"" . ($reason ? " ({$reason})" : ''),
            'metadata'  => $success ? null : ['reason' => $reason],
        ]);
    }

    public function logLogout(int $userId, string $username, ?string $role): void
    {
        $this->log([
            'user_id'   => $userId,
            'user_name' => $username,
            'user_role' => $role,
            'action'    => 'logout',
            'description' => "{$username} signed out",
        ]);
    }

    public function logPostAction(string $action, int $postId, string $postTitle = '', ?array $metadata = null): void
    {
        $actor = $this->currentActor();
        $label = $postTitle !== '' ? "\"{$postTitle}\"" : "#{$postId}";
        $descriptions = [
            'post_created'           => "Created post {$label}",
            'post_updated'           => "Updated post {$label}",
            'post_deleted'           => "Deleted post {$label}",
            'post_scheduled'         => "Scheduled post {$label}",
            'post_published'         => "Published post {$label}",
            'post_failed'            => "Failed to publish post {$label}",
            'post_posted_now'        => "Posted now: {$label}",
            'post_retried'           => "Retried failed post {$label}",
            'post_approved'          => "Approved post {$label}",
            'post_changes_requested' => "Requested changes on post {$label}",
        ];
        $this->log(array_merge($actor, [
            'action'      => $action,
            'entity_type' => 'post',
            'entity_id'   => $postId,
            'description' => $descriptions[$action] ?? "{$action} on post {$label}",
            'metadata'    => $metadata,
        ]));
    }

    public function logUserAction(string $action, int $targetUserId, string $targetName = '', ?array $metadata = null): void
    {
        $actor = $this->currentActor();
        $label = $targetName !== '' ? $targetName : "user #{$targetUserId}";
        $descriptions = [
            'user_created'           => "Created user {$label}",
            'user_updated'           => "Updated user {$label}",
            'user_deactivated'       => "Deactivated user {$label}",
            'user_activated'         => "Activated user {$label}",
            'user_deleted'           => "Deleted user {$label}",
            'user_restored'          => "Restored user {$label}",
            'user_permanently_deleted' => "Permanently deleted user {$label}",
            'role_changed'           => "Changed role for {$label}",
            'password_reset_by_admin' => "Reset password for {$label}",
            'password_self_reset'    => "Password reset (self-service) for {$label}",
            'password_changed'       => "Changed own password",
            'invite_sent'            => "Sent invitation to {$label}",
        ];
        $this->log(array_merge($actor, [
            'action'      => $action,
            'entity_type' => 'user',
            'entity_id'   => $targetUserId,
            'description' => $descriptions[$action] ?? "{$action} on {$label}",
            'metadata'    => $metadata,
        ]));
    }

    public function logSettingsChange(string $settingsKey, ?array $metadata = null): void
    {
        $actor = $this->currentActor();
        $descriptions = [
            'branding'      => 'Updated branding settings',
            'art_direction' => 'Updated art direction settings',
            'smtp'          => 'Updated email provider settings',
            'content_strategy' => 'Updated content strategy',
            'approval'      => 'Updated approval workflow settings',
        ];
        $this->log(array_merge($actor, [
            'action'      => 'settings_updated',
            'entity_type' => 'settings',
            'description' => $descriptions[$settingsKey] ?? "Updated {$settingsKey} settings",
            'metadata'    => $metadata,
        ]));
    }

    /**
     * Special helper for cron/system-initiated events (no session actor).
     * Used by cron/run_scheduled_posts.php when publishing scheduled posts.
     */
    public function logSystemAction(string $action, ?string $entityType, ?int $entityId, string $description, ?array $metadata = null): void
    {
        $this->log([
            'user_id'     => null,
            'user_name'   => 'System (cron)',
            'user_role'   => 'system',
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'description' => $description,
            'metadata'    => $metadata,
        ]);
    }

    // ====================================================================
    // Helpers
    // ====================================================================

    /** Extract actor snapshot from current session. */
    private function currentActor(): array
    {
        // Prefer first_name but fall back if it's empty OR unset (populateSession
        // stores '' when the user has no first name). Use ?: chaining rather than
        // ?? since ?? only short-circuits on null/unset.
        $name = ($_SESSION['first_name'] ?? '') ?: ($_SESSION['username'] ?? '') ?: null;
        return [
            'user_id'   => isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null,
            'user_name' => $name,
            'user_role' => $_SESSION['role'] ?? null,
        ];
    }

    private function clientIp(): string
    {
        // Respect common proxy headers but never trust them blindly.
        // Take the first IP from X-Forwarded-For if present.
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($parts[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    private function truncate(string $value, int $max): string
    {
        if (function_exists('mb_strlen') && mb_strlen($value) > $max) {
            return mb_substr($value, 0, $max);
        }
        if (strlen($value) > $max) {
            return substr($value, 0, $max);
        }
        return $value;
    }
}
