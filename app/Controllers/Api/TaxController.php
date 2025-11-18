<?php

namespace App\Controllers\Api;

use App\Core\BaseController;
use App\Services\TaxService;
use App\Core\Request;
use Exception;

class TaxController extends BaseController
{
    private TaxService $taxService;

    public function __construct(TaxService $taxService, Request $request)
    {
        parent::__construct($request);
        $this->taxService = $taxService;
    }

    public function index()
    {
        try {
            $filters = $this->request->all();
            $taxes = $this->taxService->getTaxes($filters);
            return $this->jsonResponse(['data' => $taxes]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function show(int $id)
    {
        try {
            $tax = $this->taxService->getTaxById($id);
            if (!$tax) {
                return $this->jsonResponse(['error' => 'Tax record not found'], 404);
            }
            return $this->jsonResponse(['data' => $tax]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function store()
    {
        try {
            $data = $this->request->json();
            $taxId = $this->taxService->createTax($data);
            $newTax = $this->taxService->getTaxById($taxId);
            return $this->jsonResponse(['data' => $newTax], 201);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function update(int $id)
    {
        try {
            $data = $this->request->json();
            $this->taxService->updateTax($id, $data);
            $updatedTax = $this->taxService->getTaxById($id);
            return $this->jsonResponse(['data' => $updatedTax]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function destroy(int $id)
    {
        try {
            $success = $this->taxService->deleteTax($id);
            if (!$success) {
                return $this->jsonResponse(['error' => 'Failed to delete tax record'], 500);
            }
            return $this->jsonResponse(null, 204);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
