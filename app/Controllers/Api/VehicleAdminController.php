<?php

namespace App\Controllers\Api;

use App\Services\VehicleAdminService;
use Exception;

class VehicleAdminController extends BaseApiController
{
    private VehicleAdminService $adminService;

    public function __construct(...$args)
    {
        parent::__construct(...$args);
        $this->adminService = $this->container->get(VehicleAdminService::class);
    }

    // ========== Insurance ==========
    public function getInsurances(int $vehicleId): void
    {
        try {
            $data = $this->adminService->getInsurancesByVehicle($vehicleId);
            $this->apiSuccess(array_map(fn($d) => $d->toArray(), $data));
        } catch (Exception $e) { $this->handleException($e); }
    }
    public function addInsurance(): void
    {
        try {
            $data = $this->getJsonInput();
            $item = $this->adminService->addInsurance($data);
            $this->apiSuccess($item->toArray(), 'Insurance added.');
        } catch (Exception $e) { $this->handleException($e); }
    }

    // ========== Taxes ==========
    public function getTaxes(int $vehicleId): void
    {
        try {
            $data = $this->adminService->getTaxesByVehicle($vehicleId);
            $this->apiSuccess(array_map(fn($d) => $d->toArray(), $data));
        } catch (Exception $e) { $this->handleException($e); }
    }
    public function addTax(): void
    {
        try {
            $data = $this->getJsonInput();
            $item = $this->adminService->addTax($data);
            $this->apiSuccess($item->toArray(), 'Tax record added.');
        } catch (Exception $e) { $this->handleException($e); }
    }

    // ========== Inspections ==========
    public function getInspections(int $vehicleId): void
    {
        try {
            $data = $this->adminService->getInspectionsByVehicle($vehicleId);
            $this->apiSuccess(array_map(fn($d) => $d->toArray(), $data));
        } catch (Exception $e) { $this->handleException($e); }
    }
    public function addInspection(): void
    {
        try {
            $data = $this->getJsonInput();
            $item = $this->adminService->addInspection($data);
            $this->apiSuccess($item->toArray(), 'Inspection record added.');
        } catch (Exception $e) { $this->handleException($e); }
    }

    // ========== Documents ==========
    public function getDocuments(int $vehicleId): void
    {
        try {
            $data = $this->adminService->getDocumentsByVehicle($vehicleId);
            $this->apiSuccess(array_map(fn($d) => $d->toArray(), $data));
        } catch (Exception $e) { $this->handleException($e); }
    }
    public function addDocument(): void
    {
        try {
            // File upload logic
            $uploadDir = __DIR__ . '/../../../storage/uploads/documents/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            if (!isset($_FILES['document_file'])) {
                $this->apiBadRequest('No file was uploaded.');
                return;
            }

            $file = $_FILES['document_file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $this->apiBadRequest('File upload error: ' . $file['error']);
                return;
            }

            $fileName = uniqid() . '-' . basename($file['name']);
            $targetPath = $uploadDir . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception('Failed to move uploaded file.');
            }

            // Get data from POST request
            $data = [
                'vehicle_id' => $_POST['vehicle_id'] ?? null,
                'document_type' => $_POST['document_type'] ?? null,
                'file_path' => '/storage/uploads/documents/' . $fileName,
            ];

            if (empty($data['vehicle_id']) || empty($data['document_type'])) {
                $this->apiBadRequest('Missing required fields: vehicle_id, document_type.');
                // Clean up uploaded file
                unlink($targetPath);
                return;
            }

            $item = $this->adminService->addDocument($data);
            $this->apiSuccess($item->toArray(), 'Document uploaded and added successfully.');

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
}
