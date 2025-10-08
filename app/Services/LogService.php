<?php

namespace App\Services;

use App\Repositories\LogRepository;
use Exception;

class LogService
{
    /**
     * Search logs with filters
     */
    public function searchLogs(array $filters = [], int $limit = 50): array
    {
        return LogRepository::search($filters, $limit);
    }

    /**
     * Clear all logs
     */
    public function clearLogs(): bool
    {
        return LogRepository::truncate();
    }

    /**
     * Log user activity
     */
    public function logActivity(array $logData): bool
    {
        return LogRepository::insert($logData);
    }
}