# 테이블 사용 상세 분석 보고서

이 문서는 `hr_departments`와 `hr_employees` 테이블에 접근하는 소스 코드 파일을 심층 분석한 결과를 담고 있습니다. 각 테이블별로 어떤 파일의 어떤 메소드에서 사용되며, 해당 메소드가 수행하는 주요 역할이 무엇인지 기술합니다.

---

## 1. `hr_departments` 테이블 사용 분석

`hr_departments` 테이블은 부서 정보를 관리하며, 주로 `DepartmentRepository`를 통해 직접적으로 제어됩니다. 다른 리포지토리에서는 직원 정보에 부서명을 추가하거나, 부서별로 데이터를 필터링하기 위해 `JOIN` 구문으로 참조합니다.

### 1.1. `app/Repositories/DepartmentRepository.php`

이 파일은 `hr_departments` 테이블에 대한 주된 CRUD(생성, 읽기, 수정, 삭제) 작업을 수행하는 핵심 리포지토리입니다.

-   **`getAll()`**: 모든 부서 목록을 조회합니다.
-   **`findById(int $id)`**: ID로 특정 부서를 조회합니다.
-   **`findByParentId(int $parentId)`**: 상위 부서 ID에 속한 모든 하위 부서를 조회합니다.
-   **`findSubtreeIds(int $departmentId)`**: 재귀적 쿼리를 사용하여 특정 부서 및 그 아래 모든 하위 부서의 ID 목록을 조회합니다.
-   **`findAllWithEmployees()`**: 모든 부서 목록을 조회하면서, 각 부서에 소속된 직원 정보를 `LEFT JOIN`을 통해 함께 가져옵니다.
-   **`create(array $data)`**: 새로운 부서 정보를 테이블에 삽입합니다 (`INSERT`).
-   **`update(int $id, array $data)`**: 기존 부서의 정보를 수정합니다 (`UPDATE`).
-   **`delete(int $id)`**: 특정 부서를 테이블에서 삭제합니다 (`DELETE`). 단, 소속된 직원이 없을 경우에만 삭제를 수행합니다.

### 1.2. `app/Repositories/EmployeeRepository.php`

직원 정보를 조회할 때, 부서명을 함께 표시하기 위해 `hr_departments` 테이블을 참조합니다.

-   **`findById(int $id)`**, **`findByUserId(int $userId)`**, **`findAllActive()`**: 직원 정보를 조회하면서, 부서명을 가져오기 위해 `LEFT JOIN`을 사용합니다.
-   **`getAll(...)`**: 전체 직원 목록을 조회하면서 부서명을 가져오고, 부서 ID(`department_id`)를 기준으로 결과를 필터링하기 위해 `LEFT JOIN` 및 `WHERE` 절에서 사용합니다.

### 1.3. `app/Repositories/LeaveRepository.php`

연차 부여 및 신청 내역을 조회할 때, 직원의 소속 부서명을 표시하기 위해 `hr_departments` 테이블을 참조합니다.

-   **`getAllEntitlements(...)`**: 모든 직원의 연차 부여 현황을 조회할 때, 부서명을 표시하고 부서 ID로 필터링하기 위해 `LEFT JOIN`을 사용합니다.
-   **`findAll(...)`**: 관리자가 전체 연차 신청 목록을 조회할 때, 신청자의 부서명을 표시하고 부서 ID로 필터링하기 위해 `LEFT JOIN`을 사용합니다.

### 1.4. `app/Repositories/HolidayRepository.php`

휴일 정보를 관리하며, 특정 부서에만 적용되는 휴일을 구분하기 위해 `hr_departments` 테이블을 참조합니다.

-   **`getAll(...)`**: 전체 휴일 목록을 조회하면서, 각 휴일이 특정 부서에만 적용되는 경우 해당 부서의 이름을 표시하기 위해 `LEFT JOIN`을 사용합니다.

---

## 2. `hr_employees` 테이블 사용 분석

`hr_employees` 테이블은 직원 정보를 관리하는 핵심 테이블입니다. `EmployeeRepository`에서 직접 제어되며, 다른 여러 리포지토리에서는 직원 이름, 소속 부서 등 직원과 관련된 정보를 참조하기 위해 `JOIN` 구문으로 사용됩니다.

### 2.1. `app/Repositories/EmployeeRepository.php`

이 파일은 `hr_employees` 테이블에 대한 주된 CRUD 작업을 수행하는 핵심 리포지토리입니다.

-   **`findById(int $id)`**, **`findByUserId(int $userId)`**, **`getAll(...)`** 등 대부분의 조회 메소드: `hr_employees` 테이블을 주 테이블(`FROM`)로 사용하여 직원 정보를 조회합니다.
-   **`save(array $data)`**: 신규 직원을 생성하거나(`INSERT`) 기존 직원 정보를 수정합니다(`UPDATE`). 신규 생성 시 사번을 자동으로 계산하여 부여하는 로직이 포함되어 있습니다.
-   **`delete(int $id)`**: 직원 정보를 테이블에서 삭제합니다 (`DELETE`).
-   **`requestProfileUpdate(...)`**, **`applyProfileUpdate(...)`**, **`rejectProfileUpdate(...)`**: 사용자의 프로필 수정 요청 처리와 관련된 상태 및 임시 데이터를 수정합니다 (`UPDATE`).

### 2.2. `app/Repositories/DepartmentRepository.php`

부서 정보를 조회하거나 관리할 때, 소속된 직원 정보를 함께 참조하기 위해 `hr_employees` 테이블을 사용합니다.

-   **`findAllWithEmployees()`**: 부서 목록과 함께 소속된 직원 정보를 조회하기 위해 `LEFT JOIN` 합니다.
-   **`isEmployeeAssigned(int $id)`**: 특정 부서를 삭제하기 전, 해당 부서에 소속된 직원이 있는지 확인하기 위해 `SELECT` 쿼리를 실행합니다.
-   **`findAllWithViewers()`**: 부서별 조회 권한을 가진 직원의 이름을 가져오기 위해 `LEFT JOIN` 합니다.

### 2.3. `app/Repositories/LeaveRepository.php`

연차 관련 데이터를 처리할 때, 모든 데이터의 주체인 직원을 참조하기 위해 `hr_employees` 테이블을 사용합니다.

-   **`getAllEntitlements(...)`**: 전체 직원의 연차 부여 현황을 조회하기 위해 `hr_employees`를 주 테이블로 사용합니다.
-   **`findById(int $id)`**: 특정 연차 신청 건을 조회할 때, 신청자의 이름을 가져오기 위해 `JOIN` 합니다.
-   **`findAll(...)`**: 전체 연차 신청 목록을 조회할 때, 신청자의 상세 정보(이름, 부서, 직급)를 가져오기 위해 `JOIN` 합니다.

### 2.4. `app/Repositories/PositionRepository.php`

직급 정보를 삭제하기 전, 해당 직급에 할당된 직원이 있는지 확인하기 위해 `hr_employees` 테이블을 사용합니다.

-   **`isEmployeeAssigned(int $id)`**: 특정 직급을 삭제하기 전, 해당 직급에 소속된 직원이 있는지 확인하기 위해 `SELECT` 쿼리를 실행합니다.

### 2.5. `app/Repositories/EmployeeChangeLogRepository.php`

직원 정보 변경 이력을 조회할 때, 변경을 수행한 관리자의 이름을 가져오기 위해 `hr_employees` 테이블을 사용합니다.

-   **`findByEmployeeId(int $employeeId)`**: 특정 직원의 정보 변경 이력을 조회하면서, 변경을 수행한 관리자의 이름을 표시하기 위해 `LEFT JOIN` 합니다.

### 2.6. `app/Repositories/UserRepository.php`

시스템 사용자(`sys_users`)와 직원(`hr_employees`) 정보를 연결하고, 함께 조회하기 위해 `hr_employees` 테이블을 사용합니다.

-   **`getAllWithRoles(...)`**: 전체 사용자 목록을 조회할 때, 연결된 직원의 이름을 표시하기 위해 `LEFT JOIN` 합니다.
-   **`getUnlinkedEmployees(...)`**: 아직 시스템 사용자와 연결되지 않은 직원 목록을 조회하기 위해 `hr_employees`를 직접 조회합니다.
