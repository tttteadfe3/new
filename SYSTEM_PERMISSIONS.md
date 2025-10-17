# 시스템 전체 기능 및 필요 권한 명세

이 문서는 `routes/web.php`, `routes/api.php`, 서비스 로직, 데이터베이스 시드 파일(`04_permissions.sql`)을 종합적으로 분석하여 시스템의 모든 기능과 해당 기능을 사용하기 위해 필요한 권한 키를 기능(Resource) 중심으로 정리한 것입니다.

---

### **1. 직원 관리 (Employee)**

| 기능 | 필요 권한 키 | 관련 라우트 (Web/API) | 설명 |
| :--- | :--- | :--- | :--- |
| 목록/상세/이력 조회 | `employee.view` | `/employees`, `/api/employees`, `/api/employees/{id}`, `/api/employees/{id}/history` | 직원 목록, 상세 정보, 변경 이력을 조회합니다. |
| 신규 등록 | `employee.create` | `/employees/create`, `/api/employees` | 새로운 직원을 시스템에 등록합니다. |
| 정보 수정 | `employee.update` | `/employees/edit`, `/api/employees/{id}` | 기존 직원의 정보를 수정합니다. |
| 정보 삭제 | `employee.delete` | `/api/employees/{id}` | 직원의 정보를 시스템에서 삭제합니다. |
| 변경 승인/거부 | `employee.approve` | `/api/employees/{id}/approve-update`, `/api/employees/{id}/reject-update` | 직원이 요청한 정보 변경 사항을 승인하거나 거부합니다. |

---

### **2. 사용자 관리 (User)**

| 기능 | 필요 권한 키 | 관련 라우트 (Web/API) | 설명 |
| :--- | :--- | :--- | :--- |
| 목록/상세 조회 | `user.view` | `/admin/users`, `/api/users`, `/api/users/{id}` | 시스템 사용자 계정 목록과 상세 정보를 조회합니다. |
| 상태/역할 변경 | `user.update` | `/api/users/{id}` | 사용자의 계정 상태(활성, 비활성 등)나 역할을 변경합니다. |
| 직원 정보 연결/해제 | `user.link` | `/api/users/{id}/link`, `/api/users/{id}/unlink`, `/api/employees/unlinked` | 사용자 계정과 직원 정보를 연결하거나 해제합니다. |

---

### **3. 역할 및 권한 관리 (Role & Permission)**

| 기능 | 필요 권한 키 | 관련 라우트 (Web/API) | 설명 |
| :--- | :--- | :--- | :--- |
| 목록/상세 조회 | `role.view` | `/api/roles`, `/api/roles/{id}` | 생성된 역할 목록과 상세 정보를 조회합니다. |
| 신규 생성 | `role.create` | `/api/roles` | 새로운 역할을 생성합니다. |
| 정보 수정 | `role.update` | `/api/roles/{id}` | 역할의 이름 등 기본 정보를 수정합니다. |
| 역할 삭제 | `role.delete` | `/api/roles/{id}` | 역할을 삭제합니다. |
| 권한 매핑 | `role.assign_permissions`| `/admin/role-permissions`, `/api/roles/{id}/permissions` | 특정 역할에 시스템 기능 권한을 부여하거나 해제합니다. |

---

### **4. 조직 관리 (Organization & Department)**

| 기능 | 필요 권한 키 | 관련 라우트 (Web/API) | 설명 |
| :--- | :--- | :--- | :--- |
| 조직도 조회 | `organization.view` | `/organization/chart`, `/api/organization/chart` | 전체 조직도를 조회합니다. |
| 부서/직급 관리 | `organization.manage` | `/admin/organization`, `/api/organization`, `/api/organization/{id}/eligible-managers` | 부서 및 직급을 생성, 수정, 삭제하고 부서장을 임명합니다. |
| *모든 부서 관리* | `department.manage_all` | (서비스 로직) | *(암시적 권한)* 모든 부서에 대한 관리 권한을 가집니다. |
| *전체 직원 조회* | `employee.view_all` | (서비스 로직) | *(암시적 권한, `leave.view_all`과 유사)* 전체 직원의 정보를 조회할 수 있습니다. |

---

### **5. 휴가 관리 (Leave)**

| 기능 | 필요 권한 키 | 관련 라우트 (Web/API) | 설명 |
| :--- | :--- | :--- | :--- |
| 휴가 신청/취소 | `leave.request` | `/api/leaves`, `/api/leaves/{id}/cancel`, `/api/leaves/calculate-days` | 자신의 휴가를 신청하거나 신청한 휴가를 취소합니다. |
| 내 휴가내역 조회 | `leave.view_own` | `/api/leaves` | 자신의 휴가 신청 내역 및 잔여 일수를 조회합니다. |
| 전직원 휴가내역 조회 | `leave.view_all` | `/leaves`, `/leaves/history`, `/api/leaves_admin/requests`, `/api/leaves_admin/history` | 모든 직원의 휴가 신청 및 사용 내역을 조회합니다. |
| 휴가 승인/거부 | `leave.approve` | `/leaves/approval`, `/api/leaves_admin/requests/{id}/approve`, `/api/leaves_admin/cancellations/{id}/approve` | 직원의 휴가 신청 또는 취소 요청을 승인하거나 거부합니다. |
| 연차 부여/관리 | `leave.manage_entitlement` | `/leaves/granting`, `/api/leaves_admin/entitlements`, `/api/leaves_admin/grant-all`, `/api/leaves_admin/adjust` | 전직원 연차를 일괄 부여하거나 특정 직원의 연차를 수동 조정합니다. |

---

### **6. 무단투기 관리 (Littering)**

| 기능 | 필요 권한 키 | 관련 라우트 (Web/API) | 설명 |
| :--- | :--- | :--- | :--- |
| 현황/내역 조회 | `littering.view` | `/littering/manage`, `/littering/history`, `/api/littering`, `/api/littering_admin/reports` | 무단투기 신고 현황 및 처리 완료 내역을 조회합니다. |
| 신규 등록 | `littering.create` | `/littering/create`, `/api/littering` | 새로운 무단투기 건을 등록합니다. |
| 처리(개선) | `littering.process` | `/littering/index`, `/api/littering/{id}/process` | 신고된 무단투기 건에 대해 처리(개선) 완료를 등록합니다. |
| 확인/승인 | `littering.confirm` | `/littering/edit`, `/api/littering_admin/reports/{id}/confirm` | 신고된 건을 관리자가 확인하고 승인(접수) 처리합니다. |
| 삭제 (임시) | `littering.delete` | `/api/littering_admin/reports/{id}` | 신고 정보를 임시 삭제(soft delete)합니다. |
| 복원 | `littering.restore` | `/littering/deleted`, `/api/littering_admin/reports/{id}/restore` | 임시 삭제된 정보를 복원합니다. |
| 영구 삭제 | `littering.force_delete` | `/api/littering_admin/reports/{id}/permanent` | 정보를 영구적으로 삭제합니다. |

---

### **7. 대형폐기물 관리 (Waste Collection)**

| 기능 | 필요 권한 키 | 관련 라우트 (Web/API) | 설명 |
| :--- | :--- | :--- | :--- |
| 수거 현황 조회/등록 | `waste.view` | `/waste/index`, `/api/waste-collections` | 대형폐기물 수거 현황을 조회하거나 신규 건을 등록합니다. |
| 관리자 기능 | `waste.manage_admin` | `/waste/manage`, `/api/waste-collections/admin/**` | 수거 건 처리, 수정, HTML 일괄 등록, 온라인 접수내역 삭제 등 모든 관리 기능을 수행합니다. |

---

### **8. 시스템 및 기타 관리 (System & Etc.)**

| 기능 | 필요 권한 키 | 관련 라우트 (Web/API) | 설명 |
| :--- | :--- | :--- | :--- |
| 휴일 관리 | `holiday.manage` | `/holidays`, `/api/holidays/**` | 휴일 및 특정 근무일을 생성, 수정, 삭제합니다. |
| 메뉴 관리 | `menu.manage` | `/admin/menus`, `/api/menus/**` | 시스템 메뉴를 생성, 수정, 삭제, 순서 변경합니다. |
| 로그 조회 | `log.view` | `/logs`, `/api/logs` | 시스템 활동 로그를 조회합니다. |
| 로그 삭제 | `log.delete` | `/api/logs` | 시스템 활동 로그를 삭제합니다. |

---

### **9. 인증만 필요한 기능 (Authentication Only)**

아래 기능들은 별도의 권한 키 없이 **로그인 상태**이기만 하면 접근 가능합니다.

| 기능 | 관련 라우트 (Web/API) |
| :--- | :--- |
| 마이페이지 | `/my-page` |
| 대시보드 | `/dashboard` |
| 계정 상태 | `/status` |
| 프로필 조회/수정 | `/api/profile` |
| 조직 목록 조회 | `/api/organization` |
| 관리 가능 부서 조회 | `/api/organization/managable-departments` |