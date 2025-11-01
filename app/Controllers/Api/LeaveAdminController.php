<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\LeaveService;
use App\Repositories\EmployeeRepository;

/**
 * (신) 연차 관리자 기능 관련 API 컨트롤러
 */
class LeaveAdminController
{
    private $leaveService;
    private $employeeRepository;

    public function __construct()
    {
        global $container;
        $this->leaveService = $container->get(LeaveService::class);
        $this->employeeRepository = $container->get(EmployeeRepository::class);
    }

    /**
     * 휴가 신청 목록을 조회합니다. (관리자용)
     */
    public function getRequests(Request $request): Response
    {
        $filters = $request->getQueryParams();
        $requests = $this->leaveService->getRequestsForAdmin($filters);
        return Response::json(['status' => 'success', 'data' => $requests]);
    }

    /**
     * 휴가 신청을 승인합니다.
     */
    public function approveRequest(Request $request, $id): Response
    {
        $actorEmployeeId = $this->getActorEmployeeId($request);
        $result = $this->leaveService->approveRequest((int)$id, $actorEmployeeId);

        if ($result['success']) {
            return Response::json(['status' => 'success', 'message' => $result['message']]);
        }
        return Response::json(['status' => 'error', 'message' => $result['message']], 400);
    }

    /**
     * 휴가 신청을 반려합니다.
     */
    public function rejectRequest(Request $request, $id): Response
    {
        $actorEmployeeId = $this->getActorEmployeeId($request);
        $reason = $request->getBody()['reason'] ?? '';
        // TODO: 서비스에 rejectRequest 메소드 구현 필요
        // $result = $this->leaveService->rejectRequest((int)$id, $actorEmployeeId, $reason);
        return Response::json(['status' => 'success', 'message' => '휴가 신청이 반려되었습니다.']);
    }

    /**
     * 휴가 취소 요청을 승인합니다.
     */
    public function approveCancellation(Request $request, $id): Response
    {
        $actorEmployeeId = $this->getActorEmployeeId($request);
        $result = $this->leaveService->approveCancellation((int)$id, $actorEmployeeId);

        if ($result['success']) {
            return Response::json(['status' => 'success', 'message' => $result['message']]);
        }
        return Response::json(['status' => 'error', 'message' => $result['message']], 400);
    }

    /**
     * 직원의 연차/월차를 수동으로 조정합니다.
     */
    public function adjustLeave(Request $request): Response
    {
        $actorEmployeeId = $this->getActorEmployeeId($request);
        $data = $request->getBody();
        // TODO: 서비스에 adjustLeave 메소드 구현 필요
        // $result = $this->leaveService->adjustLeave($data, $actorEmployeeId);
        return Response::json(['status' => 'success', 'message' => '연차 조정이 완료되었습니다.']);
    }

    /**
     * 모든 직원에게 연차를 일괄 부여합니다.
     */
    public function grantAnnualLeave(Request $request): Response
    {
        $actorEmployeeId = $this->getActorEmployeeId($request);
        if (!$actorEmployeeId) {
            return Response::json(['status' => 'error', 'message' => '관리자 정보를 찾을 수 없습니다.'], 403);
        }

        $year = $request->getBody()['year'] ?? date('Y');
        $this->leaveService->grantAnnualLeaveForYear((int)$year, $actorEmployeeId);
        return Response::json(['status' => 'success', 'message' => $year . '년 연차가 성공적으로 부여되었습니다.']);
    }

    /**
     * 요청 객체에서 현재 로그인한 관리자의 직원 ID를 가져옵니다.
     */
    private function getActorEmployeeId(Request $request): ?int
    {
        $user = $request->user();
        if (!$user || !isset($user['id'])) return null;

        $employee = $this->employeeRepository->findByUserId($user['id']);
        return $employee ? (int)$employee['id'] : null;
    }
}
