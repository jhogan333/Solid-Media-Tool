<?php

class GeneratedReport extends Model
{
    protected string $table = 'generated_reports';

    public function getByClient(int $clientId, int $limit = 50): array
    {
        return Database::fetchAll(
            "SELECT id, client_id, created_by_user_id, title, date_range_start, date_range_end,
                    share_token, shared_at, view_count, created_at
             FROM {$this->table}
             WHERE client_id = :cid
             ORDER BY created_at DESC
             LIMIT {$limit}",
            ['cid' => $clientId]
        );
    }

    public function findForClient(int $id, int $clientId): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE id = :id AND client_id = :cid",
            ['id' => $id, 'cid' => $clientId]
        );
    }

    public function findByShareToken(string $token): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE share_token = :t",
            ['t' => $token]
        );
    }

    /**
     * Increment view count for a specific report. Callers MUST pass the
     * client_id so we double-check tenant scope at the SQL layer — even
     * though callers already do a lookup first, this defends against
     * future refactors that could drop the pre-check.
     */
    public function incrementViewCount(int $id, int $clientId): void
    {
        Database::query(
            "UPDATE {$this->table} SET view_count = view_count + 1
             WHERE id = :id AND client_id = :cid",
            ['id' => $id, 'cid' => $clientId]
        );
    }

    public function setShareToken(int $id, int $clientId, ?string $token): void
    {
        if ($token === null) {
            Database::query(
                "UPDATE {$this->table} SET share_token = NULL, shared_at = NULL
                 WHERE id = :id AND client_id = :cid",
                ['id' => $id, 'cid' => $clientId]
            );
        } else {
            // Preserve shared_at on re-share so it continues to mean "first shared at"
            Database::query(
                "UPDATE {$this->table}
                 SET share_token = :t,
                     shared_at = COALESCE(shared_at, NOW())
                 WHERE id = :id AND client_id = :cid",
                ['id' => $id, 'cid' => $clientId, 't' => $token]
            );
        }
    }

    /**
     * Tenant-scoped delete. Safer than inheriting Model::delete which
     * deletes by id only.
     */
    public function deleteForClient(int $id, int $clientId): void
    {
        Database::query(
            "DELETE FROM {$this->table} WHERE id = :id AND client_id = :cid",
            ['id' => $id, 'cid' => $clientId]
        );
    }
}
