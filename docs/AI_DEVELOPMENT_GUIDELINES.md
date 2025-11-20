# AI 개발 작업 지침서 (AI Development Guidelines)

## 📋 문서 목적

이 문서는 AI에게 개발 작업을 지시할 때 사용하는 **필수 기본 지침서**입니다. 모든 코드 생성, 수정, 리팩토링 작업 시 이 규칙들을 엄격히 준수해야 합니다.

---

## 🚨 중요: 자주 발생하는 오류 방지 규칙

### ❌ 절대 하지 말아야 할 것들

1. **상속 구조 무시**: `BaseController`, `BasePage` 등 기존 상속 구조를 무시하고 새로운 클래스 생성
2. **DI 주입 순서 무시**: 의존성 주입 컨테이너 등록 순서를 임의로 변경
3. **데이터 스코프 누락**: 리포지토리에서 `DataScopeService` 적용 누락
4. **권한 미들웨어 누락**: 라우트에서 `auth`, `permission` 미들웨어 누락
5. **잘못된 디렉토리 구조**: 기존 디렉토리 구조와 다른 위치에 파일 생성

---

## 1. 파일 생성 및 디렉토리 구조 규칙

### 1.1. 필수 디렉토리 구조
```
app/
├── Controllers/
│   ├── Api/           # API 컨트롤러만
│   └── Web/           # 웹 페이지 컨트롤러만
├── Services/          # 비즈니스 로직
├── Repositories/      # 데이터베이스 접근
├── Models/            # 데이터 모델
├── Middleware/        # 미들웨어
└── Views/             # HTML 템플릿
    ├── layouts/       # 레이아웃 파일
    ├── pages/         # 페이지별 뷰
    └── components/    # 재사용 컴포넌트

public/assets/js/
├── core/              # 핵심 클래스 (BasePage 등)
├── pages/             # 페이지별 JavaScript
├── utils/             # 유틸리티 함수
└── services/          # API 서비스
```

### 1.2. 파일 명명 규칙
- **컨트롤러**: `{기능명}Controller.php` (예: `EmployeeController.php`)
- **서비스**: `{기능명}Service.php` (예: `EmployeeService.php`)
- **리포지토리**: `{기능명}Repository.php` (예: `EmployeeRepository.php`)
- **JavaScript**: `{페이지명}.js` (예: `employees.js`)
- **뷰 파일**: `{페이지명}.php` (예: `employees.php`)

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
class EmployeeController extends BaseController
{
    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeService $employeeService  // 추가 의존성
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger);
        $this->employeeService = $employeeService;
    }
}

// ❌ 잘못된 예시 - BaseController 상속 안함
class BadController
{
    // 상속 없이 직접 구현
}
```

#### 모델 상속
```php
// ✅ 올바른 예시
class Employee extends BaseModel
{
    protected array $fillable = ['name', 'email', 'department_id'];
    protected array $rules = [
        'name' => 'required|string|max:100',
        'email' => 'required|email'
    ];
}
```

### 2.2. JavaScript 클래스 상속 규칙

#### 페이지 클래스 상속
```javascript
// ✅ 올바른 예시
class EmployeesPage extends BasePage {
    constructor() {
        super();
        this.state = {
            employees: [],
            departments: [],
            currentEmployee: null
        };
    }

    initializeApp() {
        this.cacheDOMElements();
        this.setupEventListeners();
        this.loadInitialData();
    }

    setupEventListeners() {
        // 이벤트 리스너 설정
    }

    async loadInitialData() {
        // 초기 데이터 로드
    }
}

// ✅ 반드시 인스턴스 생성
new EmployeesPage();

// ❌ 잘못된 예시 - BasePage 상속 안함
class BadPage {
    // 상속 없이 직접 구현
}
```

---

## 3. 의존성 주입 (DI) 규칙

### 3.1. DI 컨테이너 등록 순서 (절대 변경 금지)

```php
// public/index.php에서 반드시 이 순서로 등록

// 1. 핵심 서비스 (DB, Session 등)
$container->singleton(Database::class, fn() => new Database());
$container->singleton(SessionManager::class, fn() => new SessionManager());

// 2. DataScopeService (리포지토리보다 먼저)
$container->register(\App\Services\DataScopeService::class, fn($c) => new \App\Services\DataScopeService(
    $c->resolve(SessionManager::class),
    $c->resolve(Database::class)
));

// 3. 리포지토리 (DataScopeService 의존성 포함)
$container->register(\App\Repositories\EmployeeRepository::class, fn($c) => new \App\Repositories\EmployeeRepository(
    $c->resolve(Database::class),
    $c->resolve(\App\Services\DataScopeService::class)
));

// 4. 애플리케이션 서비스
$container->register(\App\Services\EmployeeService::class, fn($c) => new \App\Services\EmployeeService(
    $c->resolve(\App\Repositories\EmployeeRepository::class)
    // 기타 의존성들...
));

// 5. 컨트롤러 (마지막)
$container->register(\App\Controllers\Web\EmployeeController::class, fn($c) => new \App\Controllers\Web\EmployeeController(
    $c->resolve(Request::class),
    $c->resolve(AuthService::class),
    // 기타 의존성들...
));
```

### 3.2. 생성자 주입 패턴

```php
// ✅ 올바른 생성자 주입
class EmployeeService
{
    private EmployeeRepository $employeeRepository;
    private DepartmentRepository $departmentRepository;

    public function __construct(
        EmployeeRepository $employeeRepository,
        DepartmentRepository $departmentRepository
    ) {
        $this->employeeRepository = $employeeRepository;
        $this->departmentRepository = $departmentRepository;
    }
}

// ❌ 잘못된 예시 - 직접 인스턴스 생성
class BadService
{
    public function __construct()
    {
        $this->repository = new EmployeeRepository(); // 금지
    }
}
```

---

## 4. 데이터 스코프 (Data Scope) 필수 적용 규칙

### 4.1. 리포지토리에서 데이터 스코프 적용 (필수)

```php
// ✅ 올바른 데이터 스코프 적용
class EmployeeRepository
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
            'sql' => "SELECT e.*, d.name as department_name FROM hr_employees e LEFT JOIN hr_departments d ON e.department_id = d.id",
            'params' => [],
            'where' => []
        ];

        // ✅ 반드시 데이터 스코프 적용
        $queryParts = $this->dataScopeService->applyEmployeeScope($queryParts, 'e');

        // 추가 필터 조건들
        if (!empty($filters['status'])) {
            $queryParts['where'][] = "e.status = ?";
            $queryParts['params'][] = $filters['status'];
        }

        // WHERE 절 조합
        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }

        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }
}

// ❌ 잘못된 예시 - 데이터 스코프 누락
class BadRepository
{
    public function getAll(): array
    {
        // 데이터 스코프 적용 없이 모든 데이터 조회 (보안 위험)
        return $this->db->query("SELECT * FROM hr_employees");
    }
}
```

### 4.2. 테이블별 스코프 메서드

```php
// 각 테이블에 맞는 스코프 메서드 사용
$queryParts = $this->dataScopeService->applyEmployeeScope($queryParts, 'e');      // 직원 테이블
$queryParts = $this->dataScopeService->applyDepartmentScope($queryParts, 'd');    // 부서 테이블
$queryParts = $this->dataScopeService->applyUserScope($queryParts, 'u');          // 사용자 테이블
$queryParts = $this->dataScopeService->applyHolidayScope($queryParts, 'h');       // 휴일 테이블
```

---

## 5. 라우트 및 권한 설정 규칙

### 5.1. 라우트 정의 필수 패턴

```php
// ✅ 올바른 라우트 정의 (routes/web.php 또는 routes/api.php)
$router->get('/employees', [EmployeeController::class, 'index'])
       ->name('employees.index')                    // 명명된 라우트
       ->middleware('auth')                         // 인증 필수
       ->middleware('permission', 'employee.view'); // 권한 필수

$router->post('/employees', [EmployeeController::class, 'store'])
       ->name('employees.store')
       ->middleware('auth')
       ->middleware('permission', 'employee.create');

// API 라우트 그룹
$router->group('/api', function($router) {
    $router->get('/employees', [EmployeeApiController::class, 'index'])
           ->middleware('auth')
           ->middleware('permission', 'employee.view');
});

// ❌ 잘못된 예시 - 미들웨어 누락
$router->get('/admin/users', [AdminController::class, 'users']); // 권한 체크 없음 (위험)
```

### 5.2. 권한 명명 규칙

```php
// ✅ 올바른 권한 명명: {리소스}.{행위}
'employee.view'      // 직원 조회
'employee.create'    // 직원 생성
'employee.update'    // 직원 수정
'employee.delete'    // 직원 삭제
'user.manage'        // 사용자 관리
'organization.view'  // 조직도 조회

// ❌ 잘못된 권한 명명
'view-employee'      // 하이픈 사용 금지
'createUser'         // 카멜케이스 금지
'manage_organization' // 언더스코어 금지
```

---

## 6. 뷰 파일 작성 규칙

### 6.1. 뷰 파일 구조 (필수 패턴)

```php
// ✅ 올바른 뷰 파일 구조 (app/Views/pages/employees/index.php)
<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">직원 관리</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/dashboard">대시보드</a></li>
                    <li class="breadcrumb-item active">직원 관리</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <!-- 직원 목록 -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">직원 목록</h5>
                    <button type="button" class="btn btn-success add-btn" id="add-employee-btn">
                        <i class="ri-add-line align-bottom me-1"></i> 신규 등록
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="employee-list-container">
                    <!-- JavaScript로 동적 로드 -->
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <!-- 직원 상세/편집 -->
        <div class="card">
            <div class="card-body">
                <div id="employee-details-container">
                    <div class="text-center p-5">
                        <i class="bi bi-person-circle fs-1 text-muted"></i>
                        <p class="mt-3 text-muted">왼쪽 목록에서 직원을 선택하거나 '신규 등록' 버튼을 클릭하여 시작하세요.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 모달이 필요한 경우 -->
<div class="modal fade" id="employee-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="employee-modal-title">직원 정보</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="employee-form">
                <div class="modal-body">
                    <!-- 폼 필드들 -->
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

// ❌ 잘못된 예시들
// 1. container-fluid 직접 사용 (레이아웃에서 이미 제공됨)
<div class="container-fluid">  <!-- 금지 -->

// 2. startSection/endSection 누락
<div class="row">  <!-- startSection 없이 시작하면 안됨 -->

// 3. JavaScript를 뷰 파일에 직접 포함
<script>
    new EmployeesPage();  <!-- 뷰 파일에 직접 스크립트 금지 -->
</script>
```

### 6.2. View 처리 프로세스 및 규칙

#### View 클래스 사용 패턴
```php
// ✅ 필수 패턴 1: 콘텐츠 섹션 정의
<?php \App\Core\View::getInstance()->startSection('content'); ?>
<!-- 페이지 내용 -->
<?php \App\Core\View::getInstance()->endSection(); ?>

// ✅ 필수 패턴 2: CSS 추가 (컨트롤러에서)
\App\Core\View::getInstance()->addCss('https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css');

// ✅ 필수 패턴 3: JavaScript 추가 (컨트롤러에서)
\App\Core\View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/employees.js');
```

#### 뷰 파일 위치 규칙
```
app/Views/
├── layouts/
│   ├── app.php           # 메인 레이아웃 (container-fluid 포함)
│   ├── auth.php          # 인증 레이아웃
│   ├── header.php        # 헤더 컴포넌트
│   ├── sidebar.php       # 사이드바 컴포넌트
│   └── footer.php        # 푸터 컴포넌트
├── pages/
│   ├── employees/
│   │   └── index.php     # 직원 관리 페이지
│   ├── admin/
│   │   ├── organization.php  # 부서/직급 관리
│   │   ├── users.php         # 사용자 관리
│   │   └── roles.php         # 역할 관리
│   └── auth/
│       └── login.php     # 로그인 페이지
├── errors/
│   ├── 404.php           # 404 에러 페이지
│   └── 500.php           # 500 에러 페이지
└── status/
    └── index.php         # 상태 페이지
```

#### 컨트롤러에서 뷰 렌더링
```php
// ✅ 올바른 뷰 렌더링 (컨트롤러에서)
public function index(): void
{
    // CSS/JS 추가
    View::getInstance()->addCss('https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css');
    View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/employees.js');
    
    // 뷰 렌더링 (레이아웃 포함)
    echo $this->render('pages/employees/index', [], 'layouts/app');
}

// ❌ 잘못된 예시
public function index(): void
{
    // JavaScript를 뷰 파일에 직접 포함하면 안됨
    echo $this->render('pages/employees/index', [
        'script' => '<script>new EmployeesPage();</script>'  // 금지
    ], 'layouts/app');
}
```

### 6.3. 뷰 파일 작성 시 주의사항

#### 절대 금지 사항
```php
// ❌ 잘못된 예시들

// 1. container-fluid 직접 사용 (레이아웃에서 이미 제공)
<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="container-fluid">  <!-- 금지: 레이아웃에서 이미 제공됨 -->
    <div class="row">
        <!-- 내용 -->
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>

// 2. startSection/endSection 누락
<div class="row">  <!-- 금지: startSection 없이 시작 -->
    <!-- 내용 -->
</div>

// 3. JavaScript를 뷰 파일에 직접 포함
<?php \App\Core\View::getInstance()->startSection('content'); ?>
<!-- 내용 -->
<script>
    new EmployeesPage();  <!-- 금지: 뷰 파일에 직접 스크립트 -->
</script>
<?php \App\Core\View::getInstance()->endSection(); ?>

// 4. CSS를 뷰 파일에 직접 포함
<style>
    .custom-style { }  <!-- 금지: 뷰 파일에 직접 스타일 -->
</style>
```

#### 올바른 패턴
```php
// ✅ 올바른 뷰 파일 패턴

// 1. 반드시 startSection으로 시작
<?php \App\Core\View::getInstance()->startSection('content'); ?>

// 2. 페이지 제목과 브레드크럼 (선택사항)
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">페이지 제목</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/dashboard">대시보드</a></li>
                    <li class="breadcrumb-item active">현재 페이지</li>
                </ol>
            </div>
        </div>
    </div>
</div>

// 3. 메인 콘텐츠
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">카드 제목</h5>
            </div>
            <div class="card-body">
                <!-- 카드 내용 -->
            </div>
        </div>
    </div>
</div>

// 4. 반드시 endSection으로 종료
<?php \App\Core\View::getInstance()->endSection(); ?>
```

#### 컨트롤러에서 뷰 관련 설정
```php
// ✅ 컨트롤러에서 올바른 뷰 설정
public function index(): void
{
    // 1. 페이지별 CSS 추가
    View::getInstance()->addCss('https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css');
    View::getInstance()->addCss(BASE_ASSETS_URL . '/assets/css/pages/employees.css');
    
    // 2. 페이지별 JavaScript 추가
    View::getInstance()->addJs('https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js');
    View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/employees.js');
    
    // 3. 뷰 렌더링 (데이터, 레이아웃 지정)
    echo $this->render('pages/employees/index', [
        'pageTitle' => '직원 관리',
        'additionalData' => $someData
    ], 'layouts/app');
}
```

---

## 7. JavaScript 개발 규칙

### 7.1. API 호출 규칙

```javascript
// ✅ 올바른 API 호출
class EmployeesPage extends BasePage {
    async loadEmployees() {
        try {
            // BasePage의 apiCall 메서드 사용 필수
            const response = await this.apiCall('/employees');
            this.renderEmployeeList(response.data);
        } catch (error) {
            Toast.error('직원 목록 로딩 실패');
            console.error('Load employees error:', error);
        }
    }

    async createEmployee(data) {
        try {
            const response = await this.apiCall('/employees', {
                method: 'POST',
                body: JSON.stringify(data),
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            Toast.success('직원이 생성되었습니다.');
            return response.data;
        } catch (error) {
            Toast.error('직원 생성 실패');
            throw error;
        }
    }
}

// ❌ 잘못된 예시 - 직접 fetch 사용
async loadData() {
    const response = await fetch('/api/employees'); // 금지
}
```

### 7.2. XSS 방지 규칙

```javascript
// ✅ 올바른 HTML 출력 (XSS 방지)
renderEmployeeList(employees) {
    const html = employees.map(emp => `
        <div class="employee-item" data-id="${emp.id}">
            <h5>${this.sanitizeHTML(emp.name)}</h5>
            <p>${this.sanitizeHTML(emp.department_name || '미지정')}</p>
            <small>${this.sanitizeHTML(emp.position_name || '미지정')}</small>
        </div>
    `).join('');
    
    document.getElementById('employee-list').innerHTML = html;
}

// ❌ 잘못된 예시 - 직접 HTML 삽입 (XSS 위험)
renderEmployeeList(employees) {
    const html = employees.map(emp => `
        <div><h5>${emp.name}</h5></div>  // XSS 위험
    `).join('');
}
```

---

## 8. 데이터베이스 관련 규칙

### 8.1. 테이블 생성 규칙

```sql
-- ✅ 올바른 테이블 생성
CREATE TABLE hr_employees (
    id int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
    name varchar(255) NOT NULL COMMENT '직원명',
    email varchar(255) NOT NULL COMMENT '이메일',
    department_id int(11) DEFAULT NULL COMMENT '부서 ID',
    position_id int(11) DEFAULT NULL COMMENT '직급 ID',
    hire_date date DEFAULT NULL COMMENT '입사일',
    termination_date date DEFAULT NULL COMMENT '퇴사일',
    created_at datetime DEFAULT current_timestamp() COMMENT '생성일시',
    updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
    
    PRIMARY KEY (id),
    UNIQUE KEY email (email),
    KEY idx_department (department_id),
    KEY idx_position (position_id),
    KEY idx_hire_date (hire_date),
    
    CONSTRAINT fk_employee_department FOREIGN KEY (department_id) REFERENCES hr_departments (id) ON DELETE SET NULL,
    CONSTRAINT fk_employee_position FOREIGN KEY (position_id) REFERENCES hr_positions (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='직원 정보';
```

### 8.2. SQL 쿼리 작성 규칙

```php
// ✅ 올바른 SQL 쿼리 (준비된 문장 사용)
public function findByDepartment(int $departmentId): array
{
    $sql = "SELECT e.*, d.name as department_name, p.name as position_name
            FROM hr_employees e
            LEFT JOIN hr_departments d ON e.department_id = d.id
            LEFT JOIN hr_positions p ON e.position_id = p.id
            WHERE e.department_id = ? AND e.termination_date IS NULL
            ORDER BY p.level ASC, e.hire_date ASC";
    
    return $this->db->query($sql, [$departmentId]);
}

// ❌ 잘못된 예시 - SQL 인젝션 위험
public function findByDepartment($departmentId): array
{
    $sql = "SELECT * FROM hr_employees WHERE department_id = $departmentId"; // 위험
    return $this->db->query($sql);
}
```

---

## 9. 에러 처리 및 로깅 규칙

### 9.1. PHP 예외 처리

```php
// ✅ 올바른 예외 처리
public function createEmployee(array $data): string
{
    try {
        // 데이터 검증
        $employee = Employee::make($data);
        if (!$employee->validate()) {
            throw new InvalidArgumentException('유효하지 않은 직원 데이터: ' . implode(', ', $employee->getErrors()));
        }

        // 트랜잭션 시작
        $this->db->beginTransaction();
        
        $employeeId = $this->employeeRepository->save($data);
        $this->logRepository->log('employee_created', $employeeId, $data);
        
        $this->db->commit();
        return $employeeId;
        
    } catch (InvalidArgumentException $e) {
        $this->db->rollback();
        throw $e; // 사용자에게 표시할 메시지
    } catch (Exception $e) {
        $this->db->rollback();
        error_log('Employee creation failed: ' . $e->getMessage());
        throw new RuntimeException('직원 생성 중 오류가 발생했습니다.');
    }
}
```

### 9.2. JavaScript 에러 처리

```javascript
// ✅ 올바른 에러 처리
async handleFormSubmit(formData) {
    this.setButtonLoading('#save-btn', '저장 중...');
    
    try {
        const response = await this.apiCall('/employees', {
            method: 'POST',
            body: JSON.stringify(formData),
            headers: { 'Content-Type': 'application/json' }
        });
        
        Toast.success('직원이 성공적으로 생성되었습니다.');
        this.resetForm();
        await this.loadEmployees(); // 목록 새로고침
        
    } catch (error) {
        console.error('Employee creation error:', error);
        
        if (error.response && error.response.data && error.response.data.error) {
            Toast.error(error.response.data.error);
        } else {
            Toast.error('직원 생성 중 오류가 발생했습니다.');
        }
    } finally {
        this.resetButtonLoading('#save-btn', '저장');
    }
}
```

---

## 10. 성능 최적화 규칙

### 10.1. 데이터베이스 쿼리 최적화

```php
// ✅ 올바른 쿼리 최적화
public function getEmployeesWithPagination(array $filters = [], int $page = 1, int $perPage = 20): array
{
    $offset = ($page - 1) * $perPage;
    
    $queryParts = [
        'sql' => "SELECT e.id, e.name, e.email, d.name as department_name, p.name as position_name,
                         COUNT(*) OVER() as total_count
                  FROM hr_employees e
                  LEFT JOIN hr_departments d ON e.department_id = d.id
                  LEFT JOIN hr_positions p ON e.position_id = p.id",
        'params' => [],
        'where' => []
    ];
    
    // 데이터 스코프 적용
    $queryParts = $this->dataScopeService->applyEmployeeScope($queryParts, 'e');
    
    // 필터 조건 추가
    if (!empty($filters['status'])) {
        $queryParts['where'][] = "e.status = ?";
        $queryParts['params'][] = $filters['status'];
    }
    
    // WHERE 절 조합
    if (!empty($queryParts['where'])) {
        $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
    }
    
    // 정렬 및 페이징
    $queryParts['sql'] .= " ORDER BY e.created_at DESC LIMIT ? OFFSET ?";
    $queryParts['params'][] = $perPage;
    $queryParts['params'][] = $offset;
    
    return $this->db->query($queryParts['sql'], $queryParts['params']);
}

// ❌ 잘못된 예시 - 모든 데이터 조회 후 PHP에서 필터링
public function getAllEmployees(): array
{
    $allEmployees = $this->db->query("SELECT * FROM hr_employees"); // 비효율적
    return array_filter($allEmployees, function($emp) {
        return $emp['status'] === 'active';
    });
}
```

---

## 11. 보안 규칙

### 11.1. 입력값 검증

```php
// ✅ 올바른 입력값 검증
public function store(): string
{
    $data = $this->request->all();
    
    // 화이트리스트 방식으로 허용된 필드만 추출
    $allowedFields = ['name', 'email', 'department_id', 'position_id', 'hire_date'];
    $filteredData = array_intersect_key($data, array_flip($allowedFields));
    
    // 데이터 검증
    $employee = Employee::make($filteredData);
    if (!$employee->validate()) {
        return $this->jsonResponse([
            'error' => '입력값이 올바르지 않습니다.',
            'details' => $employee->getErrors()
        ], 400);
    }
    
    try {
        $result = $this->employeeService->createEmployee($filteredData);
        return $this->jsonResponse(['success' => true, 'data' => $result]);
    } catch (Exception $e) {
        return $this->jsonResponse(['error' => $e->getMessage()], 500);
    }
}

---

## 12. 코드 검증 체크리스트

### 새 기능 개발 시 반드시 확인할 사항

#### PHP 백엔드
- [ ] 모든 클래스가 적절한 부모 클래스를 상속하고 있는가?
- [ ] DI 컨테이너 등록 순서가 올바른가?
- [ ] 모든 라우트에 `auth`, `permission` 미들웨어가 설정되었는가?
- [ ] 리포지토리에서 `DataScopeService`가 적용되었는가?
- [ ] SQL 인젝션 방지를 위해 준비된 문장을 사용하고 있는가?
- [ ] 트랜잭션이 필요한 작업에서 적절히 사용되고 있는가?
- [ ] 예외 처리가 적절히 구현되어 있는가?

#### 뷰 파일
- [ ] `<?php \App\Core\View::getInstance()->startSection('content'); ?>`로 시작하고 `<?php \App\Core\View::getInstance()->endSection(); ?>`로 끝나는가?
- [ ] `<div class="container-fluid">`를 직접 사용하지 않았는가? (레이아웃에서 제공)
- [ ] JavaScript 코드를 뷰 파일에 직접 포함하지 않았는가?
- [ ] 컨트롤러에서 `View::getInstance()->addJs()`로 JavaScript 파일을 추가했는가?
- [ ] 모달이나 폼 구조가 Bootstrap 5 규격에 맞게 작성되었는가?

#### JavaScript 프론트엔드
- [ ] 모든 페이지 클래스가 `BasePage`를 상속하고 있는가?
- [ ] API 호출 시 `this.apiCall()` 메서드를 사용하고 있는가?
- [ ] HTML 출력 시 `this.sanitizeHTML()`을 사용하여 XSS를 방지하고 있는가?
- [ ] 에러 처리가 적절히 구현되어 있는가?
- [ ] 사용자 피드백(Toast, 로딩 상태 등)이 적절히 제공되고 있는가?

#### 데이터베이스
- [ ] 테이블명, 컬럼명이 명명 규칙을 따르고 있는가?
- [ ] 필수 컬럼(`id`, `created_at`, `updated_at`)이 포함되어 있는가?
- [ ] 적절한 인덱스가 설정되어 있는가?
- [ ] 외래키 제약조건이 올바르게 설정되어 있는가?

---

## 📝 결론

이 지침서의 모든 규칙은 **필수 사항**입니다. AI가 코드를 생성하거나 수정할 때는 반드시 이 규칙들을 준수해야 하며, 특히 자주 발생하는 오류 부분(상속, DI 주입, 데이터 스코프, 권한 설정, 파일 구조)에 대해서는 더욱 세심한 주의를 기울여야 합니다.

**모든 코드 생성 작업 전에 이 문서를 참조하고, 완료 후에는 체크리스트를 통해 검증하시기 바랍니다.**