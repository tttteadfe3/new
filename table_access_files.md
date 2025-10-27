# 직급 및 부서 테이블 접근 파일 목록

## 컨트롤러 (Controllers)
- `app/Controllers/Web/AdminController.php`
- `app/Controllers/Api/OrganizationApiController.php`
- `app/Controllers/Api/PositionApiController.php`
- `app/Controllers/Api/EmployeeApiController.php`
- `app/Controllers/Api/DepartmentApiController.php`
- `app/Controllers/Api/HolidayApiController.php`
- `app/Controllers/Api/LeaveApiController.php`

## 뷰 (Views)
- `app/Views/pages/admin/organization.php`
- `app/Views/pages/employees/index.php`
- `app/Views/pages/leave/history.php`
- `app/Views/pages/leave/requests.php`
- `app/Views/pages/leave/entitlements.php`
- `app/Views/pages/holidays/index.php`

## 유효성 검사기 (Validators)
- `app/Validators/PositionValidator.php`
- `app/Validators/DepartmentValidator.php`

## 서비스 (Services)
- `app/Services/PositionService.php`
- `app/Services/EmployeeService.php`
- `app/Services/DepartmentService.php`
- `app/Services/HolidayService.php`
- `app/Services/LeaveService.php`
- `app/Services/OrganizationService.php`

## 리포지토리 (Repositories)
- `app/Repositories/DepartmentRepository.php`
- `app/Repositories/EmployeeRepository.php`
- `app/Repositories/HolidayRepository.php`
- `app/Repositories/LeaveRepository.php`
- `app/Repositories/PositionRepository.php`

## 데이터베이스 관련 (Database related)
- `database/migration.sql`
- `database/schema.sql`
- `database/seeds/04_permissions.sql`
- `database/seeds/09_menus.sql`

## 문서 (Documentation)
- `storage/README.md`
- `organization_development.md`
- `docs/database-guide.md`
- `docs/architecture.md`
- `mg.md`

## 자바스크립트 (Public Assets - JavaScript)
- `public/assets/js/pages/employees.js`
- `public/assets/js/pages/organization-admin.js`
- `public/assets/js/pages/leave-approval.js`
- `public/assets/js/pages/leave-history-admin.js`
- `public/assets/js/pages/leave-granting.js`
- `public/assets/js/pages/holiday-admin.js`
- `public/assets/js/pages/organization-chart.js`

## 기타 (Old/Example files)
- `_example/old_database`
