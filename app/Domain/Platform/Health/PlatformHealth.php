<?php

namespace App\Domain\Platform\Health;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Minimal health of the platform: database, `database` queue, failed jobs, disk.
 */
final class PlatformHealth
{
    private const int SLOW_QUERY_MS = 200;

    private const int QUEUE_WARNING_SECONDS = 300;

    private const int QUEUE_CRITICAL_SECONDS = 1800;

    private const int FAILED_JOBS_CRITICAL = 10;

    private const float DISK_WARNING_RATIO = 0.15;

    private const float DISK_CRITICAL_RATIO = 0.05;

    /**
     * @return list<HealthCheck>
     */
    public function checks(): array
    {
        return [$this->database(), $this->queue(), $this->failedJobs(), $this->disk()];
    }

    private function database(): HealthCheck
    {
        $label = __('Database');

        try {
            $started = hrtime(true);
            DB::select('select 1');
            $milliseconds = (int) round((hrtime(true) - $started) / 1_000_000);
        } catch (Throwable) {
            return new HealthCheck('database', $label, HealthCheck::CRITICAL, __('Unreachable'));
        }

        return new HealthCheck(
            'database',
            $label,
            $milliseconds > self::SLOW_QUERY_MS ? HealthCheck::WARNING : HealthCheck::OK,
            __(':ms ms to answer', ['ms' => $milliseconds]),
        );
    }

    private function queue(): HealthCheck
    {
        $pending = DB::table('jobs')->count();
        $oldest = DB::table('jobs')->min('available_at');
        $age = is_numeric($oldest) ? max(0, now()->getTimestamp() - (int) $oldest) : 0;

        $status = match (true) {
            $age >= self::QUEUE_CRITICAL_SECONDS => HealthCheck::CRITICAL,
            $age >= self::QUEUE_WARNING_SECONDS => HealthCheck::WARNING,
            default => HealthCheck::OK,
        };

        return new HealthCheck('queue', __('Queue'), $status, $pending === 0
            ? __('No job waiting')
            : __(':count jobs waiting, oldest :minutes min', ['count' => $pending, 'minutes' => intdiv($age, 60)]));
    }

    private function failedJobs(): HealthCheck
    {
        $failed = DB::table('failed_jobs')->count();

        $status = match (true) {
            $failed >= self::FAILED_JOBS_CRITICAL => HealthCheck::CRITICAL,
            $failed > 0 => HealthCheck::WARNING,
            default => HealthCheck::OK,
        };

        return new HealthCheck('failed-jobs', __('Failed jobs'), $status, trans_choice('{0} None|{1} One failed job|[2,*] :count failed jobs', $failed, ['count' => $failed]));
    }

    private function disk(): HealthCheck
    {
        $free = @disk_free_space(storage_path());
        $total = @disk_total_space(storage_path());

        if (! is_float($free) || ! is_float($total) || $total <= 0) {
            return new HealthCheck('disk', __('Disk'), HealthCheck::WARNING, __('Unknown'));
        }

        $ratio = $free / $total;

        $status = match (true) {
            $ratio < self::DISK_CRITICAL_RATIO => HealthCheck::CRITICAL,
            $ratio < self::DISK_WARNING_RATIO => HealthCheck::WARNING,
            default => HealthCheck::OK,
        };

        return new HealthCheck('disk', __('Disk'), $status, __(':free GB free (:percent %)', [
            'free' => number_format($free / 1024 ** 3, 1),
            'percent' => (int) round($ratio * 100),
        ]));
    }
}
