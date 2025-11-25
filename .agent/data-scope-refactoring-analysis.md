# 데이터 스코프 리팩토링 - 현재 기능 정리

## 📋 목차
1. [개요](#개요)
2. [현재 구조 분석](#현재-구조-분석)
3. [DataScopeService 기능](#datascopeservice-기능)
4. [적용 현황](#적용-현황)
5. [미적용 현황](#미적용-현황)
6. [리팩토링 목표](#리팩토링-목표)
7. [개선 방안](#개선-방안)

---

## 개요

### 현재 상태
- **파일 위치**: `app/Services/DataScopeService.php`
- **목적**: 사용자 권한에 따른 데이터 조회 범위를 중앙 집중적으로 관리
- **핵심 개념**: 부서 기반 계층적 권한 관리

### 권한 체계
1. **전체 조회 권한** (`employee.manage`): 모든 부서 데이터 조회 가능
2. **부서 관리자 권한** (`hr_department_managers`): 특정 부서 및 하위 부서 조회
3. **부서 간 조회 권한** (`hr_department_view_permissions`): 다른 부서 데이터 조회

---

## 현재 구조 분석

### DataScopeService 클래스 구조

```php
namespace App\Services;

use App\Core\SessionManager;
use App\Core\Database;

class DataScopeService
{
    private SessionManager $sessionManager;
    private Database $db;
}
```

### 주요 메서드

#### 1. 핵심 조회 메서드
```php
public function getVisibleDepartmentIdsForCurrentUser(): ?array
```
- **반환값**: 
  - `null`: 전체 조회 가능 (employee.manage 권한)
  - `[]`: 조회 불가 (권한 없음)
  - `[1, 2, 3, ...]`: 조회 가능한 부서 ID 배열
- **로직**:
  1. 세션에서 사용자 정보 확인
  2. `employee.manage` 권한 체크 → null 반환
  3. 부서 관리자 권한 조회 (`hr_department_managers`)
  4. 부서 간 조회 권한 조회 (`hr_department_view_permissions`)
  5. 각 권한에 대해 하위 부서 포함 (재귀 CTE 사용)

#### 2. Scope 적용 메서드들

##### A. applyEmployeeScope()
```php
public function applyEmployeeScope(array $queryParts, string $employeeTableAlias = 'e'): array
```
- **적용 대상**: `hr_employees` 테이블
- **조건**: `{alias}.department_id IN (...)` 추가
- **사용 패턴**:
```php
$queryParts = [
    'sql' => "SELECT e.* FROM hr_employees e",
    'params' => [],
    'where' => []
];
$queryParts = $this->dataScopeService->applyEmployeeScope($queryParts, 'e');
```

##### B. applyVehicleScope()
```php
public function applyVehicleScope(array $queryParts, string $vehicleTableAlias = 'v'): array
```
- **적용 대상**: `vehicles` 테이블
- **조건**: `{alias}.department_id IN (...)` 추가

##### C. applyDepartmentScope()
```php
public function applyDepartmentScope(array $queryParts, string $departmentTableAlias = 'd'): array
```
- **적용 대상**: `hr_departments` 테이블
- **조건**: `{alias}.id IN (...)` 추가

##### D. applyHolidayScope()
```php
public function applyHolidayScope(array $queryParts, string $holidayTableAlias = 'h'): array
```
- **적용 대상**: `hr_holidays` 테이블
- **특징**: 전체 휴일(department_id IS NULL) 포함
- **조건**: `({alias}.department_id IS NULL OR {alias}.department_id IN (...))`

##### E. applyUserScope()
```php
public function applyUserScope(array $queryParts, string $userTableAlias = 'u', string $employeeTableAlias = 'e'): array
```
- **적용 대상**: `sys_users` 테이블 (직원과 JOIN)
- **특징**: 직원 정보가 없는 사용자도 포함
- **조건**: `({employeeAlias}.department_id IN (...) OR {userAlias}.employee_id IS NULL)`

#### 3. 권한 확인 메서드

```php
public function canManageEmployee(int $targetEmployeeId): bool
```
- **용도**: 특정 직원을 관리할 수 있는지 확인
- **로직**:
  1. `employee.manage` 권한 체크
  2. 본인 여부 체크
  3. 대상 직원의 부서가 조회 가능한 부서에 포함되는지 체크

#### 4. Private Helper 메서드들

```php
private function findEmployeeById(int $id)
private function findDepartmentIdsWithEmployeeViewPermission(int $employeeId): array
private function findDepartmentViewPermissionIds(int $departmentId): array
private function findSubtreeIds(int $departmentId): array
```
- **목적**: 순환 의존성 방지를 위해 직접 DB 조회
- **특징**: Repository에 의존하지 않음

---

## DataScopeService 기능

### 1. 조회 가능 부서 계산
| 권한 유형 | 테이블 | 설명 |
|---------|-------|------|
| 전체 권한 | Permission | `employee.manage` 권한 보유 시 |
| 부서 관리자 | `hr_department_managers` | 특정 직원에게 부여된 부서 관리 권한 |
| 부서 간 조회 | `hr_department_view_permissions` | 부서 단위로 다른 부서 조회 권한 |

### 2. 하위 부서 포함 (재귀 CTE)
```sql
WITH RECURSIVE DepartmentHierarchy AS (
    SELECT id FROM hr_departments WHERE id = :department_id
    UNION ALL
    SELECT d.id FROM hr_departments d
    INNER JOIN DepartmentHierarchy dh ON d.parent_id = dh.id
)
SELECT id FROM DepartmentHierarchy
```

### 3. 쿼리 적용 패턴
```php
// 1. queryParts 구조 준비
$queryParts = [
    'sql' => "SELECT ... FROM table t",
    'params' => [],
    'where' => []
];

// 2. Scope 적용
$queryParts = $this->dataScopeService->applyXxxScope($queryParts, 't');

// 3. 추가 필터 적용
if (!empty($filters['something'])) {
    $queryParts['where'][] = "t.something = :something";
    $queryParts['params'][':something'] = $filters['something'];
}

// 4. WHERE 절 조립
if (!empty($queryParts['where'])) {
    $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
}

// 5. 쿼리 실행
return $this->db->query($queryParts['sql'], $queryParts['params']);
```

---

## 적용 현황

### ✅ DataScopeService를 사용하는 Repository

#### 1. EmployeeRepository
- **의존성 주입**: ✅
- **적용 메서드**:
  - `getAll()`: `applyEmployeeScope()` 사용
  - `getActiveEmployees()`: `applyEmployeeScope()` 사용
  - `findUnlinked()`: ❌ 미적용

#### 2. DepartmentRepository
- **의존성 주입**: ✅
- **적용 메서드**:
  - `getAll()`: `applyDepartmentScope()` 사용
  - `getAllAsArray()`: `applyDepartmentScope()` 사용
  - `findAllWithEmployees()`: `applyDepartmentScope()` 사용
  - `findAllWithViewers()`: `applyDepartmentScope()` 사용

#### 3. UserRepository
- **의존성 주입**: ✅
- **적용 메서드**:
  - `getAllWithRoles()`: `applyUserScope()` 사용
  - `findUsersWithoutEmployeeRecord()`: ❌ 미적용
  - `getUnlinkedEmployees()`: ❌ 미적용

#### 4. HolidayRepository
- **의존성 주입**: ✅
- **적용 메서드**:
  - `getAll()`: `applyHolidayScope()` 사용
  - `findForDateRange()`: ❌ 미적용 (부서 필터링은 하지만 Scope 미사용)

#### 5. LeaveRepository
- **의존성 주입**: ✅
- **적용 메서드**:
  - `getEmployeeApplications()`: `applyEmployeeScope()` 사용
  - 기타 메서드: ❌ 미적용 (직원 ID 기반 조회)

#### 6. SupplyStockRepository, SupplyPurchaseRepository, SupplyItemRepository, SupplyCategoryRepository, SupplyPlanRepository
- **의존성 주입**: ✅
- **적용 메서드**: PartialScope 또는 다른 방식 사용

---

## 미적용 현황

### ❌ DataScopeService를 사용하지 않는 Repository

#### 1. VehicleRepository
- **현재 상태**:
  - 의존성 주입: ❌
  - 대신 Service나 Controller에서 필터 전달
  - `findAll()` 메서드에서 `visible_department_ids` 필터 사용

```php
// VehicleRepository::findAll() 현재 구현
if (!empty($filters['visible_department_ids'])) {
    $deptIds = $filters['visible_department_ids'];
    $placeholders = [];
    foreach ($deptIds as $i => $id) {
        $key = ":vis_dept_$i";
        $placeholders[] = $key;
        $params[$key] = $id;
    }
    $visibilityConditions[] = "v.department_id IN (" . implode(',', $placeholders) . ")";
}
```

**문제점**:
- 수동으로 부서 ID 배열을 필터로 전달받아 처리
- DataScopeService의 표준화된 방식 미사용
- Controller/Service에서 직접 부서 ID를 계산하여 전달

#### 2. 기타 Repository들
다음 Repository들은 DataScopeService를 주입받지 않음:
- `VehicleWorkRepository`
- `VehicleInspectionRepository`
- `VehicleMaintenanceRepository`
- `VehicleConsumableRepository`
- `LitteringRepository`
- `WasteCollectionRepository`
- `HumanResourceRepository`
- `PositionRepository`
- `RoleRepository`
- `MenuRepository`
- `LogRepository`
- `EmployeeChangeLogRepository`

---

## 리팩토링 목표

### 1. 일관성 확보
- [ ] 모든 Repository에 DataScopeService 패턴 통일
- [ ] VehicleRepository 리팩토링 (우선순위 높음)
- [ ] 미적용 메서드에 Scope 적용

### 2. 확장성 개선
- [ ] 새로운 Scope 타입 추가 용이하도록 구조 개선
- [ ] 다중 테이블 JOIN 시 Scope 적용 방법 표준화

### 3. 성능 최적화
- [ ] 불필요한 재귀 쿼리 최소화
- [ ] 부서 트리 캐싱 고려

### 4. 유지보수성 향상
- [ ] 명확한 문서화
- [ ] 단위 테스트 작성
- [ ] 에러 로깅 개선

---

## 개선 방안

### 1. VehicleRepository 리팩토링

#### Before:
```php
class VehicleRepository
{
    private Database $db;
    
    public function __construct(Database $db)
    {
        $this->db = $db;
    }
    
    public function findAll(array $filters = []): array
    {
        // 수동으로 visible_department_ids 필터 처리
        if (!empty($filters['visible_department_ids'])) {
            // ... 복잡한 로직
        }
    }
}
```

#### After:
```php
class VehicleRepository
{
    private Database $db;
    private DataScopeService $dataScopeService;
    
    public function __construct(Database $db, DataScopeService $dataScopeService)
    {
        $this->db = $db;
        $this->dataScopeService = $dataScopeService;
    }
    
    public function findAll(array $filters = []): array
    {
        $queryParts = [
            'sql' => "SELECT v.*, d.name as department_name, e.name as driver_name
                      FROM vehicles v
                      LEFT JOIN hr_departments d ON v.department_id = d.id
                      LEFT JOIN hr_employees e ON v.driver_employee_id = e.id",
            'params' => [],
            'where' => []
        ];
        
        // DataScopeService 적용
        $queryParts = $this->dataScopeService->applyVehicleScope($queryParts, 'v');
        
        // 추가 필터 적용
        if (!empty($filters['department_id'])) {
            $queryParts['where'][] = "v.department_id = :department_id";
            $queryParts['params'][':department_id'] = $filters['department_id'];
        }
        
        // ... 나머지 필터 처리
        
        if (!empty($queryParts['where'])) {
            $queryParts['sql'] .= " WHERE " . implode(" AND ", $queryParts['where']);
        }
        
        $queryParts['sql'] .= " ORDER BY v.created_at DESC";
        
        return $this->db->query($queryParts['sql'], $queryParts['params']);
    }
}
```

### 2. 새로운 Scope 메서드 추가 가이드

#### 패턴:
```php
public function applyXxxScope(array $queryParts, string $xxxTableAlias = 'x'): array
{
    $visibleDepartmentIds = $this->getVisibleDepartmentIdsForCurrentUser();
    
    if ($visibleDepartmentIds === null) {
        return $queryParts; // 전체 조회 가능
    }
    
    if (empty($visibleDepartmentIds)) {
        $queryParts['where'][] = "1=0"; // 조회 불가
    } else {
        $inClause = implode(',', array_map('intval', $visibleDepartmentIds));
        $queryParts['where'][] = "{$xxxTableAlias}.department_id IN ($inClause)";
    }
    
    return $queryParts;
}
```

### 3. Controller/Service 레이어 수정

#### Before:
```php
// VehicleApiController 또는 Service
$visibleDeptIds = $this->dataScopeService->getVisibleDepartmentIdsForCurrentUser();
$filters['visible_department_ids'] = $visibleDeptIds;
$vehicles = $this->vehicleRepository->findAll($filters);
```

#### After:
```php
// VehicleApiController 또는 Service
// DataScopeService는 Repository 내부에서 자동 적용됨
$vehicles = $this->vehicleRepository->findAll($filters);
```

### 4. 특수 케이스 처리

#### 운전자 본인 차량 조회 (현재 VehicleRepository에 있는 로직)
```php
public function applyVehicleScope(array $queryParts, string $vehicleTableAlias = 'v', bool $includeDriverVehicles = true): array
{
    $visibleDepartmentIds = $this->getVisibleDepartmentIdsForCurrentUser();
    
    if ($visibleDepartmentIds === null) {
        return $queryParts;
    }
    
    $conditions = [];
    
    if (empty($visibleDepartmentIds)) {
        // 부서 조회 권한은 없지만, 본인 차량은 볼 수 있도록
        if ($includeDriverVehicles) {
            $user = $this->sessionManager->get('user');
            $employeeId = $user['employee_id'] ?? null;
            if ($employeeId) {
                $conditions[] = "{$vehicleTableAlias}.driver_employee_id = " . intval($employeeId);
            }
        }
        if (empty($conditions)) {
            $queryParts['where'][] = "1=0";
        }
    } else {
        $inClause = implode(',', array_map('intval', $visibleDepartmentIds));
        $conditions[] = "{$vehicleTableAlias}.department_id IN ($inClause)";
        
        if ($includeDriverVehicles) {
            $user = $this->sessionManager->get('user');
            $employeeId = $user['employee_id'] ?? null;
            if ($employeeId) {
                $conditions[] = "{$vehicleTableAlias}.driver_employee_id = " . intval($employeeId);
            }
        }
    }
    
    if (!empty($conditions)) {
        $queryParts['where'][] = "(" . implode(" OR ", $conditions) . ")";
    }
    
    return $queryParts;
}
```

### 5. 기타 개선 사항

#### A. 에러 로깅 개선
```php
public function getVisibleDepartmentIdsForCurrentUser(): ?array
{
    $user = $this->sessionManager->get('user');
    
    if (!$user) {
        error_log("[DataScopeService] No user in session");
        return [];
    }
    
    // ... 나머지 로직
}
```

#### B. 캐싱 고려
```php
private ?array $cachedVisibleDepartmentIds = null;

public function getVisibleDepartmentIdsForCurrentUser(): ?array
{
    if ($this->cachedVisibleDepartmentIds !== null) {
        return $this->cachedVisibleDepartmentIds;
    }
    
    // ... 계산 로직
    
    $this->cachedVisibleDepartmentIds = $result;
    return $result;
}
```

---

## 요약

### 현재 상태
- ✅ 핵심 기능 구현 완료
- ✅ 일부 Repository에 적용됨
- ⚠️ 일관성 부족 (VehicleRepository 등은 다른 방식 사용)
- ⚠️ 문서화 부족

### 다음 단계
1. **VehicleRepository 리팩토링** (최우선)
2. **미적용 메서드에 Scope 적용**
3. **새로운 Scope 메서드 추가** (필요시)
4. **단위 테스트 작성**
5. **문서 업데이트**

### 예상 작업량
- VehicleRepository 리팩토링: 2-3 시간
- 기타 Repository 적용: 4-6 시간
- 테스트 작성: 3-4 시간
- **총 예상 시간: 9-13 시간**
