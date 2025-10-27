# 상세 테이블 사용 현황 보고서 (Detailed Table Usage Report)

이 문서는 `hr_departments`와 `hr_employees` 테이블에 접근하는 모든 애플리케이션 코드의 목록과 그 역할을 상세히 기술합니다. 최근 `DataScopeService` 도입으로 인한 리팩토링 내용을 반영하여 재작성되었습니다.

---

## 1. `hr_departments` 테이블 접근 분석

`hr_departments` 테이블(부서 정보)에 대한 모든 접근은 `DepartmentRepository`를 통해 추상화되어 있으며, 서비스 계층에서는 이 리포지토리를 사용합니다.

### 1.1. `app/Repositories/DepartmentRepository.php`

- **역할**: `hr_departments` 테이블에 대한 모든 CRUD(생성, 읽기, 수정, 삭제) 작업을 직접 수행하는 유일한 클래스입니다.
- **주요 메소드**:
  - `getAll()`: 모든 부서 목록을 조회합니다.
  - `findById(int $id)`: 특정 ID의 부서 정보를 조회합니다.
  - `findByIds(array $ids)`: 여러 ID에 해당하는 부서 목록을 조회합니다. (권한 조회에 사용)
  - `findByParentId(int $parentId)`: 특정 부모 ID를 가진 자식 부서 목록을 조회합니다.
  - `findSubtreeIds(int $departmentId)`: 특정 부서를 포함한 모든 하위 부서의 ID 목록을 재귀적으로 조회합니다.
  - `create(array $data)`: 새로운 부서를 생성합니다.
  - `update(int $id, array $data)`: 기존 부서 정보를 수정합니다.
  - `delete(int $id)`: 부서를 삭제합니다.

### 1.2. `app/Services/DataScopeService.php`

- **역할**: `hr_departments` 테이블을 직접 조회하지는 않지만, `DepartmentRepository`를 사용하여 현재 로그인한 사용자가 **어떤 부서를 볼 수 있는지** 결정하는 핵심적인 역할을 합니다.
- **주요 메소드**:
  - `getVisibleDepartmentIdsForCurrentUser()`: `DepartmentRepository`를 호출하여 `hr_department_managers`와 `hr_department_view_permissions` 테이블을 기반으로 현재 사용자가 조회 가능한 모든 부서 ID 목록을 계산하여 반환합니다. 이 메소드가 모든 부서 관련 데이터 조회의 시작점입니다.

### 1.3. `app/Services/OrganizationService.php`

- **역할**: 부서 및 조직도 관련 비즈니스 로직을 처리합니다. `DepartmentRepository`를 사용하여 부서 데이터를 조회하고 조작합니다.
- **주요 메소드**:
  - `getOrganizationChartData()`: 조직도 데이터를 생성하기 위해 `DepartmentRepository`를 호출하여 모든 부서와 직원 정보를 가져온 뒤, `DataScopeService`의 결과로 필터링하여 최종 데이터를 조립합니다.
  - `createDepartment(array $data)`: 부서 생성을 위한 데이터를 받아 `DepartmentRepository::create`를 호출합니다.
  - `updateDepartment(int $id, array $data)`: 부서 수정을 위한 데이터를 받아 `DepartmentRepository::update`를 호출합니다.
  - `deleteDepartment(int $id)`: 부서 삭제를 위해 `DepartmentRepository::delete`를 호출합니다.

### 1.4. 기타 간접 접근 파일

- `app/Controllers/Api/OrganizationApiController.php`: `/api/organization` 엔드포인트 요청을 받아 `OrganizationService`의 메소드를 호출하여 최종적으로 부서 데이터를 조작합니다.
- `app/Models/Department.php`: Eloquent 모델 클래스로, 테이블 이름을 `hr_departments`로 정의합니다.

---

## 2. `hr_employees` 테이블 접근 분석

`hr_employees` 테이블(직원 정보)에 대한 접근 역시 대부분 `EmployeeRepository`를 통해 이루어지지만, 다른 리포지토리에서도 JOIN 구문을 통해 접근하는 경우가 있습니다.

### 2.1. `app/Repositories/EmployeeRepository.php`

- **역할**: `hr_employees` 테이블에 대한 주된 CRUD 작업을 직접 수행합니다.
- **주요 메소드**:
  - `findById(int $id)`: 특정 ID의 직원 정보를 조회합니다.
  - `findByUserId(int $userId)`: `sys_users` 테이블의 `user_id`를 통해 연결된 직원 정보를 조회합니다.
  - `getAll(array $filters, ?array $visibleDepartmentIds)`: `DataScopeService`로부터 받은 조회 가능한 부서 ID 목록(`visibleDepartmentIds`)을 기반으로 필터링된 직원 목록을 반환하는 핵심적인 데이터 조회 메소드입니다.
  - `save(array $data)`: 신규 직원을 생성하거나 기존 직원 정보를 수정합니다.
  - `delete(int $id)`: 직원을 삭제합니다.

### 2.2. `app/Services/AuthService.php`

- **역할**: 사용자가 로그인하거나 세션을 갱신할 때, `hr_employees` 테이블의 정보를 세션에 저장하는 매우 중요한 역할을 합니다.
- **주요 메소드**:
  - `_refreshSessionPermissions(array $user)`: 로그인한 사용자의 `employee_id`를 사용하여 `EmployeeRepository::findById`를 호출하고, 반환된 직원 객체 전체를 `$_SESSION['user']['employee']`에 저장합니다. 이 데이터는 `DataScopeService`가 권한을 계산하는 데 사용됩니다.

### 2.3. `app/Services/DataScopeService.php`

- **역할**: `hr_employees` 테이블 정보를 직접 조회하지는 않지만, `AuthService`가 세션에 저장한 직원 정보(`id`, `department_id`)를 **읽어서** 권한 계산의 기준으로 사용합니다.
- **주요 메소드**:
  - `getVisibleDepartmentIdsForCurrentUser()`: 세션의 직원 ID를 사용하여 `DepartmentRepository`를 통해 관리 부서 목록을 조회합니다.
  - `canManageEmployee(int $targetEmployeeId)`: 대상 직원의 부서가 현재 사용자의 가시성 범위에 포함되는지 확인하기 위해 `EmployeeRepository::findById`를 호출합니다.

### 2.4. `app/Repositories/DepartmentRepository.php` (JOIN을 통한 접근)

- **역할**: 조직도 등 부서와 직원을 함께 표시해야 하는 데이터를 조회할 때 `hr_employees` 테이블을 `LEFT JOIN` 합니다.
- **주요 메소드**:
  - `findAllWithEmployees()`: 조직도 생성을 위해 `hr_departments`와 `hr_employees`를 조인하여 모든 부서와 해당 부서에 소속된 직원 목록을 함께 가져옵니다.
  - `findAllWithViewers()`: 각 부서의 조회 권한을 가진 관리자(직원) 이름을 함께 표시하기 위해 `hr_department_managers`를 거쳐 `hr_employees`를 조인합니다.

### 2.5. 기타 간접 접근 파일

- `app/Services/EmployeeService.php`: 직원 관련 비즈니스 로직을 처리하며, 데이터 조작을 위해 `EmployeeRepository`를 호출합니다.
- `app/Controllers/Api/EmployeeApiController.php`: 직원 관련 API 요청을 받아 `EmployeeService` 또는 `DataScopeService`를 호출합니다.
- `app/Repositories/UserRepository.php`: 사용자 목록을 조회할 때 연결된 직원의 이름을 표시하기 위해 `hr_employees`를 `LEFT JOIN` 합니다.
- `app/Repositories/LeaveRepository.php`: 휴가 신청/승인 내역을 조회할 때 신청자/승인자의 이름을 표시하기 위해 `hr_employees`를 `JOIN` 합니다.
