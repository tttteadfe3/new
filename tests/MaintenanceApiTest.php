<?php

use App\Core\Database;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class MaintenanceApiTest extends TestCase
{
    private Client $http;
    private static int $createdVehicleId;
    private static int $createdMaintenanceId;

    public static function setUpBeforeClass(): void
    {
        $db = new Database();
        $pdo = $db->getConnection();
        $pdo->exec('DELETE FROM vm_vehicle_maintenances');
        $pdo->exec('DELETE FROM vm_vehicles');

        // Create a vehicle to associate with the maintenance record
        $stmt = $pdo->prepare("INSERT INTO vm_vehicles (vehicle_number, model, year, department_id, status_code) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['111가1111', 'K7', 2023, 1, 'NORMAL']);
        self::$createdVehicleId = $pdo->lastInsertId();
    }

    protected function setUp(): void
    {
        $this->http = new Client(['base_uri' => 'http://localhost/api/', 'http_errors' => false]);
    }

    public function testCreateMaintenance()
    {
        $response = $this->http->post('maintenances', [
            'json' => [
                'vehicle_id' => self::$createdVehicleId,
                'driver_employee_id' => 1, // Assuming employee with ID 1 exists
                'maintenance_item' => 'Oil change',
                'description' => 'Replaced engine oil and filter',
                'used_parts' => 'Engine oil, oil filter',
            ]
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('id', $data['data']);
        self::$createdMaintenanceId = $data['data']['id'];
    }

    public function testGetMaintenances()
    {
        $response = $this->http->get('maintenances');
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertIsArray($data['data']);
    }

    public function testGetMaintenanceById()
    {
        $response = $this->http->get('maintenances/' . self::$createdMaintenanceId);
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertEquals(self::$createdMaintenanceId, $data['data']['id']);
    }

    public function testUpdateMaintenance()
    {
        $response = $this->http->put('maintenances/' . self::$createdMaintenanceId, [
            'json' => [
                'status' => 'APPROVED'
            ]
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertEquals('APPROVED', $data['data']['status']);
    }

    public function testDeleteMaintenance()
    {
        $response = $this->http->delete('maintenances/' . self::$createdMaintenanceId);
        $this->assertEquals(204, $response->getStatusCode());
    }
}
