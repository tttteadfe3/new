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

        // Create a vehicle to associate with the inspection record
        $stmt = $pdo->prepare("INSERT INTO vm_vehicles (vehicle_number, model, year, department_id, status_code) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['333가3333', 'K9', 2025, 1, 'NORMAL']);
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
                'inspection_date' => '2025-01-01',
                'expiry_date' => '2026-01-01',
                'inspector_name' => 'Test Inspector',
                'result' => 'Pass',
                'cost' => 50000
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
                'cost' => 55000
            ]
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertEquals(55000, $data['data']['cost']);
    }

    public function testDeleteInspection()
    {
        $response = $this->http->delete('inspections/' . self::$createdInspectionId);
        $this->assertEquals(204, $response->getStatusCode());
    }
}
