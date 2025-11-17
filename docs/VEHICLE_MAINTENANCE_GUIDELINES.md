# 🚗 차량 유지관리 시스템 개발 작업 지침서 (Vehicle Maintenance System Development Guidelines)

## 📋 문서 목적

이 문서는 차량 유지관리 시스템 개발 작업을 AI에게 지시할 때 사용하는 **필수 기본 지침서**입니다. 모든 코드 생성, 수정, 리팩토링 작업 시 이 규칙들을 엄격히 준수해야 합니다.

---

## 1. 파일 생성 및 디렉토리 구조 규칙

### 1.1. 필수 디렉토리 구조
```
app/
├── Controllers/
│   ├── Api/
│   │   └── VehicleController.php       # 차량 관리 API
│   │   └── BreakdownController.php     # 고장 처리 API
│   └── Web/
│       └── VehicleController.php       # 차량 관리 페이지
│       └── BreakdownController.php     # 고장 처리 페이지
├── Services/
│   ├── VehicleService.php              # 차량 관리 비즈니스 로직
│   ├── BreakdownService.php            # 고장 처리 비즈니스 로직
│   └── MaintenanceService.php          # 자체 정비 로직
├── Repositories/
│   ├── VehicleRepository.php           # 차량 데이터베이스 접근
│   ├── BreakdownRepository.php         # 고장 데이터베이스 접근
│   └── MaintenanceRepository.php       # 자체 정비 데이터베이스 접근
├── Models/
│   ├── Vehicle.php                     # 차량 모델
│   ├── Breakdown.php                   # 고장 모델
│   └── Maintenance.php                 # 자체 정비 모델
└── Views/
    ├── pages/
    │   ├── vehicles/
    │   │   └── index.php               # 차량 관리 페이지 뷰
    │   ├── breakdowns/
    │   │   └── index.php               # 고장 관리 페이지 뷰
    │   └── maintenance/
    │       └── index.php               # 자체 정비 페이지 뷰
    └── components/
        └── vehicle-info-card.php       # 차량 정보 카드 (재사용)

public/assets/js/
├── pages/
│   ├── vehicles.js                     # 차량 관리 페이지 스크립트
│   ├── breakdowns.js                   # 고장 관리 페이지 스크립트
│   └── maintenance.js                  # 자체 정비 페이지 스크립트
└── services/
    ├── vehicleApiService.js            # 차량 API 호출 서비스
    └── breakdownApiService.js          # 고장 API 호출 서비스
```

### 1.2. 파일 명명 규칙
- **컨트롤러**: `{기능명}Controller.php` (예: `VehicleController.php`, `BreakdownController.php`)
- **서비스**: `{기능명}Service.php` (예: `VehicleService.php`, `MaintenanceService.php`)
- **리포지토리**: `{기능명}Repository.php` (예: `VehicleRepository.php`, `BreakdownRepository.php`)
- **JavaScript**: `{페이지명}.js` (예: `vehicles.js`, `breakdowns.js`)
- **뷰 파일**: `{기능명}/index.php` (예: `vehicles/index.php`)

### 1.3. 네임스페이스 규칙
```php
// 컨트롤러
namespace App\Controllers\Web;  // 웹 컨트롤러
namespace App\Controllers\Api;  // API 컨트롤러

// 서비스
namespace App\Services;

// 리포지토리
namespace App\Repositories;
```

---

## 2. 상속 구조 필수 준수 사항

### 2.1. PHP 클래스 상속 규칙

#### 컨트롤러 상속
```php
// ✅ 올바른 예시
class VehicleController extends BaseController
{
    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        VehicleService $vehicleService  // 추가 의존성
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger);
        $this->vehicleService = $vehicleService;
    }
}

// ❌ 잘못된 예시 - BaseController 상속 안함
class BadVehicleController
{
    // 상속 없이 직접 구현
}
```

#### 모델 상속
```php
// ✅ 올바른 예시
class Vehicle extends BaseModel
{
    protected array $fillable = [
        'vehicle_number', 'model', 'year', 'department_id', 'status_code'
    ];
    protected array $rules = [
        'vehicle_number' => 'required|string|unique:vehicles',
        'model' => 'required|string|max:100',
        'year' => 'required|integer|min:1900'
    ];
}
```

### 2.2. JavaScript 클래스 상속 규칙

#### 페이지 클래스 상속
```javascript
// ✅ 올바른 예시
class VehiclesPage extends BasePage {
    constructor() {
        super();
        this.state = {
            vehicles: [],
            departments: [],
            currentVehicle: null,
            filters: {}
        };
    }

    initializeApp() {
        this.cacheDOMElements();
        this.setupEventListeners();
        this.loadInitialData();
    }

    setupEventListeners() {
        // 예: 차량 등록 버튼 클릭 이벤트
        $('#add-vehicle-btn').on('click', () => this.handleAddVehicle());
    }

    async loadInitialData() {
        // 예: 부서 및 초기 차량 목록 로드
        await this.loadDepartments();
        await this.loadVehicles();
    }
}

// ✅ 반드시 인스턴스 생성
new VehiclesPage();

// ❌ 잘못된 예시 - BasePage 상속 안함
class BadVehiclesPage {
    // 상속 없이 직접 구현
}
```

---

## 3. 의존성 주입 (DI) 규칙

### 3.1. DI 컨테이너 등록 순서 (절대 변경 금지)

```php
// public/index.php에서 반드시 이 순서로 등록

// ... (기존 핵심 서비스 등록) ...

// 2. DataScopeService (리포지토리보다 먼저)
$container->register(\App\Services\DataScopeService::class, /* ... */);

// 3. 리포지토리 (DataScopeService 의존성 포함)
$container->register(\App\Repositories\VehicleRepository::class, fn($c) => new \App\Repositories\VehicleRepository(
    $c->resolve(Database::class),
    $c->resolve(\App\Services\DataScopeService::class)
));
$container->register(\App\Repositories\BreakdownRepository::class, fn($c) => new \App\Repositories\BreakdownRepository(
    $c->resolve(Database::class),
    $c->resolve(\App\Services\DataScopeService::class)
));

// 4. 애플리케이션 서비스
$container->register(\App\Services\VehicleService::class, fn($c) => new \App\Services\VehicleService(
    $c->resolve(\App\Repositories\VehicleRepository::class)
));
$container->register(\App\Services\BreakdownService::class, fn($c) => new \App\Services\BreakdownService(
    $c->resolve(\App\Repositories\BreakdownRepository::class),
    $c->resolve(\App\Repositories\VehicleRepository::class) // 다른 리포지토리 의존성 예시
));

// 5. 컨트롤러 (마지막)
$container->register(\App\Controllers\Web\VehicleController::class, fn($c) => new \App\Controllers\Web\VehicleController(
    $c->resolve(Request::class),
    $c->resolve(AuthService::class),
    $c->resolve(\App\Services\VehicleService::class)
    // 기타 의존성들...
));
```

### 3.2. 생성자 주입 패턴

```php
// ✅ 올바른 생성자 주입
class BreakdownService
{
    private BreakdownRepository $breakdownRepository;
    private VehicleRepository $vehicleRepository;

    public function __construct(
        BreakdownRepository $breakdownRepository,
        VehicleRepository $vehicleRepository
    ) {
        $this.breakdownRepository = $breakdownRepository;
        $this->vehicleRepository = $vehicleRepository;
    }
}

// ❌ 잘못된 예시 - 직접 인스턴스 생성
class BadVehicleService
{
    public function __construct()
    {
        $this->repository = new VehicleRepository(); // 금지
    }
}
```

---

## 4. 데이터 스코프 (Data Scope) 필수 적용 규칙

### 4.1. 리포지토리에서 데이터 스코프 적용 (필수)

```php
// ✅ 올바른 데이터 스코프 적용
class VehicleRepository
{
    private Database $db;
    private DataScopeService $dataScopeService;

    public function __construct(Database $db, DataScopeService $dataScopeService)
    {
        $this->db = $db;
        $this->dataScopeService = $dataScopeService;
    }

    public function getAll(array $filters = []): array
    {
        $queryParts = [
            'sql' => "SELECT v.*, d.name as department_name FROM vehicles v LEFT JOIN hr_departments d ON v.department_id = d.id",
            'params' => [],
            'where' => []
        ];

        // ✅ 반드시 데이터 스코프 적용
        $queryParts = $this->dataScopeService->applyVehicleScope($queryParts, 'v');

        // 추가 필터 조건들
        if (!empty($filters['status_code'])) {
            $queryParts['where'][] = "v.status_code = ?";
            $queryParts['params'][] = $filters['status_code'];
        }

        // WHERE 절 조합
        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }
}

// ❌ 잘못된 예시 - 데이터 스코프 누락
class BadVehicleRepository
{
    public function getAll(): array
    {
        // 데이터 스코프 적용 없이 모든 데이터 조회 (보안 위험)
        return $this->db->query("SELECT * FROM vehicles");
    }
}
```

### 4.2. 테이블별 스코프 메서드

```php
// 각 테이블에 맞는 스코프 메서드 사용
$queryParts = $this->dataScopeService->applyVehicleScope($queryParts, 'v');         // 차량 테이블
$queryParts = $this->dataScopeService->applyBreakdownScope($queryParts, 'b');       // 고장 테이블
$queryParts = $this->dataScopeService->applyMaintenanceScope($queryParts, 'm');   // 정비 테이블
```

---

## 5. 라우트 및 권한 설정 규칙

### 5.1. 라우트 정의 필수 패턴

```php
// ✅ 올바른 라우트 정의 (routes/web.php)
$router->get('/vehicles', [VehicleController::class, 'index'])
       ->name('vehicles.index')                     // 명명된 라우트
       ->middleware('auth')                         // 인증 필수
       ->middleware('permission', 'vehicle.view');  // 권한 필수

$router->post('/vehicles', [VehicleController::class, 'store'])
       ->name('vehicles.store')
       ->middleware('auth')
       ->middleware('permission', 'vehicle.create');

// ✅ API 라우트 그룹 (routes/api.php)
$router->group('/api', function($router) {
    $router->get('/vehicles', [VehicleApiController::class, 'index'])
           ->middleware('auth')
           ->middleware('permission', 'vehicle.view');

    $router->post('/breakdowns', [BreakdownApiController::class, 'store'])
           ->middleware('auth')
           ->middleware('permission', 'breakdown.create'); // 운전자(driver)도 생성 가능해야 함
});

// ❌ 잘못된 예시 - 미들웨어 누락
$router->get('/vehicles/all', [VehicleController::class, 'showAll']); // 권한 체크 없음 (위험)
```

### 5.2. 권한 명명 규칙

```php
// ✅ 올바른 권한 명명: {리소스}.{행위}
'vehicle.view'          // 차량 조회
'vehicle.create'        // 차량 생성
'vehicle.update'        // 차량 수정
'vehicle.delete'        // 차량 삭제
'breakdown.view'        // 고장 내역 조회
'breakdown.create'      // 고장 내역 생성 (운전자)
'breakdown.manage'      // 고장 내역 관리 (중간관리자)
'maintenance.view'      // 자체 정비 내역 조회
'maintenance.create'    // 자체 정비 내역 생성 (운전자)
'report.view'           // 리포트 조회

// ❌ 잘못된 권한 명명
'view-vehicles'         // 하이픈 사용 금지
'createBreakdown'       // 카멜케이스 금지
'manage_maintenance'    // 언더스코어 금지
```

---

## 6. 뷰 파일 작성 규칙

### 6.1. 뷰 파일 구조 (필수 패턴)

```php
// ✅ 올바른 뷰 파일 구조 (app/Views/pages/vehicles/index.php)
<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">차량 관리</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/dashboard">대시보드</a></li>
                    <li class="breadcrumb-item active">차량 관리</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">차량 목록</h5>
                    <button type="button" class="btn btn-success add-btn" id="add-vehicle-btn">
                        <i class="ri-add-line align-bottom me-1"></i> 신규 등록
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="vehicle-list-container">
                    <!-- JavaScript로 차량 목록 동적 로드 -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 차량 등록/수정 모달 -->
<div class="modal fade" id="vehicle-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="vehicle-modal-title">차량 정보</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="vehicle-form">
                <div class="modal-body">
                    <!-- 차량 정보 폼 필드들 (차량번호, 차종, 모델 등) -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
```

### 6.2. 컨트롤러에서 뷰 렌더링

```php
// ✅ 올바른 뷰 렌더링 (VehicleController에서)
public function index(): void
{
    // 페이지별 CSS/JS 추가
    View::getInstance()->addCss(BASE_ASSETS_URL . '/libs/choices.js/public/assets/styles/choices.min.css');
    View::getInstance()->addJs(BASE_ASSETS_URL . '/libs/choices.js/public/assets/scripts/choices.min.js');
    View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/vehicles.js');

    // 뷰 렌더링 (레이아웃 포함)
    echo $this->render('pages/vehicles/index', [
        'pageTitle' => '차량 관리'
    ], 'layouts/app');
}

// ❌ 잘못된 예시
public function index(): void
{
    // JavaScript를 뷰 파일에 직접 포함하면 안됨
    echo $this->render('pages/vehicles/index', [
        'script' => '<script>new VehiclesPage();</script>'  // 금지
    ], 'layouts/app');
}
```

---

## 7. JavaScript 개발 규칙

### 7.1. API 호출 규칙

```javascript
// ✅ 올바른 API 호출
class VehiclesPage extends BasePage {
    async loadVehicles() {
        try {
            // BasePage의 apiCall 메서드 사용 필수
            const response = await this.apiCall('/api/vehicles');
            this.state.vehicles = response.data;
            this.renderVehicleList(this.state.vehicles);
        } catch (error) {
            Toast.error('차량 목록 로딩 실패');
            console.error('Load vehicles error:', error);
        }
    }

    async saveVehicle(vehicleData) {
        try {
            const endpoint = vehicleData.id ? `/api/vehicles/${vehicleData.id}` : '/api/vehicles';
            const method = vehicleData.id ? 'PUT' : 'POST';

            const response = await this.apiCall(endpoint, {
                method: method,
                body: JSON.stringify(vehicleData)
            });
            Toast.success('차량 정보가 저장되었습니다.');
            return response.data;
        } catch (error) {
            Toast.error('차량 정보 저장 실패');
            throw error;
        }
    }
}

// ❌ 잘못된 예시 - 직접 fetch 사용
async loadData() {
    const response = await fetch('/api/vehicles'); // 금지
}
```

### 7.2. XSS 방지 규칙

```javascript
// ✅ 올바른 HTML 출력 (XSS 방지)
renderVehicleList(vehicles) {
    const listContainer = $('#vehicle-list-container');
    if (vehicles.length === 0) {
        listContainer.html('<p class="text-center text-muted">표시할 차량이 없습니다.</p>');
        return;
    }

    const html = vehicles.map(vehicle => `
        <div class="vehicle-item" data-id="${this.sanitizeHTML(vehicle.id)}">
            <h5>${this.sanitizeHTML(vehicle.model)} (${this.sanitizeHTML(vehicle.vehicle_number)})</h5>
            <p>부서: ${this.sanitizeHTML(vehicle.department_name || '미배정')}</p>
            <span class="badge bg-success">${this.sanitizeHTML(vehicle.status_name)}</span>
        </div>
    `).join('');

    listContainer.html(html);
}

// ❌ 잘못된 예시 - 직접 HTML 삽입 (XSS 위험)
renderBadList(vehicles) {
    const html = vehicles.map(v => `<div><h5>${v.model}</h5></div>`).join(''); // XSS 위험
    $('#vehicle-list-container').html(html);
}
```

---

## 8. 데이터베이스 관련 규칙

### 8.1. 테이블 생성 규칙

```sql
-- ✅ 올바른 테이블 생성 (vehicles)
CREATE TABLE vehicles (
    id INT(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
    vehicle_number VARCHAR(255) NOT NULL COMMENT '차량번호',
    model VARCHAR(255) NOT NULL COMMENT '차종/모델',
    year YEAR DEFAULT NULL COMMENT '연식',
    department_id INT(11) DEFAULT NULL COMMENT '배정 부서 ID',
    status_code VARCHAR(50) NOT NULL DEFAULT 'NORMAL' COMMENT '차량 상태 코드',
    scan_registration_path VARCHAR(255) DEFAULT NULL COMMENT '등록증 스캔 파일 경로',
    scan_insurance_path VARCHAR(255) DEFAULT NULL COMMENT '보험증서 스캔 파일 경로',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',

    PRIMARY KEY (id),
    UNIQUE KEY uq_vehicle_number (vehicle_number),
    KEY idx_department (department_id),
    KEY idx_status_code (status_code),

    CONSTRAINT fk_vehicle_department FOREIGN KEY (department_id) REFERENCES hr_departments (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='차량 기본 정보';
```

### 8.2. SQL 쿼리 작성 규칙

```php
// ✅ 올바른 SQL 쿼리 (준비된 문장 사용)
class VehicleRepository
{
    // ... constructor ...

    public function findById(int $vehicleId): ?array
    {
        $sql = "SELECT v.*, d.name as department_name
                FROM vehicles v
                LEFT JOIN hr_departments d ON v.department_id = d.id
                WHERE v.id = ?";

        $result = $this->db->query($sql, [$vehicleId]);
        return $result[0] ?? null;
    }
}


// ❌ 잘못된 예시 - SQL 인젝션 위험
class BadVehicleRepository
{
    public function findById($vehicleId): ?array
    {
        $sql = "SELECT * FROM vehicles WHERE id = $vehicleId"; // 위험
        return $this->db->query($sql);
    }
}
```

---

## 9. 에러 처리 및 로깅 규칙

### 9.1. PHP 예외 처리

```php
// ✅ 올바른 예외 처리
class BreakdownService
{
    // ... constructor ...

    public function registerBreakdown(array $data, array $files): int
    {
        try {
            // 데이터 검증
            $breakdown = Breakdown::make($data);
            if (!$breakdown->validate()) {
                throw new InvalidArgumentException('유효하지 않은 고장 데이터: ' . implode(', ', $breakdown->getErrors()));
            }

            // 트랜잭션 시작
            $this->db->beginTransaction();

            // 파일 업로드 처리 (예시)
            if (!empty($files['photo'])) {
                $data['photo_path'] = $this->fileUploader->upload($files['photo'], 'breakdowns');
            }

            $breakdownId = $this->breakdownRepository->save($data);
            $this->activityLogger->log('breakdown_registered', $breakdownId, ['vehicle_id' => $data['vehicle_id']]);

            $this->db->commit();
            return $breakdownId;

        } catch (InvalidArgumentException $e) {
            $this->db->rollback();
            throw $e; // 사용자에게 표시할 유효성 검사 메시지
        } catch (Exception $e) {
            $this->db->rollback();
            // 에러 로깅
            error_log('Breakdown registration failed: ' . $e->getMessage());
            throw new RuntimeException('고장 등록 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.');
        }
    }
}
```

### 9.2. JavaScript 에러 처리

```javascript
// ✅ 올바른 에러 처리
class BreakdownsPage extends BasePage {
    async handleFormSubmit(formData) {
        this.setButtonLoading('#submit-breakdown-btn', '제출 중...');

        try {
            // FormData를 사용해 파일과 함께 전송
            const response = await this.apiCall('/api/breakdowns', {
                method: 'POST',
                body: formData // FormData 객체 직접 전달
            });

            Toast.success('고장 신고가 성공적으로 등록되었습니다.');
            this.closeModalAndResetForm('#breakdown-modal');

        } catch (error) {
            console.error('Breakdown submission error:', error);

            if (error.response?.data?.error) {
                Toast.error(error.response.data.error);
            } else {
                Toast.error('고장 신고 등록 중 오류가 발생했습니다.');
            }
        } finally {
            this.resetButtonLoading('#submit-breakdown-btn', '제출');
        }
    }
}
```

---

## 10. 성능 및 보안 규칙

### 10.1. 데이터베이스 쿼리 최적화

```php
// ✅ 올바른 쿼리 최적화 (페이징 및 인덱스 활용)
public function getVehiclesWithPagination(array $filters = [], int $page = 1, int $perPage = 20): array
{
    $offset = ($page - 1) * $perPage;

    $queryParts = [
        'sql' => "SELECT v.*, d.name as department_name, COUNT(*) OVER() as total_count
                  FROM vehicles v
                  LEFT JOIN hr_departments d ON v.department_id = d.id",
        'params' => [],
        'where' => []
    ];

    // 데이터 스코프 적용
    $queryParts = $this->dataScopeService->applyVehicleScope($queryParts, 'v');

    // 필터 조건 추가 (예: 차량 상태)
    if (!empty($filters['status_code'])) {
        $queryParts['where'][] = "v.status_code = ?";
        $queryParts['params'][] = $filters['status_code'];
    }

    if (!empty($queryParts['where'])) {
        $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
    }

    // 정렬 및 페이징
    $queryParts['sql'] .= " ORDER BY v.created_at DESC LIMIT ? OFFSET ?";
    $queryParts['params'][] = $perPage;
    $queryParts['params'][] = $offset;

    return $this->db->query($queryParts['sql'], $queryParts['params']);
}

// ❌ 잘못된 예시 - 모든 데이터 조회 후 PHP에서 필터링
public function getAllVehicles(): array
{
    $allVehicles = $this->db->query("SELECT * FROM vehicles"); // 비효율적
    // ... PHP에서 필터링 및 페이징 ...
    return $filteredVehicles;
}
```

### 10.2. 입력값 검증 (Security)

```php
// ✅ 올바른 입력값 검증
public function storeVehicle(): string
{
    $data = $this->request->all();

    // 1. 화이트리스트 방식으로 허용된 필드만 추출
    $allowedFields = ['vehicle_number', 'model', 'year', 'department_id', 'status_code'];
    $filteredData = array_intersect_key($data, array_flip($allowedFields));

    // 2. 데이터 유효성 검증 (모델 사용)
    $vehicle = Vehicle::make($filteredData);
    if (!$vehicle->validate()) {
        return $this->jsonResponse([
            'error' => '입력값이 올바르지 않습니다.',
            'details' => $vehicle->getErrors()
        ], 400);
    }

    // 3. 서비스 레이어로 전달
    try {
        $result = $this->vehicleService->createVehicle($filteredData);
        return $this->jsonResponse(['success' => true, 'data' => $result]);
    } catch (Exception $e) {
        return $this->jsonResponse(['error' => $e->getMessage()], 500);
    }
}
```

### 10.3. CSRF 보호

```php
// ✅ 폼에 CSRF 토큰 포함
<form id="vehicle-form">
    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
    <!-- 차량 정보 폼 필드들 -->
</form>
```

---

## 11. 코드 검증 체크리스트

### 새 기능 개발 시 반드시 확인할 사항

#### PHP 백엔드
- [ ] `VehicleController`, `BreakdownController` 등 모든 컨트롤러가 `BaseController`를 상속하는가?
- [ ] `Vehicle`, `Breakdown` 등 모든 모델이 `BaseModel`을 상속하는가?
- [ ] `VehicleRepository`, `BreakdownService` 등 새로운 클래스가 DI 컨테이너에 올바른 순서로 등록되었는가?
- [ ] 차량, 고장, 정비 관련 모든 라우트에 `auth` 및 `permission` 미들웨어가 적절히 설정되었는가?
- [ ] `VehicleRepository` 등 모든 리포지토리의 조회 메서드에 `DataScopeService`가 적용되었는가?
- [ ] SQL 인젝션 방지를 위해 모든 DB 쿼리가 준비된 문장을 사용하고 있는가?
- [ ] 고장 등록, 수리 완료 등 중요한 데이터 변경 작업에 DB 트랜잭션이 적용되었는가?

#### 뷰 파일 (`.php`)
- [ ] `startSection('content')`로 시작하고 `endSection()`으로 끝나는가?
- [ ] `<div class="container-fluid">`를 직접 사용하지 않았는가?
- [ ] `<script>` 태그를 뷰 파일에 직접 작성하지 않고, 컨트롤러에서 `View::getInstance()->addJs()`로 추가했는가?
- [ ] 차량 등록/수정, 고장 접수 등 모든 폼에 CSRF 토큰이 포함되어 있는가?

#### JavaScript 프론트엔드 (`.js`)
- [ ] `VehiclesPage`, `BreakdownsPage` 등 모든 페이지 클래스가 `BasePage`를 상속하는가?
- [ ] 모든 API 요청에 `this.apiCall()`을 사용하고, 직접 `fetch()`를 사용하지 않았는가?
- [ ] 차량 번호, 고장 내용 등 동적 데이터를 HTML에 삽입할 때 `this.sanitizeHTML()`을 사용했는가?
- [ ] API 호출 실패 시 `try...catch` 블록과 `Toast`를 이용해 사용자에게 적절한 피드백을 제공하는가?

#### 데이터베이스
- [ ] 테이블명이 `vehicles`, `breakdowns` 등 복수형 snake_case를 따르는가?
- [ ] 모든 테이블에 `id`, `created_at`, `updated_at` 컬럼이 포함되어 있는가?
- [ ] `department_id`, `status_code` 등 자주 조회되는 컬럼에 인덱스가 설정되었는가?
- [ ] 외래키 제약조건(`CONSTRAINT`)이 올바르게 설정되어 있는가?

---

## 📝 결론

이 지침서의 모든 규칙은 **필수 사항**입니다. AI가 차량 유지관리 시스템의 코드를 생성하거나 수정할 때는 반드시 이 규칙들을 준수해야 합니다. 특히 자주 발생하는 오류 부분(상속, DI 주입, 데이터 스코프, 권한 설정, 파일 구조)에 대해서는 더욱 세심한 주의를 기울여야 합니다.

**모든 코드 생성 작업 전에 이 문서를 참조하고, 완료 후에는 체크리스트를 통해 검증하시기 바랍니다.**
