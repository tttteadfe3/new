<?php
namespace App\Services;

use App\Repositories\WasteCollectionRepository;
use App\Models\WasteCollection;
use App\Core\Database;
use App\Core\FileUploader;
use App\Core\Validator;
use Exception;

/**
 * 폐기물 수거 신고와 관련된 모든 비즈니스 로직을 처리하기 위한 통합 서비스입니다.
 */
class WasteCollectionService
{
    private WasteCollectionRepository $wasteCollectionRepository;
    private Database $db;

    public function __construct(WasteCollectionRepository $wasteCollectionRepository, Database $db)
    {
        $this->wasteCollectionRepository = $wasteCollectionRepository;
        $this->db = $db;
    }

    /**
     * 사용자 보기를 위한 모든 폐기물 수거 내역을 가져옵니다.
     * @return array
     */
    public function getCollections(): array
    {
        return $this->wasteCollectionRepository->findAllWithItems();
    }

    /**
     * ID로 폐기물 수거 내역을 가져옵니다.
     * @param int $id
     * @return array|null
     * @throws Exception
     */
    public function getCollectionById(int $id): ?array
    {
        if (empty($id)) {
            throw new Exception("ID가 필요합니다.", 400);
        }
        
        return $this->wasteCollectionRepository->findById($id);
    }

    /**
     * 현장에서 새로운 폐기물 수거 내역을 등록합니다.
     * @param array $postData
     * @param array $files
     * @param int|null $employeeId
     * @return array
     * @throws Exception
     */
    public function registerCollection(array $postData, array $files, ?int $employeeId): array
    {
        $this->db->beginTransaction();
        
        try {
            // 사진 업로드 처리
            $photoPath = null;
            if (isset($files['photo']) && $files['photo']['error'] === UPLOAD_ERR_OK) {
                $photoPath = FileUploader::validateAndUpload($files['photo'], 'waste', 'coll_');
            }

            // 유효성 검사를 위한 폐기물 수거 모델 생성
            $collectionData = [
                'latitude' => floatval($postData['lat']),
                'longitude' => floatval($postData['lng']),
                'address' => $postData['address'] ?? '',
                'photo_path' => $photoPath,
                'created_by' => $employeeId,
                'issue_date' => date('Y-m-d H:i:s'),
                'type' => 'field'
            ];

            $collection = WasteCollection::make($collectionData);

            // 이제 BaseModel과 일치하는 모델 자체의 유효성 검사 메서드를 사용합니다.
            if (!$collection->validate()) {
                // 컨트롤러가 잡을 수 있도록 유효성 검사 오류와 함께 예외를 발생시킵니다.
                throw new Exception(implode(', ', $collection->getErrors()), 400);
            }

            // 수거 내역 저장
            $collectionId = $this->wasteCollectionRepository->createCollection($collection->toArray());
            if ($collectionId === null) {
                throw new Exception("수거 정보 등록에 실패했습니다.", 500);
            }

            // 품목 처리
            $items = json_decode($postData['items'] ?? '[]', true);
            if (empty($items)) {
                throw new Exception("품목 정보가 없습니다.", 400);
            }

            $itemAdded = false;
            foreach ($items as $item) {
                $quantity = intval($item['quantity'] ?? 0);
                if ($quantity > 0) {
                    if (!$this->wasteCollectionRepository->createCollectionItem($collectionId, $item['name'], $quantity)) {
                        throw new Exception("품목 정보 저장에 실패했습니다: " . $item['name'], 500);
                    }
                    $itemAdded = true;
                }
            }

            if (!$itemAdded) {
                throw new Exception("수량이 1개 이상인 품목이 하나 이상 필요합니다.", 400);
            }

            $this->db->commit();
            return $this->wasteCollectionRepository->findById($collectionId);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            
            // 오류 시 업로드된 파일 정리
            if (isset($photoPath) && !empty($photoPath)) {
                // $photoPath는 '/uploads/waste/photo.jpg'와 같은 URL입니다.
                // UPLOAD_DIR를 사용하여 전체 파일 시스템 경로로 변환해야 합니다.
                $prefix = UPLOAD_URL_PATH . '/';
                if (strpos($photoPath, $prefix) === 0) {
                    $relativeFilePath = substr($photoPath, strlen($prefix));
                    $fullPath = UPLOAD_DIR . $relativeFilePath;
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                }
            }
            
            throw $e;
        }
    }

    /**
     * 주소로 수거 내역을 처리합니다.
     * @param string $address
     * @param int $employeeId
     * @return bool
     * @throws Exception
     */
    public function processCollectionsByAddress(string $address, int $employeeId): bool
    {
        if (empty($address)) {
            throw new Exception("주소가 필요합니다.", 400);
        }
        
        return $this->wasteCollectionRepository->processByAddress($address, $employeeId);
    }

    /**
     * ID로 수거 내역을 처리합니다.
     * @param int $id
     * @param int $employeeId
     * @return bool
     * @throws Exception
     */
    public function processCollectionById(int $id, int $employeeId): bool
    {
        if (empty($id)) {
            throw new Exception("ID가 필요합니다.", 400);
        }
        
        return $this->wasteCollectionRepository->processById($id, $employeeId);
    }

    // === 관리자 메서드 ===

    /**
     * 필터가 있는 관리자 보기의 수거 내역을 가져옵니다.
     * @param array $filters
     * @return array
     */
    public function getAdminCollections(array $filters): array
    {
        $sanitizedFilters = [];
        foreach ($filters as $key => $value) {
            $sanitizedFilters[$key] = Validator::sanitizeString($value);
        }
        
        return $this->wasteCollectionRepository->findAllForAdmin($sanitizedFilters);
    }

    /**
     * 일괄 등록을 위해 HTML 파일을 구문 분석합니다.
     * @param array $file
     * @return array
     * @throws Exception
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

            // 7번째 열(인덱스 6)에서 수수료 추출
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
     * 구문 분석된 데이터에서 수거 내역을 일괄 등록합니다.
     * @param array $collections
     * @param int $adminUserId
     * @return array
     * @throws Exception
     */
    public function batchRegisterCollections(array $collections, int $adminUserId): array
    {
        $this->db->beginTransaction();
        
        try {
            $newIds = [];
            $failedCount = 0;
            $duplicateCount = 0;

            foreach ($collections as $collectionData) {
                // 3주 기간 제한 확인
                $dischargeDate = new \DateTime($collectionData['dischargeDate']);
                $threeWeeksAgo = new \DateTime('-3 weeks');
                if ($dischargeDate < $threeWeeksAgo) {
                    continue;
                }

                // 중복 확인
                if (!empty($collectionData['receiptNumber']) && 
                    $this->wasteCollectionRepository->findByDischargeNumber($collectionData['receiptNumber'])) {
                    $duplicateCount++;
                    continue;
                }

                // 카카오 API에서 주소 정보 가져오기
                $addressInfo = $this->getAddressInfoFromKakao($collectionData['address']);

                $dataToSave = [
                    'latitude' => $addressInfo['latitude'] ?? 0,
                    'longitude' => $addressInfo['longitude'] ?? 0,
                    'address' => $addressInfo['final_address'] ?? $collectionData['address'],
                    'geocoding_status' => $addressInfo ? '성공' : '실패',
                    'photo_path' => null,
                    'created_by' => $adminUserId,
                    'issue_date' => $collectionData['dischargeDate'],
                    'discharge_number' => $collectionData['receiptNumber'],
                    'submitter_name' => $collectionData['name'],
                    'submitter_phone' => $collectionData['phone'],
                    'fee' => $collectionData['fee'] ?? 0,
                    'type' => 'online'
                ];

                $newId = $this->wasteCollectionRepository->createCollection($dataToSave);
                if ($newId === null) {
                    $failedCount++;
                    error_log("Failed to save collection for receipt number: " . $collectionData['receiptNumber']);
                    continue;
                }
                
                $newIds[] = $newId;
            }

            $this->db->commit();
            return [
                'count' => count($newIds), 
                'failures' => $failedCount, 
                'duplicates' => $duplicateCount
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * 수거 품목을 업데이트합니다.
     * @param int $collectionId
     * @param string $itemsJson
     * @return bool
     * @throws Exception
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

        $this->db->beginTransaction();
        
        try {
            // 기존 품목 삭제
            $this->wasteCollectionRepository->deleteItemsByCollectionId($collectionId);

            // 새 품목 추가
            if (!empty($items)) {
                foreach ($items as $item) {
                    $itemName = trim($item['name'] ?? '');
                    $quantity = intval($item['quantity'] ?? 0);

                    if (empty($itemName) || $quantity <= 0) {
                        continue; // 잘못된 품목 건너뛰기
                    }

                    if (!$this->wasteCollectionRepository->createCollectionItem($collectionId, $itemName, $quantity)) {
                        throw new Exception("품목 정보 저장에 실패했습니다: " . $item['name'], 500);
                    }
                }
            }

            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * 관리자 메모를 업데이트합니다.
     * @param int $id
     * @param string $memo
     * @param int $employeeId
     * @return bool
     * @throws Exception
     */
    public function updateAdminMemo(int $id, string $memo, int $employeeId): bool
    {
        if (empty($id)) {
            throw new Exception("ID가 필요합니다.", 400);
        }
        
        return $this->wasteCollectionRepository->updateAdminMemo($id, $memo, $employeeId);
    }

    /**
     * 모든 온라인 제출을 지웁니다.
     * @return bool
     */
    public function clearOnlineSubmissions(): bool
    {
        return $this->wasteCollectionRepository->clearOnlineSubmissions();
    }

    /**
     * 카카오 API에서 주소 정보를 가져옵니다.
     * @param string $address
     * @return array|null
     */
    private function getAddressInfoFromKakao(string $address): ?array
    {
        $apiKey = '42f32b3a748e93c5ac949d79243a526f'; // 실제 API 키로 교체
        
        // 주소 정리
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
            // 키워드 검색으로 재시도
            return $this->searchAddressByKeyword($cleanAddress, $apiKey);
        }
        
        $doc = $result['documents'][0];
        
        // 지번 및 도로명 주소 가져오기
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
     * 대체 수단으로 키워드로 주소 검색
     * @param string $address
     * @param string $apiKey
     * @return array|null
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
