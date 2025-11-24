# 차량 관리 시스템 메뉴 설정

## 추가된 메뉴 (Menus)

`database/seeds/09_menus.sql` 파일에 다음 메뉴들이 추가되었습니다:

| ID  | 상위 ID | 메뉴명 | URL | 권한 (Permission) |
|-----|---------|--------|-----|-------------------|
| 500 | NULL    | 차량 관리 | # | `vehicle.view` |
| 501 | 500     | 차량 목록 | `/vehicles` | `vehicle.view` |
| 502 | 500     | 내 작업 관리 | `/vehicles/my-work` | `vehicle.work.report` |
| 503 | 500     | 작업 처리 관리 | `/vehicles/manager/work` | `vehicle.work.manage` |
| 504 | 500     | 차량 검사 | `/vehicles/inspections` | `vehicle.inspection.view` |

## 메뉴 구조

- **차량 관리** (사이드바 메뉴)
  - **차량 목록**: 모든 사용자가 접근 가능 (차량 조회 권한 필요)
  - **내 작업 관리**: 운전원이 고장 신고 및 정비 등록을 하는 페이지 (작업 신고 권한 필요)
  - **작업 처리 관리**: Manager가 고장/정비 건을 승인하고 처리하는 페이지 (작업 관리 권한 필요)
  - **차량 검사**: 차량 정기 검사 내역 조회 (검사 조회 권한 필요)

## 적용 방법

데이터베이스 시드를 다시 실행하여 메뉴를 적용해야 합니다.

```bash
# 전체 시드 실행 (주의: 기존 데이터 초기화 가능성 있음)
php run_seeds.php

# 또는 특정 시드 파일만 실행하는 기능이 있다면 사용
```
