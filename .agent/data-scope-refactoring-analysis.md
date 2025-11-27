# DataScopeService 마이그레이션 분석 결과

`DataScopeService`를 `PolicyEngine`으로 교체하기 위한 코드베이스 분석을 완료했습니다. 분석 결과, 관련 파일들은 세 가지 유형으로 분류할 수 있습니다.

---

### 1. PolicyEngine으로 마이그레이션이 필요한 파일 (총 7개)

이 파일들은 현재 `DataScopeService`를 사용하여 데이터 접근 권한을 제어하고 있으며, `PolicyEngine`으로의 전환이 필요한 핵심 대상입니다.

- **`app/Repositories/LeaveAdminRepository.php`**: `applyEmployeeScope`를 여러 메소드에서 광범위하게 사용 중입니다.
- **`app/Repositories/VehicleMaintenanceRepository.php`**: `applyVehicleScope`를 사용하여 차량 정비 목록 조회를 제한합니다.
- **`app/Repositories/HolidayRepository.php`**: `applyHolidayScope`를 사용하여 휴일 정보 조회를 제한합니다.
- **`app/Repositories/LeaveRepository.php`**: 일부 기능은 `PolicyEngine`으로 마이그레이션되었으나, 여전히 `DataScopeService`를 사용하는 코드가 남아있습니다 (부분 완료).
- **`app/Repositories/SupplyPurchaseRepository.php`**: 실제 코드는 주석 처리되어 있으나, 향후 데이터 스코프 적용이 예정되어 있어 마이그레이션 대상에 포함됩니다.
- **`app/Repositories/SupplyPlanRepository.php`**: `SupplyPurchaseRepository`와 유사하게 향후 확장 계획이 언급되어 있습니다.
- **`app/Services/LeaveAdminService.php`**: `LeaveAdminRepository`에 강하게 의존하고 있어, Repository 변경 후 영향도 검토가 필요합니다.

---

### 2. 불필요한 의존성 제거가 필요한 파일 (총 8개)

이 파일들은 `DataScopeService`를 의존성으로 주입받고 있으나, 실제 코드 내에서는 사용하지 않습니다. 코드 품질 향상을 위해 불필요한 의존성을 제거하는 리팩토링이 필요합니다.

- `app/Repositories/SupplyItemRepository.php`
- `app/Repositories/SupplyStockRepository.php`
- `app/Repositories/SupplyCategoryRepository.php`
- `app/Services/OrganizationService.php`
- `app/Services/EmployeeService.php`
- `app/Services/LeaveService.php`
- `app/Services/HolidayService.php`
- `app/Services/UserService.php`

---

### 3. 분석에서 제외된 파일

- `app/Services/DataScopeService.php`: 서비스 자체 정의 파일로, 마이그레이션 완료 후 삭제 대상입니다.
- `docs/*`, `.md`, `.git/*`: 문서, 마크다운 파일, Git 인덱스 등 실제 코드와 관련 없는 파일들입니다.

이 분석 결과를 바탕으로 아래와 같이 상세 마이그레이션 계획을 제안합니다.
