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
     * 필터로 로그 검색
     * @param array $filters
     * @param int $limit
     * @return array
     */
    public function searchLogs(array $filters = [], int $limit = 50): array
    {
        return $this->logRepository->search($filters, $limit);
    }

    /**
     * 모든 로그 지우기
     * @return bool
     */
    public function clearLogs(): bool
    {
        return $this->logRepository->truncate();
    }

    /**
     * 사용자 활동 기록
     * @param array $logData
     * @return bool
     */
    public function logActivity(array $logData): bool
    {
        return $this->logRepository->insert($logData);
    }
}
