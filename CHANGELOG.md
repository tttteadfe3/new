# 통합 변경 이력 (Changelog)

이 문서는 프로젝트의 주요 변경 사항, 특히 기존 코드베이스에 영향을 줄 수 있는 중요한 수정 내역을 기록합니다. 모든 개발 에이전트는 코드 변경 시 이 문서를 참조하고, 자신의 변경 사항을 아래 형식에 맞게 기록해야 합니다.

---
## [1.4.7 - 2025-10-28]

### ✨ 새로운 기능 (Features)
- **무단투기 관리(`littering/manage`) 삭제 기능 확장**:
  - **설명**: '승인 대기(처리완료)' 상태의 항목 상세 보기에도 '삭제' 버튼을 추가하여, 모든 관리 단계에서 항목을 삭제(소프트 삭제)할 수 있도록 워크플로우를 개선했습니다.
  - **영향 범위**: `public/assets/js/pages/littering-manage.js`
- **삭제된 무단투기(`littering/deleted`) 페이지 기능 및 UI 전면 개편**:
  - **설명**: `littering/manage` 페이지와 동일한 사용자 경험을 제공하기 위해 UI를 분할 화면(지도+목록) 레이아웃으로 변경하고, 모든 데이터 로딩을 AJAX 비동기 방식으로 구현했습니다.
  - **변경 내용**:
    - 왼쪽 사이드바에 '확인 전 삭제'와 '처리 후 삭제' 목록을 분리하여 표시합니다.
    - 목록의 항목 클릭 시, 오른쪽에 상세 정보가 표시되고 지도에 해당 위치의 마커가 나타나도록 하는 전체 인터랙티브 기능을 구현했습니다.
    - 상세 정보 보기에서, 항목이 삭제되기 전의 상태('대기삭제' 또는 '처리삭제')에 따라 표시되는 사진의 개수(각각 2개, 3개)를 동적으로 조절합니다.
  - **영향 범위**: `app/Controllers/Web/LitteringController.php`, `app/Views/pages/littering/deleted.php`, `public/assets/js/pages/littering-deleted-admin.js`, `app/Controllers/Api/LitteringAdminApiController.php`

### ♻️ 리팩토링 (Refactoring)
- **무단투기 삭제 상태(status) 값 세분화**:
  - **변경 이유**: 사용자가 삭제된 항목이 어떤 관리 단계에서 삭제되었는지 명확하게 구분할 수 있도록 데이터 구조를 개선했습니다.
  - **변경 내용**: `illegal_disposal_cases2` 테이블의 `status` 값을 기존의 '삭제' 대신, 삭제 시점의 상태를 기록하는 '대기삭제'와 '처리삭제'로 세분화했습니다. 이를 위해 모든 관련 백엔드 로직(Repository, Service, API Controller)을 수정했으며, 기존 '삭제' 데이터를 '대기삭제'로 전환하는 마이그레이션 스크립트를 추가했습니다.
  - **영향 범위**: `app/Repositories/LitteringRepository.php`, `app/Services/LitteringService.php`, `app/Controllers/Api/LitteringAdminApiController.php`, `database/migrations/20251028_update_deleted_status.sql` (신규)
- **사진 보기 기능 `glightbox`로 통일**:
  - **변경 이유**: 사이트 전체의 사진 보기 사용자 경험을 일관성 있게 통일하기 위함입니다.
  - **변경 내용**: `littering/manage` 및 `littering/deleted` 페이지의 사진 보기 방식을 기존의 Bootstrap 모달에서 `littering/history` 페이지와 동일한 `glightbox` 라이브러리를 사용하도록 변경했습니다.
  - **영향 범위**: `app/Controllers/Web/LitteringController.php`, `public/assets/js/pages/littering-manage.js`, `public/assets/js/pages/littering-deleted-admin.js`, `app/Views/pages/littering/manage.php`, `app/Views/pages/littering/deleted.php`

### 🐛 버그 수정 (Bug Fixes)
- **무단투기 항목 복원 로직 오류 수정**:
  - **문제**: `littering/deleted` 페이지에서 '처리삭제' 상태의 항목을 복원할 때, '처리완료'가 아닌 '대기' 상태로 잘못 돌아가는 버그.
  - **원인**: `LitteringRepository`의 `restore` 메소드가 복원 시 상태를 무조건 '대기'로 하드코딩하고 있었음.
  - **수정**: `restore` 메소드가 복원하려는 항목의 현재 상태('대기삭제' 또는 '처리삭제')를 확인하고, 그에 맞는 원래 상태('대기' 또는 '처리완료')로 올바르게 복원하도록 로직을 수정했습니다.
  - **영향 범위**: `app/Repositories/LitteringRepository.php`
- **복원 확인 창 메시지 오류 수정**:
  - **문제**: `littering/deleted` 페이지에서 항목 복원 시, 확인 창에 실제 복원될 상태와 관계없이 항상 '(상태: '대기')'라고 부정확한 메시지가 표시되는 문제.
  - **수정**: `littering-deleted-admin.js`의 `restoreReport` 함수를 수정하여, 복원될 항목의 실제 최종 상태('대기' 또는 '처리완료')를 확인하고 이를 확인 창 메시지에 동적으로 반영하도록 변경했습니다.
  - **영향 범위**: `public/assets/js/pages/littering-deleted-admin.js`

## [1.4.6 - 2025-10-28]

### 📝 문서 (Documentation)
- **프로젝트 문서 전체 재구성**:
  - **변경 이유**: 신규 개발자가 프로젝트를 쉽게 이해하고 온보딩할 수 있도록 분산된 문서를 정리하고, 각 문서의 역할을 명확히 하여 접근성을 개선했습니다.
  - **변경 내용**:
    - **`README.md` 재작성**: 프로젝트의 핵심 정보(개요, 설치 방법)만을 담은 요약본으로 재작성하고, 상세 정보는 `docs/` 디렉토리의 전문 가이드로 연결하는 중앙 허브 역할을 하도록 변경했습니다.
    - **상세 가이드 문서 재구성**: 기존 `README.md`에 있던 상세 내용을 `docs/` 디렉토리의 각 목적에 맞는 파일로 이전하고 보강했습니다.
        - 아키텍처(요청 흐름, 디렉토리 구조) 관련 내용은 `docs/architecture.md`로 통합했습니다.
        - 백엔드 및 프론트엔드 개발 절차는 각각 `docs/backend-guide.md`, `docs/frontend-guide.md`로 이전하여 내용을 보강했습니다.
    - **`ai.md` 재배치**: AI 에이전트 전용 가이드였던 `ai.md` 파일을 `docs/AGENT_GUIDE.md`로 이동하여 문서 구조를 단순화했습니다.
  - **영향 범위**: `README.md`, `ai.md`, `docs/architecture.md`, `docs/backend-guide.md`, `docs/frontend-guide.md`
  - **함께 수정된 파일**: `docs/AGENT_GUIDE.md` (신규)

## [1.4.5 - 2025-10-28]

### ✨ 새로운 기능 (Features)
- **사이트 전체 활동 로깅 기능 추가**:
  - **설명**: 모든 사용자의 사이트 이용 행위(페이지 접근, API 호출)를 추적하고 감사할 수 있도록 `sys_activity_logs` 테이블에 기록하는 기능을 구현했습니다.
  - **변경 내용**:
    - `ActivityLogger` 서비스를 확장하여 페이지 접근(`logPageAccess`) 및 API 호출(`logApiCall`)을 기록하는 메서드를 추가했습니다.
    - `Web/BaseController`와 `Api/BaseApiController`의 생성자에서 `ActivityLogger`를 호출하여, 모든 웹 페이지 요청과 API 호출이 자동으로 기록되도록 수정했습니다.
    - 로그인, 정적 에셋(.js, .css) 등 불필요한 요청은 로그 대상에서 제외하여 효율성을 높였습니다.
  - **영향 범위**: `app/Services/ActivityLogger.php`, `app/Controllers/Web/BaseController.php`, `app/Controllers/Api/BaseApiController.php`

### ♻️ 리팩토링 (Refactoring)
- **활동 로그 중복 제거**:
  - **변경 이유**: '메뉴 접근' 로그와 '페이지 접근' 로그가 중복으로 기록되는 문제를 해결하고 코드를 단순화했습니다.
  - **변경 내용**:
    - `ViewDataService`에서 `logMenuAccess` 메서드 호출을 제거했습니다.
    - `ActivityLogger` 서비스에서 더 이상 사용되지 않는 `logMenuAccess` 메서드를 삭제하여 `logPageAccess`로 로깅을 일원화했습니다.
  - **영향 범위**: `app/Services/ViewDataService.php`, `app/Services/ActivityLogger.php`

## [1.4.4 - 2025-10-28]

### ♻️ 리팩토링 (Refactoring)
- **직원 관리 워크플로우 개선**:
  - **변경 이유**: 사용자 피드백에 따라 퇴사 처리 기능의 위치를 조정하여 워크플로우를 개선했습니다.
  - **변경 내용**: 기존에 직원 상세 정보 보기 화면에 노출되어 있던 '퇴사 처리' 버튼을 '수정하기' 버튼 클릭 후 진입하는 수정 폼 화면으로 이동시켰습니다. 이를 통해 정보 수정과 퇴사 처리를 같은 맥락의 작업으로 그룹화하여 UI를 더 직관적으로 만들었습니다.
  - **영향 범위**: `public/assets/js/pages/employees.js`

## [1.4.3 - 2025-10-28]

### ♻️ 리팩토링 (Refactoring)
- **직원 관리 상세 정보 UI 개선**:
  - **변경 이유**: 사용자 피드백에 따라 정보 확인의 편의성을 높이기 위해 UI를 개선했습니다.
  - **변경 내용**: 기존에는 '수정하기' 버튼을 눌러야만 탭으로 표시되었던 '연락처/주소', '비상 연락처', '의류 사이즈' 정보를, 수정 전의 일반 상세 보기 화면에서도 항상 탭 형태로 표시되도록 변경했습니다. 이를 통해 모든 정보를 한 화면에서 직관적으로 확인할 수 있게 되었습니다.
  - **영향 범위**: `public/assets/js/pages/employees.js`

## [1.4.2 - 2025-10-28]

### ✨ 새로운 기능 (Features)
- **직원 정보 변경 이력 표시 기능 복원**:
  - **설명**: 직원 관리 페이지의 UI가 리팩토링되는 과정에서 누락되었던 '변경 내역' 탭과 그 내용을 복원했습니다.
  - **변경 내용**:
    - 직원을 선택했을 때 상세 정보 뷰에 '변경 내역' 탭을 다시 추가했습니다.
    - 해당 탭을 클릭하면 `hr_employee_change_logs` 테이블에 기록된 모든 변경 이력(프로필 수정, 인사 발령 등)이 조회되어 시간순으로 표시됩니다.
  - **영향 범위**: `public/assets/js/pages/employees.js`

### 🐛 버그 수정 (Bug Fixes)
- **직원 정보 저장 유효성 검사 오류 수정**:
  - **문제**: 직원 정보 수정 후 저장 시, '잘못된 직원 데이터' 오류가 발생하며 저장이 실패하는 문제.
  - **원인**: `EmployeeService`의 유효성 검사 로직이 수정 불가능한 필드(부서, 직급 등)까지 검사하면서, 해당 필드들이 폼 데이터에 포함되지 않아 오류를 발생시켰음.
  - **수정**: `EmployeeService::validateEmployeeData` 메서드에서 부서, 직급 등 인사 발령을 통해 변경되어야 하는 필드에 대한 유효성 검사를 제거하여, 프로필 수정 시에는 연락처, 주소 등 허용된 필드만 검사하도록 수정했습니다.
  - **영향 범위**: `app/Services/EmployeeService.php`

## [1.4.1 - 2025-10-28]

### 🐛 버그 수정 (Bug Fixes)
- **직원 관리 페이지 다중 버그 수정**:
  - **설명**: 직원 관리 페이지의 대규모 UI 리팩토링 이후 발생한 여러 JavaScript 오류와 UI 표시 문제를 해결했습니다.
  - **변경 내용**:
    - **승인/반려 기능 오류**: 리팩토링 과정에서 누락되었던 `approveProfileUpdate` 및 `rejectProfileUpdate` 함수를 `employees.js`에 복원하여 기능이 정상적으로 동작하도록 수정했습니다.
    - **편집 폼 드롭다운 누락**: 직원 정보 '수정' 시, 부서 및 직급 선택 드롭다운이 표시되지 않던 문제를 수정했습니다.
    - **수정 요청 표시 오류**: 프로필 변경 요청 승인 화면에서, 기존 값이 빈 문자열일 경우 `<i>없음</i>`으로 잘못 표시되던 문제를 수정했습니다.
    - **저장 시 페이지 새로고침**: 정보 저장 시 페이지 전체가 새로고침되는 현상을 해결하고, 안정적인 AJAX 처리를 위해 폼 `submit` 이벤트 리스너를 동적으로 바인딩하도록 로직을 개선했습니다.
  - **영향 범위**: `public/assets/js/pages/employees.js`

## [1.4.0 - 2025-10-28]

### ✨ 새로운 기능 (Features)
- **직원 프로필 변경 요청 관리 기능 추가**:
  - **설명**: 관리자가 직원의 프로필 정보 변경 요청을 직접 확인하고 승인 또는 반려할 수 있는 UI를 추가했습니다.
  - **변경 내용**:
    - 직원 목록에서 수정 요청이 있는 직원을 '수정 요청' 배지로 명확하게 표시합니다.
    - 해당 직원을 선택하면, 상세 정보 뷰에 기존 정보와 변경 요청된 정보를 나란히 비교하여 보여줍니다.
    - 상세 정보 뷰에 '승인' 및 '반려' 버튼을 추가하고, 기존 API와 연동하여 관리자가 즉시 요청을 처리할 수 있도록 했습니다.
  - **영향 범위**: `public/assets/js/pages/employees.js`

### ♻️ 리팩토링 (Refactoring)
- **직원 관리 페이지 UI/UX 개선**:
  - **변경 이유**: 사용자 요청에 따라 직원 상세 정보 확인 및 수정 워크플로우를 개선하고, 불필요한 UI 요소를 제거하여 사용성을 높였습니다.
  - **변경 내용**:
    - 직원 상세 정보 뷰에서 '목록으로'와 '인사발령' 버튼을 제거하여 UI를 간소화했습니다.
    - 직원 정보 수정 폼에 '비상 연락처'와 '의류 사이즈' 탭 UI를 복원하고, 관련 모든 필드가 정상적으로 저장되도록 `handleFormSubmit` 로직을 수정했습니다.
  - **영향 범위**: `public/assets/js/pages/employees.js`

## [1.3.5 - 2025-10-27]

### ♻️ 리팩토링 (Refactoring)
- **직원 관리 페이지 UI/UX 개선**:
  - **변경 이유**: 사용자 경험을 개선하고 워크플로우를 명확하게 하기 위해 직원 관리 페이지의 인터페이스를 리팩토링했습니다.
  - **변경 내용**:
    - 직원 목록에서 '수정', '인사발령', '퇴사처리' 버튼을 제거하고 '정보 보기' 버튼으로 통합했습니다.
    - '정보 보기' 버튼 클릭 시 직원의 모든 상세 정보가 담긴 모달이 표시됩니다.
    - 정보 보기 모달 내에서 '수정하기'와 '퇴사 처리' 기능을 수행할 수 있도록 버튼을 배치하여 관련 작업을 한 곳에서 처리할 수 있도록 개선했습니다.
  - **영향 범위**: `app/Views/pages/employees/index.php`, `public/assets/js/pages/employees.js`

### 🐛 버그 수정 (Bug Fixes)
- **인사 발령 확인창 HTML 렌더링 오류 수정**:
  - **문제**: 인사 발령 등록 시, 확인을 위해 표시되는 `SweetAlert` 다이얼로그에서 HTML 태그가 텍스트로 그대로 노출되는 문제.
  - **원인**: `ui-helpers.js`의 `Confirm.fire` 헬퍼 함수가 `text` 옵션만 지원하고 `html` 옵션을 처리하지 못했음.
  - **수정**: `Confirm.fire` 함수가 `title`, `text`, `html`을 포함하는 객체를 인자로 받도록 수정하여, `html` 콘텐츠도 올바르게 렌더링되도록 개선했습니다.
  - **영향 범위**: `public/assets/js/utils/ui-helpers.js`, `public/assets/js/pages/hr-order.js`

## [1.3.4 - 2025-10-27]

### ✨ 새로운 기능 (Features)
- **인사 발령 페이지 부서 필터 추가**:
  - **설명**: 인사 발령 등록 페이지에서 발령 대상 직원을 부서별로 필터링하는 기능을 추가했습니다.
  - **변경 내용**: `/hr/order/create` 페이지에 부서 선택 드롭다운을 추가하고, 선택된 부서에 따라 직원 목록이 동적으로 변경되도록 `hr-order.js` 로직을 수정했습니다. 데이터는 기존의 권한이 적용된 API를 활용합니다.

### 🐛 버그 수정 (Bug Fixes)
- **인사 발령 페이지 JS 오류 수정**:
  - **문제**: `/hr/order/create` 페이지에서 `sanitizeHTML` 함수를 찾을 수 없다는 `TypeError`가 발생하여 스크립트가 동작하지 않는 문제.
  - **수정**: `hr-order.js` 파일에 누락되었던 `sanitizeHTML` 유틸리티 함수를 추가하여 오류를 해결했습니다.

## [1.3.3 - 2025-10-27]

### 🐛 버그 수정 (Bug Fixes)
- **인사 발령 페이지 API 중복 호출 오류 수정 (Frontend)**:
  - **문제**: 인사 발령 등록 페이지(`/hr/order/create`) 로드 시, 초기 데이터 조회를 위한 API가 두 번씩 호출되고 페이지가 정상적으로 동작하지 않는 문제.
  - **원인**: `BasePage` 클래스와 이를 상속하는 `HrOrderPage` 자식 클래스 양쪽에서 초기화 로직이 중복 실행됨. `hr-order.js` 파일에 불필요한 `DOMContentLoaded` 리스너가 포함되어 있었음.
  - **수정**: `hr-order.js`에서 `DOMContentLoaded` 리스너를 제거하여, `BasePage`의 생명주기에 따라 초기화가 한 번만 실행되도록 수정.
  - **영향 범위**: `public/assets/js/pages/hr-order.js`

## [1.3.2 - 2025-10-27]

### 📝 문서 (Documentation)
- **백엔드 개발 가이드 보강**:
  - **설명**: 뷰(View) 파일 작성 방법에 대한 명확한 예시가 없어 발생했던 템플릿 렌더링 오류를 방지하기 위해 개발 문서를 개선했습니다.
  - **변경 내용**: `docs/backend-guide.md`의 '새로운 기능 추가 절차' 섹션에 `startSection`과 `endSection`을 사용한 올바른 뷰 파일 작성법 예시 코드를 추가했습니다.

### 🐛 버그 수정 (Bug Fixes)
- **뷰 렌더링 오류 수정**:
  - **문제**: 새로 추가된 인사 발령 관련 페이지(`order.php`, `history.php`)에서 존재하지 않는 템플릿 메서드(`$this->layout()`)를 호출하여 치명적인 오류(Fatal Error)가 발생하는 문제.
  - **수정**: 프로젝트 규칙에 맞게 `\App\Core\View::getInstance()->startSection('content')`를 사용하도록 수정하여 렌더링 오류를 해결했습니다.

## [1.3.1 - 2025-10-27]

### ✨ 새로운 기능 (Features)
- **인사 발령 메뉴 추가**:
  - **설명**: 사이드바 메뉴에 '인사 발령 등록' 메뉴를 추가하여 기능 접근성을 개선했습니다.
  - **변경 내용**: '시스템 관리 > 인사 관리' 하위에 '인사 발령 등록' 메뉴를 추가했습니다.

### 🐛 버그 수정 (Bug Fixes)
- **뷰 렌더링 오류 수정**:
  - **문제**: 새로 추가된 인사 발령 관련 페이지(`order.php`, `history.php`)에서 존재하지 않는 템플릿 메서드(`$this->layout()`)를 호출하여 치명적인 오류(Fatal Error)가 발생하는 문제.
  - **수정**: 프로젝트 규칙에 맞게 `\App\Core\View::getInstance()->startSection('content')`를 사용하도록 수정하여 렌더링 오류를 해결했습니다.

## [1.3.0 - 2025-10-27]

### ✨ 새로운 기능 (Features)
- **인사 발령 기능 추가**:
  - **설명**: 직원의 부서 및 직급을 변경하고 이력을 관리하는 별도의 인사 발령 기능을 추가했습니다.
  - **변경 내용**:
    - 인사 발령 등록 및 이력 조회를 위한 `HumanResourceController`, `HumanResourceService`, `HumanResourceRepository` 및 관련 API 컨트롤러(`HumanResourceApiController`)를 생성했습니다.
    - `/hr/order/create` 경로에 인사 발령 UI를 구현하고, `/employees` 목록에서 '인사발령' 버튼을 통해 접근할 수 있도록 했습니다.
    - 변경 이력은 `hr_employee_change_logs` 테이블에 기록됩니다.
- **퇴사 처리 기능 추가**:
  - **설명**: 직원을 퇴사 처리하는 기능을 신규 개발했습니다. 기존에는 관련 로직이 존재하지 않았습니다.
  - **변경 내용**:
    - 직원의 퇴사일(`termination_date`)을 지정하고, 연결된 사용자 계정을 비활성화(`inactive`)하며, 모든 권한을 제거하는 `terminateEmployee` 로직을 `EmployeeService`에 구현했습니다.
    - `/employees` 목록의 '퇴사처리' 버튼을 통해 기능을 실행할 수 있습니다.

### ♻️ 리팩토링 (Refactoring)
- **직원 정보 수정 로직 변경**:
  - **변경 이유**: 핵심 인적 정보의 무결성을 보장하고, 변경 이력을 명확히 관리하기 위해 직접 수정을 제한하고 기능별로 책임을 분리했습니다.
  - **변경 내용**:
    - `/employees`의 직원 정보 수정 기능에서 이름, 사번, 입사일, 부서, 직급 필드를 수정할 수 없도록 변경했습니다.
    - 부서 및 직급 변경은 신설된 '인사 발령' 기능을 통해서만 가능하도록 강제했습니다.
- **직원 정보 수정 UI 분리**:
  - **설명**: 사용자 편의성 향상을 위해 직원 정보 수정 모달의 UI를 논리적 단위로 분리했습니다.
  - **변경 내용**:
    - 수정 불가능한 '기본 정보' 섹션과 수정 가능한 정보를 담은 '연락처/주소', '비상 연락처', '의류 사이즈' 탭으로 UI를 재구성했습니다.

## [1.2.0 - 2025-10-27]

### ♻️ 리팩토링 (Refactoring)
- **데이터 조회 권한 로직 중앙화**:
  - **변경 이유**: 여러 서비스(`OrganizationService`, `EmployeeService`, `LeaveService` 등)에 분산되어 있던 부서 데이터 조회 권한 로직을 하나로 통합하여 유지보수성을 높이고 중복을 제거하기 위함.
  - **변경 내용**:
    - `DataScopeService`를 신설하여, 현재 사용자가 조회할 수 있는 부서 ID 목록을 계산하는 모든 권한 관련 로직을 중앙에서 관리하도록 함.
    - 기존에 각 서비스가 자체적으로 수행하던 권한 확인 로직을 모두 제거하고, `DataScopeService`를 호출하는 방식으로 통일함.
    - `AuthService`에서 데이터 조회 범위와 관련된 책임을 제거하여, 인증 및 기능 권한 관리에만 집중하도록 역할을 명확히 함.
  - **영향 범위**: `app/Services/OrganizationService.php`, `app/Services/EmployeeService.php`, `app/Services/LeaveService.php`, `app/Services/HolidayService.php`, `app/Services/UserService.php`, `app/Services/AuthService.php`, `app/Controllers/Api/OrganizationApiController.php`, `app/Controllers/Api/EmployeeApiController.php`
  - **함께 수정된 파일**: `public/index.php` (DI 컨테이너 설정), `app/Repositories/DepartmentRepository.php` (`findByIds` 메소드 추가)
- **직원 데이터 접근 권한 로직 중앙화**:
  - **변경 이유**: 부서 데이터 접근 권한 중앙화의 후속 조치로, 특정 직원을 관리할 수 있는지 확인하는 로직(`canManageEmployee`)을 `AuthService`에서 `DataScopeService`로 이전하여 데이터 접근 범위 관련 책임을 일원화함.
  - **변경 내용**:
    - `DataScopeService`에 `canManageEmployee` 메소드를 구현하고, `getVisibleDepartmentIdsForCurrentUser`를 활용하여 효율적으로 권한을 확인하도록 개선.
    - `EmployeeApiController`가 `AuthService` 대신 `DataScopeService`의 `canManageEmployee`를 호출하도록 수정.
  - **영향 범위**: `app/Services/DataScopeService.php`, `app/Controllers/Api/EmployeeApiController.php`

### 🐛 버그 수정 (Bug Fixes)
- **리팩토링 과정에서 발생한 DI 컨테이너 및 메소드 호출 오류 수정**:
  - **문제**: 데이터 조회 권한 로직 중앙화 리팩토링 중 `OrganizationService`에 잘못된 의존성이 주입되고, `DataScopeService`에서 존재하지 않는 메소드를 호출하여 Fatal Error가 발생하는 문제.
  - **수정**:
    - `public/index.php`에서 `OrganizationService`의 생성자에 `DataScopeService`가 올바르게 주입되도록 수정.
    - `DataScopeService` 내에서 `authService->getCurrentUser()`로 잘못 호출된 부분을 `authService->user()`로 수정.
  - **영향 범위**: `public/index.php`, `app/Services/DataScopeService.php`
- **데이터 조회 권한 범위 재수정**:
  - **문제**: 리팩토링 과정에서 데이터 조회 범위가 '자신의 소속 부서 및 하위 부서'로 잘못 확장되는 오류 발생.
  - **원인**: `DataScopeService`에 자신의 소속 부서를 기본적으로 포함하는 로직이 추가되어, `hr_department_managers` 등에 명시된 권한 이상으로 데이터가 노출됨.
  - **수정**: `DataScopeService`에서 자신의 소속 부서를 기본값으로 포함하는 로직을 제거하여, **명시적으로 권한이 부여된 부서와 그 하위 조직에 대해서만** 데이터를 조회할 수 있도록 권한 범위를 정확하게 수정.
  - **영향 범위**: `app/Services/DataScopeService.php`
- **세션 데이터 누락으로 인한 권한 조회 실패 오류 수정**:
  - **문제**: 리팩토링 이후, 관리 권한을 가진 사용자가 재로그인하기 전까지 권한이 적용되지 않는 문제.
  - **원인**: `AuthService`가 로그인 시점에만 직원 정보를 세션에 기록하고, 기존 세션에는 직원 정보가 없어 `DataScopeService`가 권한 계산에 실패함.
  - **수정**:
    - `AuthService`가 `EmployeeRepository`에 의존하도록 DI 컨테이너(`public/index.php`)를 수정.
    - `AuthService`의 `_refreshSessionPermissions` 메소드가 `employee_id`를 기반으로 직원 정보를 조회하여 세션(`$_SESSION['user']['employee']`)에 저장하도록 로직 추가. 이로써 로그인 또는 세션 갱신 시 항상 최신 직원 정보가 보장됨.
  - **영향 범위**: `app/Services/AuthService.php`, `public/index.php`
- **부서 정보 업데이트 API 오류 수정**:
  - **문제**: 부서 정보 수정 API(`/api/organization/{id}`) 호출 시, 요청 본문에 `name` 필드가 포함되지 않으면 `Column 'name' cannot be null` SQL 오류가 발생하는 문제.
  - **원인**: `OrganizationService::updateDepartment` 메소드가 API로부터 받은 데이터를 그대로 `DepartmentRepository::update`로 전달하여, `name` 필드가 누락될 경우를 처리하지 못함.
  - **수정**: `OrganizationService::updateDepartment` 메소드에 방어 코드를 추가하여, 요청 데이터에 `name`이 없는 경우 DB에서 기존 부서 정보를 조회하여 `name` 값을 유지하도록 수정.
  - **영향 범위**: `app/Services/OrganizationService.php`
- **부서 수정 폼 이름 표시 오류 수정**:
  - **문제**: 부서 관리 페이지에서 부서 정보를 수정하려고 할 때, 이름 입력 폼에 '가로청소' 대신 '관리부 > 가로청소'와 같이 전체 경로가 표시되는 문제.
  - **원인**: 부서 목록 API가 목록 표시를 위해 `name` 필드의 값을 계층 전체 경로로 덮어썼고, 프론트엔드가 이 값을 수정 폼에서 그대로 사용함.
  - **수정**: `OrganizationService`의 `flattenTree` 메소드를 수정하여, API 응답 시 순수한 부서명은 `name` 필드에 유지하고, 전체 경로는 `hierarchical_name`이라는 새로운 필드에 담아 보내도록 변경.
  - **영향 범위**: `app/Services/OrganizationService.php`
- **부서 경로 업데이트 시 이름 초기화 오류 수정**:
  - **문제**: 최상위 부서의 소속을 변경할 때, 해당 부서의 모든 하위 부서 이름이 `null`로 초기화되는 문제.
  - **원인**: `OrganizationService::updateSubtreePaths` 메소드가 하위 부서들의 경로(`path`)를 재귀적으로 업데이트하면서 `name` 필드를 전달하지 않아, `UPDATE` 쿼리에서 이름이 누락됨.
  - **수정**: `updateSubtreePaths` 메소드가 하위 부서 정보를 업데이트할 때, 기존의 `name`과 `parent_id`를 함께 전달하도록 수정하여 데이터 유실을 방지.
  - **영향 범위**: `app/Services/OrganizationService.php`
- **관리 페이지 API 중복 호출 오류 수정 (Frontend)**:
  - **문제**: 부서 및 직급 관리(`/admin/organization`) 페이지 로드 시, 초기 데이터 조회를 위한 API가 두 번씩 호출되는 문제.
  - **원인**: `BasePage` 클래스와 이를 상속하는 자식 클래스(`DepartmentAdminPage` 등) 양쪽에서 페이지 초기화 함수(`initializeApp`)를 각각 호출하여 중복 실행됨.
  - **수정**: 자식 클래스 생성자에서 `initializeApp`을 직접 호출하는 코드를 제거하여, `BasePage`가 `DOMContentLoaded` 이벤트 시점에 한 번만 초기화하도록 수정.
  - **영향 범위**: `public/assets/js/pages/organization-admin.js`
## [1.1.1 - 2025-10-27]

### 🐛 버그 수정 (Bug Fixes)
- **애플리케이션 코드의 상태 값 한글화**:
  - **문제**: 데이터베이스 마이그레이션(`20251026_translate_status_enums.sql`)을 통해 DB의 `ENUM` 값들이 한글로 변경되었으나, 일부 PHP 서비스 로직 코드에 이전의 영어 상태 값(`pending`, `active` 등)이 하드코딩되어 남아있어 로직이 올바르게 동작하지 않는 문제 발생.
  - **원인**: `CHANGELOG.md` v1.0.6에서 코드 베이스 전체가 수정되었다고 기록되었으나, 일부 서비스 클래스(`ProfileService`, `LeaveService`, `UserService`)가 누락됨.
  - **수정**: `grep`을 통해 영어 상태 값을 사용하는 부분을 모두 찾아내어, 데이터베이스와 일치하도록 한글 값으로 수정.
  - **영향 범위**: `app/Services/ProfileService.php`, `app/Services/LeaveService.php`, `app/Services/UserService.php`, `app/Controllers/Api/LitteringAdminApiController.php`, `app/Models/WasteCollection.php`, `public/assets/js/pages/waste-index.js`, `app/Repositories/WasteCollectionRepository.php`, `app/Views/pages/waste/manage.php`
  - **함께 수정된 파일**: `database/20251026_translate_status_enums2.sql` (신규)

## [1.1.0 - 2025-10-27]

### ✨ 새로운 기능 (Features)
- **직원 관리 부서 필터에 권한 로직 적용**:
  - **설명**: `/employees` 페이지의 부서 선택 드롭다운에 현재 로그인한 사용자의 조회 권한(`hr_department_managers` 기반)이 적용되도록 개선했습니다.
  - **변경 내용**: `EmployeeApiController::getInitialData()`가 권한이 적용된 부서 목록을 반환하는 `OrganizationService::getManagableDepartments()`를 호출하도록 수정하여, 권한이 있는 부서만 필터에 표시되도록 변경했습니다.
  - **영향 범위**: `app/Controllers/Api/EmployeeApiController.php`

### 🐛 버그 수정 (Bug Fixes)
- **데이터 조회 권한 로직 수정**:
  - **문제**: 데이터 조회 권한이 없는 사용자에게도 자신의 소속 부서가 기본적으로 보여지는 문제.
  - **원인**: `OrganizationService::_getPermittedDepartmentIds()` 메서드가 `hr_department_managers` 확인 로직과 별개로 현재 사용자의 소속 부서를 항상 포함시키고 있었음.
  - **수정**: `OrganizationService`에서 자신의 소속 부서를 자동으로 포함하는 로직을 제거하여, `hr_department_managers` 테이블에 명시적으로 부여된 권한만 적용되도록 수정했습니다.
  - **영향 범위**: `app/Services/OrganizationService.php`
- **모델 객체 처리 관련 치명적 오류 수정 (Fullstack)**:
  - **문제**: `OrganizationService`와 `EmployeeService`에서 `Department` 및 `Position` 모델 객체를 배열처럼 접근하여 "Cannot use object of type ... as array" Fatal Error가 발생하는 문제.
  - **원인**: `DepartmentRepository` 등이 반환하는 객체 배열을 처리하는 과정에서 잘못된 배열 구문(`$model['property']`)을 사용함.
  - **수정**: `OrganizationService::getManagableDepartments`, `EmployeeService::logChanges` 및 관련 헬퍼 메서드 내에서 객체 속성에 접근할 때 올바른 객체 구문(`$model->property`)을 사용하도록 일괄 수정했습니다.
  - **영향 범위**: `app/Services/OrganizationService.php`, `app/Services/EmployeeService.php`
- **부서 생성 시 'name' 필드 누락 오류 수정**:
  - **문제**: 부서 생성 API 호출 시 `SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'name' cannot be null` 오류 발생.
  - **원인**: `OrganizationService::createDepartment` 메서드에서 `DepartmentRepository::create`로 전달하는 데이터 배열에 `name` 필드가 누락됨.
  - **수정**: `createDepartment` 메서드 내에서 `create` 메서드로 전달할 데이터 배열을 명시적으로 생성하여 `name` 필드가 항상 포함되도록 수정했습니다.
  - **영향 범위**: `app/Services/OrganizationService.php`

## [1.0.9 - 2025-10-26]

### ✨ 새로운 기능 (Features)
- **무단투기 승인 시 개선여부 상태 함께 처리**:
  - **설명**: `/littering/manage` 페이지의 상세 보기에서 '개선여부' 상태를 선택한 후, '승인' 버튼을 누를 때 해당 상태가 최종적으로 함께 서버에 저장되도록 기능을 개선했습니다.
  - **변경 내용**:
    - **Backend**: `approve` API가 요청 본문에 `corrected` 상태 값을 포함하여 처리하도록 컨트롤러, 서비스, 리포지토리 로직을 수정했습니다.
    - **Frontend**: '승인' 버튼 클릭 시, 상세 보기 내의 '개선여부' 드롭다운 값을 읽어 API 요청에 포함하도록 `littering-manage.js`의 `approveReport` 메서드를 수정했습니다.
  - **영향 범위**: `app/Controllers/Api/LitteringAdminApiController.php`, `app/Services/LitteringService.php`, `app/Repositories/LitteringRepository.php`, `app/Views/pages/littering/manage.php`, `public/assets/js/pages/littering-manage.js`

## [1.0.8 - 2025-10-26]

### 🐛 버그 수정 (Bug Fixes)
- **직원 API 목록 부서 필터 기능 수정**:
  - **문제**: `/api/employees` 엔드포인트에 `department_id` 쿼리 파라미터를 전달해도 부서별로 직원 목록이 필터링되지 않는 문제.
  - **원인**: `EmployeeRepository::getAll` 메서드에서 `department_id` 필터를 처리하는 SQL `WHERE` 조건절 생성이 누락되었음.
  - **수정**: `EmployeeRepository::getAll` 메서드에 `department_id` 파라미터가 존재할 경우, `WHERE` 절에 부서 필터링 조건을 추가하는 로직을 구현.
  - **영향 범위**: `app/Repositories/EmployeeRepository.php`
  - **함께 수정된 파일**: 없음

## [1.0.6 - 2025-10-26]

### ♻️ 리팩토링 (Refactoring)
- **데이터베이스 및 애플리케이션 전반의 상태 값 한글화 (수정 반영)**:
  - **변경 이유**: 코드 가독성 향상, 데이터 일관성 확보, 프론트엔드 변환 로직 제거를 위해 시스템 전반의 영어 상태 값을 한글로 통일. (기존 작업의 오류 수정)
  - **변경 내용**:
    - **[수정]** 마이그레이션 스크립트(`database/20251026_translate_status_enums.sql`)를 보완하여 누락된 상태 값(`illegal_disposal_cases2`의 'processed')을 추가하고, 불일치하던 상태 값(`'확인'`과 `'처리완료'` 등)을 코드와 통일. `sys_users`에 '차단' 상태를 추가.
    - **[수정]** PHP 백엔드 코드(`app/` 전체)와 JavaScript 프론트엔드 코드(`public/assets/js/` 전체)를 **수정된 스크립트와 완벽히 일치**하도록 재수정. 특히 `Littering` 관련 기능의 상태 값 불일치 오류를 해결.
  - **영향 범위**: `database/schema.sql`, `app/` 디렉토리 전체, `public/assets/js/` 디렉토리 전체
  - **함께 수정된 파일**: `database/20251026_translate_status_enums.sql` (신규), `app/Views/pages/leaves/approval.php`

## [1.0.5 - 2025-10-26]

### ♻️ 리팩토링 (Refactoring)
- **`waste.manage_admin` 권한 분리 및 이름 변경**:
  - **변경 이유**: 단일 책임 원칙에 따라, 하나의 권한이 너무 많은 책임을 갖는 것을 방지하고 역할 분리를 명확히 하기 위함.
  - **변경 내용**: 기존의 `waste.manage_admin` 권한을 다음과 같이 두 개의 구체적인 권한으로 분리하고 최종적으로 이름을 확정:
    - `waste.process`: 현장 등록 및 수거 처리 관련 기능(개별 처리)을 제어.
    - `waste.manage`: 인터넷 배출 신고 관리 기능(조회, 수정, 삭제, 일괄 등록)을 제어.
  - **영향 범위**: `database/seeds/04_permissions.sql`, `database/seeds/09_menus.sql`, `routes/web.php`, `routes/api.php`
  - **함께 수정된 파일**: 상기 영향 범위와 동일.

## [1.0.4 - 2025-10-26]

### 🐛 버그 수정 (Bug Fixes)
- **API 컨트롤러의 Fatal Error 수정**:
  - **문제**: `/api/employees/unlinked` 엔드포인트 호출 시 `Call to undefined method App\Core\Request::get()` Fatal Error 발생.
  - **원인**: `EmployeeApiController`에서 `Request` 객체의 존재하지 않는 `get()` 메서드를 호출함.
  - **수정**: `get()` 메서드 호출을 올바른 `input()` 메서드로 변경하여 API가 정상적으로 작동하도록 수정.
  - **영향 범위**: `app/Controllers/Api/EmployeeApiController.php`
  - **함께 수정된 파일**: 없음

## [1.0.3 - 2025-10-26]

### 🐛 버그 수정 (Bug Fixes)
- **사용자 관리의 직원 연결 필터 기능 수정 (Fullstack)**:
  - **문제**: `/admin/users` 페이지에서 직원을 연결할 때 부서 필터가 작동하지 않아 다른 부서의 직원들이 목록에 포함되는 문제.
  - **원인**: 1) 프론트엔드 `users.js`에서 API 요청 시 `department_id`를 보내지 않았고, 2) 백엔드 API에서 해당 파라미터를 처리하는 로직이 누락되었음.
  - **수정**:
    - **Frontend**: `users.js`의 `departmentFilter` 이벤트 리스너가 선택된 부서 ID를 `loadUnlinkedEmployees()` 함수로 전달하도록 수정.
    - **Backend**: `EmployeeApiController`, `EmployeeService`, `EmployeeRepository`를 모두 수정하여 `department_id` 파라미터를 받아 SQL 쿼리에서 필터링하도록 로직 추가.
  - **영향 범위**: `public/assets/js/pages/users.js`, `app/Controllers/Api/EmployeeApiController.php`, `app/Services/EmployeeService.php`, `app/Repositories/EmployeeRepository.php`
  - **함께 수정된 파일**: 상기 영향 범위와 동일

## [1.0.2 - 2025-10-26]

### 🐛 버그 수정 (Bug Fixes)
- **부서 관리자 권한 확인 로직 수정**:
  - **문제**: 부서 관리자로 지정된 사용자가 부서 데이터를 관리할 수 없는 심각한 버그.
  - **원인**: `AuthService::canManageEmployee()` 메서드가 `hr_departments` 테이블의 존재하지 않는 `manager_id` 컬럼을 확인하고 있었음. 올바른 로직은 `hr_department_managers` 테이블을 통해 권한을 확인해야 함.
  - **수정**: `departmentRepository->findDepartmentIdsWithEmployeeViewPermission()`를 호출하여 사용자가 관리하는 부서 목록을 가져온 후, 대상 직원의 부서가 해당 목록 또는 그 상위 부서에 속하는지 재귀적으로 확인하도록 로직을 변경.
  - **영향 범위**: `app/Services/AuthService.php`
  - **함께 수정된 파일**: 없음

## [1.0.1 - 2025-10-26]

### 🐛 버그 수정 (Bug Fixes)
- **조직도 데이터 접근 권한 수정**:
  - **문제**: `hr_department_managers`에 등록된 사용자임에도 불구하고 조직도 조회 시 권한이 없다는 오류가 발생하는 문제.
  - **원인**: `OrganizationService::getOrganizationChartData()` 메서드가 모든 부서 정보를 필터링 없이 반환하여, 프론트엔드에서 권한 없는 데이터에 접근 시도.
  - **수정**: `getVisibleDepartmentIdsForCurrentUser()`를 사용하여 현재 사용자가 볼 수 있는 부서만 필터링하도록 로직 추가.
  - **영향 범위**: `app/Services/OrganizationService.php`
  - **함께 수정된 파일**: 없음

## [버전 - YYY-MM-DD]

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
