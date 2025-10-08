<?php
namespace App\Services;

use App\Repositories\WasteCollectionRepository;
use App\Models\WasteCollection;
use App\Core\Database;
use App\Core\FileUploader;
use App\Core\Validator;
use Exception;

class WasteCollectionService extends BaseService
{
    /**
     * Get all waste collections for user view
     */
    public function getCollections(): array
    {
        return WasteCollectionRepository::findAllWithItems();
    }

    /**
     * Get waste collection by ID
     */
    public function getCollectionById(int $id): ?array
    {
        if (empty($id)) {
            throw new Exception("ID가 필요합니다.", 400);
        }
        
        return WasteCollectionRepository::findById($id);
    }

    /**
     * Register new waste collection from field
     */
    public function registerCollection(array $postData, array $files, int $userId, ?int $employeeId): array
    {
        $this->beginTransaction();
        
        try {
            // Handle photo upload
            $photoPath = null;
            if (isset($files['photo']) && $files['photo']['error'] === UPLOAD_ERR_OK) {
                $photoPath = FileUploader::validateAndUpload($files['photo'], 'waste', 'coll_');
            }

            // Create waste collection model for validation
            $collectionData = [
                'latitude' => floatval($postData['lat']),
                'longitude' => floatval($postData['lng']),
                'address' => $postData['address'] ?? '',
                'photo_path' => $photoPath,
                'user_id' => $userId,
                'employee_id' => $employeeId,
                'issue_date' => date('Y-m-d H:i:s'),
                'type' => 'field'
            ];

            $collection = WasteCollection::make($collectionData);
            $this->validateModel($collection);

            // Save collection
            $collectionId = WasteCollectionRepository::createCollection($collection->toArray());
            if ($collectionId === null) {
                throw new Exception("수거 정보 등록에 실패했습니다.", 500);
            }

            // Process items
            $items = json_decode($postData['items'] ?? '[]', true);
            if (empty($items)) {
                throw new Exception("품목 정보가 없습니다.", 400);
            }

            $itemAdded = false;
            foreach ($items as $item) {
                $quantity = intval($item['quantity'] ?? 0);
                if ($quantity > 0) {
                    if (!WasteCollectionRepository::createCollectionItem($collectionId, $item['name'], $quantity)) {
                        throw new Exception("품목 정보 저장에 실패했습니다: " . $item['name'], 500);
                    }
                    $itemAdded = true;
                }
            }

            if (!$itemAdded) {
                throw new Exception("수량이 1개 이상인 품목이 하나 이상 필요합니다.", 400);
            }

            $this->commit();
            return WasteCollectionRepository::findById($collectionId);
            
        } catch (Exception $e) {
            $this->rollback();
            
            // Clean up uploaded file on error
            if (isset($photoPath) && !empty($photoPath) && file_exists(UPLOADS_PATH . $photoPath)) {
                unlink(UPLOADS_PATH . $photoPath);
            }
            
            throw $e;
        }
    }

    /**
     * Process collections by address
     */
    public function processCollectionsByAddress(string $address): bool
    {
        if (empty($address)) {
            throw new Exception("주소가 필요합니다.", 400);
        }
        
        return WasteCollectionRepository::processByAddress($address);
    }

    /**
     * Process collection by ID
     */
    public function processCollectionById(int $id): bool
    {
        if (empty($id)) {
            throw new Exception("ID가 필요합니다.", 400);
        }
        
        return WasteCollectionRepository::processById($id);
    }

    // === Admin Methods ===

    /**
     * Get collections for admin view with filters
     */
    public function getAdminCollections(array $filters): array
    {
        $sanitizedFilters = [];
        foreach ($filters as $key => $value) {
            $sanitizedFilters[$key] = Validator::sanitizeString($value);
        }
        
        return WasteCollectionRepository::findAllForAdmin($sanitizedFilters);
    }

    /**
     * Parse HTML file for batch registration
     */
    public function parseHtmlFile(array $file): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("파일 업로드 오류가 발생했습니다.", 400);
        }

        $htmlContent = file_get_contents($file['tmp_name']);
        if ($htmlContent === false) {
            throw new Exception("파일을 읽을 수 없습니다.", 500);
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $htmlContent);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $rows = $xpath->query("//table[contains(@class, 'tableSt_list')]//tbody//tr");

        $parsedData = [];
        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName('td');
            if ($cells->length < 8) continue;

            $dateNode = $cells->item(7);
            $datePart = trim($dateNode->childNodes->item(0)->textContent);
            $timePart = '00:00';
            
            if ($dateNode->getElementsByTagName('br')->length > 0 && $dateNode->childNodes->item(2)) {
                $timePart = trim(str_replace(['(', ')'], '', $dateNode->childNodes->item(2)->textContent));
            }

            // Extract fee from 7th column (index 6)
            $feeText = trim($cells->item(6)->textContent);
            $fee = (int)preg_replace('/[^0-9]/', '', $feeText);

            $parsedData[] = [
                'receiptNumber' => trim($cells->item(1)->textContent),
                'name' => trim($cells->item(2)->textContent),
                'phone' => trim($cells->item(3)->textContent),
                'address' => trim($cells->item(4)->textContent),
                'fee' => $fee,
                'dischargeDate' => $datePart . ' ' . $timePart . ':00'
            ];
        }

        return $parsedData;
    }

    /**
     * Batch register collections from parsed data
     */
    public function batchRegisterCollections(array $collections, int $adminUserId): array
    {
        $this->beginTransaction();
        
        try {
            $newIds = [];
            $failedCount = 0;
            $duplicateCount = 0;

            foreach ($collections as $collectionData) {
                // Check for duplicates
                if (!empty($collectionData['receiptNumber']) && 
                    WasteCollectionRepository::findByDischargeNumber($collectionData['receiptNumber'])) {
                    $duplicateCount++;
                    continue;
                }

                // Get address info from Kakao API
                $addressInfo = $this->getAddressInfoFromKakao($collectionData['address']);

                $dataToSave = [
                    'latitude' => $addressInfo['latitude'] ?? 0,
                    'longitude' => $addressInfo['longitude'] ?? 0,
                    'address' => $addressInfo['final_address'] ?? $collectionData['address'],
                    'geocoding_status' => $addressInfo ? 'success' : 'failure',
                    'photo_path' => null,
                    'user_id' => $adminUserId,
                    'employee_id' => null,
                    'issue_date' => $collectionData['dischargeDate'],
                    'discharge_number' => $collectionData['receiptNumber'],
                    'submitter_name' => $collectionData['name'],
                    'submitter_phone' => $collectionData['phone'],
                    'fee' => $collectionData['fee'] ?? 0,
                    'type' => 'online'
                ];

                $newId = WasteCollectionRepository::createCollection($dataToSave);
                if ($newId === null) {
                    $failedCount++;
                    error_log("Failed to save collection for receipt number: " . $collectionData['receiptNumber']);
                    continue;
                }
                
                $newIds[] = $newId;
            }

            $this->commit();
            return [
                'count' => count($newIds), 
                'failures' => $failedCount, 
                'duplicates' => $duplicateCount
            ];
            
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Update collection items
     */
    public function updateCollectionItems(int $collectionId, string $itemsJson): bool
    {
        if (empty($collectionId)) {
            throw new Exception("ID가 필요합니다.", 400);
        }

        $items = json_decode($itemsJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("잘못된 품목 데이터 형식입니다.", 400);
        }

        $this->beginTransaction();
        
        try {
            // Delete existing items
            WasteCollectionRepository::deleteItemsByCollectionId($collectionId);

            // Add new items
            if (!empty($items)) {
                foreach ($items as $item) {
                    $itemName = trim($item['name'] ?? '');
                    $quantity = intval($item['quantity'] ?? 0);

                    if (empty($itemName) || $quantity <= 0) {
                        continue; // Skip invalid items
                    }

                    if (!WasteCollectionRepository::createCollectionItem($collectionId, $itemName, $quantity)) {
                        throw new Exception("품목 정보 저장에 실패했습니다: " . $itemName, 500);
                    }
                }
            }

            $this->commit();
            return true;
            
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Update admin memo
     */
    public function updateAdminMemo(int $id, string $memo): bool
    {
        if (empty($id)) {
            throw new Exception("ID가 필요합니다.", 400);
        }
        
        return WasteCollectionRepository::updateAdminMemo($id, $memo);
    }

    /**
     * Clear all online submissions
     */
    public function clearOnlineSubmissions(): bool
    {
        return WasteCollectionRepository::clearOnlineSubmissions();
    }

    /**
     * Get address information from Kakao API
     */
    private function getAddressInfoFromKakao(string $address): ?array
    {
        $apiKey = '42f32b3a748e93c5ac949d79243a526f'; // Replace with actual API key
        
        // Clean address
        $cleanAddress = trim($address);
        $cleanAddress = preg_replace('/\s+/', ' ', $cleanAddress);
        
        $url = "https://dapi.kakao.com/v2/local/search/address.json?query=" . urlencode($cleanAddress);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => ['Authorization: KakaoAK ' . $apiKey],
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Waste Collection System)',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        
        if ($curlError) {
            curl_close($ch);
            error_log("cURL Error on Kakao API call: " . $curlError . " for address: " . $address);
            return null;
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("Kakao API HTTP Error: " . $httpCode . " Response: " . $response . " for address: " . $address);
            return null;
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg() . " Response: " . $response);
            return null;
        }
        
        if (empty($result['documents'])) {
            // Retry with keyword search
            return $this->searchAddressByKeyword($cleanAddress, $apiKey);
        }
        
        $doc = $result['documents'][0];
        
        // Get jibun and road addresses
        $jibunAddress = null;
        $roadAddress = null;
        $adminDong = null;
        
        if (isset($doc['address'])) {
            $jibunAddress = $doc['address']['address_name'] ?? null;
            $adminDong = $doc['address']['region_3depth_name'] ?? null;
        }
        
        if (isset($doc['road_address'])) {
            $roadAddress = $doc['road_address']['address_name'] ?? null;
            if (!$adminDong) {
                $adminDong = $doc['road_address']['region_3depth_name'] ?? null;
            }
        }
        
        return [
            'latitude' => floatval($doc['y']),
            'longitude' => floatval($doc['x']),
            'jibun_address' => $jibunAddress,
            'road_address' => $roadAddress,
            'admin_dong' => $adminDong,
            'final_address' => $roadAddress ?: $jibunAddress ?: $address
        ];
    }

    /**
     * Search address by keyword as fallback
     */
    private function searchAddressByKeyword(string $address, string $apiKey): ?array
    {
        $url = "https://dapi.kakao.com/v2/local/search/keyword.json?query=" . urlencode($address);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => ['Authorization: KakaoAK ' . $apiKey],
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Waste Collection System)',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        
        if ($curlError || $httpCode !== 200) {
            curl_close($ch);
            error_log("Keyword search failed for address: " . $address);
            return null;
        }
        
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if (empty($result['documents'])) {
            error_log("No results found for address: " . $address);
            return null;
        }
        
        $doc = $result['documents'][0];
        
        return [
            'latitude' => floatval($doc['y']),
            'longitude' => floatval($doc['x']),
            'jibun_address' => $doc['address_name'] ?? null,
            'road_address' => $doc['road_address_name'] ?? null,
            'admin_dong' => null,
            'final_address' => $doc['place_name'] ?? $doc['address_name'] ?? $address
        ];
    }
}