<?php

namespace App\Services;

use DateTime;

class LeaveCalculationService
{
    private function getYearsOfService(string $hireDate, string $currentDate): int
    {
        $hire = new DateTime($hireDate);
        $current = new DateTime($currentDate);
        return $hire->diff($current)->y;
    }

    public function calculateSeniorityLeave(string $hireDate, int $year): int
    {
        $hire = new DateTime($hireDate);
        $seniorityStartDate = ($hire->format('m-d') === '01-01')
            ? $hire
            : new DateTime(($hire->format('Y') + 1) . '-01-01');

        $targetDate = new DateTime($year . '-01-01');
        if ($targetDate < $seniorityStartDate) return 0;

        $yearsOfService = $this->getYearsOfService($seniorityStartDate->format('Y-m-d'), $targetDate->format('Y-m-d'));
        if ($yearsOfService < 3) return 0;

        $seniorityLeave = 1 + floor(($yearsOfService - 3) / 2);
        return min($seniorityLeave, 10);
    }

    /**
     * 중도 입사자의 두 번째 해 연차를 비례 계산합니다.
     * 사용자가 확인해준 공식: (입사 첫 해의 재직일수 / 해당 연도의 총 일수) * 15일
     */
    public function calculateProratedLeaveForNewbie(string $hireDate): float
    {
        $hire = new DateTime($hireDate);
        $hireYear = (int)$hire->format('Y');

        $endOfYear = new DateTime($hireYear . '-12-31');

        // 입사 첫 해의 재직일수 계산 (입사일 포함)
        $daysWorkedInHireYear = $hire->diff($endOfYear)->days + 1;

        // 해당 연도의 총 일수 (윤년 고려)
        $totalDaysInYear = (clone $endOfYear)->format('z') + 1;

        $proratedLeave = (15 / $totalDaysInYear) * $daysWorkedInHireYear;

        return round($proratedLeave, 2);
    }

    public function calculateMonthlyLeaveCount(string $hireDate, int $year): int
    {
        $hire = new DateTime($hireDate);
        $hireYear = (int)$hire->format('Y');
        if ($year < $hireYear || $year > $hireYear + 1) return 0;

        $firstAnniversary = (new DateTime($hireDate))->modify('+1 year');
        $startMonth = ($year == $hireYear) ? (int)$hire->format('m') + 1 : 1;

        $endMonth = 12;
        if($year == $hireYear + 1) {
            $endMonth = (int)$firstAnniversary->format('m');
            // 입사 1주년이 되는 날이 그 달의 1일이 아니면, 해당 월은 월차 발생 월에서 제외
            if((int)$firstAnniversary->format('d') > 1) {
                $endMonth -= 1;
            }
        }

        if ($startMonth > $endMonth) return 0;
        return ($endMonth - $startMonth) + 1;
    }
}
