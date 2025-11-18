<?php

namespace App\Controllers\Api;

use App\Core\BaseController;
use App\Services\InsuranceService;
use Exception;

class InsuranceController extends BaseController
{
    private InsuranceService $insuranceService;

    public function __construct(InsuranceService $insuranceService)
    {
        $this->insuranceService = $insuranceService;
    }

    public function index()
    {
        try {
            $filters = $this->request->all();
            $insurances = $this->insuranceService->getInsurances($filters);
            return $this->jsonResponse(['data' => $insurances]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function show(int $id)
    {
        try {
            $insurance = $this->insuranceService->getInsuranceById($id);
            if (!$insurance) {
                return $this->jsonResponse(['error' => 'Insurance record not found'], 404);
            }
            return $this->jsonResponse(['data' => $insurance]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function store()
    {
        try {
            $data = $this->request->json();
            $insuranceId = $this->insuranceService->createInsurance($data);
            $newInsurance = $this->insuranceService->getInsuranceById($insuranceId);
            return $this->jsonResponse(['data' => $newInsurance], 201);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function update(int $id)
    {
        try {
            $data = $this->request->json();
            $this->insuranceService->updateInsurance($id, $data);
            $updatedInsurance = $this->insuranceService->getInsuranceById($id);
            return $this->jsonResponse(['data' => $updatedInsurance]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function destroy(int $id)
    {
        try {
            $success = $this->insuranceService->deleteInsurance($id);
            if (!$success) {
                return $this->jsonResponse(['error' => 'Failed to delete insurance record'], 500);
            }
            return $this->jsonResponse(null, 204);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
