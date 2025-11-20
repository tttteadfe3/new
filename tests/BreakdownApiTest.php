<?php

use App\Core\Database;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class BreakdownApiTest extends TestCase
{
    private Client $http;
    private static int $createdVehicleId;
    private static int $createdBreakdownId;

    public static function setUpBeforeClass(): void
    {
        $db = new Database();
        $pdo = $db->getConnection();
        $pdo->exec('DELETE FROM vm_vehicle_breakdowns');
        $pdo->exec('DELETE FROM vm_vehicles');
        $pdo->exec('DELETE FROM hr_departments');

        // Create a department to associate with the vehicle
        $stmt = $pdo->prepare("INSERT INTO hr_departments (name) VALUES (?)");
        $stmt->execute(['Test Department']);
        $departmentId = $pdo->lastInsertId();

        // Create a vehicle to associate with the breakdown
        $stmt = $pdo->prepare("INSERT INTO vm_vehicles (vehicle_number, model, year, department_id, status_code) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['987하6543', 'K3', 2021, $departmentId, 'NORMAL']);
        self::$createdVehicleId = $pdo->lastInsertId();
    }

    protected function setUp(): void
    {
        $this->http = new Client(['base_uri' => 'http://localhost:8080/api/', 'http_errors' => false]);
    }

    public function testCreateBreakdown()
    {
        $response = $this->http->post('breakdowns', [
            'json' => [
                'vehicle_id' => self::$createdVehicleId,
                'driver_employee_id' => 1, // Assuming employee with ID 1 exists
                'breakdown_item' => 'Engine noise',
                'description' => 'Loud rattling sound from the engine',
                'mileage' => 50000,
            ]
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('id', $data['data']);
        self::$createdBreakdownId = $data['data']['id'];
    }

    public function testGetBreakdowns()
    {
        $response = $this->http->get('breakdowns');
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertIsArray($data['data']);
    }

    public function testGetBreakdownById()
    {
        $response = $this->http->get('breakdowns/' . self::$createdBreakdownId);
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertEquals(self::$createdBreakdownId, $data['data']['id']);
    }

    public function testUpdateBreakdown()
    {
        $response = $this->http->put('breakdowns/' . self::$createdBreakdownId, [
            'json' => [
                'status' => 'RECEIVED'
            ]
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertEquals('RECEIVED', $data['data']['status']);
    }

    public function testDeleteBreakdown()
    {
        $response = $this->http->delete('breakdowns/' . self::$createdBreakdownId);
        $this->assertEquals(204, $response->getStatusCode());
    }
}
