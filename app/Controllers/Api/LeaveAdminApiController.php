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
    public function __construct(NewLeaveService $leaveService, EmployeeRepository $employeeRepository)
    {
        $this->leaveService = $leaveService;
        $this->employeeRepository = $employeeRepository;
    }
    private function getActorEmployeeId(Request $request): ?int
    {
        $user = $request->user();
        if (!$user || !isset($user['id'])) return null;
        $employee = $this->employeeRepository->findByUserId($user['id']);
        return $employee ? (int)$employee['id'] : null;
    }
    public function getRequests(Request $request): Response
    {
        $filters = $request->getQueryParams();
        $requests = $this->leaveService->getRequestsForAdmin($filters);
        return Response::json(['status' => 'success', 'data' => $requests]);
    }
    public function approveRequest(Request $request, $id): Response
    {
        $actorEmployeeId = $this->getActorEmployeeId($request);
        $result = $this->leaveService->approveRequest((int)$id, $actorEmployeeId);
        if ($result['success']) {
            return Response::json(['status' => 'success', 'message' => $result['message']]);
        }
        return Response::json(['status' => 'error', 'message' => $result['message']], 400);
    }
    public function rejectRequest(Request $request, $id): Response
    {
        $actorEmployeeId = $this->getActorEmployeeId($request);
        $reason = $request->getBody()['reason'] ?? '';
        $result = $this->leaveService->rejectRequest((int)$id, $actorEmployeeId, $reason);
        if ($result['success']) {
            return Response::json(['status' => 'success', 'message' => $result['message']]);
        }
        return Response::json(['status' => 'error', 'message' => $result['message']], 400);
    }
    public function approveCancellation(Request $request, $id): Response
    {
        $actorEmployeeId = $this->getActorEmployeeId($request);
        $result = $this->leaveService->approveCancellation((int)$id, $actorEmployeeId);
        if ($result['success']) {
            return Response::json(['status' => 'success', 'message' => $result['message']]);
        }
        return Response::json(['status' => 'error', 'message' => $result['message']], 400);
    }
    public function adjustLeave(Request $request): Response
    {
        $actorEmployeeId = $this->getActorEmployeeId($request);
        $data = $request->getBody();
        $result = $this->leaveService->adjustLeave($data, $actorEmployeeId);
        if ($result['success']) {
            return Response::json(['status' => 'success', 'message' => $result['message']]);
        }
        return Response::json(['status' => 'error', 'message' => $result['message']], 400);
    }
    public function getEntitlements(Request $request): Response
    {
        $year = $request->getQueryParams()['year'] ?? date('Y');
        $departmentId = $request->getQueryParams()['department_id'] ?? null;
        $entitlements = $this->leaveService->getEntitlements((int)$year, $departmentId ? (int)$departmentId : null);
        return Response::json(['status' => 'success', 'data' => $entitlements]);
    }

    public function calculateAnnualLeaveForAll(Request $request): Response
    {
        $year = $request->getBody()['year'] ?? date('Y');
        $calculatedData = $this->leaveService->calculateAnnualLeaveForAll((int)$year);
        return Response::json(['status' => 'success', 'data' => $calculatedData]);
    }

    public function grantAnnualLeave(Request $request): Response
    {
        $actorEmployeeId = $this->getActorEmployeeId($request);
        if (!$actorEmployeeId) {
            return Response::json(['status' => 'error', 'message' => 'Admin user not found'], 403);
        }

        $body = $request->getBody();
        $year = $body['year'] ?? date('Y');
        $employees = $body['employees'] ?? [];

        if (empty($employees)) {
            return Response::json(['status' => 'error', 'message' => 'No employees selected for granting leave.'], 400);
        }

        $this->leaveService->grantAnnualLeaveForYear((int)$year, $employees, $actorEmployeeId);
        return Response::json(['status' => 'success', 'message' => $year . ' annual leave granted successfully.']);
    }
    public function expireLeave(Request $request): Response
    {
        $actorEmployeeId = $this->getActorEmployeeId($request);
        if (!$actorEmployeeId) {
            return Response::json(['status' => 'error', 'message' => 'Admin user not found'], 403);
        }
        $year = $request->getBody()['year'] ?? date('Y');
        $this->leaveService->expireLeaveForYear((int)$year, $actorEmployeeId);
        return Response::json(['status' => 'success', 'message' => $year . ' year-end leave expiration processed successfully.']);
    }
}
