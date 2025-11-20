<?php

use App\Core\Database;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class ConsumableLogApiTest extends TestCase
{
    private Client $http;
    private static int $createdVehicleId;
    private static int $createdConsumableId;
    private static int $createdConsumableLogId;

    public static function setUpBeforeClass(): void
    {
        $db = new Database();
        $pdo = $db->getConnection();
        $pdo->exec('DELETE FROM vm_consumable_logs');
        $pdo->exec('DELETE FROM vm_vehicle_consumables');
        $pdo->exec('DELETE FROM vm_vehicles');
        $pdo->exec('DELETE FROM hr_departments');

        // Create a department to associate with the vehicle
        $stmt = $pdo->prepare("INSERT INTO hr_departments (name) VALUES (?)");
        $stmt->execute(['Test Department']);
        $departmentId = $pdo->lastInsertId();

        // Create a vehicle to associate with the log
        $stmt = $pdo->prepare("INSERT INTO vm_vehicles (vehicle_number, model, year, department_id, status_code) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['123가4567', 'Sonata', 2022, $departmentId, 'NORMAL']);
        self::$createdVehicleId = $pdo->lastInsertId();

        // Create a consumable to associate with the log
        $stmt = $pdo->prepare("INSERT INTO vm_vehicle_consumables (name, unit_price, unit) VALUES (?, ?, ?)");
        $stmt->execute(['Engine Oil', 25000, 'L']);
        self::$createdConsumableId = $pdo->lastInsertId();
    }

    protected function setUp(): void
    {
        $this->http = new Client(['base_uri' => 'http://localhost:8080/api/', 'http_errors' => false]);
    }

    public function testCreateConsumableLog()
    {
        $response = $this->http->post('consumable-logs', [
            'json' => [
                'vehicle_id' => self::$createdVehicleId,
                'consumable_id' => self::$createdConsumableId,
                'quantity' => 5,
                'total_cost' => 125000,
                'replacement_date' => '2023-10-15',
            ]
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('id', $data['data']);
        self::$createdConsumableLogId = $data['data']['id'];
    }

    public function testGetConsumableLogs()
    {
        $response = $this->http->get('consumable-logs');
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertIsArray($data['data']);
    }

    public function testGetConsumableLogById()
    {
        $response = $this->http->get('consumable-logs/' . self::$createdConsumableLogId);
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertEquals(self::$createdConsumableLogId, $data['data']['id']);
    }

    public function testUpdateConsumableLog()
    {
        $response = $this->http->put('consumable-logs/' . self::$createdConsumableLogId, [
            'json' => [
                'total_cost' => 130000
            ]
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertEquals(130000, $data['data']['total_cost']);
    }

    public function testDeleteConsumableLog()
    {
        $response = $this->http->delete('consumable-logs/' . self::$createdConsumableLogId);
        $this->assertEquals(204, $response->getStatusCode());
    }
}
