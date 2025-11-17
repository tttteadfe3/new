# 지급품 관리 시스템 라우팅 및 메뉴 통합 가이드

## 개요

이 문서는 지급품 관리 시스템의 라우팅 구조와 메뉴 통합 방법을 설명합니다.

## 라우팅 구조

### 웹 라우트 (routes/web.php)

웹 라우트는 HTML 페이지를 렌더링하는 엔드포인트를 정의합니다.

#### 라우트 형식

```php
'GET /supply/categories' => [
    'controller' => SupplyCategoryController::class,
    'method' => 'index',
    'middleware' => ['auth', 'permission:supply_category_manage'],
    'name' => 'supply.categories.index'
]
```

#### 주요 웹 라우트

| URL 패턴 | 컨트롤러 | 메서드 | 권한 | 설명 |
|---------|---------|--------|------|------|
| `/supply` | SupplyReportController | index | supply_management | 대시보드 |
| `/supply/categories` | SupplyCategoryController | index | supply_category_manage | 분류 목록 |
| `/supply/plans` | SupplyPlanController | index | supply_plan_manage | 계획 목록 |
| `/supply/purchases` | SupplyPurchaseController | index | supply_purchase_manage | 구매 목록 |
| `/supply/distributions` | SupplyDistributionController | index | supply_distribution_manage | 지급 목록 |
| `/supply/reports/distribution` | SupplyReportController | distributionStatus | supply_report_view | 지급 현황 |
| `/supply/reports/stock` | SupplyReportController | stockStatus | supply_report_view | 재고 현황 |
| `/supply/reports/budget` | SupplyReportController | budgetExecution | supply_report_view | 예산 집행률 |
| `/supply/reports/department` | SupplyReportController | departmentUsage | supply_report_view | 부서별 사용 |

### API 라우트 (routes/api.php)

API 라우트는 JSON 응답을 반환하는 RESTful 엔드포인트를 정의합니다.

#### RESTful API 패턴

| HTTP 메서드 | URL 패턴 | 메서드 | 설명 |
|------------|---------|--------|------|
| GET | `/api/supply/categories` | index | 목록 조회 |
| GET | `/api/supply/categories/:id` | show | 상세 조회 |
| POST | `/api/supply/categories` | store | 생성 |
| PUT/PATCH | `/api/supply/categories/:id` | update | 수정 |
| DELETE | `/api/supply/categories/:id` | destroy | 삭제 |

#### 특수 API 엔드포인트

**분류 관리**
- `GET /api/supply/categories/level/:level` - 레벨별 분류 조회

**연간 계획**
- `POST /api/supply/plans/import-excel` - 엑셀 업로드
- `GET /api/supply/plans/export-excel/:year` - 엑셀 다운로드
- `GET /api/supply/plans/budget-summary/:year` - 예산 요약
- `POST /api/supply/plans/copy` - 계획 복사

**구매 관리**
- `PATCH /api/supply/purchases/:id/mark-received` - 입고 처리

**지급 관리**
- `PATCH /api/supply/distributions/:id/cancel` - 지급 취소
- `GET /api/supply/distributions/available-items` - 지급 가능 품목
- `GET /api/supply/distributions/employees/:deptId` - 부서별 직원 목록

**보고서**
- `GET /api/supply/reports/distribution` - 지급 현황 데이터
- `GET /api/supply/reports/stock` - 재고 현황 데이터
- `GET /api/supply/reports/budget/:year` - 예산 집행률 데이터
- `GET /api/supply/reports/department/:deptId/:year` - 부서별 사용 현황
- `POST /api/supply/reports/export` - 보고서 엑셀 내보내기

## 메뉴 구조

### 메뉴 계층

```
지급품 관리 (supply_management)
├── 대시보드 (/supply)
├── 분류 관리 (/supply/categories) - supply_category_manage
├── 연간 계획 (/supply/plans) - supply_plan_manage
├── 구매 관리 (/supply/purchases) - supply_purchase_manage
├── 지급 관리 (/supply/distributions) - supply_distribution_manage
└── 보고서 (supply_report_view)
    ├── 지급 현황 (/supply/reports/distribution)
    ├── 재고 현황 (/supply/reports/stock)
    ├── 예산 집행률 (/supply/reports/budget)
    └── 부서별 사용 (/supply/reports/department)
```

### 메뉴 데이터베이스 구조

메뉴는 `sys_menus` 테이블에 저장됩니다:

```sql
CREATE TABLE sys_menus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT NULL,
    name VARCHAR(100) NOT NULL,
    url VARCHAR(255),
    icon VARCHAR(50),
    permission_key VARCHAR(100),
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 메뉴 설치

#### 신규 설치

신규 시스템의 경우 다음 스크립트를 실행하세요:

```bash
# 전체 시스템 설치 (권한 + 메뉴 포함)
mysql -u username -p database_name < database/migrations/setup_supply_management_system.sql

# 또는 메뉴만 설치
mysql -u username -p database_name < database/seeds/15_supply_menus.sql
```

#### 기존 시스템 업데이트

기존 시스템에 메뉴 구조를 업데이트하려면:

```bash
mysql -u username -p database_name < database/migrations/update_supply_menus_structure.sql
```

## 권한 체계

### 권한 키 목록

| 권한 키 | 설명 | 적용 범위 |
|--------|------|----------|
| `supply_management` | 지급품 관리 기본 접근 | 대시보드 |
| `supply_category_manage` | 분류 관리 | 분류 CRUD |
| `supply_plan_manage` | 계획 관리 | 계획 CRUD, 엑셀 업로드 |
| `supply_plan_view` | 계획 조회 | 계획 조회, 예산 요약 |
| `supply_purchase_manage` | 구매 관리 | 구매 CRUD, 입고 처리 |
| `supply_purchase_view` | 구매 조회 | 구매 조회 |
| `supply_distribution_manage` | 지급 관리 | 지급 CRUD, 취소 |
| `supply_distribution_view` | 지급 조회 | 지급 조회 |
| `supply_report_view` | 보고서 조회 | 모든 보고서 |

### 역할별 권한 매핑

**관리자 (Administrator)**
- 모든 권한 보유

**총무팀 (General Affairs)**
- supply_management
- supply_category_manage
- supply_plan_manage
- supply_purchase_manage
- supply_distribution_manage
- supply_report_view

**부서담당자 (Department Manager)**
- supply_plan_view
- supply_distribution_view
- supply_report_view (본인 부서만)

**일반직원 (Employee)**
- supply_distribution_view (본인 기록만)

## 미들웨어

### 인증 미들웨어 (auth)

모든 라우트는 인증된 사용자만 접근할 수 있습니다.

### 권한 미들웨어 (permission)

각 라우트는 특정 권한을 요구합니다:

```php
'middleware' => ['auth', 'permission:supply_category_manage']
```

## 라우트 사용 예시

### 프론트엔드에서 라우트 호출

```javascript
// 웹 페이지 이동
window.location.href = '/supply/categories';

// API 호출 (GET)
fetch('/api/supply/categories')
    .then(response => response.json())
    .then(data => console.log(data));

// API 호출 (POST)
fetch('/api/supply/categories', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        category_name: '안전장구',
        level: 1
    })
})
.then(response => response.json())
.then(data => console.log(data));

// API 호출 (DELETE)
fetch('/api/supply/categories/1', {
    method: 'DELETE'
})
.then(response => response.json())
.then(data => console.log(data));
```

### 컨트롤러에서 리다이렉트

```php
// BaseController의 redirect 메서드 사용
$this->redirect('/supply/categories');

// 쿼리 파라미터 포함
$this->redirect('/supply/plans?year=2025');
```

## 문제 해결

### 메뉴가 표시되지 않는 경우

1. 메뉴 데이터가 올바르게 삽입되었는지 확인:
```sql
SELECT * FROM sys_menus WHERE name = '지급품 관리';
```

2. 사용자에게 적절한 권한이 있는지 확인:
```sql
SELECT p.* FROM sys_permissions p
JOIN sys_role_permissions rp ON p.id = rp.permission_id
JOIN sys_user_roles ur ON rp.role_id = ur.role_id
WHERE ur.user_id = [USER_ID] AND p.permission_key LIKE 'supply%';
```

3. 메뉴가 활성화되어 있는지 확인:
```sql
UPDATE sys_menus SET is_active = TRUE WHERE name = '지급품 관리';
```

### 라우트 접근 시 403 오류

1. 사용자 권한 확인
2. 미들웨어 설정 확인
3. 권한 키가 올바른지 확인

### API 호출 시 404 오류

1. URL 패턴이 정확한지 확인
2. HTTP 메서드가 올바른지 확인
3. 라우트 파일이 로드되는지 확인

## 추가 정보

### 관련 파일

- `routes/web.php` - 웹 라우트 정의
- `routes/api.php` - API 라우트 정의
- `database/seeds/15_supply_menus.sql` - 메뉴 시드 데이터
- `database/migrations/update_supply_menus_structure.sql` - 메뉴 업데이트 스크립트
- `app/Middleware/PermissionMiddleware.php` - 권한 미들웨어

### 참고 문서

- [개발자 가이드](./DEVELOPER_GUIDE.md)
- [권한 및 데이터 접근 제어](./PermissionAndDataAuthorization.md)
- [지급품 관리 설계 문서](../.kiro/specs/supply-management/design.md)
