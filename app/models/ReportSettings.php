<?php

class ReportSettings extends Model
{
    protected string $table = 'report_settings';

    public function getByClient(int $clientId): array
    {
        $row = Database::fetch(
            "SELECT * FROM {$this->table} WHERE client_id = :cid",
            ['cid' => $clientId]
        );
        if (!$row) {
            // Return defaults if no row exists yet
            return [
                'client_id'        => $clientId,
                'minutes_per_post' => 30,
                'hourly_rate'      => 29.00,
                'currency_symbol'  => '$',
                'updated_at'       => null,
            ];
        }
        return $row;
    }

    public function upsertByClient(int $clientId, array $data): void
    {
        $existing = Database::fetch(
            "SELECT client_id FROM {$this->table} WHERE client_id = :cid",
            ['cid' => $clientId]
        );

        $minutes = max(1, min(600, (int) ($data['minutes_per_post'] ?? 30)));
        $rate    = max(0, min(9999.99, (float) ($data['hourly_rate'] ?? 29.00)));
        $curr    = substr((string) ($data['currency_symbol'] ?? '$'), 0, 5);

        if ($existing) {
            Database::query(
                "UPDATE {$this->table}
                 SET minutes_per_post = :m, hourly_rate = :r, currency_symbol = :c
                 WHERE client_id = :cid",
                ['m' => $minutes, 'r' => $rate, 'c' => $curr, 'cid' => $clientId]
            );
        } else {
            Database::query(
                "INSERT INTO {$this->table} (client_id, minutes_per_post, hourly_rate, currency_symbol)
                 VALUES (:cid, :m, :r, :c)",
                ['cid' => $clientId, 'm' => $minutes, 'r' => $rate, 'c' => $curr]
            );
        }
    }
}
