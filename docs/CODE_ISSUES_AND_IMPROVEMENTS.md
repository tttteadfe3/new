# 코드 구조 문제점 및 개선 사항

## 개요

현재 코드베이스를 분석하면서 발견된 구조적 문제점, 잠재적 위험 요소, 그리고 개선이 필요한 부분들을 정리한 문서입니다.

---

## 1. 심각도 높음 (Critical) - 즉시 수정 필요

### 1.1. 의존성 주입 컨테이너의 순환 의존성 위험

**문제점**: `public/index.php`에서 DI 컨테이너 등록 순서가 복잡하고, 주석으로만 의존성 순서를 관리하고 있음

**현재 코드**:
```php
// public/index.php (라인 20-90)
// The order of registration is critical to avoid circular dependencies.

// 1. Core services with no repository dependencies, or only DB/Session.
$container->register(\App\Services\DataScopeService::class, fn($c) => new \App\Services\DataScopeService(
    $c->resolve(SessionManager::class),
    $c->resolve(Database::class)
));

// 2. Repositories - some now depend on DataScopeService.
$container->register(\App\Repositories\DepartmentRepository::class, fn($c) => new \App\Repositories\DepartmentRepository($c->resolve(Database::class), $c->resolve(\App\Services\DataScopeService::class)));
$container->register(\App\Repositories\EmployeeRepository::class, fn($c) => new \App\Repositories\EmployeeRepository($c->resolve(Database::class), $c->resolve(\App\Services\DataScopeService::class)));
```

**문제점**:
- 수동으로 의존성 순서를 관리해야 함
- 새로운 서비스 추가 시 순서 실수 가능성
- 코드가 길고 가독성이 떨어짐

**개선 방안**:
```php
// 개선안 1: 서비스 프로바이더 패턴 도입
class ServiceProvider {
    public static function register(Container $container): void {
        self::registerCoreServices($container);
        self::registerRepositories($container);
        self::registerServices($container);
        self::registerControllers($container);
    }
    
    private static function registerCoreServices(Container $container): void {
        $container->singleton(Database::class, fn() => new Database());
        $container->singleton(SessionManager::class, fn() => new SessionManager());
        // ...
    }
}

// 개선안 2: 자동 의존성 해결 강화
// Container 클래스에서 더 스마트한 의존성 해결 로직 구현
```

### 1.2. BaseModel의 유효성 검사 로직 문제

**문제점**: `app/Models/BaseModel.php`의 `validate()` 메서드가 너무 단순하고 확장성이 부족

**현재 코드**:
```php
// app/Models/BaseModel.php (라인 90-150)
protected function validateRule(string $field, mixed $value, string $rule): bool
{
    $ruleParts = explode(':', $rule);
    $ruleName = $ruleParts[0];
    $ruleValue = $ruleParts[1] ?? null;
    
    switch ($ruleName) {
        case 'required':
            if (empty($value) && $value !== '0' && $value !== 0) {
                $this->errors[$field] = "{$field}은(는) 필수 항목입니다.";
                return false;
            }
            break;
        case 'string':
            if (!is_string($value) && $value !== null) {
                $this->errors[$field] = "{$field}은(는) 문자열이어야 합니다.";
                return false;
            }
            break;
        // ... 기타 규칙들
    }
}
```

**문제점**:
- 하드코딩된 에러 메시지 (다국어 지원 불가)
- 복잡한 유효성 검사 규칙 지원 부족
- 커스텀 유효성 검사 함수 지원 없음
- 필드 간 의존성 검사 불가능

**개선 방안**:
```php
// 개선안: 전용 Validator 클래스 사용
class Employee extends BaseModel
{
    protected array $rules = [
        'name' => 'required|string|max:100',
        'email' => 'required|email|unique:hr_employees,email',
        'department_id' => 'required|exists:hr_departments,id'
    ];
    
    public function validate(): bool
    {
        $validator = new Validator($this->attributes, $this->rules);
        $this->errors = $validator->getErrors();
        return $validator->passes();
    }
}
```

### 1.3. 라우터의 에러 처리 보안 문제

**문제점**: `app/Core/Router.php`에서 에러 발생 시 민감한 정보 노출 가능성

**현재 코드**:
```php
// app/Core/Router.php (라인 130-150)
public function handleError(int $statusCode, string $message): void
{
    http_response_code($statusCode);
    $isApiRequest = str_starts_with(strtok($_SERVER['REQUEST_URI'], '?'), '/api/');

    if ($isApiRequest) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE); // ⚠️ 위험
    } else {
        $viewPath = defined('BASE_PATH') ? BASE_PATH . "/errors/{$statusCode}.php" : __DIR__ . "/../../errors/{$statusCode}.php";
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo "<h1>오류 {$statusCode}</h1><p>{$message}</p>";
        }
    }
    exit();
}
```

**문제점**:
- 개발 환경과 운영 환경 구분 없이 동일한 에러 메시지 출력
- 내부 시스템 정보가 사용자에게 노출될 수 있음
- 에러 로깅 없음

**개선 방안**:
```php
public function handleError(int $statusCode, string $message, ?Exception $exception = null): void
{
    // 에러 로깅
    if ($exception) {
        error_log("Router Error: " . $exception->getMessage() . "\n" . $exception->getTraceAsString());
    }
    
    http_response_code($statusCode);
    $isApiRequest = str_starts_with(strtok($_SERVER['REQUEST_URI'], '?'), '/api/');
    
    // 운영 환경에서는 일반적인 메시지만 표시
    $userMessage = $_ENV['APP_ENV'] === 'production' 
        ? $this->getGenericErrorMessage($statusCode)
        : $message;

    if ($isApiRequest) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $userMessage], JSON_UNESCAPED_UNICODE);
    } else {
        // ...
    }
}
```

---

## 2. 심각도 중간 (Medium) - 개선 권장

### 2.1. JavaScript 클래스 구조의 일관성 부족

**문제점**: 프론트엔드 JavaScript 코드에서 클래스 구조가 일관되지 않음

**현재 코드 분석**:
```javascript
// public/assets/js/pages/employees.js
class EmployeesPage extends BasePage {
    constructor() {
        super();
        this.state = {
            ...this.state,  // ⚠️ 부모 state 확장 방식이 명확하지 않음
            allDepartments: [],
            allPositions: [],
            currentEmployee: null,
            viewMode: 'welcome',
        };
    }
}
```

**문제점**:
- `this.state` 초기화 방식이 일관되지 않음
- 상태 관리 패턴이 명확하지 않음
- 컴포넌트 간 통신 방법이 정의되지 않음

**개선 방안**:
```javascript
// 개선안: 명확한 상태 관리 패턴
class EmployeesPage extends BasePage {
    constructor() {
        super();
        this.initializeState();
    }
    
    initializeState() {
        this.state = {
            // 데이터 상태
            data: {
                employees: [],
                departments: [],
                positions: []
            },
            // UI 상태
            ui: {
                viewMode: 'welcome',
                loading: false,
                selectedEmployee: null
            },
            // 필터 상태
            filters: {
                department: '',
                position: '',
                status: 'active'
            }
        };
    }
}
```

### 2.2. 데이터베이스 쿼리 최적화 부족

**문제점**: 리포지토리에서 N+1 쿼리 문제 발생 가능성

**현재 코드**:
```php
// app/Repositories/EmployeeRepository.php
public function findById(int $id) {
    $sql = "SELECT e.*, d.name as department_name, p.name as position_name
            FROM hr_employees e
            LEFT JOIN hr_departments d ON e.department_id = d.id
            LEFT JOIN hr_positions p ON e.position_id = p.id
            WHERE e.id = :id";
    return $this->db->fetchOne($sql, [':id' => $id]);
}
```

**문제점**:
- 개별 조회는 최적화되어 있으나, 목록 조회에서 관련 데이터를 별도로 조회할 가능성
- 페이징 처리가 없음
- 인덱스 활용도 검증 필요

**개선 방안**:
```php
// 개선안: 일괄 조회 및 페이징 지원
public function findAllWithRelations(array $filters = [], int $page = 1, int $perPage = 20): array
{
    $offset = ($page - 1) * $perPage;
    
    $sql = "SELECT e.*, d.name as department_name, p.name as position_name,
                   COUNT(*) OVER() as total_count
            FROM hr_employees e
            LEFT JOIN hr_departments d ON e.department_id = d.id
            LEFT JOIN hr_positions p ON e.position_id = p.id
            WHERE 1=1";
    
    // 동적 필터 추가
    $params = [];
    if (!empty($filters['department_id'])) {
        $sql .= " AND e.department_id = ?";
        $params[] = $filters['department_id'];
    }
    
    $sql .= " ORDER BY e.id DESC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    
    return $this->db->query($sql, $params);
}
```

### 2.3. 환경 설정 관리 개선 필요

**문제점**: 환경별 설정 관리가 체계적이지 않음

**현재 구조**:
```
.env                    # 환경 변수
.env.example           # 예시 파일
config/config.php      # PHP 설정
```

**문제점**:
- 환경별 설정 파일 분리 없음 (dev, staging, production)
- 설정 검증 로직 없음
- 민감한 정보와 일반 설정의 구분 없음

**개선 방안**:
```php
// config/app.php
return [
    'name' => env('APP_NAME', 'ERP System'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    
    'database' => [
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', 3306),
        'database' => env('DB_DATABASE'),
        'username' => env('DB_USERNAME'),
        'password' => env('DB_PASSWORD'),
    ],
];

// config/ConfigValidator.php
class ConfigValidator
{
    public static function validate(): void
    {
        $required = ['DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'];
        
        foreach ($required as $key) {
            if (empty($_ENV[$key])) {
                throw new RuntimeException("Required environment variable {$key} is not set");
            }
        }
    }
}
```

---

## 3. 심각도 낮음 (Low) - 장기 개선 사항

### 3.1. 코드 문서화 부족

**문제점**:
- PHPDoc 주석이 일부 클래스에만 존재
- JavaScript 함수에 JSDoc 주석 없음
- API 문서 자동 생성 시스템 없음

**개선 방안**:
```php
/**
 * 직원 정보를 관리하는 서비스 클래스
 * 
 * @package App\Services
 * @author Team Name
 * @since 1.0.0
 */
class EmployeeService
{
    /**
     * 새로운 직원을 생성합니다.
     * 
     * @param array $data 직원 정보 배열
     * @return string|null 생성된 직원의 ID, 실패 시 null
     * @throws InvalidArgumentException 유효하지 않은 데이터인 경우
     * @throws RuntimeException 데이터베이스 오류인 경우
     */
    public function createEmployee(array $data): ?string
    {
        // ...
    }
}
```

### 3.2. 테스트 코드 부재

**문제점**:
- 단위 테스트 없음
- 통합 테스트 없음
- 테스트 자동화 환경 없음

**개선 방안**:
```php
// tests/Unit/Services/EmployeeServiceTest.php
class EmployeeServiceTest extends TestCase
{
    private EmployeeService $employeeService;
    private MockObject $employeeRepository;
    
    protected function setUp(): void
    {
        $this->employeeRepository = $this->createMock(EmployeeRepository::class);
        $this->employeeService = new EmployeeService($this->employeeRepository);
    }
    
    public function testCreateEmployeeWithValidData(): void
    {
        $data = ['name' => 'John Doe', 'email' => 'john@example.com'];
        
        $this->employeeRepository
            ->expects($this->once())
            ->method('save')
            ->with($data)
            ->willReturn('123');
            
        $result = $this->employeeService->createEmployee($data);
        
        $this->assertEquals('123', $result);
    }
}
```

### 3.3. 로깅 시스템 개선

**문제점**:
- 구조화된 로깅 없음
- 로그 레벨 관리 없음
- 로그 로테이션 설정 없음

**개선 방안**:
```php
// app/Core/Logger.php
class Logger
{
    private string $logPath;
    private string $logLevel;
    
    public function __construct()
    {
        $this->logPath = $_ENV['LOG_PATH'] ?? storage_path('logs');
        $this->logLevel = $_ENV['LOG_LEVEL'] ?? 'info';
    }
    
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }
    
    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }
    
    private function log(string $level, string $message, array $context): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ];
        
        $logFile = $this->logPath . '/' . date('Y-m-d') . '.log';
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    }
}
```

---

## 4. 성능 최적화 사항

### 4.1. 데이터베이스 인덱스 최적화

**현재 상태 분석 필요**:
```sql
-- 성능 분석이 필요한 쿼리들
EXPLAIN SELECT e.*, d.name as department_name 
FROM hr_employees e 
LEFT JOIN hr_departments d ON e.department_id = d.id 
WHERE e.status = 'active' 
ORDER BY e.created_at DESC;

-- 권장 인덱스
CREATE INDEX idx_employees_status_created ON hr_employees(status, created_at);
CREATE INDEX idx_employees_department_status ON hr_employees(department_id, status);
```

### 4.2. 프론트엔드 최적화

**개선 사항**:
- JavaScript 번들링 및 압축
- CSS 최적화
- 이미지 최적화
- 브라우저 캐싱 설정

```javascript
// 개선안: 지연 로딩 구현
class EmployeesPage extends BasePage {
    async loadEmployees(page = 1) {
        // 무한 스크롤 또는 페이징 구현
        const response = await this.apiCall(`/employees?page=${page}&per_page=20`);
        
        if (page === 1) {
            this.renderEmployeeList(response.data);
        } else {
            this.appendEmployeeList(response.data);
        }
    }
}
```

---

## 5. 보안 강화 사항

### 5.1. CSRF 보호 강화

**현재 상태**: CSRF 토큰 검증이 일부 폼에만 적용됨

**개선 방안**:
```php
// app/Middleware/CsrfMiddleware.php
class CsrfMiddleware extends BaseMiddleware
{
    public function handle($value = null): void
    {
        if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
            $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            
            if (!$this->validateCsrfToken($token)) {
                $this->jsonResponse(['error' => 'CSRF token mismatch'], 419);
                exit();
            }
        }
    }
}
```

### 5.2. 입력값 검증 강화

**현재 상태**: 기본적인 검증만 수행

**개선 방안**:
```php
// app/Validators/EmployeeValidator.php
class EmployeeValidator
{
    public static function validateCreate(array $data): array
    {
        $rules = [
            'name' => 'required|string|max:100|regex:/^[가-힣a-zA-Z\s]+$/',
            'email' => 'required|email|unique:hr_employees,email',
            'phone' => 'required|regex:/^01[0-9]-[0-9]{4}-[0-9]{4}$/',
            'department_id' => 'required|exists:hr_departments,id',
            'position_id' => 'required|exists:hr_positions,id',
        ];
        
        return Validator::make($data, $rules);
    }
}
```

---

## 6. 우선순위별 개선 로드맵

### Phase 1 (즉시 수정 - 1-2주)
1. 라우터 에러 처리 보안 강화
2. DI 컨테이너 구조 개선
3. 기본적인 로깅 시스템 구현

### Phase 2 (단기 개선 - 1-2개월)
1. 유효성 검사 시스템 개선
2. JavaScript 클래스 구조 표준화
3. 데이터베이스 쿼리 최적화
4. CSRF 보호 강화

### Phase 3 (중기 개선 - 3-6개월)
1. 테스트 코드 작성
2. API 문서 자동화
3. 성능 모니터링 시스템
4. 코드 품질 자동화 도구 도입

### Phase 4 (장기 개선 - 6개월 이상)
1. 마이크로서비스 아키텍처 검토
2. 캐싱 시스템 도입
3. CI/CD 파이프라인 구축
4. 모니터링 및 알림 시스템

---

## 결론

현재 코드베이스는 전반적으로 잘 구조화되어 있으나, 보안, 성능, 유지보수성 측면에서 개선이 필요한 부분들이 있습니다. 특히 심각도가 높은 문제들은 즉시 수정하여 시스템의 안정성을 확보해야 하며, 중장기적으로는 테스트 코드 작성과 성능 최적화를 통해 더욱 견고한 시스템으로 발전시켜야 합니다.

**모든 개선 사항은 단계적으로 적용하되, 기존 기능에 영향을 주지 않도록 충분한 테스트와 검토를 거쳐 진행해야 합니다.**