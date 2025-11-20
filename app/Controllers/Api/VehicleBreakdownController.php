<?php

namespace App\Controllers\Api;

use App\Services\VehicleBreakdownService;
use Exception;

class VehicleBreakdownController extends BaseApiController
{
    private VehicleBreakdownService $breakdownService;

    public function __construct(...$args)
    {
        parent::__construct(...$args);
        $this->breakdownService = $this->container->get(VehicleBreakdownService::class);
    }

    public function index(): void
    {
        try {
            $vehicleId = $this->request->getParam('vehicle_id');
            if ($vehicleId) {
                $breakdowns = $this->breakdownService->getBreakdownsByVehicleId((int)$vehicleId);
            } else {
                $breakdowns = $this->breakdownService->getAllBreakdowns();
            }
            $this->apiSuccess(array_map(fn($b) => $b->toArray(), $breakdowns));
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function show(int $id): void
    {
        try {
            $breakdown = $this->breakdownService->getBreakdownById($id);
            if ($breakdown) {
                $this->apiSuccess($breakdown->toArray());
            } else {
                $this->apiNotFound("Breakdown with ID {$id} not found.");
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function store(): void
    {
        try {
            $data = $this->getJsonInput();
            $data['reporter_id'] = $this->getCurrentEmployeeId(); // Automatically set reporter

            if (empty($data['reporter_id'])) {
                $this->apiForbidden('Could not identify the reporting employee.');
                return;
            }

            $breakdown = $this->breakdownService->reportBreakdown($data);
            $this->apiSuccess($breakdown->toArray(), 'Breakdown reported successfully.');
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function updateStatus(int $id): void
    {
        try {
            $data = $this->getJsonInput();
            if (!isset($data['status'])) {
                $this->apiBadRequest("'status' field is required.");
                return;
            }

            $success = $this->breakdownService->updateBreakdownStatus($id, $data['status']);
            if ($success) {
                $this->apiSuccess(null, "Breakdown status updated successfully.");
            } else {
                $this->apiNotFound("Breakdown with ID {$id} not found.");
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
