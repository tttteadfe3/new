<?php

namespace App\Controllers\Api;

use App\Core\BaseController;
use App\Services\InspectionService;
use App\Core\Request;
use Exception;

class InspectionController extends BaseController
{
    private InspectionService $inspectionService;

    public function __construct(InspectionService $inspectionService, Request $request)
    {
        parent::__construct($request);
        $this->inspectionService = $inspectionService;
    }

    public function index()
    {
        try {
            $filters = $this->request->all();
            $inspections = $this->inspectionService->getInspections($filters);
            return $this->jsonResponse(['data' => $inspections]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function show(int $id)
    {
        try {
            $inspection = $this->inspectionService->getInspectionById($id);
            if (!$inspection) {
                return $this->jsonResponse(['error' => 'Inspection record not found'], 404);
            }
            return $this->jsonResponse(['data' => $inspection]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function store()
    {
        try {
            $data = $this->request->json();
            $inspectionId = $this->inspectionService->createInspection($data);
            $newInspection = $this->inspectionService->getInspectionById($inspectionId);
            return $this->jsonResponse(['data' => $newInspection], 201);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function update(int $id)
    {
        try {
            $data = $this->request->json();
            $this->inspectionService->updateInspection($id, $data);
            $updatedInspection = $this->inspectionService->getInspectionById($id);
            return $this->jsonResponse(['data' => $updatedInspection]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function destroy(int $id)
    {
        try {
            $success = $this->inspectionService->deleteInspection($id);
            if (!$success) {
                return $this->jsonResponse(['error' => 'Failed to delete inspection record'], 500);
            }
            return $this->jsonResponse(null, 204);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
