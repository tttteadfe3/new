<?php

use App\Core\Database;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class ConsumableLogApiTest extends TestCase
{
    private Client $http;
    private static int $createdConsumableLogId;
    private static int $vehicleId;
    private static int $consumableId;

    public static function setUpBeforeClass(): void
    {
        $db = new Database();
        $pdo = $db->getConnection();
        $pdo->exec('DELETE FROM vm_consumable_logs');
        $pdo->exec('DELETE FROM vm_vehicles');
        $pdo->exec('DELETE FROM vm_vehicle_consumables');

        // Create a vehicle and a consumable for testing
        $stmt = $pdo->prepare("INSERT INTO vm_vehicles (vehicle_number, model, year, status_code, department_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['123가4567', 'K5', 2022, 'NORMAL', 1]);
        self::$vehicleId = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO vm_vehicle_consumables (name, unit, unit_price) VALUES (?, ?, ?)");
        $stmt->execute(['Engine Oil', 'L', 15000]);
        self::$consumableId = $pdo->lastInsertId();
    }

    protected function setUp(): void
    {
        $this->http = new Client(['base_uri' => 'http://localhost/api/', 'http_errors' => false]);
    }

    public function testCreateConsumableLog()
    {
        $response = $this->http->post('consumable-logs', [
            'json' => [
                'vehicle_id' => self::$vehicleId,
                'consumable_id' => self::$consumableId,
                'replacement_date' => date('Y-m-d'),
                'quantity' => 4,
                'total_cost' => 60000,
                'notes' => 'Regular engine oil change'
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
                'quantity' => 5,
                'total_cost' => 75000,
                'notes' => 'Updated notes'
            ]
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertEquals(5, $data['data']['quantity']);
    }

    public function testDeleteConsumableLog()
    {
        $response = $this->http->delete('consumable-logs/' . self::$createdConsumableLogId);
        $this->assertEquals(204, $response->getStatusCode());
    }
}
