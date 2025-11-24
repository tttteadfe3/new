# 차량 소모품 관리 모듈 - 설치 가이드

## 📦 데이터베이스 마이그레이션 순서

터미널에서 다음 순서로 실행하세요:

### 1. 메인 테이블 생성
```bash
mysql -u [사용자명] -p [데이터베이스명] < database/migrations/create_vehicle_consumables.sql
```

### 2. 퍼미션 추가
```bash
mysql -u [사용자명] -p [데이터베이스명] < database/seeds/10_vehicle_consumable_permissions.sql
```

### 3. 메뉴 추가
```bash
mysql -u [사용자명] -p [데이터베이스명] < database/seeds/11_vehicle_consumable_menu.sql
```

### 4. 역할-퍼미션 매핑
```bash
mysql -u [사용자명] -p [데이터베이스명] < database/seeds/12_vehicle_consumable_role_permissions.sql
```

## 🔐 추가된 퍼미션

| ID  | Key | 설명 |
|-----|-----|------|
| 157 | `vehicle.consumable.view` | 차량 소모품 조회 |
| 158 | `vehicle.consumable.manage` | 차량 소모품 관리 (등록/수정/삭제) |
| 159 | `vehicle.consumable.stock` | 차량 소모품 입출고 처리 |

## 📋 메뉴 구조

```
차량 관리 (500)
├── 차량 목록 (501)
├── 수리/정비 요청 (502)
├── 작업 접수/승인 (503)
├── 차량 검사 (504)
└── 소모품 관리 (505) ← 새로 추가됨
    └── /vehicles/consumables
    └── Icon: ri-shopping-cart-2-line
    └── Permission: vehicle.consumable.view
```

## 👥 기본 역할 권한 매핑

다음 역할에 모든 소모품 권한이 부여됩니다:
- **Super Admin** (role_id: 1)
- **차량관리자** (role_id: 16)

## 🔄 한 번에 실행 (올인원 스크립트)

PowerShell에서:
```powershell
$db_user = "your_username"
$db_name = "erp"

mysql -u $db_user -p $db_name < database/migrations/create_vehicle_consumables.sql
mysql -u $db_user -p $db_name < database/seeds/10_vehicle_consumable_permissions.sql
mysql -u $db_user -p $db_name < database/seeds/11_vehicle_consumable_menu.sql
mysql -u $db_user -p $db_name < database/seeds/12_vehicle_consumable_role_permissions.sql

Write-Host "✅ 차량 소모품 모듈 설치 완료!" -ForegroundColor Green
```

Bash/Linux에서:
```bash
#!/bin/bash
DB_USER="your_username"
DB_NAME="erp"

mysql -u $DB_USER -p $DB_NAME < database/migrations/create_vehicle_consumables.sql
mysql -u $DB_USER -p $DB_NAME < database/seeds/10_vehicle_consumable_permissions.sql
mysql -u $DB_USER -p $DB_NAME < database/seeds/11_vehicle_consumable_menu.sql
mysql -u $DB_USER -p $DB_NAME < database/seeds/12_vehicle_consumable_role_permissions.sql

echo "✅ 차량 소모품 모듈 설치 완료!"
```

## ✅ 설치 확인

설치가 완료되면 다음을 확인하세요:

1. **데이터베이스 테이블 확인**
   ```sql
   SHOW TABLES LIKE 'vehicle_consumable%';
   ```
   결과: 3개의 테이블이 표시되어야 함
   - `vehicle_consumables`
   - `vehicle_consumable_usage`
   - `vehicle_consumable_stock_in`

2. **퍼미션 확인**
   ```sql
   SELECT * FROM sys_permissions WHERE id BETWEEN 157 AND 159;
   ```

3. **메뉴 확인**
   ```sql
   SELECT * FROM sys_menus WHERE id = 505;
   ```

4. **역할-퍼미션 매핑 확인**
   ```sql
   SELECT * FROM sys_role_permissions 
   WHERE permission_id BETWEEN 157 AND 159;
   ```

## 🚀 접속 방법

1. 브라우저에서 로그인
2. 좌측 메뉴에서 **차량 관리** → **소모품 관리** 클릭
3. 또는 직접 URL 접속: `http://your-domain/vehicles/consumables`

## 🎯 다음 단계

설치 완료 후:
1. 소모품 카테고리 설정 (예: 엔진오일, 타이어, 브레이크패드 등)
2. 초기 소모품 등록
3. 최소 재고량 설정
4. 팀원들에게 사용 교육

---

**설치 중 문제 발생 시:**
- 데이터베이스 접속 정보 확인
- 테이블/퍼미션 ID 충돌 확인
- 에러 로그 확인

진행 완료! 🎉
