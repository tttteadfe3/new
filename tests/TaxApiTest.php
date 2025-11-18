<?php

use App\Core\Database;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class TaxApiTest extends TestCase
{
    private Client $http;
    private static int $createdVehicleId;
    private static int $createdTaxId;

    public static function setUpBeforeClass(): void
    {
        $db = new Database();
        $pdo = $db->getConnection();
        $pdo->exec('DELETE FROM vm_vehicle_taxes');
        $pdo->exec('DELETE FROM vm_vehicles');

        // Create a vehicle to associate with the tax record
        $stmt = $pdo->prepare("INSERT INTO vm_vehicles (vehicle_number, model, year, department_id, status_code) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['444가4444', 'GV80', 2023, 1, 'NORMAL']);
        self::$createdVehicleId = $pdo->lastInsertId();
    }

    protected function setUp(): void
    {
        $this->http = new Client(['base_uri' => 'http://localhost/api/', 'http_errors' => false]);
    }

    public function testCreateTax()
    {
        $response = $this->http->post('taxes', [
            'json' => [
                'vehicle_id' => self::$createdVehicleId,
                'payment_date' => '2024-06-01',
                'amount' => 500000,
                'tax_type' => 'Automobile Tax'
            ]
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('id', $data['data']);
        self::$createdTaxId = $data['data']['id'];
    }

    public function testGetTaxes()
    {
        $response = $this->http->get('taxes');
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertIsArray($data['data']);
    }

    public function testGetTaxById()
    {
        $response = $this->http->get('taxes/' . self::$createdTaxId);
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertEquals(self::$createdTaxId, $data['data']['id']);
    }

    public function testUpdateTax()
    {
        $response = $this->http->put('taxes/' . self::$createdTaxId, [
            'json' => [
                'amount' => 510000
            ]
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertEquals(510000, $data['data']['amount']);
    }

    public function testDeleteTax()
    {
        $response = $this->http->delete('taxes/' . self::$createdTaxId);
        $this->assertEquals(204, $response->getStatusCode());
    }
}
