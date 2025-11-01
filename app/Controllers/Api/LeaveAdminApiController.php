<?php
namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;
use App\Services\NewLeaveService;
use App\Repositories\EmployeeRepository;

class LeaveAdminApiController
{
    private $leaveService;
    private $employeeRepository;
    private $request;

    public function __construct(NewLeaveService $leaveService, EmployeeRepository $employeeRepository, Request $request)
    {
        $this->leaveService = $leaveService;
        $this->employeeRepository = $employeeRepository;
        $this->request = $request;
    }

    private function getActorEmployeeId(): ?int
    {
        $user = $this->request->user();
        if (!$user || !isset($user['id'])) return null;
        $employee = $this->employeeRepository->findByUserId($user['id']);
        return $employee ? (int)$employee['id'] : null;
    }

    public function getRequests(): Response
    {
        $filters = $this->request->getQueryParams();
        $requests = $this->leaveService->getRequestsForAdmin($filters);
        return Response::json(['status' => 'success', 'data' => $requests]);
    }

    public function approveRequest($id): Response
    {
        $actorEmployeeId = $this->getActorEmployeeId();
        $result = $this->leaveService->approveRequest((int)$id, $actorEmployeeId);
        if ($result['success']) {
            return Response::json(['status' => 'success', 'message' => $result['message']]);
        }
        return Response::json(['status' => 'error', 'message' => $result['message']], 400);
    }

    public function rejectRequest($id): Response
    {
        $actorEmployeeId = $this->getActorEmployeeId();
        $reason = $this->request->getBody()['reason'] ?? '';
        $result = $this->leaveService->rejectRequest((int)$id, $actorEmployeeId, $reason);
        if ($result['success']) {
            return Response::json(['status' => 'success', 'message' => $result['message']]);
        }
        return Response::json(['status' => 'error', 'message' => $result['message']], 400);
    }

    public function approveCancellation($id): Response
    {
        $actorEmployeeId = $this->getActorEmployeeId();
        $result = $this->leaveService->approveCancellation((int)$id, $actorEmployeeId);
        if ($result['success']) {
            return Response::json(['status' => 'success', 'message' => $result['message']]);
        }
        return Response::json(['status' => 'error', 'message' => $result['message']], 400);
    }

    public function rejectCancellation($id): Response
    {
        $actorEmployeeId = $this->getActorEmployeeId();
        $reason = $this->request->getBody()['reason'] ?? '';
        $result = $this->leaveService->rejectCancellation((int)$id, $actorEmployeeId, $reason);
         if ($result['success']) {
            return Response::json(['status' => 'success', 'message' => $result['message']]);
        }
        return Response::json(['status' => 'error', 'message' => $result['message']], 400);
    }

    public function adjustLeave(): Response
    {
        $actorEmployeeId = $this->getActorEmployeeId();
        $data = $this->request->getBody();
        $result = $this->leaveService->adjustLeave($data, $actorEmployeeId);
        if ($result['success']) {
            return Response::json(['status' => 'success', 'message' => $result['message']]);
        }
        return Response::json(['status' => 'error', 'message' => $result['message']], 400);
    }

    public function getEntitlements(): Response
    {
        $year = $this->request->getQueryParams()['year'] ?? date('Y');
        $departmentId = $this->request->getQueryParams()['department_id'] ?? null;
        $entitlements = $this->leaveService->getEntitlements((int)$year, $departmentId ? (int)$departmentId : null);
        return Response::json(['status' => 'success', 'data' => $entitlements]);
    }

    public function calculateAnnualLeaveForAll(): Response
    {
        $year = $this->request->getBody()['year'] ?? date('Y');
        $calculatedData = $this->leaveService->calculateAnnualLeaveForAll((int)$year);
        return Response::json(['status' => 'success', 'data' => $calculatedData]);
    }

    public function grantAnnualLeave(): Response
    {
        $actorEmployeeId = $this->getActorEmployeeId();
        if (!$actorEmployeeId) {
            return Response::json(['status' => 'error', 'message' => 'Admin user not found'], 403);
        }

        $body = $this->request->getBody();
        $year = $body['year'] ?? date('Y');
        $employees = $body['employees'] ?? [];

        if (empty($employees)) {
            return Response::json(['status' => 'error', 'message' => 'No employees selected for granting leave.'], 400);
        }

        $this->leaveService->grantAnnualLeaveForYear((int)$year, $employees, $actorEmployeeId);
        return Response::json(['status' => 'success', 'message' => $year . ' annual leave granted successfully.']);
    }

    public function expireLeave(): Response
    {
        $actorEmployeeId = $this->getActorEmployeeId();
        if (!$actorEmployeeId) {
            return Response::json(['status' => 'error', 'message' => 'Admin user not found'], 403);
        }
        $year = $this->request->getBody()['year'] ?? date('Y');
        $this->leaveService->expireLeaveForYear((int)$year, $actorEmployeeId);
        return Response::json(['status' => 'success', 'message' => $year . ' year-end leave expiration processed successfully.']);
    }

    public function getLogs(): Response
    {
        $filters = $this->request->getQueryParams();
        $logs = $this->leaveService->getLogsForAdmin($filters);
        return Response::json(['status' => 'success', 'data' => $logs]);
    }
}
