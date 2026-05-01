<?php

class ActivityLog extends Model
{
    protected string $table = 'activity_logs';

    /**
     * Filtered + paginated query for the admin view.
     *
     * @param array $filters Supported keys:
     *   - user_id (int)
     *   - action (string)
     *   - entity_type (string)
     *   - from (Y-m-d or Y-m-d H:i:s)
     *   - to (Y-m-d or Y-m-d H:i:s)
     *   - q (free text; matches description + user_name)
     */
    public function getFiltered(int $clientId, array $filters, int $limit, int $offset): array
    {
        $where = ['client_id = :cid'];
        $params = ['cid' => $clientId];

        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = :uid';
            $params['uid'] = (int) $filters['user_id'];
        }
        if (!empty($filters['action'])) {
            $where[] = 'action = :action';
            $params['action'] = $filters['action'];
        }
        if (!empty($filters['entity_type'])) {
            $where[] = 'entity_type = :etype';
            $params['etype'] = $filters['entity_type'];
        }
        if (!empty($filters['from'])) {
            $where[] = 'created_at >= :from';
            $params['from'] = $this->normalizeDate($filters['from'], false);
        }
        if (!empty($filters['to'])) {
            $where[] = 'created_at <= :to';
            $params['to'] = $this->normalizeDate($filters['to'], true);
        }
        if (!empty($filters['q'])) {
            $where[] = '(description LIKE :q OR user_name LIKE :q)';
            // Escape LIKE metacharacters so literal % and _ in search text
            // aren't interpreted as wildcards.
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $filters['q']);
            $params['q'] = '%' . $escaped . '%';
        }

        $whereSql = implode(' AND ', $where);
        // LIMIT/OFFSET cannot be bound as parameters on all drivers — cast to int and inline.
        $limit = max(1, min(500, $limit));
        $offset = max(0, $offset);

        return Database::fetchAll(
            "SELECT * FROM {$this->table}
             WHERE {$whereSql}
             ORDER BY created_at DESC, id DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    public function countFiltered(int $clientId, array $filters): int
    {
        $where = ['client_id = :cid'];
        $params = ['cid' => $clientId];

        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = :uid';
            $params['uid'] = (int) $filters['user_id'];
        }
        if (!empty($filters['action'])) {
            $where[] = 'action = :action';
            $params['action'] = $filters['action'];
        }
        if (!empty($filters['entity_type'])) {
            $where[] = 'entity_type = :etype';
            $params['etype'] = $filters['entity_type'];
        }
        if (!empty($filters['from'])) {
            $where[] = 'created_at >= :from';
            $params['from'] = $this->normalizeDate($filters['from'], false);
        }
        if (!empty($filters['to'])) {
            $where[] = 'created_at <= :to';
            $params['to'] = $this->normalizeDate($filters['to'], true);
        }
        if (!empty($filters['q'])) {
            $where[] = '(description LIKE :q OR user_name LIKE :q)';
            // Escape LIKE metacharacters so literal % and _ in search text
            // aren't interpreted as wildcards.
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $filters['q']);
            $params['q'] = '%' . $escaped . '%';
        }

        $whereSql = implode(' AND ', $where);
        $row = Database::fetch(
            "SELECT COUNT(*) AS c FROM {$this->table} WHERE {$whereSql}",
            $params
        );
        return (int) ($row['c'] ?? 0);
    }

    /** Distinct action names present in this client's logs, for the filter dropdown. */
    public function getActionsUsed(int $clientId): array
    {
        $rows = Database::fetchAll(
            "SELECT DISTINCT action FROM {$this->table} WHERE client_id = :cid ORDER BY action ASC",
            ['cid' => $clientId]
        );
        return array_column($rows, 'action');
    }

    /** Distinct users who have events logged, for the filter dropdown. */
    public function getUsersWithActivity(int $clientId): array
    {
        return Database::fetchAll(
            "SELECT DISTINCT user_id, user_name
             FROM {$this->table}
             WHERE client_id = :cid AND user_id IS NOT NULL
             ORDER BY user_name ASC",
            ['cid' => $clientId]
        );
    }

    /**
     * Derive approximate per-user active time from login + latest-activity pairs
     * within a date range. Clamped at 4 hours per session to avoid the
     * browser-left-open-overnight inflation problem.
     *
     * Algorithm: for each user in the range, walk their events chronologically.
     * A login_success event starts a new "session window". The window ends at
     * the earliest of: (a) a logout event for the same user, (b) the next
     * login_success, (c) the last event for the user in the range, or
     * (d) 4 hours after the login.
     */
    public function getSessionDurations(int $clientId, string $start, string $end): array
    {
        $rows = Database::fetchAll(
            "SELECT user_id, user_name, user_role, action, created_at
             FROM {$this->table}
             WHERE client_id = :cid
               AND user_id IS NOT NULL
               AND created_at BETWEEN :start AND :end
             ORDER BY user_id ASC, created_at ASC",
            [
                'cid' => $clientId,
                'start' => $this->normalizeDate($start, false),
                'end' => $this->normalizeDate($end, true),
            ]
        );

        $byUser = [];
        foreach ($rows as $r) {
            $uid = (int) $r['user_id'];
            if (!isset($byUser[$uid])) {
                $byUser[$uid] = [
                    'user_id' => $uid,
                    'user_name' => $r['user_name'],
                    'user_role' => $r['user_role'],
                    'sessions' => [],
                    'last_seen' => $r['created_at'],
                    'login_count' => 0,
                    'active_seconds' => 0,
                ];
            }
            $byUser[$uid]['last_seen'] = $r['created_at'];
        }

        // Second pass: build session windows per user.
        // Pre-group rows by user_id in a single pass so we don't re-scan
        // $rows once per user (O(N) instead of O(N·U)).
        $rowsByUser = [];
        foreach ($rows as $r) {
            $uid = (int) $r['user_id'];
            $rowsByUser[$uid][] = $r;
        }

        $maxSession = 4 * 3600; // 4 hours
        // If the query window extends to "now", an open session at the end
        // of the stream should get a sensible duration (not 0). We use
        // the smaller of: now, or the end of the requested window, to avoid
        // pretending time passed that the window doesn't cover.
        $endOfWindow = strtotime($this->normalizeDate($end, true));
        $openSessionAnchor = min(time(), $endOfWindow);

        foreach ($byUser as $uid => &$u) {
            $userRows = $rowsByUser[$uid] ?? [];
            $currentLogin = null;
            $currentLast = null;
            foreach ($userRows as $r) {
                $ts = strtotime($r['created_at']);
                if ($r['action'] === 'login_success') {
                    if ($currentLogin !== null) {
                        // Close previous session at its last observed activity
                        $duration = max(0, min($maxSession, $currentLast - $currentLogin));
                        $u['active_seconds'] += $duration;
                    }
                    $currentLogin = $ts;
                    $currentLast = $ts;
                    $u['login_count']++;
                } elseif ($r['action'] === 'logout') {
                    if ($currentLogin !== null) {
                        $currentLast = $ts;
                        $duration = max(0, min($maxSession, $currentLast - $currentLogin));
                        $u['active_seconds'] += $duration;
                        $currentLogin = null;
                        $currentLast = null;
                    }
                } else {
                    if ($currentLogin !== null) {
                        $currentLast = $ts;
                    }
                }
            }
            // Tail: handle an open session (no logout event seen).
            // For an open session, prefer the live anchor (now/window-end) so
            // "user logged in 2 hours ago and is idle" reads as ~2h, not 0.
            // Still clamped at 4h to avoid overnight inflation.
            if ($currentLogin !== null && $currentLast !== null) {
                $tail = max($currentLast, $openSessionAnchor);
                $duration = max(0, min($maxSession, $tail - $currentLogin));
                $u['active_seconds'] += $duration;
            }
        }
        unset($u);

        // Sort by active_seconds desc (usort reindexes; no array_values needed)
        usort($byUser, fn($a, $b) => $b['active_seconds'] <=> $a['active_seconds']);
        return $byUser;
    }

    private function normalizeDate(string $date, bool $endOfDay): string
    {
        // Accept YYYY-MM-DD or full datetime
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date . ($endOfDay ? ' 23:59:59' : ' 00:00:00');
        }
        return $date;
    }
}
