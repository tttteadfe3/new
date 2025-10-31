<?php

namespace App\Services;

use DateTime;
use DateInterval;

class LeaveCalculationService
{
    /**
     * 특정 직원의 특정 연도에 부여될 연차 정보를 계산하는 메인 메소드.
     * @param string $hireDate 직원의 입사일 (YYYY-MM-DD)
     * @param int $targetYear 계산 대상 연도
     * @return array ['base' => float, 'seniority' => int, 'monthly' => int]
     */
    public function calculateLeaveEntitlementForYear(string $hireDate, int $targetYear): array
    {
        $hire = new DateTime($hireDate);
        $hireYear = (int)$hire->format('Y');

        $baseLeave = 0;
        $seniorityLeave = 0;
        $monthlyLeave = 0;

        // 입사년도와 대상년도가 같으면, 월차만 계산 (연차는 없음)
        if ($targetYear === $hireYear) {
            $monthlyLeave = $this->calculateMonthlyLeaveForHireYear($hireDate);
        }
        // 대상년도가 입사 다음 해인 경우
        else if ($targetYear === $hireYear + 1) {
            // 1. 전년도 입사자로서 입사 1년이 아직 안된 시점의 월차 부여
            $monthlyLeave = $this->calculateMonthlyLeaveForSecondYear($hireDate);
            // 2. 2년차 비례연차 부여
            $baseLeave = $this->calculateProratedLeaveByDaysWorked($hireDate);
        }
        // 대상년도가 입사 2년 후부터 (정기 연차)
        else if ($targetYear > $hireYear + 1) {
            $baseLeave = 15;
            $seniorityLeave = $this->calculateSeniorityLeave($hireDate, $targetYear);
        }

        return ['base' => $baseLeave, 'seniority' => $seniorityLeave, 'monthly' => $monthlyLeave];
    }

    /**
     * 요구사항 1: 2년차 연차 계산 (근무일수 비례)
     * (입사 첫 해 근무일수 / 해당 연도 총 일수) * 15일
     */
    private function calculateProratedLeaveByDaysWorked(string $hireDate): float
    {
        $hire = new DateTime($hireDate);
        $hireYear = (int)$hire->format('Y');

        // 1월 1일 입사자는 비례계산이 아닌 15일 부여
        if ($hire->format('m-d') === '01-01') {
            return 15.0;
        }

        $endOfYear = new DateTime($hireYear . '-12-31');
        $daysWorkedInHireYear = $hire->diff($endOfYear)->days + 1;
        $totalDaysInYear = date('L', $hire->getTimestamp()) ? 366 : 365;

        $proratedLeave = (15 / $totalDaysInYear) * $daysWorkedInHireYear;

        // 최종 결과는 소수점 첫째 자리까지 반올림 (예: 14.1일, 5.2일)
        return round($proratedLeave, 1);
    }

    /**
     * 요구사항 2: 신규 입사 시, 입사 연도에 부여될 월차 계산
     * (예: 2025-09-27 입사자는 4일)
     */
    private function calculateMonthlyLeaveForHireYear(string $hireDate): int
    {
        $hire = new DateTime($hireDate);
        $hireMonth = (int)$hire->format('m');

        // 입사 연도의 월차는 입사한 달을 포함하여 연말까지의 개월 수.
        // 예: 9월 27일 입사 -> 9, 10, 11, 12월 -> 4일
        return 12 - $hireMonth + 1;
    }

    /**
     * 요구사항 2: 전년도 입사자의 경우, 다음 해 1월 1일에 부여될 월차 계산
     * 입사 1년이 되는 시점까지 남은 개월 수
     */
    private function calculateMonthlyLeaveForSecondYear(string $hireDate): int
    {
        $hire = new DateTime($hireDate);
        $hireMonth = (int)$hire->format('m');
        $hireDay = (int)$hire->format('d');

        // 1월 1일 입사자는 월차 개념이 없음
        if ($hireMonth === 1 && $hireDay === 1) {
            return 0;
        }

        // 입사월부터 12월까지의 월차는 전년도에 이미 계산됨.
        // 다음 해에는 1월부터 입사 1주년이 되는 달까지의 월차가 발생.
        // (입사 1주년이 되는 달은 만근해야 발생)
        return $hireMonth - 1;
    }

    /**
     * 근속 연차 계산 (로직 변경 없음)
     */
    private function calculateSeniorityLeave(string $hireDate, int $targetYear): int
    {
        $hire = new DateTime($hireDate);
        $seniorityStartDate = ($hire->format('m-d') === '01-01')
            ? $hire
            : new DateTime(($hire->format('Y') + 1) . '-01-01');

        $targetDate = new DateTime($targetYear . '-01-01');
        if ($targetDate < $seniorityStartDate) return 0;

        $yearsOfService = $seniorityStartDate->diff($targetDate)->y;

        if ($yearsOfService < 3) return 0;

        $seniorityLeave = 1 + floor(($yearsOfService - 3) / 2);

        return min($seniorityLeave, 10);
    }
}
