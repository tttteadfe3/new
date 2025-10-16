<?php

namespace App\Services;

use App\Repositories\LogRepository;
use Exception;

class LogService
{
    private LogRepository $logRepository;

    public function __construct(LogRepository $logRepository)
    {
        $this->logRepository = $logRepository;
    }

    /**
     * Search logs with filters
     */
    public function searchLogs(array $filters = [], int $limit = 50): array
    {
        return $this->logRepository->search($filters, $limit);
    }

    /**
     * Clear all logs
     */
    public function clearLogs(): bool
    {
        return $this->logRepository->truncate();
    }

    /**
     * Log user activity
     */
    public function logActivity(array $logData): bool
    {
        return $this->logRepository->insert($logData);
    }
}
