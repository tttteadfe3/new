<?php

use App\Core\Database;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class ConsumableApiTest extends TestCase
{
    private Client $http;
    private static int $createdConsumableId;

    public static function setUpBeforeClass(): void
    {
        $db = new Database();
        $pdo = $db->getConnection();
        $pdo->exec('DELETE FROM vm_vehicle_consumables');
    }

    protected function setUp(): void
    {
        $this->http = new Client(['base_uri' => 'http://localhost:8080/api/', 'http_errors' => false]);
    }

    public function testCreateConsumable()
    {
        $response = $this->http->post('consumables', [
            'json' => [
                'name' => 'Engine Oil',
                'unit_price' => 25000,
                'unit' => 'L',
            ]
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('id', $data['data']);
        self::$createdConsumableId = $data['data']['id'];
    }

    public function testGetConsumables()
    {
        $response = $this->http->get('consumables');
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertIsArray($data['data']);
    }

    public function testGetConsumableById()
    {
        $response = $this->http->get('consumables/' . self::$createdConsumableId);
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertEquals(self::$createdConsumableId, $data['data']['id']);
    }

    public function testUpdateConsumable()
    {
        $response = $this->http->put('consumables/' . self::$createdConsumableId, [
            'json' => [
                'unit_price' => 26000
            ]
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertEquals(26000, $data['data']['unit_price']);
    }

    public function testDeleteConsumable()
    {
        $response = $this->http->delete('consumables/' . self::$createdConsumableId);
        $this->assertEquals(204, $response->getStatusCode());
    }
}
