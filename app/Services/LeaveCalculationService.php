<?php

namespace App\Services;

use DateTime;
use DateInterval;

class LeaveCalculationService
{
    /**
     * 특정 직원의 특정 연도에 부여될 총 연차 일수를 계산하는 메인 메소드.
     * 입사일에 따라 월차, 2년차 비례연차, 정기연차, 근속연차를 모두 고려합니다.
     *
     * @param string $hireDate 직원의 입사일 (YYYY-MM-DD)
     * @param int $targetYear 계산 대상 연도
     * @return array ['base' => float, 'seniority' => int]
     */
    public function calculateLeaveEntitlementForYear(string $hireDate, int $targetYear): array
    {
        $hire = new DateTime($hireDate);
        $hireYear = (int)$hire->format('Y');

        // 1. 입사 첫 해 (월차만 발생, 연차 부여량은 0)
        if ($targetYear === $hireYear) {
            return ['base' => 0, 'seniority' => 0];
        }

        // 2. 입사 두 번째 해 (비례 연차 부여)
        if ($targetYear === $hireYear + 1) {
            $baseLeave = $this->calculateProratedLeaveForSecondYear($hireDate);
            $seniorityLeave = 0; // 2년차에는 근속 연차 없음
            return ['base' => $baseLeave, 'seniority' => $seniorityLeave];
        }

        // 3. 입사 세 번째 해 이후 (정기 연차 + 근속 연차)
        if ($targetYear > $hireYear + 1) {
            $baseLeave = 15;
            $seniorityLeave = $this->calculateSeniorityLeave($hireDate, $targetYear);
            return ['base' => $baseLeave, 'seniority' => $seniorityLeave];
        }

        return ['base' => 0, 'seniority' => 0];
    }

    /**
     * 요구사항 3.3: 1년 미만 입사자의 월차 발생 개수를 계산합니다.
     * 입사 후 1년간, 매월 만근 시 1일씩 최대 11개 발생합니다.
     * 이 메소드는 특정 시점에 사용 가능한 월차의 총 개수를 계산합니다.
     *
     * @param string $hireDate 입사일
     * @param string $currentDate 기준일
     * @return int 사용 가능한 월차 개수
     */
    public function calculateAvailableMonthlyLeave(string $hireDate, string $currentDate): int
    {
        $hire = new DateTime($hireDate);
        $current = new DateTime($currentDate);
        $firstAnniversary = (new DateTime($hireDate))->add(new DateInterval('P1Y'));

        // 기준일이 입사일 이전이거나, 입사 1주년이 지났으면 월차는 0개
        if ($current < $hire || $current >= $firstAnniversary) {
            return 0;
        }

        $monthsPassed = $hire->diff($current)->m + ($hire->diff($current)->y * 12);

        // 입사일의 '일'이 기준일의 '일'보다 크면 아직 한 달이 꽉 차지 않은 것으로 간주
        if ($hire->format('d') > $current->format('d')) {
            $monthsPassed--;
        }

        return min(max(0, $monthsPassed), 11);
    }

    /**
     * 요구사항 3.3: 입사 후 첫 1월 1일 (두 번째 해)에 부여될 비례 연차를 계산합니다.
     * 계산식: (15일 / 12개월) * 잔월 수
     * 잔월: 입사 후 첫 1월 1일부터 ~ 입사 1주년까지 남은 개월 수
     */
    private function calculateProratedLeaveForSecondYear(string $hireDate): float
    {
        $hire = new DateTime($hireDate);
        $hireMonth = (int)$hire->format('m');
        $hireDay = (int)$hire->format('d');

        // 만약 1월 1일 입사자라면, 2년차에는 15일이 정상 부여됨 (비례 계산 아님)
        if ($hireMonth === 1 && $hireDay === 1) {
            return 15;
        }

        // 잔월 계산: (12 - 입사월) + 1. 하지만 입사일이 1일이 아니면 해당 월은 제외.
        $remainingMonths = 12 - $hireMonth;
        if ($hireDay > 1) {
             // 예를 들어 7월 2일 입사 시, 8,9,10,11,12월. 즉 5개월치가 남음. (12 - 7)
        } else {
             // 예를 들어 7월 1일 입사 시, 7,8,9,10,11,12월. 즉 6개월치가 남음. (12 - 7 + 1)
             $remainingMonths += 1;
        }

        if($remainingMonths <= 0) return 0;

        // 요구사항의 계산식 적용
        $proratedLeave = (15 / 12) * $remainingMonths;

        // 소수점은 그대로 사용하기로 했으므로 반올림 등 처리 안함
        return $proratedLeave;
    }

    /**
     * 요구사항 3.2: 근속 연차를 계산합니다.
     * 근속 기산일 기준 만 3년부터 1일, 이후 2년마다 1일 추가.
     */
    private function calculateSeniorityLeave(string $hireDate, int $targetYear): int
    {
        $hire = new DateTime($hireDate);

        // 근속 기산일 설정: 1월 1일이 아니면 다음 해 1월 1일
        $seniorityStartDate = ($hire->format('m-d') === '01-01')
            ? $hire
            : new DateTime(($hire->format('Y') + 1) . '-01-01');

        $targetDate = new DateTime($targetYear . '-01-01');
        if ($targetDate < $seniorityStartDate) return 0;

        $yearsOfService = $seniorityStartDate->diff($targetDate)->y;

        if ($yearsOfService < 3) return 0;

        // 3년차: 1일, 5년차: 2일, 7년차: 3일 ...
        $seniorityLeave = 1 + floor(($yearsOfService - 3) / 2);

        // 기본 15일 + 근속 연차의 총합은 25일을 넘을 수 없음.
        // 여기서는 근속 연차 자체만 반환하므로 최대 10일.
        return min($seniorityLeave, 10);
    }
}
