<?php

use App\Core\Database;
use App\Core\Container;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use PHPUnit\Framework\TestCase;

class VehicleApiTest extends TestCase
{
    private Client $http;
    private static int $createdVehicleId;

    public static function setUpBeforeClass(): void
    {
        // This is a bit of a hack to ensure the test database is clean.
        // In a real application, we would use a dedicated test database and transactions.
        $db = new Database();
        $pdo = $db->getConnection();
        $pdo->exec('DELETE FROM vm_vehicles');
    }

    protected function setUp(): void
    {
        $this->http = new Client(['base_uri' => 'http://localhost/api/', 'http_errors' => false]);
    }

    public function testCreateVehicle()
    {
        $response = $this->http->post('vehicles', [
            'json' => [
                'vehicle_number' => '123가4567',
                'model' => 'K5',
                'year' => 2022,
                'status_code' => 'NORMAL',
                'department_id' => 1
            ]
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('id', $data['data']);
        self::$createdVehicleId = $data['data']['id'];
    }

    public function testGetVehicles()
    {
        $response = $this->http->get('vehicles');
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertIsArray($data['data']);
    }

    public function testGetVehicleById()
    {
        $response = $this->http->get('vehicles/' . self::$createdVehicleId);
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertEquals(self::$createdVehicleId, $data['data']['id']);
    }

    public function testUpdateVehicle()
    {
        $response = $this->http->put('vehicles/' . self::$createdVehicleId, [
            'json' => [
                'status_code' => 'REPAIRING'
            ]
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertEquals('REPAIRING', $data['data']['status_code']);
    }

    public function testDeleteVehicle()
    {
        $response = $this->http->delete('vehicles/' . self::$createdVehicleId);
        $this->assertEquals(204, $response->getStatusCode());
    }
}
