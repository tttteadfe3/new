# 코드베이스 분석 및 개발 규칙 문서

## 개요

이 문서는 현재 ERP 시스템 코드베이스의 구조, 기능, 작성 규칙을 상세히 분석하고 정리한 문서입니다. 기능 구현 시 규칙을 어기는 사례를 방지하기 위해 작성되었습니다.

---

## 1. 시스템 아키텍처 분석

### 1.1. 전체 구조
- **아키텍처 패턴**: MVC (Model-View-Controller) 패턴
- **언어**: PHP 8.x (백엔드), JavaScript ES6+ (프론트엔드)
- **데이터베이스**: MySQL/MariaDB
- **의존성 관리**: Composer (PHP), npm/yarn (JavaScript)
- **프론트엔드 프레임워크**: Vanilla JavaScript + Bootstrap

### 1.2. 디렉토리 구조
```
app/
├── Controllers/        # HTTP 요청 처리
│   ├── Api/           # API 컨트롤러
│   └── Web/           # 웹 페이지 컨트롤러
├── Core/              # 프레임워크 핵심 기능
├── Middleware/        # 미들웨어 (인증, 권한)
├── Models/            # 데이터 모델
├── Repositories/      # 데이터베이스 접근 계층
├── Services/          # 비즈니스 로직 계층
├── Validators/        # 데이터 검증
└── Views/             # HTML 템플릿

public/
├── assets/
│   ├── css/          # 스타일시트
│   ├── js/           # JavaScript 파일
│   │   ├── core/     # 핵심 클래스
│   │   ├── pages/    # 페이지별 로직
│   │   └── utils/    # 유틸리티 함수
│   └── libs/         # 외부 라이브러리
└── index.php         # 애플리케이션 진입점

routes/
├── web.php           # 웹 라우트
└── api.php           # API 라우트

config/               # 설정 파일
database/             # 데이터베이스 스키마 및 마이그레이션
docs/                 # 문서
```

---

## 2. 핵심 컴포넌트 분석

### 2.1. 의존성 주입 컨테이너 (DI Container)

**파일**: `app/Core/Container.php`

**기능**:
- 클래스 간 의존성 자동 해결
- 싱글톤 패턴 지원
- 리플렉션을 통한 자동 인스턴스 생성

**중요 규칙**:
```php
// ✅ 올바른 등록 순서 (public/index.php 참조)
// 1. 핵심 서비스 (DB, Session 등)
// 2. 리포지토리
// 3. 서비스
// 4. 컨트롤러

// ❌ 잘못된 예: 순환 의존성 발생
$container->register(ServiceA::class, fn($c) => new ServiceA($c->resolve(ServiceB::class)));
$container->register(ServiceB::class, fn($c) => new ServiceB($c->resolve(ServiceA::class)));
```

### 2.2. 라우터 시스템

**파일**: `app/Core/Router.php`

**기능**:
- HTTP 메서드별 라우트 등록 (GET, POST, PUT, DELETE)
- 라우트 그룹화 및 미들웨어 체이닝
- 동적 파라미터 지원 (`{id}`)
- 명명된 라우트 지원

**작성 규칙**:
```php
// ✅ 올바른 라우트 정의
$router->get('/employees/{id}', [EmployeeController::class, 'show'])
       ->name('employees.show')
       ->middleware('auth')
       ->middleware('permission', 'employee.view');

// ❌ 잘못된 예: 미들웨어 누락
$router->get('/admin/users', [AdminController::class, 'users']); // 권한 체크 없음
```

### 2.3. 데이터베이스 접근

**파일**: `app/Core/Database.php`

**기능**:
- PDO 기반 데이터베이스 연결
- 준비된 문장(Prepared Statement) 지원
- 트랜잭션 관리

---

## 3. 계층별 개발 규칙

### 3.1. 컨트롤러 (Controller) 규칙

**역할**: HTTP 요청 처리 및 응답 생성

**필수 규칙**:
1. **생성자 주입만 사용**: 필요한 서비스는 생성자를 통해 주입받아야 함
2. **비즈니스 로직 금지**: 컨트롤러에서 직접 비즈니스 로직을 구현하면 안 됨
3. **Request 객체 사용**: 사용자 입력은 반드시 `Request` 객체를 통해 받아야 함

**올바른 구조**:
```php
class EmployeeController extends BaseController
{
    private EmployeeService $employeeService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        EmployeeService $employeeService  // ✅ 생성자 주입
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger);
        $this->employeeService = $employeeService;
    }

    public function store(): string
    {
        $data = $this->request->all(); // ✅ Request 객체 사용
        
        try {
            $result = $this->employeeService->createEmployee($data); // ✅ 서비스 호출
            return $this->jsonResponse(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            return $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}
```

**금지사항**:
```php
// ❌ 잘못된 예시들
class BadController
{
    public function store()
    {
        // ❌ 전역 변수 직접 사용
        $name = $_POST['name'];
        
        // ❌ 직접 SQL 실행
        $pdo = new PDO(...);
        $stmt = $pdo->prepare("INSERT INTO employees...");
        
        // ❌ 복잡한 비즈니스 로직
        if ($employee->department === 'HR' && $employee->level > 5) {
            // 복잡한 계산 로직...
        }
    }
}
```

### 3.2. 서비스 (Service) 규칙

**역할**: 비즈니스 로직 처리 및 트랜잭션 관리

**필수 규칙**:
1. **상태 비저장**: 서비스는 인스턴스 변수에 상태를 저장하면 안 됨
2. **리포지토리 조합**: 여러 리포지토리를 조합하여 비즈니스 로직 구현
3. **트랜잭션 관리**: 데이터 일관성이 중요한 작업은 트랜잭션으로 처리

**올바른 구조**:
```php
class EmployeeService
{
    private EmployeeRepository $employeeRepository;
    private DepartmentRepository $departmentRepository;
    private LogRepository $logRepository;

    public function createEmployee(array $data): ?string
    {
        // ✅ 데이터 검증
        $employee = Employee::make($data);
        if (!$employee->validate()) {
            throw new InvalidArgumentException('잘못된 직원 데이터');
        }

        // ✅ 트랜잭션 사용
        $this->db->beginTransaction();
        try {
            $employeeId = $this->employeeRepository->save($data);
            $this->logRepository->log('employee_created', $employeeId);
            $this->db->commit();
            return $employeeId;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
```

### 3.3. 리포지토리 (Repository) 규칙

**역할**: 데이터베이스 CRUD 작업 전담

**필수 규칙**:
1. **단일 테이블 책임**: 하나의 리포지토리는 하나의 주 테이블만 담당
2. **데이터 스코프 적용**: 모든 조회 메서드는 `DataScopeService` 적용 필수
3. **SQL 인젝션 방지**: 모든 쿼리는 준비된 문장 사용

**올바른 구조**:
```php
class EmployeeRepository
{
    private Database $db;
    private DataScopeService $dataScopeService;

    public function getAll(array $filters = []): array
    {
        // ✅ 데이터 스코프 적용
        $visibleDeptIds = $this->dataScopeService->getVisibleDepartmentIdsForCurrentUser();
        
        $sql = "SELECT e.*, d.name as department_name 
                FROM hr_employees e 
                LEFT JOIN hr_departments d ON e.department_id = d.id 
                WHERE e.department_id IN (" . implode(',', array_fill(0, count($visibleDeptIds), '?')) . ")";
        
        // ✅ 준비된 문장 사용
        return $this->db->query($sql, $visibleDeptIds);
    }
}
```

**금지사항**:
```php
// ❌ 잘못된 예시들
class BadRepository
{
    public function getAll($departmentName)
    {
        // ❌ SQL 인젝션 위험
        $sql = "SELECT * FROM employees WHERE department = '$departmentName'";
        
        // ❌ 데이터 스코프 무시
        return $this->db->query($sql);
    }
    
    public function createWithComplexLogic($data)
    {
        // ❌ 비즈니스 로직 포함
        if ($data['salary'] > 100000 && $data['department'] === 'IT') {
            $data['bonus'] = $data['salary'] * 0.1;
        }
        
        return $this->save($data);
    }
}
```

---

## 4. 보안 및 권한 관리 규칙

### 4.1. 권한 기반 접근 제어

**필수 적용 사항**:
1. **모든 보호된 라우트**: `auth` 미들웨어 필수
2. **기능별 권한**: `permission` 미들웨어로 세분화된 권한 체크
3. **권한 명명 규칙**: `{리소스}.{행위}` 형식 (예: `employee.view`, `user.create`)

```php
// ✅ 올바른 권한 설정
$router->get('/employees', [EmployeeController::class, 'index'])
       ->middleware('auth')
       ->middleware('permission', 'employee.view');

$router->post('/employees', [EmployeeController::class, 'store'])
       ->middleware('auth')
       ->middleware('permission', 'employee.create');
```

### 4.2. 데이터 스코프 (Data Scope) 규칙

**핵심 원칙**: 사용자는 자신의 부서 및 허가된 하위 부서 데이터만 접근 가능

**구현 방법**:
```php
// ✅ 모든 리포지토리에서 적용
class AnyRepository
{
    public function findAll(): array
    {
        $queryParts = [
            'sql' => "SELECT * FROM table_name",
            'params' => [],
            'where' => []
        ];
        
        // DataScopeService를 통해 자동으로 권한 적용
        $queryParts = $this->dataScopeService->applyTableScope($queryParts, 'alias');
        
        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }
        
        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }
}
```

---

## 5. 프론트엔드 개발 규칙

### 5.1. 클래스 기반 구조

**필수 규칙**:
1. **BasePage 상속**: 모든 페이지는 `BasePage` 클래스를 상속해야 함
2. **생명주기 메서드**: `initializeApp()`, `setupEventListeners()`, `loadInitialData()` 구현
3. **API 호출 표준화**: `this.apiCall()` 메서드 사용 필수

**올바른 구조**:
```javascript
class EmployeesPage extends BasePage {
    constructor() {
        super();
        this.state = {
            allDepartments: [],
            allPositions: [],
            currentEmployee: null
        };
    }

    initializeApp() {
        this.cacheDOMElements();
        this.setupEventListeners();
        this.loadInitialData();
    }

    async loadInitialData() {
        try {
            // ✅ 표준화된 API 호출
            const response = await this.apiCall('/employees/initial-data');
            this.state.allDepartments = response.data.departments;
            this.state.allPositions = response.data.positions;
        } catch (error) {
            Toast.error('데이터 로딩 실패');
        }
    }
}

// ✅ 인스턴스 생성
new EmployeesPage();
```

### 5.2. 보안 규칙

**XSS 방지**:
```javascript
// ✅ HTML 출력 시 반드시 sanitize
renderEmployeeList(employees) {
    const html = employees.map(emp => `
        <div class="employee-item">
            <h5>${this.sanitizeHTML(emp.name)}</h5>
            <p>${this.sanitizeHTML(emp.department_name)}</p>
        </div>
    `).join('');
    
    container.innerHTML = html;
}

// ❌ 잘못된 예: 직접 HTML 삽입
container.innerHTML = `<h5>${employee.name}</h5>`; // XSS 위험
```

---

## 6. 데이터베이스 설계 규칙

### 6.1. 테이블 명명 규칙

- **테이블명**: `snake_case`, 복수형 (예: `hr_employees`, `sys_users`)
- **컬럼명**: `snake_case` (예: `employee_id`, `created_at`)
- **외래키**: `{테이블명}_id` 형식 (예: `department_id`, `position_id`)

### 6.2. 필수 컬럼

**모든 마스터 테이블**:
```sql
CREATE TABLE example_table (
    id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT '고유 ID',
    -- 기타 컬럼들
    created_at datetime DEFAULT current_timestamp() COMMENT '생성일시',
    updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='테이블 설명';
```

**계층 구조 테이블** (부서 등):
```sql
CREATE TABLE hr_departments (
    id int(11) NOT NULL AUTO_INCREMENT COMMENT '고유 ID',
    name varchar(255) NOT NULL COMMENT '부서명',
    parent_id int(11) DEFAULT NULL COMMENT '상위 부서 ID (최상위 부서는 NULL)',
    path varchar(255) DEFAULT NULL COMMENT '계층 구조 경로 (예: /1/3/)',
    created_at datetime DEFAULT current_timestamp() COMMENT '생성일시',
    updated_at datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
    PRIMARY KEY (id),
    UNIQUE KEY name (name),
    KEY fk_department_parent (parent_id),
    CONSTRAINT fk_department_parent FOREIGN KEY (parent_id) REFERENCES hr_departments (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='부서 정보';
```

---

## 7. 공통 유틸리티 및 헬퍼

### 7.1. PHP 헬퍼 함수

**파일**: `app/Core/helpers.php`

```php
// ✅ 사용 권장 헬퍼들
route($name, $params = [])              // 명명된 라우트 URL 생성
sanitize($input)                        // 입력값 정화
formatDate($date, $format = 'Y-m-d')    // 날짜 포맷팅
```

### 7.2. JavaScript 유틸리티

**파일**: `public/assets/js/utils/`

```javascript
// ✅ 사용 권장 유틸리티들
Toast.success('성공 메시지')
Toast.error('오류 메시지')
await Confirm.fire({ title: '정말 삭제하시겠습니까?' })
this.sanitizeHTML(userInput)            // XSS 방지 (BasePage 메서드)
```

---

## 8. 성능 및 최적화 규칙

### 8.1. 데이터베이스 쿼리 최적화

```php
// ✅ 올바른 예: 필요한 컬럼만 선택
$sql = "SELECT id, name, department_id FROM hr_employees WHERE status = 'active'";

// ❌ 잘못된 예: 모든 컬럼 선택
$sql = "SELECT * FROM hr_employees WHERE status = 'active'";

// ✅ 올바른 예: 인덱스 활용
$sql = "SELECT * FROM hr_employees WHERE department_id = ? AND status = ?";

// ❌ 잘못된 예: 함수 사용으로 인덱스 무효화
$sql = "SELECT * FROM hr_employees WHERE UPPER(name) = ?";
```

### 8.2. 프론트엔드 최적화

```javascript
// ✅ 올바른 예: 이벤트 위임 사용
document.getElementById('employee-list').addEventListener('click', (e) => {
    if (e.target.classList.contains('edit-btn')) {
        this.handleEdit(e.target.dataset.id);
    }
});

// ❌ 잘못된 예: 개별 이벤트 리스너
employees.forEach(emp => {
    document.getElementById(`edit-${emp.id}`).addEventListener('click', () => {
        this.handleEdit(emp.id);
    });
});
```

---

## 9. 에러 처리 및 로깅

### 9.1. PHP 예외 처리

```php
// ✅ 올바른 예외 처리
public function createEmployee(array $data): string
{
    try {
        $this->validateEmployeeData($data);
        return $this->employeeRepository->save($data);
    } catch (ValidationException $e) {
        throw new InvalidArgumentException($e->getMessage());
    } catch (DatabaseException $e) {
        $this->logger->error('Employee creation failed', ['data' => $data, 'error' => $e->getMessage()]);
        throw new RuntimeException('직원 생성 중 오류가 발생했습니다.');
    }
}
```

### 9.2. JavaScript 에러 처리

```javascript
// ✅ 올바른 에러 처리
async loadEmployees() {
    try {
        const response = await this.apiCall('/employees');
        this.renderEmployeeList(response.data);
    } catch (error) {
        console.error('Failed to load employees:', error);
        Toast.error('직원 목록을 불러오는데 실패했습니다.');
        this.showErrorState();
    }
}
```

---

## 10. 테스트 및 검증

### 10.1. 코드 검증 체크리스트

**새 기능 개발 시 반드시 확인**:

- [ ] 모든 라우트에 적절한 권한 미들웨어가 설정되었는가?
- [ ] 리포지토리에서 데이터 스코프가 적용되었는가?
- [ ] 사용자 입력값이 적절히 검증되고 있는가?
- [ ] SQL 인젝션 방지를 위해 준비된 문장을 사용하고 있는가?
- [ ] XSS 방지를 위해 출력값이 적절히 이스케이프되고 있는가?
- [ ] 트랜잭션이 필요한 작업에서 적절히 사용되고 있는가?
- [ ] 에러 상황에 대한 적절한 처리가 구현되어 있는가?

### 10.2. 성능 검증

- [ ] N+1 쿼리 문제가 없는가?
- [ ] 불필요한 데이터를 조회하고 있지 않은가?
- [ ] 적절한 인덱스가 사용되고 있는가?
- [ ] 프론트엔드에서 불필요한 DOM 조작이 없는가?

---

## 결론

이 문서에 정리된 규칙들은 코드의 일관성, 보안성, 유지보수성을 보장하기 위한 필수 사항들입니다. 새로운 기능을 개발하거나 기존 코드를 수정할 때는 반드시 이 규칙들을 준수해야 하며, 코드 리뷰 시에도 이 문서를 기준으로 검토해야 합니다.

**모든 개발자는 이 문서를 숙지하고, 의문사항이 있을 때는 팀 리더나 시니어 개발자에게 문의하여 명확히 한 후 개발을 진행해야 합니다.**