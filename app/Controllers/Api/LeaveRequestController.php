<?php

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\LeaveService;
use App\Repositories\EmployeeRepository;

/**
 * (신) 연차 신청 관련 API 컨트롤러
 */
class LeaveRequestController
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
     * 현재 로그인한 사용자의 잔여 연차/월차를 조회합니다.
     */
    public function getBalance(Request $request): Response
    {
        $employee = $this->employeeRepository->findByUserId($request->user()['id']);
        if (!$employee) {
            return Response::json(['status' => 'error', 'message' => '직원 정보를 찾을 수 없습니다.'], 404);
        }

        $balance = $this->leaveService->getLeaveBalance($employee['id']);
        return Response::json(['status' => 'success', 'data' => $balance]);
    }

    /**
     * 현재 로그인한 사용자의 휴가 신청 목록을 조회합니다.
     */
    public function index(Request $request): Response
    {
        $employee = $this->employeeRepository->findByUserId($request->user()['id']);
        if (!$employee) {
            return Response::json(['status' => 'error', 'message' => '직원 정보를 찾을 수 없습니다.'], 404);
        }

        // TODO: LeaveService에 getRequestsByEmployeeId 메소드 구현 필요
        // $requests = $this->leaveService->getRequestsByEmployeeId($employee['id']);
        $requests = []; // 임시 데이터

        return Response::json(['status' => 'success', 'data' => $requests]);
    }

    /**
     * 새로운 휴가를 신청합니다.
     */
    public function store(Request $request): Response
    {
        $employee = $this->employeeRepository->findByUserId($request->user()['id']);
        if (!$employee) {
            return Response::json(['status' => 'error', 'message' => '직원 정보를 찾을 수 없습니다.'], 404);
        }

        // TODO: 입력값 유효성 검사 로직 추가 필요
        $data = $request->getBody();

        $result = $this->leaveService->createLeaveRequest($employee['id'], $data);

        if ($result['success']) {
            return Response::json(['status' => 'success', 'message' => $result['message']]);
        } else {
            return Response::json(['status' => 'error', 'message' => $result['message']], 400);
        }
    }

    /**
     * 기존 휴가 신청을 취소 요청합니다.
     */
    public function cancel(Request $request, $id): Response
    {
        $employee = $this->employeeRepository->findByUserId($request->user()['id']);
        if (!$employee) {
            return Response::json(['status' => 'error', 'message' => '직원 정보를 찾을 수 없습니다.'], 404);
        }

        $result = $this->leaveService->requestCancellation((int)$id, $employee['id']);

        if ($result['success']) {
            return Response::json(['status' => 'success', 'message' => $result['message']]);
        }
        return Response::json(['status' => 'error', 'message' => $result['message']], 400);
    }
}
