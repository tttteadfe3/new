# 데이터 레벨 접근제어 리팩토링 문서

## 📋 목차
1. [개요](#개요)
2. [현재 상황](#현재-상황)
3. [리팩토링 목표](#리팩토링-목표)
4. [데이터베이스 스키마](#데이터베이스-스키마)
5. [핵심 로직 구현](#핵심-로직-구현)
6. [권한 그룹 시스템](#권한-그룹-시스템)
7. [UI/UX 설계](#uiux-설계)
8. [적용 예시](#적용-예시)

---

## 개요

### 프로젝트 정보
- **기술 스택**: PHP, MariaDB, Nginx
- **목적**: 기존 롤 퍼미션 시스템에 데이터 레벨 접근제어 추가
- **범위**: 계층적 부서 구조에서 담당자별 데이터 접근 범위 제어

### 주요 개선사항
1. ✅ 부서별 데이터 접근 제어
2. ✅ 계층적 부서 구조 지원 (본부 > 팀 > 파트)
3. ✅ 담당자별 복수 부서 관리 (a과장 → a팀, b팀)
4. ✅ 권한 그룹화로 필수 권한 누락 방지
5. ✅ 본인 데이터 항상 접근 가능

---

## 현재 상황

### 구현된 것
- ✅ **기능 레벨 접근제한**: 롤 퍼미션으로 보기/승인/반려 등 제어
- ✅ 사용자-롤 매핑 구조

### 구현되지 않은 것
- ❌ **데이터 레벨 접근제한**: 부서별 데이터 필터링
- ❌ 담당자별 관리 부서 설정
- ❌ 권한 의존성 관리

### 문제점
```
예시: a과에 a팀, b팀, c팀이 있을 때
- a과장: a팀, b팀 담당
- b과장: c팀 담당

현재는 이런 세밀한 제어가 불가능
```

---

## 리팩토링 목표

### 기능 요구사항
1. A부서 담당자는 A부서 + 하위부서 데이터만 조회/처리
2. 본인 데이터는 부서와 무관하게 항상 접근 가능
3. 한 담당자가 여러 부서를 관리할 수 있음
4. 하위 부서 포함 여부를 부서별로 설정 가능

### 비기능 요구사항
- 기존 롤 퍼미션 시스템과 통합
- 성능 최적화 (계층 조회)
- 관리자 친화적 UI
- 권한 누락 방지 시스템

---

## 데이터베이스 스키마

### 1. 직원 테이블 (user → employee)

```sql
-- 직원 기본 정보
CREATE TABLE employees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_no VARCHAR(20) UNIQUE COMMENT '사번',
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    department_id INT COMMENT '소속 부서',
    position VARCHAR(50) COMMENT '직급',
    hire_date DATE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id),
    INDEX idx_department (department_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='직원 정보';
```

### 2. 부서 테이블 (계층 구조)

```sql
-- 부서 계층 구조
CREATE TABLE departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) UNIQUE COMMENT '부서 코드',
    parent_id INT NULL COMMENT '상위 부서',
    depth INT DEFAULT 0 COMMENT '계층 깊이',
    path VARCHAR(500) COMMENT '계층 경로 (예: /1/3/5/)',
    manager_id INT NULL COMMENT '부서장',
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES departments(id),
    FOREIGN KEY (manager_id) REFERENCES employees(id),
    INDEX idx_parent (parent_id),
    INDEX idx_path (path),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='부서 정보';

-- path 자동 업데이트 트리거
DELIMITER //
CREATE TRIGGER trg_departments_path_insert
BEFORE INSERT ON departments
FOR EACH ROW
BEGIN
    IF NEW.parent_id IS NULL THEN
        SET NEW.path = CONCAT('/', NEW.id, '/');
        SET NEW.depth = 0;
    ELSE
        SELECT CONCAT(path, NEW.id, '/'), depth + 1 
        INTO NEW.path, NEW.depth
        FROM departments WHERE id = NEW.parent_id;
    END IF;
END//

CREATE TRIGGER trg_departments_path_update
BEFORE UPDATE ON departments
FOR EACH ROW
BEGIN
    IF NEW.parent_id != OLD.parent_id OR (NEW.parent_id IS NULL AND OLD.parent_id IS NOT NULL) THEN
        IF NEW.parent_id IS NULL THEN
            SET NEW.path = CONCAT('/', NEW.id, '/');
            SET NEW.depth = 0;
        ELSE
            SELECT CONCAT(path, NEW.id, '/'), depth + 1 
            INTO NEW.path, NEW.depth
            FROM departments WHERE id = NEW.parent_id;
        END IF;
    END IF;
END//
DELIMITER ;
```

### 3. 역할 및 권한

```sql
-- 역할
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) UNIQUE,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='역할';

-- 권한
CREATE TABLE permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(100) UNIQUE COMMENT '예: hr.leave.view',
    module VARCHAR(50) COMMENT '모듈명',
    description TEXT,
    is_menu_permission BOOLEAN DEFAULT FALSE COMMENT '메뉴 접근 권한 여부',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_module (module),
    INDEX idx_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='권한';

-- 직원-역할 매핑
CREATE TABLE employee_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    role_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES employees(id),
    UNIQUE KEY unique_employee_role (employee_id, role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='직원-역할 매핑';

-- 역할별 데이터 접근 범위 정책
CREATE TABLE role_data_scopes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    scope_type ENUM('ALL', 'DEPARTMENT_AND_BELOW', 'DEPARTMENT', 'SELF') NOT NULL 
        COMMENT 'ALL: 전체, DEPARTMENT_AND_BELOW: 담당부서+하위, DEPARTMENT: 담당부서만, SELF: 본인만',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_scope (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='역할별 데이터 스코프';
```

### 4. 권한 그룹 (신규)

```sql
-- 권한 그룹
CREATE TABLE permission_groups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) UNIQUE,
    description TEXT,
    menu_code VARCHAR(50) COMMENT '연관 메뉴',
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='권한 그룹';

-- 권한 그룹 상세
CREATE TABLE permission_group_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    group_id INT NOT NULL,
    permission_id INT NOT NULL,
    is_required BOOLEAN DEFAULT FALSE COMMENT '필수 권한 여부',
    display_order INT DEFAULT 0,
    FOREIGN KEY (group_id) REFERENCES permission_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_group_permission (group_id, permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='권한 그룹 상세';

-- 역할-권한그룹 매핑
CREATE TABLE role_permission_groups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    group_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES permission_groups(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_group (role_id, group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='역할-권한그룹 매핑';

-- 역할-개별권한 매핑 (고급 사용자용)
CREATE TABLE role_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_permission (role_id, permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='역할-권한 매핑';
```

### 5. 직원별 관리 부서 (핵심 신규)

```sql
-- 직원별 데이터 접근 범위
CREATE TABLE employee_department_scopes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    department_id INT NOT NULL,
    include_children BOOLEAN DEFAULT TRUE COMMENT '하위 부서 포함 여부',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES employees(id),
    UNIQUE KEY unique_employee_dept (employee_id, department_id),
    INDEX idx_employee (employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='직원별 관리 부서';
```

### 6. 예시: 연차 테이블

```sql
-- 연차 신청
CREATE TABLE leaves (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    department_id INT NOT NULL COMMENT '신청 당시 소속 부서',
    leave_type ENUM('annual', 'sick', 'personal', 'special') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    days DECIMAL(3,1) NOT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    reject_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (approved_by) REFERENCES employees(id),
    INDEX idx_employee (employee_id),
    INDEX idx_department (department_id),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='연차 신청';
```

### 초기 데이터 삽입

```sql
-- 권한 그룹 예시
INSERT INTO permission_groups (name, code, description, menu_code) VALUES
('연차 관리자', 'leave_manager', '연차 관련 모든 권한', 'leaves'),
('연차 승인자', 'leave_approver', '연차 조회 및 승인 권한', 'leaves'),
('연차 신청자', 'leave_requester', '연차 조회 및 신청 권한', 'leaves');

-- 권한 예시
INSERT INTO permissions (name, code, module, is_menu_permission) VALUES
('연차 페이지 접근', 'hr.leave.view', 'hr', TRUE),
('연차 신청', 'hr.leave.create', 'hr', FALSE),
('연차 수정', 'hr.leave.update', 'hr', FALSE),
('연차 삭제', 'hr.leave.delete', 'hr', FALSE),
('연차 승인', 'hr.leave.approve', 'hr', FALSE);

-- 권한 그룹-권한 매핑
-- 연차 관리자 그룹
INSERT INTO permission_group_items (group_id, permission_id, is_required) VALUES
(1, 1, TRUE),  -- view (필수)
(1, 2, FALSE), -- create
(1, 3, FALSE), -- update
(1, 4, FALSE), -- delete
(1, 5, FALSE); -- approve

-- 연차 승인자 그룹
INSERT INTO permission_group_items (group_id, permission_id, is_required) VALUES
(2, 1, TRUE),  -- view (필수)
(2, 5, FALSE); -- approve

-- 연차 신청자 그룹
INSERT INTO permission_group_items (group_id, permission_id, is_required) VALUES
(3, 1, TRUE),  -- view (필수)
(3, 2, FALSE); -- create

-- 역할 예시
INSERT INTO roles (name, code, description) VALUES
('시스템 관리자', 'admin', '전체 시스템 관리'),
('인사팀', 'hr', '인사 관련 업무'),
('부서장', 'manager', '소속 부서 관리'),
('일반 직원', 'employee', '기본 권한');

-- 역할별 데이터 스코프
INSERT INTO role_data_scopes (role_id, scope_type) VALUES
(1, 'ALL'),                      -- 관리자: 모든 데이터
(2, 'ALL'),                      -- 인사팀: 모든 데이터
(3, 'DEPARTMENT_AND_BELOW'),     -- 부서장: 담당 부서+하위
(4, 'SELF');                     -- 직원: 본인만
```

---

## 핵심 로직 구현

### 1. DepartmentService.php

```php
<?php
/**
 * 부서 관련 서비스
 */
class DepartmentService {
    
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * 특정 부서와 모든 하위 부서 ID 조회 (path 기반)
     * 
     * @param int $departmentId
     * @return array 부서 ID 배열
     */
    public function getDepartmentAndChildren($departmentId) {
        $dept = $this->db->queryOne(
            "SELECT path FROM departments WHERE id = ?",
            [$departmentId]
        );
        
        if (!$dept) {
            return [];
        }
        
        $result = $this->db->query(
            "SELECT id FROM departments WHERE path LIKE ? AND is_active = 1",
            [$dept['path'] . '%']
        );
        
        return array_column($result, 'id');
    }
    
    /**
     * 부서 트리 구조 조회
     * 
     * @param int|null $parentId
     * @return array
     */
    public function getDepartmentTree($parentId = null) {
        $departments = $this->db->query(
            "SELECT * FROM departments 
             WHERE parent_id " . ($parentId ? "= ?" : "IS NULL") . "
             AND is_active = 1 
             ORDER BY display_order, name",
            $parentId ? [$parentId] : []
        );
        
        foreach ($departments as &$dept) {
            $dept['children'] = $this->getDepartmentTree($dept['id']);
        }
        
        return $departments;
    }
    
    /**
     * 부서 경로 문자열 조회 (예: "본부 > 팀 > 파트")
     * 
     * @param int $departmentId
     * @return string
     */
    public function getDepartmentPathString($departmentId) {
        $dept = $this->db->queryOne(
            "SELECT path FROM departments WHERE id = ?",
            [$departmentId]
        );
        
        if (!$dept) {
            return '';
        }
        
        $ids = array_filter(explode('/', $dept['path']));
        
        if (empty($ids)) {
            return '';
        }
        
        $depts = $this->db->query(
            "SELECT name FROM departments WHERE id IN (" . 
            implode(',', $ids) . ") ORDER BY depth"
        );
        
        return implode(' > ', array_column($depts, 'name'));
    }
}
```

### 2. DataScopeService.php

```php
<?php
/**
 * 데이터 접근 범위 서비스
 */
class DataScopeService {
    
    private $db;
    private $deptService;
    
    public function __construct($db, DepartmentService $deptService) {
        $this->db = $db;
        $this->deptService = $deptService;
    }
    
    /**
     * 직원이 접근 가능한 모든 부서 ID 조회
     * 
     * @param int $employeeId
     * @return array 부서 ID 배열
     */
    public function getAccessibleDepartments($employeeId) {
        $employee = $this->getEmployee($employeeId);
        
        if (!$employee) {
            return [];
        }
        
        $scopeType = $this->getEmployeeScopeType($employeeId);
        
        switch ($scopeType) {
            case 'ALL':
                // 전체 부서 접근
                $allDepts = $this->db->query(
                    "SELECT id FROM departments WHERE is_active = 1"
                );
                return array_column($allDepts, 'id');
                
            case 'DEPARTMENT_AND_BELOW':
                // 담당 부서 + 하위 부서
                return $this->getDepartmentScopesWithChildren($employeeId);
                
            case 'DEPARTMENT':
                // 담당 부서만
                return $this->getDepartmentScopesOnly($employeeId);
                
            case 'SELF':
            default:
                // 본인 데이터만 (부서 없음)
                return [];
        }
    }
    
    /**
     * 직원의 데이터 스코프 타입 조회
     * 
     * @param int $employeeId
     * @return string
     */
    private function getEmployeeScopeType($employeeId) {
        // 직원의 역할 중 가장 넓은 범위 선택
        $scope = $this->db->queryOne("
            SELECT rds.scope_type
            FROM employee_roles er
            JOIN role_data_scopes rds ON er.role_id = rds.role_id
            WHERE er.employee_id = ?
            ORDER BY 
                CASE rds.scope_type
                    WHEN 'ALL' THEN 1
                    WHEN 'DEPARTMENT_AND_BELOW' THEN 2
                    WHEN 'DEPARTMENT' THEN 3
                    WHEN 'SELF' THEN 4
                END
            LIMIT 1
        ", [$employeeId]);
        
        return $scope['scope_type'] ?? 'SELF';
    }
    
    /**
     * 담당 부서 + 하위 부서 조회
     * 
     * @param int $employeeId
     * @return array
     */
    private function getDepartmentScopesWithChildren($employeeId) {
        // 직원이 관리하는 부서 목록
        $managedDepts = $this->db->query(
            "SELECT department_id, include_children 
             FROM employee_department_scopes 
             WHERE employee_id = ?",
            [$employeeId]
        );
        
        // 설정이 없으면 본인 소속 부서
        if (empty($managedDepts)) {
            $employee = $this->getEmployee($employeeId);
            if ($employee['department_id']) {
                return $this->deptService->getDepartmentAndChildren(
                    $employee['department_id']
                );
            }
            return [];
        }
        
        $accessibleDepts = [];
        
        foreach ($managedDepts as $scope) {
            if ($scope['include_children']) {
                // 하위 부서 포함
                $children = $this->deptService->getDepartmentAndChildren(
                    $scope['department_id']
                );
                $accessibleDepts = array_merge($accessibleDepts, $children);
            } else {
                // 해당 부서만
                $accessibleDepts[] = $scope['department_id'];
            }
        }
        
        return array_unique($accessibleDepts);
    }
    
    /**
     * 담당 부서만 조회 (하위 제외)
     * 
     * @param int $employeeId
     * @return array
     */
    private function getDepartmentScopesOnly($employeeId) {
        $scopes = $this->db->query(
            "SELECT department_id FROM employee_department_scopes WHERE employee_id = ?",
            [$employeeId]
        );
        
        if (empty($scopes)) {
            $employee = $this->getEmployee($employeeId);
            return $employee['department_id'] ? [$employee['department_id']] : [];
        }
        
        return array_column($scopes, 'department_id');
    }
    
    /**
     * 직원 정보 조회
     * 
     * @param int $employeeId
     * @return array|null
     */
    private function getEmployee($employeeId) {
        return $this->db->queryOne(
            "SELECT * FROM employees WHERE id = ?",
            [$employeeId]
        );
    }
    
    /**
     * 특정 데이터에 접근 가능한지 체크
     * 
     * @param int $employeeId
     * @param int $targetDepartmentId
     * @param int|null $targetEmployeeId
     * @return bool
     */
    public function canAccessData($employeeId, $targetDepartmentId, $targetEmployeeId = null) {
        // 본인 데이터는 항상 접근 가능
        if ($targetEmployeeId && $targetEmployeeId == $employeeId) {
            return true;
        }
        
        $accessibleDepts = $this->getAccessibleDepartments($employeeId);
        
        // 접근 가능한 부서에 포함되는지 확인
        return in_array($targetDepartmentId, $accessibleDepts);
    }
}
```

### 3. DataScopeFilter.php

```php
<?php
/**
 * 데이터 접근 범위 필터
 */
class DataScopeFilter {
    
    private $dataScopeService;
    
    public function __construct(DataScopeService $dataScopeService) {
        $this->dataScopeService = $dataScopeService;
    }
    
    /**
     * 쿼리에 데이터 스코프 필터 적용
     * 
     * @param int $employeeId
     * @param string $baseQuery
     * @param string $deptColumn 부서 컬럼명 (기본: 'department_id')
     * @param string $empColumn 직원 컬럼명 (기본: 'employee_id')
     * @return string
     */
    public function applyScope($employeeId, $baseQuery, $deptColumn = 'department_id', $empColumn = 'employee_id') {
        $deptIds = $this->dataScopeService->getAccessibleDepartments($employeeId);
        
        if (empty($deptIds)) {
            // 부서 접근 권한 없음 → 본인 데이터만
            return $baseQuery . " AND {$empColumn} = {$employeeId}";
        }
        
        // 담당 부서 데이터 OR 본인 데이터
        $deptIdsStr = implode(',', $deptIds);
        return $baseQuery . " AND ({$deptColumn} IN ({$deptIdsStr}) OR {$empColumn} = {$employeeId})";
    }
    
    /**
     * WHERE 조건절만 반환 (AND 제외)
     * 
     * @param int $employeeId
     * @param string $deptColumn
     * @param string $empColumn
     * @return string
     */
    public function getScopeCondition($employeeId, $deptColumn = 'department_id', $empColumn = 'employee_id') {
        $deptIds = $this->dataScopeService->getAccessibleDepartments($employeeId);
        
        if (empty($deptIds)) {
            return "{$empColumn} = {$employeeId}";
        }
        
        $deptIdsStr = implode(',', $deptIds);
        return "({$deptColumn} IN ({$deptIdsStr}) OR {$empColumn} = {$employeeId})";
    }
}
```

### 4. PermissionService.php

```php
<?php
/**
 * 권한 관리 서비스
 */
class PermissionService {
    
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * 직원의 모든 권한 조회 (그룹 + 개별)
     * 
     * @param int $employeeId
     * @return array 권한 코드 배열
     */
    public function getEmployeePermissions($employeeId) {
        $permissions = [];
        
        // 1. 권한 그룹을 통한 권한
        $groupPerms = $this->db->query("
            SELECT DISTINCT p.code
            FROM employee_roles er
            JOIN role_permission_groups rpg ON er.role_id = rpg.role_id
            JOIN permission_group_items pgi ON rpg.group_id = pgi.group_id
            JOIN permissions p ON pgi.permission_id = p.id
            WHERE er.employee_id = ?
        ", [$employeeId]);
        
        $permissions = array_merge($permissions, array_column($groupPerms, 'code'));
        
        // 2. 개별 권한
        $individualPerms = $this->db->query("
            SELECT DISTINCT p.code
            FROM employee_roles er
            JOIN role_permissions rp ON er.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE er.employee_id = ?
        ", [$employeeId]);
        
        $permissions = array_merge($permissions, array_column($individualPerms, 'code'));
        
        return array_unique($permissions);
    }
    
    /**
     * 권한 체크
     * 
     * @param int $employeeId
     * @param string $permissionCode
     * @return bool
     */
    public function hasPermission($employeeId, $permissionCode) {
        $permissions = $this->getEmployeePermissions($employeeId);
        return in_array($permissionCode, $permissions);
    }
    
    /**
     * 역할에 권한 그룹 할당
     * 
     * @param int $roleId
     * @param int $groupId
     * @return bool
     */
    public function assignPermissionGroup($roleId, $groupId) {
        try {
            $this->db->execute(
                "INSERT IGNORE INTO role_permission_groups (role_id, group_id) VALUES (?, ?)",
                [$roleId, $groupId]
            );
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 역할에서 권한 그룹 제거
     * 
     * @param int $roleId
     * @param int $groupId
     * @return bool
     */
    public function removePermissionGroup($roleId, $groupId) {
        try {
            $this->db->execute(
                "DELETE FROM role_permission_groups WHERE role_id = ? AND group_id = ?",
                [$roleId, $groupId]
            );
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * 권한 그룹 상세 조회
     * 
     * @param int $groupId
     * @return array
     */
    public function getPermissionGroupDetail($groupId) {
        $group = $this->db->queryOne(
            "SELECT * FROM permission_groups WHERE id = ?",
            [$groupId]
        );
        
        if (!$group) {
            return null;
        }
        
        $group['permissions'] = $this->db->query("
            SELECT p.*, pgi.is_required
            FROM permission_group_items pgi
            JOIN permissions p ON pgi.permission_id = p.id
            WHERE pgi.group_id = ?
            ORDER BY pgi.display_order, p.name
        ", [$groupId]);
        
        return $group;
    }
}
```

---

## 권한 그룹 시스템

### 1. 권한 그룹 관리 컨트롤러

```php
<?php
/**
 * 권한 그룹 관리 컨트롤러
 */
class PermissionGroupController {
    
    private $permissionService;
    
    public function __construct(PermissionService $permissionService) {
        $this->permissionService = $permissionService;
    }
    
    /**
     * 권한 그룹 목록
     */
    public function index() {
        $groups = $this->db->query("
            SELECT pg.*, COUNT(pgi.id) as permission_count
            FROM permission_groups pg
            LEFT JOIN permission_group_items pgi ON pg.id = pgi.group_id
            WHERE pg.is_active = 1
            GROUP BY pg.id
            ORDER BY pg.display_order, pg.name
        ");
        
        return $this->view('permissions/groups/index', ['groups' => $groups]);
    }
    
    /**
     * 권한 그룹 상세
     */
    public function show($groupId) {
        $group = $this->permissionService->getPermissionGroupDetail($groupId);
        
        if (!$group) {
            return $this->error404('권한 그룹을 찾을 수 없습니다');
        }
        
        return $this->view('permissions/groups/show', ['group' => $group]);
    }
    
    /**
     * 권한 그룹 생성
     */
    public function create() {
        $permissions = $this->db->query("
            SELECT * FROM permissions 
            ORDER BY module, name
        ");
        
        return $this->view('permissions/groups/create', ['permissions' => $permissions]);
    }
    
    /**
     * 권한 그룹 저장
     */
    public function store($request) {
        $this->db->beginTransaction();
        
        try {
            // 그룹 생성
            $groupId = $this->db->execute(
                "INSERT INTO permission_groups (name, code, description, menu_code) 
                 VALUES (?, ?, ?, ?)",
                [
                    $request['name'],
                    $request['code'],
                    $request['description'],
                    $request['menu_code']
                ]
            );
            
            // 권한 추가
            if (!empty($request['permissions'])) {
                foreach ($request['permissions'] as $permId => $data) {
                    $this->db->execute(
                        "INSERT INTO permission_group_items 
                         (group_id, permission_id, is_required, display_order) 
                         VALUES (?, ?, ?, ?)",
                        [
                            $groupId,
                            $permId,
                            $data['is_required'] ?? 0,
                            $data['display_order'] ?? 0
                        ]
                    );
                }
            }
            
            $this->db->commit();
            return $this->redirect('/permissions/groups/' . $groupId);
            
        } catch (Exception $e) {
            $this->db->rollback();
            return $this->error('권한 그룹 생성 실패: ' . $e->getMessage());
        }
    }
}
```

### 2. 역할에 권한 그룹 할당

```php
<?php
/**
 * 역할-권한 관리 컨트롤러
 */
class RolePermissionController {
    
    private $permissionService;
    
    /**
     * 역할의 권한 설정 페이지
     */
    public function edit($roleId) {
        $role = $this->db->queryOne("SELECT * FROM roles WHERE id = ?", [$roleId]);
        
        if (!$role) {
            return $this->error404('역할을 찾을 수 없습니다');
        }
        
        // 모든 권한 그룹
        $allGroups = $this->db->query("
            SELECT pg.*, 
                   CASE WHEN rpg.id IS NOT NULL THEN 1 ELSE 0 END as is_assigned
            FROM permission_groups pg
            LEFT JOIN role_permission_groups rpg 
                ON pg.id = rpg.group_id AND rpg.role_id = ?
            WHERE pg.is_active = 1
            ORDER BY pg.display_order, pg.name
        ", [$roleId]);
        
        // 각 그룹의 권한 상세
        foreach ($allGroups as &$group) {
            $group['permissions'] = $this->db->query("
                SELECT p.*, pgi.is_required
                FROM permission_group_items pgi
                JOIN permissions p ON pgi.permission_id = p.id
                WHERE pgi.group_id = ?
                ORDER BY pgi.display_order
            ", [$group['id']]);
        }
        
        // 개별 권한 (그룹에 속하지 않은)
        $individualPermissions = $this->db->query("
            SELECT p.*,
                   CASE WHEN rp.id IS NOT NULL THEN 1 ELSE 0 END as is_assigned
            FROM permissions p
            LEFT JOIN role_permissions rp 
                ON p.id = rp.permission_id AND rp.role_id = ?
            WHERE NOT EXISTS (
                SELECT 1 FROM permission_group_items pgi WHERE pgi.permission_id = p.id
            )
            ORDER BY p.module, p.name
        ", [$roleId]);
        
        return $this->view('roles/permissions/edit', [
            'role' => $role,
            'permissionGroups' => $allGroups,
            'individualPermissions' => $individualPermissions
        ]);
    }
    
    /**
     * 권한 그룹 할당/해제 (AJAX)
     */
    public function toggleGroup($roleId, $groupId) {
        $exists = $this->db->queryOne(
            "SELECT id FROM role_permission_groups WHERE role_id = ? AND group_id = ?",
            [$roleId, $groupId]
        );
        
        if ($exists) {
            // 제거
            $result = $this->permissionService->removePermissionGroup($roleId, $groupId);
            return $this->json(['success' => $result, 'action' => 'removed']);
        } else {
            // 추가
            $result = $this->permissionService->assignPermissionGroup($roleId, $groupId);
            return $this->json(['success' => $result, 'action' => 'assigned']);
        }
    }
}
```

---

## UI/UX 설계

### 1. 메뉴 구성 및 권한 제어

```php
<?php
/**
 * 메뉴 렌더링 클래스
 */
class Menu {
    
    private $currentEmployee;
    private $permissionService;
    
    public function __construct($currentEmployee, PermissionService $permissionService) {
        $this->currentEmployee = $currentEmployee;
        $this->permissionService = $permissionService;
    }
    
    /**
     * 메뉴 구조 정의
     */
    private function getMenuStructure() {
        return [
            [
                'title' => '조직 관리',
                'icon' => '🏢',
                'children' => [
                    [
                        'title' => '부서 관리',
                        'url' => '/departments',
                        'permission' => 'org.dept.view'
                    ],
                    [
                        'title' => '직원 관리',
                        'url' => '/employees',
                        'permission' => 'org.employee.view'
                    ],
                    [
                        'title' => '조직도',
                        'url' => '/organization-chart',
                        'permission' => 'org.chart.view'
                    ],
                ]
            ],
            [
                'title' => '인사 관리',
                'icon' => '👤',
                'children' => [
                    [
                        'title' => '연차 관리',
                        'url' => '/leaves',
                        'permission' => 'hr.leave.view'
                    ],
                    [
                        'title' => '근태 관리',
                        'url' => '/attendance',
                        'permission' => 'hr.attendance.view'
                    ],
                    [
                        'title' => '평가 관리',
                        'url' => '/evaluation',
                        'permission' => 'hr.eval.view'
                    ],
                ]
            ],
            [
                'title' => '시스템 관리',
                'icon' => '⚙️',
                'permission' => 'system.manage',
                'children' => [
                    [
                        'title' => '역할 관리',
                        'url' => '/roles',
                        'permission' => 'system.role.manage'
                    ],
                    [
                        'title' => '권한 관리',
                        'url' => '/permissions',
                        'permission' => 'system.perm.manage'
                    ],
                    [
                        'title' => '권한 그룹 관리',
                        'url' => '/permission-groups',
                        'permission' => 'system.perm.manage'
                    ],
                    [
                        'title' => '데이터 접근 관리',
                        'url' => '/data-scopes',
                        'permission' => 'system.scope.manage'
                    ],
                ]
            ],
            [
                'title' => '리포트',
                'icon' => '📊',
                'children' => [
                    [
                        'title' => '연차 현황',
                        'url' => '/reports/leaves',
                        'permission' => 'report.leave.view'
                    ],
                    [
                        'title' => '부서별 통계',
                        'url' => '/reports/departments',
                        'permission' => 'report.dept.view'
                    ],
                ]
            ]
        ];
    }
    
    /**
     * 메뉴 HTML 렌더링
     */
    public function render() {
        $menuItems = $this->getMenuStructure();
        return $this->renderMenuItems($menuItems);
    }
    
    /**
     * 재귀적 메뉴 렌더링
     */
    private function renderMenuItems($items, $depth = 0) {
        $html = '<ul class="menu-level-' . $depth . '">';
        
        foreach ($items as $item) {
            // 권한 체크
            if (isset($item['permission']) && 
                !$this->hasPermission($item['permission'])) {
                continue;
            }
            
            // 자식 메뉴 권한 체크
            if (isset($item['children'])) {
                $item['children'] = array_filter($item['children'], function($child) {
                    return !isset($child['permission']) || 
                           $this->hasPermission($child['permission']);
                });
                
                // 접근 가능한 자식이 없으면 부모도 숨김
                if (empty($item['children'])) {
                    continue;
                }
            }
            
            $html .= '<li class="menu-item">';
            
            if (isset($item['children'])) {
                $html .= '<a href="#" class="menu-toggle">';
                $html .= '<span class="menu-icon">' . ($item['icon'] ?? '') . '</span>';
                $html .= '<span class="menu-title">' . $item['title'] . '</span>';
                $html .= '<span class="menu-arrow">▼</span>';
                $html .= '</a>';
                $html .= $this->renderMenuItems($item['children'], $depth + 1);
            } else {
                $html .= '<a href="' . $item['url'] . '">';
                $html .= '<span class="menu-icon">' . ($item['icon'] ?? '') . '</span>';
                $html .= '<span class="menu-title">' . $item['title'] . '</span>';
                $html .= '</a>';
            }
            
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        return $html;
    }
    
    /**
     * 권한 체크
     */
    private function hasPermission($permission) {
        return $this->permissionService->hasPermission(
            $this->currentEmployee['id'],
            $permission
        );
    }
}
```

### 2. 역할 권한 설정 UI

```php
<!-- views/roles/permissions/edit.php -->
<!DOCTYPE html>
<html>
<head>
    <title>권한 설정 - <?= htmlspecialchars($role['name']) ?></title>
    <link rel="stylesheet" href="/css/admin.css">
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1>권한 설정</h1>
            <p class="text-muted">역할: <?= htmlspecialchars($role['name']) ?></p>
        </div>
        
        <!-- 탭 메뉴 -->
        <ul class="nav nav-tabs">
            <li class="active">
                <a href="#by-group" data-toggle="tab">그룹별 권한</a>
            </li>
            <li>
                <a href="#by-individual" data-toggle="tab">개별 권한</a>
            </li>
        </ul>
        
        <div class="tab-content">
            <!-- 그룹별 권한 -->
            <div id="by-group" class="tab-pane active">
                <div class="alert alert-info">
                    💡 권한 그룹을 선택하면 관련된 모든 권한이 자동으로 부여됩니다.
                    필수 권한이 누락되는 것을 방지할 수 있습니다.
                </div>
                
                <div class="permission-groups">
                    <?php foreach ($permissionGroups as $group): ?>
                    <div class="group-card">
                        <div class="group-header">
                            <label class="group-label">
                                <input type="checkbox" 
                                       class="group-checkbox"
                                       data-group-id="<?= $group['id'] ?>"
                                       data-role-id="<?= $role['id'] ?>"
                                       <?= $group['is_assigned'] ? 'checked' : '' ?>>
                                <strong><?= htmlspecialchars($group['name']) ?></strong>
                            </label>
                            <span class="badge badge-secondary">
                                <?= count($group['permissions']) ?>개 권한
                            </span>
                        </div>
                        
                        <div class="group-description">
                            <?= htmlspecialchars($group['description']) ?>
                        </div>
                        
                        <div class="group-permissions">
                            <small class="text-muted">포함된 권한:</small>
                            <ul class="permission-list">
                                <?php foreach ($group['permissions'] as $perm): ?>
                                <li>
                                    <span class="permission-name">
                                        <?= htmlspecialchars($perm['name']) ?>
                                    </span>
                                    <code class="permission-code">
                                        <?= htmlspecialchars($perm['code']) ?>
                                    </code>
                                    <?php if ($perm['is_required']): ?>
                                    <span class="badge badge-danger">필수</span>
                                    <?php endif; ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- 개별 권한 -->
            <div id="by-individual" class="tab-pane">
                <div class="alert alert-warning">
                    ⚠️ <strong>주의:</strong> 개별 권한을 직접 설정하면 필수 권한이 누락될 수 있습니다.
                    가급적 "그룹별 권한" 탭을 사용하세요.
                </div>
                
                <form method="POST" action="/roles/<?= $role['id'] ?>/permissions/individual">
                    <div class="individual-permissions">
                        <?php 
                        $currentModule = null;
                        foreach ($individualPermissions as $perm): 
                            if ($currentModule !== $perm['module']):
                                if ($currentModule !== null) echo '</div>';
                                $currentModule = $perm['module'];
                        ?>
                        <div class="permission-module">
                            <h4><?= htmlspecialchars($perm['module']) ?></h4>
                        <?php endif; ?>
                            
                            <label class="permission-item">
                                <input type="checkbox" 
                                       name="permissions[]" 
                                       value="<?= $perm['id'] ?>"
                                       <?= $perm['is_assigned'] ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($perm['name']) ?></span>
                                <code><?= htmlspecialchars($perm['code']) ?></code>
                                <?php if ($perm['is_menu_permission']): ?>
                                <span class="badge badge-primary">메뉴 접근</span>
                                <?php endif; ?>
                            </label>
                        
                        <?php endforeach; ?>
                        <?php if ($currentModule !== null) echo '</div>'; ?>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">저장</button>
                        <a href="/roles" class="btn btn-secondary">취소</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="/js/role-permissions.js"></script>
</body>
</html>
```

```javascript
// public/js/role-permissions.js
document.addEventListener('DOMContentLoaded', function() {
    
    // 권한 그룹 체크박스 이벤트
    document.querySelectorAll('.group-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', async function() {
            const groupId = this.dataset.groupId;
            const roleId = this.dataset.roleId;
            const isChecked = this.checked;
            
            try {
                const response = await fetch(
                    `/api/roles/${roleId}/permission-groups/${groupId}/toggle`,
                    { method: 'POST' }
                );
                
                const result = await response.json();
                
                if (result.success) {
                    showToast(
                        isChecked ? '권한 그룹이 추가되었습니다' : '권한 그룹이 제거되었습니다',
                        'success'
                    );
                } else {
                    this.checked = !isChecked; // 원상복구
                    showToast('오류가 발생했습니다', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                this.checked = !isChecked;
                showToast('네트워크 오류가 발생했습니다', 'error');
            }
        });
    });
    
    // 토스트 알림
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
});
```

### 3. 데이터 접근 관리 UI

```php
<!-- views/data-scopes/index.php -->
<!DOCTYPE html>
<html>
<head>
    <title>데이터 접근 권한 관리</title>
    <link rel="stylesheet" href="/css/admin.css">
</head>
<body>
    <div class="container-fluid">
        <div class="page-header">
            <h1>데이터 접근 권한 관리</h1>
            <p class="text-muted">직원별로 접근 가능한 부서를 설정합니다</p>
        </div>
        
        <div class="row">
            <!-- 직원 목록 -->
            <div class="col-md-4">
                <div class="panel">
                    <div class="panel-header">
                        <h3>직원 목록</h3>
                        <input type="text" 
                               id="employee-search" 
                               class="form-control" 
                               placeholder="이름 또는 사번 검색">
                    </div>
                    
                    <div class="panel-body">
                        <ul class="employee-list" id="employee-list">
                            <?php foreach ($employees as $emp): ?>
                            <li class="employee-item" 
                                data-employee-id="<?= $emp['id'] ?>"
                                onclick="loadEmployeeScopes(<?= $emp['id'] ?>)">
                                <div class="emp-avatar">
                                    <?= mb_substr($emp['name'], 0, 1) ?>
                                </div>
                                <div class="emp-info">
                                    <div class="emp-name"><?= htmlspecialchars($emp['name']) ?></div>
                                    <div class="emp-no"><?= htmlspecialchars($emp['employee_no']) ?></div>
                                    <div class="emp-dept text-muted">
                                        <?= htmlspecialchars($emp['department_name']) ?>
                                    </div>
                                </div>
                                <div class="emp-roles">
                                    <?php foreach ($emp['roles'] as $role): ?>
                                    <span class="badge badge-info">
                                        <?= htmlspecialchars($role['name']) ?>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- 관리 부서 설정 -->
            <div class="col-md-8">
                <div class="panel" id="scope-config-panel" style="display:none;">
                    <div class="panel-header">
                        <h3>관리 부서 설정</h3>
                        <div class="selected-employee">
                            <strong id="selected-employee-name"></strong>
                            <span id="selected-employee-no" class="text-muted"></span>
                        </div>
                    </div>
                    
                    <div class="panel-body">
                        <!-- 부서 트리 -->
                        <div class="dept-tree-section">
                            <h4>부서 선택</h4>
                            <div class="dept-tree" id="dept-tree">
                                <?= renderDepartmentTree($departments) ?>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <!-- 현재 설정된 관리 부서 -->
                        <div class="current-scopes-section">
                            <h4>현재 관리 부서</h4>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>부서명</th>
                                        <th width="150">하위 부서 포함</th>
                                        <th width="100">작업</th>
                                    </tr>
                                </thead>
                                <tbody id="current-scopes">
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">
                                            직원을 선택하세요
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="form-actions">
                            <button class="btn btn-primary" onclick="saveDepartmentScopes()">
                                💾 저장
                            </button>
                            <button class="btn btn-secondary" onclick="resetScopes()">
                                🔄 초기화
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="empty-state" id="empty-state">
                    <p class="text-muted">좌측에서 직원을 선택하세요</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/js/data-scopes.js"></script>
</body>
</html>

<?php
/**
 * 부서 트리 렌더링 함수
 */
function renderDepartmentTree($departments, $parentId = null, $depth = 0) {
    $html = '<ul class="dept-tree-list">';
    
    foreach ($departments as $dept) {
        if ($dept['parent_id'] == $parentId) {
            $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $depth);
            
            $html .= '<li class="dept-tree-item">';
            $html .= '<label class="dept-checkbox-label">';
            $html .= '<input type="checkbox" 
                            class="dept-checkbox" 
                            data-dept-id="' . $dept['id'] . '"
                            data-dept-name="' . htmlspecialchars($dept['name']) . '">';
            $html .= $indent . htmlspecialchars($dept['name']);
            $html .= '</label>';
            $html .= '<label class="children-checkbox-label">';
            $html .= '<input type="checkbox" class="children-checkbox" checked>';
            $html .= '하위 포함';
            $html .= '</label>';
            
            // 재귀: 하위 부서
            $html .= renderDepartmentTree($departments, $dept['id'], $depth + 1);
            $html .= '</li>';
        }
    }
    
    $html .= '</ul>';
    return $html;
}
?>
```

```javascript
// public/js/data-scopes.js
let currentEmployeeId = null;

// 직원 선택 시 관리 부서 로드
async function loadEmployeeScopes(employeeId) {
    currentEmployeeId = employeeId;
    
    // UI 업데이트
    document.querySelectorAll('.employee-item').forEach(item => {
        item.classList.remove('active');
    });
    event.currentTarget.classList.add('active');
    
    const emp = event.currentTarget;
    document.getElementById('selected-employee-name').textContent = 
        emp.querySelector('.emp-name').textContent;
    document.getElementById('selected-employee-no').textContent = 
        emp.querySelector('.emp-no').textContent;
    
    document.getElementById('scope-config-panel').style.display = 'block';
    document.getElementById('empty-state').style.display = 'none';
    
    try {
        const response = await fetch(`/api/employees/${employeeId}/department-scopes`);
        const data = await response.json();
        
        // 현재 설정 표시
        renderCurrentScopes(data.scopes);
        
        // 체크박스 상태 업데이트
        document.querySelectorAll('.dept-checkbox').forEach(cb => {
            cb.checked = false;
        });
        
        data.scopes.forEach(scope => {
            const checkbox = document.querySelector(
                `.dept-checkbox[data-dept-id="${scope.department_id}"]`
            );
            if (checkbox) {
                checkbox.checked = true;
                const childrenCb = checkbox.parentElement.nextElementSibling.querySelector('.children-checkbox');
                if (childrenCb) {
                    childrenCb.checked = scope.include_children;
                }
            }
        });
        
    } catch (error) {
        console.error('Error loading scopes:', error);
        alert('데이터를 불러오는 중 오류가 발생했습니다.');
    }
}

// 현재 관리 부서 렌더링
function renderCurrentScopes(scopes) {
    const tbody = document.getElementById('current-scopes');
    
    if (scopes.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="3" class="text-center text-muted">
                    설정된 관리 부서가 없습니다
                </td>
            </tr>
        `;
        return;
    }
    
    let html = '';
    scopes.forEach(scope => {
        html += `
            <tr>
                <td>${escapeHtml(scope.department_name)}</td>
                <td>
                    <span class="badge ${scope.include_children ? 'badge-success' : 'badge-secondary'}">
                        ${scope.include_children ? '포함' : '제외'}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-danger" 
                            onclick="removeScope(${scope.id})">
                        삭제
                    </button>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

// 관리 부서 저장
async function saveDepartmentScopes() {
    if (!currentEmployeeId) {
        alert('직원을 선택하세요');
        return;
    }
    
    const scopes = [];
    
    document.querySelectorAll('.dept-checkbox:checked').forEach(cb => {
        const deptId = cb.dataset.deptId;
        const includeChildren = cb.parentElement.nextElementSibling
            .querySelector('.children-checkbox').checked;
        
        scopes.push({
            department_id: parseInt(deptId),
            include_children: includeChildren
        });
    });
    
    try {
        const response = await fetch(
            `/api/employees/${currentEmployeeId}/department-scopes`,
            {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ scopes })
            }
        );
        
        const result = await response.json();
        
        if (result.success) {
            alert('저장되었습니다');
            loadEmployeeScopes(currentEmployeeId);
        } else {
            alert('저장 실패: ' + result.message);
        }
    } catch (error) {
        console.error('Error saving scopes:', error);
        alert('저장 중 오류가 발생했습니다');
    }
}

// 관리 부서 초기화
function resetScopes() {
    if (!confirm('모든 설정을 초기화하시겠습니까?')) {
        return;
    }
    
    document.querySelectorAll('.dept-checkbox').forEach(cb => {
        cb.checked = false;
    });
}

// 직원 검색
document.getElementById('employee-search')?.addEventListener('input', function(e) {
    const keyword = e.target.value.toLowerCase();
    
    document.querySelectorAll('.employee-item').forEach(item => {
        const name = item.querySelector('.emp-name').textContent.toLowerCase();
        const no = item.querySelector('.emp-no').textContent.toLowerCase();
        
        if (name.includes(keyword) || no.includes(keyword)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
});

// HTML 이스케이프
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
```

### 4. 연차 관리 페이지

```php
<!-- views/leaves/index.php -->
<!DOCTYPE html>
<html>
<head>
    <title>연차 관리</title>
    <link rel="stylesheet" href="/css/admin.css">
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1>연차 관리</h1>
        </div>
        
        <!-- 탭 메뉴 -->
        <ul class="nav nav-tabs">
            <li class="active">
                <a href="#my-leaves" data-toggle="tab">내 연차</a>
            </li>
            
            <?php if ($hasPermission('hr.leave.view')): ?>
            <li>
                <a href="#team-leaves" data-toggle="tab">팀원 연차</a>
            </li>
            <?php endif; ?>
            
            <?php if ($hasPermission('hr.leave.approve')): ?>
            <li>
                <a href="#approval" data-toggle="tab">
                    승인 대기
                    <?php if ($pendingCount > 0): ?>
                    <span class="badge badge-danger"><?= $pendingCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endif; ?>
        </ul>
        
        <div class="tab-content">
            <!-- 내 연차 -->
            <div id="my-leaves" class="tab-pane active">
                <div class="leave-summary">
                    <div class="summary-card">
                        <h3>연차 현황</h3>
                        <div class="summary-item">
                            <span>총 연차</span>
                            <strong><?= $myLeaveStatus['total'] ?>일</strong>
                        </div>
                        <div class="summary-item">
                            <span>사용</span>
                            <strong class="text-danger"><?= $myLeaveStatus['used'] ?>일</strong>
                        </div>
                        <div class="summary-item">
                            <span>잔여</span>
                            <strong class="text-success"><?= $myLeaveStatus['remaining'] ?>일</strong>
                        </div>
                    </div>
                </div>
                
                <?php if ($hasPermission('hr.leave.create')): ?>
                <button class="btn btn-primary" onclick="openLeaveRequestModal()">
                    ➕ 연차 신청
                </button>
                <?php endif; ?>
                
                <div id="my-leaves-list"></div>
            </div>
            
            <!-- 팀원 연차 -->
            <?php if ($hasPermission('hr.leave.view')): ?>
            <div id="team-leaves" class="tab-pane">
                <!-- 필터 -->
                <div class="filter-section">
                    <div class="row">
                        <div class="col-md-3">
                            <label>부서</label>
                            <select id="filter-department" class="form-control">
                                <option value="">전체</option>
                                <?php foreach ($accessibleDepartments as $dept): ?>
                                <option value="<?= $dept['id'] ?>">
                                    <?= str_repeat('　', $dept['depth']) . htmlspecialchars($dept['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label>상태</label>
                            <select id="filter-status" class="form-control">
                                <option value="">전체</option>
                                <option value="pending">대기</option>
                                <option value="approved">승인</option>
                                <option value="rejected">반려</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label>기간</label>
                            <div class="input-group">
                                <input type="date" id="filter-start-date" class="form-control">
                                <span class="input-group-addon">~</span>
                                <input type="date" id="filter-end-date" class="form-control">
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <label>&nbsp;</label>
                            <button class="btn btn-primary btn-block" onclick="searchLeaves()">
                                🔍 조회
                            </button>
                        </div>
                    </div>
                </div>
                
                <div id="team-leaves-list"></div>
            </div>
            <?php endif; ?>
            
            <!-- 승인 대기 -->
            <?php if ($hasPermission('hr.leave.approve')): ?>
            <div id="approval" class="tab-pane">
                <div class="approval-actions">
                    <button class="btn btn-success" onclick="bulkApprove()">
                        ✓ 선택 항목 일괄 승인
                    </button>
                    <button class="btn btn-danger" onclick="bulkReject()">
                        ✗ 선택 항목 일괄 반려
                    </button>
                </div>
                
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="50">
                                <input type="checkbox" id="select-all">
                            </th>
                            <th>신청일</th>
                            <th>사번</th>
                            <th>이름</th>
                            <th>부서</th>
                            <th>유형</th>
                            <th>시작일</th>
                            <th>종료일</th>
                            <th>일수</th>
                            <th>사유</th>
                            <th width="150">작업</th>
                        </tr>
                    </thead>
                    <tbody id="pending-leaves-list">
                        <!-- AJAX로 로드 -->
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="/js/leaves.js"></script>
</body>
</html>
```

```javascript
// public/js/leaves.js
class LeaveManager {
    
    constructor() {
        this.init();
    }
    
    init() {
        // 탭 활성화
        document.querySelectorAll('[data-toggle="tab"]').forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                this.switchTab(tab.getAttribute('href').substring(1));
            });
        });
        
        // 초기 데이터 로드
        this.loadMyLeaves();
        
        if (document.getElementById('pending-leaves-list')) {
            this.loadPendingLeaves();
        }
    }
    
    switchTab(tabId) {
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.remove('active');
        });
        document.getElementById(tabId).classList.add('active');
        
        document.querySelectorAll('.nav-tabs li').forEach(li => {
            li.classList.remove('active');
        });
        event.target.parentElement.classList.add('active');
    }
    
    // 내 연차 목록
    async loadMyLeaves() {
        try {
            const response = await fetch('/api/leaves/my');
            const data = await response.json();
            
            this.renderLeaveList('my-leaves-list', data.leaves, true);
        } catch (error) {
            console.error('Error loading my leaves:', error);
        }
    }
    
    // 팀원 연차 목록
    async searchLeaves() {
        const filters = {
            department_id: document.getElementById('filter-department').value,
            status: document.getElementById('filter-status').value,
            start_date: document.getElementById('filter-start-date').value,
            end_date: document.getElementById('filter-end-date').value
        };
        
        try {
            const response = await fetch('/api/leaves?' + new URLSearchParams(filters));
            const data = await response.json();
            
            this.renderLeaveList('team-leaves-list', data.leaves, false);
        } catch (error) {
            console.error('Error searching leaves:', error);
        }
    }
    
    // 승인 대기 목록
    async loadPendingLeaves() {
        try {
            const response = await fetch('/api/leaves?status=pending');
            const data = await response.json();
            
            this.renderPendingList(data.leaves);
        } catch (error) {
            console.error('Error loading pending leaves:', error);
        }
    }
    
    // 연차 목록 렌더링
    renderLeaveList(containerId, leaves, isMine) {
        const container = document.getElementById(containerId);
        
        if (leaves.length === 0) {
            container.innerHTML = '<p class="text-center text-muted">연차 내역이 없습니다</p>';
            return;
        }
        
        let html = '<table class="table table-hover"><thead><tr>';
        html += '<th>신청일</th><th>유형</th><th>기간</th><th>일수</th>';
        html += '<th>사유</th><th>상태</th>';
        if (!isMine) html += '<th>신청자</th><th>부서</th>';
        html += '</tr></thead><tbody>';
        
        leaves.forEach(leave => {
            const statusClass = {
                'pending': 'warning',
                'approved': 'success',
                'rejected': 'danger'
            }[leave.status] || 'secondary';
            
            const statusText = {
                'pending': '대기',
                'approved': '승인',
                'rejected': '반려'
            }[leave.status] || leave.status;
            
            html += '<tr>';
            html += `<td>${leave.created_at}</td>`;
            html += `<td>${leave.leave_type}</td>`;
            html += `<td>${leave.start_date} ~ ${leave.end_date}</td>`;
            html += `<td>${leave.days}일</td>`;
            html += `<td>${leave.reason || '-'}</td>`;
            html += `<td><span class="badge badge-${statusClass}">${statusText}</span></td>`;
            if (!isMine) {
                html += `<td>${leave.employee_name}</td>`;
                html += `<td>${leave.department_name}</td>`;
            }
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        container.innerHTML = html;
    }
    
    // 승인 대기 목록 렌더링
    renderPendingList(leaves) {
        const tbody = document.getElementById('pending-leaves-list');
        
        if (leaves.length === 0) {
            tbody.innerHTML = '<tr><td colspan="11" class="text-center text-muted">승인 대기 중인 연차가 없습니다</td></tr>';
            return;
        }
        
        let html = '';
        leaves.forEach(leave => {
            html += '<tr>';
            html += `<td><input type="checkbox" class="leave-checkbox" value="${leave.id}"></td>`;
            html += `<td>${leave.created_at}</td>`;
            html += `<td>${leave.employee_no}</td>`;
            html += `<td>${leave.employee_name}</td>`;
            html += `<td>${leave.department_name}</td>`;
            html += `<td>${leave.leave_type}</td>`;
            html += `<td>${leave.start_date}</td>`;
            html += `<td>${leave.end_date}</td>`;
            html += `<td>${leave.days}일</td>`;
            html += `<td>${leave.reason || '-'}</td>`;
            html += `<td>
                <button class="btn btn-sm btn-success" onclick="leaveManager.approveLeave(${leave.id})">
                    ✓ 승인
                </button>
                <button class="btn btn-sm btn-danger" onclick="leaveManager.rejectLeave(${leave.id})">
                    ✗ 반려
                </button>
            </td>`;
            html += '</tr>';
        });
        
        tbody.innerHTML = html;
    }
    
    // 연차 승인
    async approveLeave(leaveId) {
        if (!confirm('이 연차를 승인하시겠습니까?')) return;
        
        try {
            const response = await fetch(`/api/leaves/${leaveId}/approve`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'}
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('승인되었습니다');
                this.loadPendingLeaves();
            } else {
                alert('오류: ' + result.message);
            }
        } catch (error) {
            console.error('Error approving leave:', error);
            alert('승인 처리 중 오류가 발생했습니다');
        }
    }
    
    // 연차 반려
    async rejectLeave(leaveId) {
        const reason = prompt('반려 사유를 입력하세요:');
        if (!reason) return;
        
        try {
            const response = await fetch(`/api/leaves/${leaveId}/reject`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ reason })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('반려되었습니다');
                this.loadPendingLeaves();
            } else {
                alert('오류: ' + result.message);
            }
        } catch (error) {
            console.error('Error rejecting leave:', error);
            alert('반려 처리 중 오류가 발생했습니다');
        }
    }
    
    // 일괄 승인
    async bulkApprove() {
        const selected = this.getSelectedLeaves();
        if (selected.length === 0) {
            alert('승인할 항목을 선택하세요');
            return;
        }
        
        if (!confirm(`${selected.length}건을 일괄 승인하시겠습니까?`)) return;
        
        try {
            const response = await fetch('/api/leaves/bulk-approve', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ leave_ids: selected })
            });
            
            const result = await response.json();
            
            alert(`${result.success_count}건 승인 완료`);
            this.loadPendingLeaves();
        } catch (error) {
            console.error('Error bulk approving:', error);
            alert('일괄 승인 중 오류가 발생했습니다');
        }
    }
    
    // 일괄 반려
    async bulkReject() {
        const selected = this.getSelectedLeaves();
        if (selected.length === 0) {
            alert('반려할 항목을 선택하세요');
            return;
        }
        
        const reason = prompt('반려 사유를 입력하세요:');
        if (!reason) return;
        
        try {
            const response = await fetch('/api/leaves/bulk-reject', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ leave_ids: selected, reason })
            });
            
            const result = await response.json();
            
            alert(`${result.success_count}건 반려 완료`);
            this.loadPendingLeaves();
        } catch (error) {
            console.error('Error bulk rejecting:', error);
            alert('일괄 반려 중 오류가 발생했습니다');
        }
    }
    
    // 선택된 연차 ID 가져오기
    getSelectedLeaves() {
        const checkboxes = document.querySelectorAll('.leave-checkbox:checked');
        return Array.from(checkboxes).map(cb => parseInt(cb.value));
    }
}

// 전역 인스턴스
const leaveManager = new LeaveManager();

// 전체 선택 체크박스
document.getElementById('select-all')?.addEventListener('change', function() {
    document.querySelectorAll('.leave-checkbox').forEach(cb => {
        cb.checked = this.checked;
    });
});
```

---

## 적용 예시

### 1. 연차 관리 컨트롤러

```php
<?php
/**
 * 연차 관리 컨트롤러
 */
class LeaveController {
    
    private $db;
    private $dataScopeFilter;
    private $permissionService;
    
    public function __construct($db, DataScopeFilter $filter, PermissionService $permService) {
        $this->db = $db;
        $this->dataScopeFilter = $filter;
        $this->permissionService = $permService;
    }
    
    /**
     * 연차 목록 조회
     */
    public function index($request) {
        $employeeId = $this->getCurrentEmployeeId();
        
        // 권한 체크
        if (!$this->hasPermission($employeeId, 'hr.leave.view')) {
            return $this->error403('연차 조회 권한이 없습니다');
        }
        
        // 기본 쿼리
        $baseQuery = "
            SELECT l.*, 
                   e.employee_no, e.name as employee_name,
                   d.name as department_name,
                   approver.name as approver_name
            FROM leaves l
            JOIN employees e ON l.employee_id = e.id
            JOIN departments d ON l.department_id = d.id
            LEFT JOIN employees approver ON l.approved_by = approver.id
            WHERE 1=1
        ";
        
        // 데이터 스코프 필터 적용
        $filteredQuery = $this->dataScopeFilter->applyScope(
            $employeeId,
            $baseQuery,
            'l.department_id',
            'l.employee_id'
        );
        
        // 추가 필터
        $params = [];
        if (!empty($request['department_id'])) {
            $filteredQuery .= " AND l.department_id = ?";
            $params[] = $request['department_id'];
        }
        
        if (!empty($request['status'])) {
            $filteredQuery .= " AND l.status = ?";
            $params[] = $request['status'];
        }
        
        if (!empty($request['start_date'])) {
            $filteredQuery .= " AND l.start_date >= ?";
            $params[] = $request['start_date'];
        }
        
        if (!empty($request['end_date'])) {
            $filteredQuery .= " AND l.end_date <= ?";
            $params[] = $request['end_date'];
        }
        
        $filteredQuery .= " ORDER BY l.created_at DESC LIMIT 100";
        
        $leaves = $this->db->query($filteredQuery, $params);
        
        return $this->json(['success' => true, 'leaves' => $leaves]);
    }
    
    /**
     * 연차 승인
     */
    public function approve($leaveId, $request) {
        $employeeId = $this->getCurrentEmployeeId();
        
        // 1. 기능 권한 체크
        if (!$this->hasPermission($employeeId, 'hr.leave.approve')) {
            return $this->json([
                'success' => false,
                'message' => '연차 승인 권한이 없습니다'
            ], 403);
        }
        
        // 2. 연차 정보 조회
        $leave = $this->db->queryOne("
            SELECT l.*, e.department_id, e.name as employee_name
            FROM leaves l
            JOIN employees e ON l.employee_id = e.id
            WHERE l.id = ?
        ", [$leaveId]);
        
        if (!$leave) {
            return $this->json([
                'success' => false,
                'message' => '연차를 찾을 수 없습니다'
            ], 404);
        }
        
        // 3. 데이터 접근 권한 체크
        if (!$this->canAccessData($employeeId, $leave['department_id'], $leave['employee_id'])) {
            return $this->json([
                'success' => false,
                'message' => '해당 연차를 승인할 권한이 없습니다'
            ], 403);
        }
        
        // 4. 승인 처리
        $this->db->execute("
            UPDATE leaves 
            SET status = 'approved',
                approved_by = ?,
                approved_at = NOW()
            WHERE id = ?
        ", [$employeeId, $leaveId]);
        
        return $this->json([
            'success' => true,
            'message' => '승인되었습니다'
        ]);
    }
    
    /**
     * 연차 반려
     */
    public function reject($leaveId, $request) {
        $employeeId = $this->getCurrentEmployeeId();
        
        // 권한 체크 (승인과 동일)
        if (!$this->hasPermission($employeeId, 'hr.leave.approve')) {
            return $this->json([
                'success' => false,
                'message' => '연차 반려 권한이 없습니다'
            ], 403);
        }
        
        $leave = $this->db->queryOne("
            SELECT l.*, e.department_id
            FROM leaves l
            JOIN employees e ON l.employee_id = e.id
            WHERE l.id = ?
        ", [$leaveId]);
        
        if (!$leave || !$this->canAccessData($employeeId, $leave['department_id'], $leave['employee_id'])) {
            return $this->json([
                'success' => false,
                'message' => '권한이 없습니다'
            ], 403);
        }
        
        // 반려 처리
        $this->db->execute("
            UPDATE leaves 
            SET status = 'rejected',
                approved_by = ?,
                approved_at = NOW(),
                reject_reason = ?
            WHERE id = ?
        ", [$employeeId, $request['reason'] ?? '', $leaveId]);
        
        return $this->json([
            'success' => true,
            'message' => '반려되었습니다'
        ]);
    }
    
    /**
     * 일괄 승인
     */
    public function bulkApprove($request) {
        $employeeId = $this->getCurrentEmployeeId();
        $leaveIds = $request['leave_ids'] ?? [];
        
        if (empty($leaveIds)) {
            return $this->json([
                'success' => false,
                'message' => '선택된 항목이 없습니다'
            ]);
        }
        
        $successCount = 0;
        
        foreach ($leaveIds as $leaveId) {
            $leave = $this->db->queryOne("
                SELECT l.*, e.department_id
                FROM leaves l
                JOIN employees e ON l.employee_id = e.id
                WHERE l.id = ?
            ", [$leaveId]);
            
            if ($leave && $this->canAccessData($employeeId, $leave['department_id'], $leave['employee_id'])) {
                $this->db->execute("
                    UPDATE leaves 
                    SET status = 'approved',
                        approved_by = ?,
                        approved_at = NOW()
                    WHERE id = ?
                ", [$employeeId, $leaveId]);
                
                $successCount++;
            }
        }
        
        return $this->json([
            'success' => true,
            'success_count' => $successCount,
            'total_count' => count($leaveIds)
        ]);
    }
    
    /**
     * 데이터 접근 권한 체크
     */
    private function canAccessData($employeeId, $targetDeptId, $targetEmployeeId = null) {
        $dataScopeService = new DataScopeService($this->db, new DepartmentService($this->db));
        return $dataScopeService->canAccessData($employeeId, $targetDeptId, $targetEmployeeId);
    }
    
    /**
     * 권한 체크
     */
    private function hasPermission($employeeId, $permission) {
        return $this->permissionService->hasPermission($employeeId, $permission);
    }
    
    /**
     * 현재 로그인한 직원 ID
     */
    private function getCurrentEmployeeId() {
        return $_SESSION['employee_id'] ?? null;
    }
}
```
