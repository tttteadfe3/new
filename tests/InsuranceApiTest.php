<?php

use App\Core\Database;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class InsuranceApiTest extends TestCase
{
    private Client $http;
    private static int $createdVehicleId;
    private static int $createdInsuranceId;

    public static function setUpBeforeClass(): void
    {
        $db = new Database();
        $pdo = $db->getConnection();
        $pdo->exec('DELETE FROM vm_vehicle_insurance');
        $pdo->exec('DELETE FROM vm_vehicles');

        // Create a vehicle to associate with the insurance record
        $stmt = $pdo->prepare("INSERT INTO vm_vehicles (vehicle_number, model, year, department_id, status_code) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['222가2222', 'K8', 2024, 1, 'NORMAL']);
        self::$createdVehicleId = $pdo->lastInsertId();
    }

    protected function setUp(): void
    {
        $this->http = new Client(['base_uri' => 'http://localhost/api/', 'http_errors' => false]);
    }

    public function testCreateInsurance()
    {
        $response = $this->http->post('insurances', [
            'json' => [
                'vehicle_id' => self::$createdVehicleId,
                'insurer_name' => 'Test Insurance Co.',
                'policy_number' => 'POL-12345',
                'start_date' => '2024-01-01',
                'end_date' => '2025-01-01',
                'premium' => 1000000
            ]
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('id', $data['data']);
        self::$createdInsuranceId = $data['data']['id'];
    }

    public function testGetInsurances()
    {
        $response = $this->http->get('insurances');
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertIsArray($data['data']);
    }

    public function testGetInsuranceById()
    {
        $response = $this->http->get('insurances/' . self::$createdInsuranceId);
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertEquals(self::$createdInsuranceId, $data['data']['id']);
    }

    public function testUpdateInsurance()
    {
        $response = $this->http->put('insurances/' . self::$createdInsuranceId, [
            'json' => [
                'premium' => 1100000
            ]
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertEquals(1100000, $data['data']['premium']);
    }

    public function testDeleteInsurance()
    {
        $response = $this->http->delete('insurances/' . self::$createdInsuranceId);
        $this->assertEquals(204, $response->getStatusCode());
    }
}
