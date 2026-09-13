<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ArchiveAnalytics extends Command
{
    protected $signature = 'analytics:archive {--batch=5000 : Rows moved per transaction}';

    protected $description = 'Move analytics telemetry older than the retention window into archive tables';

    public function handle(): int
    {
        $batchSize = min(20_000, max(1, (int) $this->option('batch')));
        $cutoff = now()->subDays((int) config('analytics.retention_days'));
        $events = $this->archiveTable('user_analytics', 'user_analytics_archive', 'created_at', $cutoff, $batchSize);
        $sessions = $this->archiveTable('analytics_sessions', 'analytics_sessions_archive', 'last_seen_at', $cutoff, $batchSize);

        $this->info("Archived {$events} events and {$sessions} sessions.");

        return self::SUCCESS;
    }

    private function archiveTable(string $source, string $archive, string $dateColumn, mixed $cutoff, int $batchSize): int
    {
        $moved = 0;

        do {
            $count = DB::transaction(function () use ($source, $archive, $dateColumn, $cutoff, $batchSize): int {
                $rows = DB::table($source)
                    ->where($dateColumn, '<', $cutoff)
                    ->orderBy('id')
                    ->limit($batchSize)
                    ->get();

                if ($rows->isEmpty()) {
                    return 0;
                }

                $payload = $rows->map(fn (object $row) => (array) $row)->all();
                DB::table($archive)->insertOrIgnore($payload);
                DB::table($source)->whereIn('id', $rows->pluck('id'))->delete();

                return $rows->count();
            });

            $moved += $count;
        } while ($count === $batchSize);

        return $moved;
    }
}
