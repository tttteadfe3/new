<?php

namespace App\Services;

use App\Repositories\VehicleInsuranceRepository;
use App\Repositories\VehicleTaxRepository;
use App\Repositories\VehicleInspectionRepository;
use App\Repositories\VehicleDocumentRepository;
use App\Models\VehicleInsurance;
use App\Models\VehicleTax;
use App\Models\VehicleInspection;
use App\Models\VehicleDocument;
use Exception;

class VehicleAdminService
{
    private VehicleInsuranceRepository $insuranceRepository;
    private VehicleTaxRepository $taxRepository;
    private VehicleInspectionRepository $inspectionRepository;
    private VehicleDocumentRepository $documentRepository;

    public function __construct(
        VehicleInsuranceRepository $insuranceRepository,
        VehicleTaxRepository $taxRepository,
        VehicleInspectionRepository $inspectionRepository,
        VehicleDocumentRepository $documentRepository
    ) {
        $this->insuranceRepository = $insuranceRepository;
        $this->taxRepository = $taxRepository;
        $this->inspectionRepository = $inspectionRepository;
        $this->documentRepository = $documentRepository;
    }

    // Insurance
    public function getInsurancesByVehicle(int $vehicleId): array { return $this->insuranceRepository->findByVehicleId($vehicleId); }
    public function addInsurance(array $data): VehicleInsurance {
        $item = new VehicleInsurance($data);
        if (!$item->validate()) throw new Exception(implode(", ", $item->getErrors()));
        return $this->insuranceRepository->create($item);
    }

    // Tax
    public function getTaxesByVehicle(int $vehicleId): array { return $this->taxRepository->findByVehicleId($vehicleId); }
    public function addTax(array $data): VehicleTax {
        $item = new VehicleTax($data);
        if (!$item->validate()) throw new Exception(implode(", ", $item->getErrors()));
        return $this->taxRepository->create($item);
    }

    // Inspection
    public function getInspectionsByVehicle(int $vehicleId): array { return $this->inspectionRepository->findByVehicleId($vehicleId); }
    public function addInspection(array $data): VehicleInspection {
        $item = new VehicleInspection($data);
        if (!$item->validate()) throw new Exception(implode(", ", $item->getErrors()));
        return $this->inspectionRepository->create($item);
    }

    // Document
    public function getDocumentsByVehicle(int $vehicleId): array { return $this->documentRepository->findByVehicleId($vehicleId); }
    public function addDocument(array $data): VehicleDocument {
        $item = new VehicleDocument($data);
        if (!$item->validate()) throw new Exception(implode(", ", $item->getErrors()));
        // Note: File upload logic should be handled in the controller.
        // This service just records the path.
        return $this->documentRepository->create($item);
    }
}
