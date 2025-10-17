# 종합 메뉴 권한 명세서

이 문서는 각 메뉴 화면에 진입하고, 화면 내의 모든 기능을 사용하는 데 필요한 권한을 코드 레벨에서 종합적으로 분석한 최종 명세서입니다.

---

### **1. 마이페이지 (`/my-page`)**

*   **메뉴 접근**: `(로그인)`
*   **화면 내 기능별 권한**:
    *   `leave.view_own`: 내 연차 현황 조회 (`GET /api/leaves`)
    *   `leave.request`: 내 연차 신청/취소 (`POST /api/leaves`, `POST /api/leaves/{id}/cancel`)
    *   `(인증만 필요)`: 내 정보 조회/수정 (`GET /api/profile`, `PUT /api/profile`)

| 기능 | 필요 권한 | 설명 |
| :--- | :--- | :--- |
| 메뉴 접근 | **(로그인)** | 로그인한 모든 사용자가 접근 가능합니다. |
| 내 연차 현황 조회 | `leave.view_own` | 페이지 로딩 시 자신의 연차 현황 데이터를 조회합니다. |
| 내 연차 신청/취소 | `leave.request` | 연차 신청 및 기존 신청 건을 취소합니다. |
| 내 정보 조회/수정 | `(인증만 필요)` | 자신의 프로필 정보를 조회하고 수정합니다. |

---

### **2. 대형폐기물 수거 (`/waste/index`)**

*   **메뉴 접근**: `waste.view`
*   **화면 내 기능별 권한**:
    *   `waste.view`: 수거 현황 조회 및 신규 등록 (`GET /api/waste-collections`, `POST /api/waste-collections`)

| 기능 | 필요 권한 | 설명 |
| :--- | :--- | :--- |
| 메뉴 접근 및 모든 기능 | `waste.view` | 대형폐기물 수거 현황을 조회하고 신규 건을 등록합니다. |

---

### **3. 인터넷배출 관리 (`/waste/manage`)**

*   **메뉴 접근**: `waste.manage_admin`
*   **화면 내 기능별 권한**:
    *   `waste.manage_admin`: 모든 관리 기능 수행

| 기능 | 필요 권한 | 설명 |
| :--- | :--- | :--- |
| 메뉴 접근 및 모든 기능 | `waste.manage_admin` | 인터넷 배출 신청 건 조회, 처리, 수정, HTML 일괄 등록, 삭제 등 모든 관리 기능을 수행합니다. |

---

### **4. 무단투기 신고/처리 (`/littering/index`)**

*   **메뉴 접근**: `littering.process`
*   **화면 내 기능별 권한**:
    *   `littering.view`: 처리 대상 목록 조회 (`GET /api/littering_admin/reports`)
    *   `littering.process`: 신고 건 처리(개선) 완료 등록 (`POST /api/littering/{id}/process`)

| 기능 | 필요 권한 | 설명 |
| :--- | :--- | :--- |
| 메뉴 접근 | `littering.process` | 메뉴 접근을 위해 기본적으로 필요합니다. |
| 처리 대상 목록 조회 | `littering.view` | 처리해야 할 무단투기 신고 목록을 조회합니다. |
| 처리(개선) 완료 등록 | `littering.process` | 신고 건에 대한 처리(개선) 완료를 시스템에 등록합니다. |

---

### **5. 검토 및 승인 (`/littering/manage`)**

*   **메뉴 접근**: `littering.view` (라우트 기준), `littering.confirm` (메뉴 DB 기준) -> **`littering.confirm`** 이 더 적합
*   **화면 내 기능별 권한**:
    *   `littering.view`: 신고 목록 조회 (`GET /api/littering_admin/reports`)
    *   `littering.create`: 신규 등록 페이지 이동 (UI 버튼)
    *   `littering.confirm`: 신고 내용 확인/승인 (`POST /api/littering_admin/reports/{id}/confirm`)
    *   `littering.delete`: 신고 정보 임시 삭제 (`DELETE /api/littering_admin/reports/{id}`)

| 기능 | 필요 권한 | 설명 |
| :--- | :--- | :--- |
| 메뉴 접근 | `littering.confirm`| 신고 건을 검토하고 승인하는 페이지에 접근합니다. |
| 신고 목록 조회 | `littering.view` | 모든 상태의 신고 목록을 조회합니다. |
| 신규 등록 | `littering.create` | 신규 무단투기 건을 등록하는 페이지로 이동합니다. |
| 확인/승인 | `littering.confirm` | 신고된 건을 관리자가 확인하고 승인(접수) 처리합니다. |
| 임시 삭제 | `littering.delete` | 신고 정보를 임시 삭제(soft delete)합니다. |

---

### **6. 처리 완료 내역 (`/littering/history`)**

*   **메뉴 접근**: `littering.view`
*   **화면 내 기능별 권한**:
    *   `littering.view`: 처리 완료된 목록 조회 (`GET /api/littering`)

| 기능 | 필요 권한 | 설명 |
| :--- | :--- | :--- |
| 메뉴 접근 및 모든 기능 | `littering.view` | 처리가 완료된 무단투기 내역을 조회합니다. |

---

### **7. 삭제된 항목 (`/littering/deleted`)**

*   **메뉴 접근**: `littering.restore`
*   **화면 내 기능별 권한**:
    *   `littering.view`: 삭제된 목록 조회 (`GET /api/littering_admin/reports`)
    *   `littering.restore`: 정보 복원 (`POST /api/littering_admin/reports/{id}/restore`)
    *   `littering.force_delete`: 영구 삭제 (`DELETE /api/littering_admin/reports/{id}/permanent`)

| 기능 | 필요 권한 | 설명 |
| :--- | :--- | :--- |
| 메뉴 접근 | `littering.restore`| 임시 삭제된 항목 관리 페이지에 접근합니다. |
| 삭제 목록 조회 | `littering.view` | 임시 삭제된 신고 목록을 조회합니다. |
| 정보 복원 | `littering.restore` | 임시 삭제된 정보를 복원합니다. |
| 영구 삭제 | `littering.force_delete` | 정보를 DB에서 영구적으로 삭제합니다. |

---

### **8. 직원 관리 (`/employees`)**

*   **메뉴 접근**: `employee.view`
*   **화면 내 기능별 권한**:
    *   `employee.view`: 직원 목록/상세/이력 조회
    *   `employee.create`: 직원 추가
    *   `employee.update`: 직원 정보 수정
    *   `employee.delete`: 직원 정보 삭제
    *   `employee.approve`: 정보 변경 요청 승인/반려

| 기능 | 필요 권한 | 설명 |
| :--- | :--- | :--- |
| 메뉴 접근 | `employee.view` | 직원 관리 페이지에 접근하고 목록을 조회합니다. |
| 직원 추가 | `employee.create`| 신규 직원을 등록합니다. |
| 직원 수정 | `employee.update`| 기존 직원의 정보를 수정합니다. |
| 직원 삭제 | `employee.delete`| 직원을 삭제합니다. |
| 변경 요청 처리 | `employee.approve`| 직원이 요청한 정보 변경을 승인하거나 반려합니다. |

---

### **9. 사용자 목록 (`/admin/users`)**

*   **메뉴 접근**: `user.view`
*   **화면 내 기능별 권한**:
    *   `role.view`: 역할 필터 및 수정 모달에 역할 목록 표시
    *   `user.update`: 사용자의 상태 및 역할 수정
    *   `user.link`: 사용자와 직원 정보 연결/해제

| 기능 | 필요 권한 | 설명 |
| :--- | :--- | :--- |
| 메뉴 접근 | `user.view` | 사용자 관리 페이지에 접근하고 목록을 조회합니다. |
| 역할 목록 조회 | `role.view` | 검색 필터와 수정 모달에서 역할 목록을 조회합니다. |
| 상태/역할 수정 | `user.update` | 사용자의 상태(활성/중지)와 역할을 변경합니다. |
| 직원 연결/해제 | `user.link` | 사용자 계정과 직원 데이터를 연결하거나 해제합니다. |

---

### **10. 역할 및 권한 (`/admin/role-permissions`)**

*   **메뉴 접근**: `role.assign_permissions`
*   **화면 내 기능별 권한**:
    *   `role.view`: 역할 목록 및 상세 정보 조회
    *   `role.create`: 역할 생성
    *   `role.update`: 역할 정보 수정
    *   `role.delete`: 역할 삭제

| 기능 | 필요 권한 | 설명 |
| :--- | :--- | :--- |
| 메뉴 접근 | `role.assign_permissions`| 역할별 권한 설정 페이지에 접근하고 권한을 저장합니다. |
| 역할 목록/상세 조회 | `role.view` | 역할 목록과 역할별 상세 정보를 조회합니다. |
| 역할 생성 | `role.create`| 새로운 역할을 생성합니다. |
| 역할 수정 | `role.update`| 기존 역할의 이름, 설명을 수정합니다. |
| 역할 삭제 | `role.delete`| 역할을 삭제합니다. |

---

### **11. 조직도 (`/organization/chart`)**

*   **메뉴 접근**: `organization.view`
*   **화면 내 기능별 권한**:
    *   `organization.view`: 조직도 데이터 조회

| 기능 | 필요 권한 | 설명 |
| :--- | :--- | :--- |
| 메뉴 접근 및 모든 기능 | `organization.view` | 조직도 데이터를 조회하고 차트를 렌더링합니다. |

---

### **12. 부서/직급 관리 (`/admin/organization`)**

*   **메뉴 접근**: `organization.manage`
*   **화면 내 기능별 권한**:
    *   `employee.view`: 새 부서 추가 시 부서장으로 지정할 직원 목록 조회

| 기능 | 필요 권한 | 설명 |
| :--- | :--- | :--- |
| 메뉴 접근 | `organization.manage` | 부서/직급을 생성, 수정, 삭제하는 관리 페이지에 접근합니다. |
| 직원 목록 조회 | `employee.view` | 새 부서 추가 시, 부서장으로 지정할 직원 목록을 불러옵니다. |

---

### **13. 연차 부여/계산 (`/leaves/granting`)**

*   **메뉴 접근**: `leave.manage_entitlement`
*   **화면 내 기능별 권한**:
    *   `leave.manage_entitlement`: 연차 현황 조회, 계산, 저장, 수동 조정

| 기능 | 필요 권한 | 설명 |
| :--- | :--- | :--- |
| 메뉴 접근 및 모든 기능 | `leave.manage_entitlement` | 전직원 연차 현황 조회, 일괄 계산, 저장, 개별 수동 조정을 수행합니다. |

---

### **14. 연차 신청 승인 (`/leaves/approval`)**

*   **메뉴 접근**: `leave.approve`
*   **화면 내 기능별 권한**:
    *   `leave.view_all`: 승인/반려 대기중인 연차 신청 목록 조회
    *   `leave.approve`: 신청 건 승인/반려

| 기능 | 필요 권한 | 설명 |
| :--- | :--- | :--- |
| 메뉴 접근 | `leave.approve` | 연차 신청 승인 페이지에 접근합니다. |
| 신청 목록 조회 | `leave.view_all` | 승인 대기 중인 모든 연차 신청 목록을 조회합니다. |
| 신청 승인/반려 | `leave.approve` | 연차 신청 및 취소 요청을 승인하거나 반려합니다. |

---

### **15. 직원 연차 내역 (`/leaves/history`)**

*   **메뉴 접근**: `leave.view_all`
*   **화면 내 기능별 권한**:
    *   `leave.view_all`: 전직원 연차 사용 내역 조회

| 기능 | 필요 권한 | 설명 |
| :--- | :--- | :--- |
| 메뉴 접근 및 모든 기능 | `leave.view_all` | 모든 직원의 연도별 연차 사용 상세 내역을 조회합니다. |

---

### **16. 휴일/근무일 설정 (`/holidays`)**

*   **메뉴 접근**: `holiday.manage`
*   **화면 내 기능별 권한**:
    *   `holiday.manage`: 휴일 조회, 생성, 수정, 삭제

| 기능 | 필요 권한 | 설명 |
| :--- | :--- | :--- |
| 메뉴 접근 및 모든 기능 | `holiday.manage` | 달력의 휴일 및 특정 근무일을 조회, 생성, 수정, 삭제합니다. |

---

### **17. 메뉴 관리 (`/admin/menus`)**

*   **메뉴 접근**: `menu.manage`
*   **화면 내 기능별 권한**:
    *   `menu.manage`: 메뉴 조회, 생성, 수정, 삭제, 순서 변경

| 기능 | 필요 권한 | 설명 |
| :--- | :--- | :--- |
| 메뉴 접근 및 모든 기능 | `menu.manage` | 사이드바 메뉴를 조회, 생성, 수정, 삭제하고 순서를 변경합니다. |

---

### **18. 시스템 로그 (`/logs`)**

*   **메뉴 접근**: `log.view`
*   **화면 내 기능별 권한**:
    *   `log.delete`: 로그 삭제

| 기능 | 필요 권한 | 설명 |
| :--- | :--- | :--- |
| 메뉴 접근 | `log.view` | 시스템 활동 로그를 조회합니다. |
| 로그 삭제 | `log.delete` | 선택한 기간의 로그를 삭제합니다. |