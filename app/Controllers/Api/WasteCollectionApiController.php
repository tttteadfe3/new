<?php
namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\WasteCollectionService;
use App\Core\JsonResponse;
use App\Core\SessionManager;
use App\Repositories\UserRepository;
use Exception;

class WasteCollectionApiController extends BaseController
{
    private WasteCollectionService $wasteCollectionService;

    public function __construct()
    {
        $this->wasteCollectionService = new WasteCollectionService();
    }

    /**
     * Get waste collections for user view
     */
    public function index(): void
    {
        if (!SessionManager::has('user')) {
            JsonResponse::forbidden('로그인이 필요합니다.');
            return;
        }

        try {
            $data = $this->wasteCollectionService->getCollections();
            JsonResponse::success($data, '수거 목록 조회 성공');
        } catch (Exception $e) {
            $code = is_int($e->getCode()) && $e->getCode() >= 400 ? $e->getCode() : 500;
            JsonResponse::error($e->getMessage(), 'SERVER_ERROR', $code);
        }
    }

    /**
     * Store new waste collection
     */
    public function store(): void
    {
        if (!SessionManager::has('user')) {
            JsonResponse::forbidden('로그인이 필요합니다.');
            return;
        }

        $userId = SessionManager::get('user')['id'];
        $user = UserRepository::findById($userId);
        if (!$user) {
            JsonResponse::error('사용자 정보를 찾을 수 없습니다.', 'USER_NOT_FOUND', 404);
            return;
        }
        
        $employeeId = $user['employee_id'] ?? null;

        try {
            $result = $this->wasteCollectionService->registerCollection($_POST, $_FILES, $userId, $employeeId);
            JsonResponse::success($result, '폐기물 수거 정보가 성공적으로 등록되었습니다.');
        } catch (Exception $e) {
            $code = is_int($e->getCode()) && $e->getCode() >= 400 ? $e->getCode() : 500;
            JsonResponse::error($e->getMessage(), 'SERVER_ERROR', $code);
        }
    }

    /**
     * Update waste collection
     */
    public function update(): void
    {
        $this->requireAuth('waste_admin');

        try {
            $id = $_POST['id'] ?? null;
            if (!$id) {
                JsonResponse::badRequest('처리할 수거 ID가 필요합니다.');
                return;
            }

            $result = $this->wasteCollectionService->processCollectionById((int)$id);

            if ($result) {
                JsonResponse::success(true, '해당 수거건이 처리 완료되었습니다.');
            } else {
                JsonResponse::error('이미 처리되었거나 존재하지 않는 수거 건입니다.', 'ALREADY_PROCESSED', 409);
            }
        } catch (Exception $e) {
            $code = is_int($e->getCode()) && $e->getCode() >= 400 ? $e->getCode() : 500;
            JsonResponse::error($e->getMessage(), 'SERVER_ERROR', $code);
        }
    }

    /**
     * Delete waste collection
     */
    public function destroy(): void
    {
        $this->requireAuth('waste_admin');
        
        // Implementation would depend on business requirements
        JsonResponse::success(true, '삭제 기능은 현재 구현되지 않았습니다.');
    }

    /**
     * Admin operations for waste collections
     */
    public function admin(): void
    {
        $this->requireAuth('waste_admin');

        $userId = SessionManager::get('user')['id'];
        
        // Handle JSON input
        $input = [];
        if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
            $json = file_get_contents('php://input');
            $input = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                JsonResponse::badRequest('잘못된 JSON 형식입니다.');
                return;
            }
        }

        // Combine sources of input, with JSON body taking precedence
        $requestData = array_merge($_POST, $_FILES, $input);
        $action = $_GET['action'] ?? $requestData['action'] ?? null;

        try {
            switch ($action) {
                case 'get_collections':
                    $collections = $this->wasteCollectionService->getAdminCollections($_GET);
                    JsonResponse::success($collections);
                    break;

                case 'parse_html_file':
                    if (!isset($requestData['htmlFile'])) {
                        JsonResponse::badRequest('HTML 파일이 없습니다.');
                        return;
                    }
                    $result = $this->wasteCollectionService->parseHtmlFile($requestData['htmlFile']);
                    JsonResponse::success($result);
                    break;

                case 'batch_register':
                    $collections = $requestData['collections'] ?? [];
                    if (empty($collections)) {
                        JsonResponse::badRequest('등록할 데이터가 없습니다.');
                        return;
                    }
                    $result = $this->wasteCollectionService->batchRegisterCollections($collections, $userId);
                    JsonResponse::success($result, '데이터가 일괄 등록되었습니다.');
                    break;

                case 'update_items':
                    $id = $requestData['id'] ?? null;
                    $items = $requestData['items'] ?? '[]';
                    $result = $this->wasteCollectionService->updateCollectionItems($id, $items);
                    JsonResponse::success($result, '품목이 저장되었습니다.');
                    break;

                case 'update_memo':
                    $id = $requestData['id'] ?? null;
                    $memo = $requestData['memo'] ?? '';
                    $result = $this->wasteCollectionService->updateAdminMemo($id, $memo);
                    JsonResponse::success($result, '메모가 업데이트되었습니다.');
                    break;

                case 'process_collection':
                    $id = $requestData['id'] ?? null;
                    $result = $this->wasteCollectionService->processCollectionById($id);
                    JsonResponse::success($result, '선택한 항목이 처리되었습니다.');
                    break;

                case 'clear_online_submissions':
                    $result = $this->wasteCollectionService->clearOnlineSubmissions();
                    JsonResponse::success($result, '모든 인터넷 배출 데이터가 삭제되었습니다.');
                    break;

                default:
                    JsonResponse::notFound('요청한 API 엔드포인트를 찾을 수 없습니다.');
                    break;
            }
        } catch (Exception $e) {
            $code = is_int($e->getCode()) && $e->getCode() >= 400 ? $e->getCode() : 500;
            JsonResponse::error($e->getMessage(), 'SERVER_ERROR', $code);
        }
    }
}