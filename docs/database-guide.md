# 데이터베이스 가이드

## 1. 네이밍 컨벤션

-   **테이블 접두사**: `hr_` (인사 관련), `sys_` (시스템 관련) 접두사를 사용합니다.
-   **예외**: `waste_collections`, `illegal_disposal_cases2` 테이블은 접두사 규칙에서 제외됩니다.

## 2. 스키마 및 시딩

-   **스키마**: 모든 테이블 구조는 `database/schema.sql` 파일에 정의됩니다.
-   **시드 데이터**: `database/seeds/` 디렉토리 내의 `.sql` 파일들이 순차적으로 실행됩니다.
-   **자동화**: `scripts/seed.php` 스크립트를 통해 스키마 생성과 시딩을 자동화합니다.
-   **주의사항**:
    -   시드 파일이 `schema.sql`에 존재하지 않는 컬럼을 참조할 경우, 데이터 손실을 방지하기 위해 해당 컬럼을 `schema.sql`에 `NULL`을 허용하는 상태로 다시 추가하는 것을 원칙으로 합니다.
    -   특정 외부 프로세스가 파일명으로 시드 파일을 직접 실행하므로, 불필요해진 시드 파일이라도 삭제하는 대신 빈 파일을 유지하여 'file not found' 오류를 방지해야 합니다. (예: `02_positions.sql`)

## 3. 공통 컬럼 설계

-   **감사 (Auditing)**: 생성자(`created_by`), 처리자(`completed_by` 등) 컬럼에는 `employee_id`를 저장하여 모든 데이터 변경 이력을 추적합니다.
-   **소프트 삭제 (Soft Delete)**: `status` 컬럼을 `deleted`로 변경하고 `deleted_at` 타임스탬프를 기록합니다. 데이터를 조회할 때는 반드시 `WHERE deleted_at IS NULL` 조건을 포함해야 합니다.
-   **워크플로우 추적**: 상태 변경을 추적하는 컬럼은 `processed_by`, `processed_at`와 같이 상태 값과 의미가 명확하게 연결되도록 명명합니다.

## 4. 쿼리 작성

-   `IN()` 절을 사용할 때는 SQL 인젝션 공격을 방지하기 위해 반드시 파라미터화된 쿼리를 사용해야 합니다.
-   부서와 같이 계층적인 데이터를 조회할 때는 `DepartmentRepository`에서 사용하는 재귀적 CTE(Common Table Expression) 방식을 활용하여 효율성을 높입니다.
