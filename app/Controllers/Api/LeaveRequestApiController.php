<?php
namespace App\Controllers\Api;
use App\Core\Request;
use App\Core\Response;
use App\Services\NewLeaveService;
use App\Repositories\EmployeeRepository;
class LeaveRequestApiController
{
    private $leaveService;
    private $employeeRepository;
    public function __construct(NewLeaveService $leaveService, EmployeeRepository $employeeRepository)
    {
        $this->leaveService = $leaveService;
        $this->employeeRepository = $employeeRepository;
    }
    public function getBalance(Request $request): Response
    {
        $employee = $this->employeeRepository->findByUserId($request->user()['id']);
        if (!$employee) {
            return Response::json(['status' => 'error', 'message' => 'Employee not found'], 404);
        }
        $balance = $this->leaveService->getLeaveBalance($employee['id']);
        return Response::json(['status' => 'success', 'data' => $balance]);
    }
    public function index(Request $request): Response
    {
        $employee = $this->employeeRepository->findByUserId($request->user()['id']);
        if (!$employee) {
            return Response::json(['status' => 'error', 'message' => 'Employee not found'], 404);
        }
        $requests = $this->leaveService->getRequestsByEmployeeId($employee['id']);
        return Response::json(['status' => 'success', 'data' => $requests]);
    }
    public function store(Request $request): Response
    {
        $employee = $this->employeeRepository->findByUserId($request->user()['id']);
        if (!$employee) {
            return Response::json(['status' => 'error', 'message' => 'Employee not found'], 404);
        }
        $data = $request->getBody();
        $result = $this->leaveService->createLeaveRequest($employee['id'], $data);
        if ($result['success']) {
            return Response::json(['status' => 'success', 'message' => $result['message']]);
        }
        return Response::json(['status' => 'error', 'message' => $result['message']], 400);
    }
    public function cancel(Request $request, $id): Response
    {
        $employee = $this->employeeRepository->findByUserId($request->user()['id']);
        if (!$employee) {
            return Response::json(['status' => 'error', 'message' => 'Employee not found'], 404);
        }
        $result = $this->leaveService->requestCancellation((int)$id, $employee['id']);
        if ($result['success']) {
            return Response::json(['status' => 'success', 'message' => $result['message']]);
        }
        return Response::json(['status' => 'error', 'message' => $result['message']], 400);
    }
}
