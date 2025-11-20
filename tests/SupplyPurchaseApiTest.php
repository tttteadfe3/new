<?php

use App\Core\Database;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class SupplyPurchaseApiTest extends TestCase
{
    private Client $http;
    private static int $createdItemId;
    private static int $createdPurchaseId;

    public static function setUpBeforeClass(): void
    {
        $db = new Database();
        $pdo = $db->getConnection();
        $pdo->exec('DELETE FROM supply_purchases');
        $pdo->exec('DELETE FROM supply_stocks');
        $pdo->exec('DELETE FROM supply_items');
        $pdo->exec('DELETE FROM supply_categories');

        // Create a category to associate with the item
        $stmt = $pdo->prepare("INSERT INTO supply_categories (name) VALUES (?)");
        $stmt->execute(['Test Category']);
        $categoryId = $pdo->lastInsertId();

        // Create an item to associate with the purchase
        $stmt = $pdo->prepare("INSERT INTO supply_items (category_id, name, unit, is_active) VALUES (?, ?, ?, ?)");
        $stmt->execute([$categoryId, 'Test Item', 'EA', 1]);
        self::$createdItemId = $pdo->lastInsertId();
    }

    protected function setUp(): void
    {
        $this->http = new Client(['base_uri' => 'http://localhost/api/', 'http_errors' => false]);
    }

    public function testCreatePurchaseAndVerifyStockUpdate()
    {
        $initialStock = $this->getCurrentStock(self::$createdItemId);
        $this->assertEquals(0, $initialStock);

        $purchaseQuantity = 100;

        $response = $this->http->post('supply-purchases', [
            'json' => [
                'item_id' => self::$createdItemId,
                'purchase_date' => '2025-11-18',
                'quantity' => $purchaseQuantity,
                'unit_price' => 500,
                'is_received' => true,
                'received_date' => '2025-11-18'
            ]
        ]);

        $this->assertEquals(201, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('id', $data['data']);
        self::$createdPurchaseId = $data['data']['id'];

        // Verify stock is updated
        $updatedStock = $this->getCurrentStock(self::$createdItemId);
        $this->assertEquals($initialStock + $purchaseQuantity, $updatedStock);
    }

    public function testGetPurchases()
    {
        $response = $this->http->get('supply-purchases');
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertIsArray($data['data']);
    }

    public function testGetPurchaseById()
    {
        $response = $this->http->get('supply-purchases/' . self::$createdPurchaseId);
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody(), true);
        $this->assertEquals(self::$createdPurchaseId, $data['data']['id']);
    }

    private function getCurrentStock(int $itemId): int
    {
        $db = new Database();
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare('SELECT quantity FROM supply_stocks WHERE item_id = ?');
        $stmt->execute([$itemId]);
        $result = $stmt->fetchColumn();
        return $result === false ? 0 : (int) $result;
    }
}
