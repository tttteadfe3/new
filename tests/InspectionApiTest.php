<?php

use App\Core\Database;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class InspectionApiTest extends TestCase
{
    private Client $http;
    private static int $createdVehicleId;
    private static int $createdInspectionId;

    public static function setUpBeforeClass(): void
    {
        $db = new Database();
        $pdo = $db->getConnection();
        $pdo->exec('DELETE FROM vm_vehicle_inspections');
        $pdo->exec('DELETE FROM vm_vehicles');
        $pdo->exec('DELETE FROM hr_departments');

        // Create a department to associate with the vehicle
        $stmt = $pdo->prepare("INSERT INTO hr_departments (name) VALUES (?)");
        $stmt->execute(['Test Department']);
        $departmentId = $pdo->lastInsertId();

        // Create a vehicle to associate with the inspection
        $stmt = $pdo->prepare("INSERT INTO vm_vehicles (vehicle_number, model, year, department_id, status_code) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['123가4567', 'Sonata', 2022, $departmentId, 'NORMAL']);
        self::$createdVehicleId = $pdo->lastInsertId();
    }

    protected function setUp(): void
    {
        $this->http = new Client(['base_uri' => 'http://localhost/api/', 'http_errors' => false]);
    }

    public function testCreateInspection()
    {
        $response = $this->http->post('inspections', [
            'json' => [
                'vehicle_id' => self::$createdVehicleId,
                'inspection_date' => '2023-10-01',
                'expiry_date' => '2025-09-30',
                'inspector_name' => 'Korea Transportation Safety Authority',
                'result' => '합격',
                'cost' => 55000,
            ]
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('id', $data['data']);
        self::$createdInspectionId = $data['data']['id'];
    }

    public function testGetInspections()
    {
        $response = $this->http->get('inspections');
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertIsArray($data['data']);
    }

    public function testGetInspectionById()
    {
        $response = $this->http->get('inspections/' . self::$createdInspectionId);
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertEquals(self::$createdInspectionId, $data['data']['id']);
    }

    public function testUpdateInspection()
    {
        $response = $this->http->put('inspections/' . self::$createdInspectionId, [
            'json' => [
                'cost' => 60000
            ]
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertEquals(60000, $data['data']['cost']);
    }

    public function testDeleteInspection()
    {
        $response = $this->http->delete('inspections/' . self::$createdInspectionId);
        $this->assertEquals(204, $response->getStatusCode());
    }
}
