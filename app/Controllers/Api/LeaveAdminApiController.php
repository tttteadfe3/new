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
    public function grantAnnualLeave(Request $request): Response
    {
        $actorEmployeeId = $this->getActorEmployeeId($request);
        if (!$actorEmployeeId) {
            return Response::json(['status' => 'error', 'message' => 'Admin user not found'], 403);
        }
        $year = $request->getBody()['year'] ?? date('Y');
        $this->leaveService->grantAnnualLeaveForYear((int)$year, $actorEmployeeId);
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
