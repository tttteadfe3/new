<?php

namespace App\Controllers\Api;

use App\Core\BaseController;
use App\Services\BreakdownService;
use Exception;

class BreakdownController extends BaseController
{
    private BreakdownService $breakdownService;

    public function __construct(BreakdownService $breakdownService)
    {
        $this->breakdownService = $breakdownService;
    }

    public function index()
    {
        try {
            $filters = $this->request->all();
            $breakdowns = $this->breakdownService->getBreakdowns($filters);
            return $this->jsonResponse(['data' => $breakdowns]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function show(int $id)
    {
        try {
            $breakdown = $this->breakdownService->getBreakdownById($id);
            if (!$breakdown) {
                return $this->jsonResponse(['error' => 'Breakdown not found'], 404);
            }
            return $this->jsonResponse(['data' => $breakdown]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function store()
    {
        try {
            $data = $this->request->json();
            $breakdownId = $this->breakdownService->createBreakdown($data);
            $newBreakdown = $this->breakdownService->getBreakdownById($breakdownId);
            return $this->jsonResponse(['data' => $newBreakdown], 201);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function update(int $id)
    {
        try {
            $data = $this->request->json();
            $this->breakdownService->updateBreakdown($id, $data);
            $updatedBreakdown = $this->breakdownService->getBreakdownById($id);
            return $this->jsonResponse(['data' => $updatedBreakdown]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function destroy(int $id)
    {
        try {
            $success = $this->breakdownService->deleteBreakdown($id);
            if (!$success) {
                return $this->jsonResponse(['error' => 'Failed to delete breakdown'], 500);
            }
            return $this->jsonResponse(null, 204);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
