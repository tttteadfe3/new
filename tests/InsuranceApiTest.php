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
        $pdo->exec('DELETE FROM hr_departments');

        // Create a department to associate with the vehicle
        $stmt = $pdo->prepare("INSERT INTO hr_departments (name) VALUES (?)");
        $stmt->execute(['Test Department']);
        $departmentId = $pdo->lastInsertId();

        // Create a vehicle to associate with the insurance
        $stmt = $pdo->prepare("INSERT INTO vm_vehicles (vehicle_number, model, year, department_id, status_code) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['123가4567', 'Sonata', 2022, $departmentId, 'NORMAL']);
        self::$createdVehicleId = $pdo->lastInsertId();
    }

    protected function setUp(): void
    {
        $this->http = new Client(['base_uri' => 'http://localhost/api/', 'http_errors' => false]);
    }

    public function testCreateInsurance()
    {
        $response = $this->http->post('insurance', [
            'json' => [
                'vehicle_id' => self::$createdVehicleId,
                'insurer_name' => 'ABC Insurance',
                'policy_number' => 'POL123456',
                'start_date' => '2023-01-01',
                'end_date' => '2024-01-01',
                'premium' => 1200000,
            ]
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('id', $data['data']);
        self::$createdInsuranceId = $data['data']['id'];
    }

    public function testGetInsurances()
    {
        $response = $this->http->get('insurance');
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertIsArray($data['data']);
    }

    public function testGetInsuranceById()
    {
        $response = $this->http->get('insurance/' . self::$createdInsuranceId);
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertEquals(self::$createdInsuranceId, $data['data']['id']);
    }

    public function testUpdateInsurance()
    {
        $response = $this->http->put('insurance/' . self::$createdInsuranceId, [
            'json' => [
                'premium' => 1250000
            ]
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertEquals(1250000, $data['data']['premium']);
    }

    public function testDeleteInsurance()
    {
        $response = $this->http->delete('insurance/' . self::$createdInsuranceId);
        $this->assertEquals(204, $response->getStatusCode());
    }
}
