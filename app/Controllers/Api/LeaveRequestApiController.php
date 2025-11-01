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
    private $request;

    public function __construct(NewLeaveService $leaveService, EmployeeRepository $employeeRepository, Request $request)
    {
        $this->leaveService = $leaveService;
        $this->employeeRepository = $employeeRepository;
        $this->request = $request;
    }

    private function getCurrentEmployee(): ?array
    {
        $user = $this->request->user();
        if (!$user || !isset($user['id'])) return null;
        return $this->employeeRepository->findByUserId($user['id']);
    }

    public function getBalance(): Response
    {
        $employee = $this->getCurrentEmployee();
        if (!$employee) {
            return Response::json(['status' => 'error', 'message' => 'Employee not found'], 404);
        }
        $balance = $this->leaveService->getLeaveBalance($employee['id']);
        return Response::json(['status' => 'success', 'data' => $balance]);
    }

    public function index(): Response
    {
        $employee = $this->getCurrentEmployee();
        if (!$employee) {
            return Response::json(['status' => 'error', 'message' => 'Employee not found'], 404);
        }
        $requests = $this->leaveService->getRequestsByEmployeeId($employee['id']);
        return Response::json(['status' => 'success', 'data' => $requests]);
    }

    public function store(): Response
    {
        $employee = $this->getCurrentEmployee();
        if (!$employee) {
            return Response::json(['status' => 'error', 'message' => 'Employee not found'], 404);
        }
        $data = $this->request->all();
        $result = $this->leaveService->createLeaveRequest($employee['id'], $data);
        if ($result['success']) {
            return Response::json(['status' => 'success', 'message' => $result['message']]);
        }
        return Response::json(['status' => 'error', 'message' => $result['message']], 400);
    }

    public function cancel($id): Response
    {
        $employee = $this->getCurrentEmployee();
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
