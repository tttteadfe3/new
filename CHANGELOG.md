# 통합 변경 이력 (Changelog)

이 문서는 프로젝트의 주요 변경 사항, 특히 기존 코드베이스에 영향을 줄 수 있는 중요한 수정 내역을 기록합니다. 모든 개발 에이전트는 코드 변경 시 이 문서를 참조하고, 자신의 변경 사항을 아래 형식에 맞게 기록해야 합니다.

---

## [버전 - YYYY-MM-DD]

### ✨ 새로운 기능 (Features)
- **(설명)**: (작업 내용에 대한 상세 설명)
- **(설명)**: (작업 내용에 대한 상세 설명)

### 🐛 버그 수정 (Bug Fixes)
- **(설명)**: (작업 내용에 대한 상세 설명)

### ♻️ 리팩토링 (Refactoring)
- **클래스명::메서드명() 변경**:
  - **변경 이유**: (예: 성능 개선, 로직 명확화 등)
  - **영향 범위**: (해당 메서드를 호출하던 모든 파일 목록)
  - **함께 수정된 파일**: (변경에 따라 함께 수정한 파일 목록)

### 💥 주요 변경 사항 (Breaking Changes)
- **(설명)**: (하위 호환성을 깨뜨리는 변경 사항에 대한 상세 설명)

---

### 예시:

## [1.0.0 - 2025-10-25]

### ♻️ 리팩토링 (Refactoring)
- **`EmployeeService::getEmployee()` 메서드 시그니처 변경**:
  - **변경 이유**: 직원 ID 외에 사번(employee\_number)으로도 조회할 수 있도록 파라미터 추가.
  - **영향 범위**:
    - `app/Controllers/Web/EmployeeController.php`
    - `app/Controllers/Api/EmployeeApiController.php`
  - **함께 수정된 파일**:
    - `app/Controllers/Web/EmployeeController.php`
    - `app/Controllers/Api/EmployeeApiController.php`
