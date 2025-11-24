# 차량 관리 시스템 - 상태 값 정의

## 차량 상태 (vm_vehicles.status_code)

| 한글 | 설명 |
|------|------|
| 정상 | 정상 운행 가능한 상태 |
| 수리중 | 수리 진행 중 |
| 폐차 | 폐차 처리된 차량 |

## 고장 처리 상태 (vm_vehicle_breakdowns.status)

| 한글 | 설명 | 처리자 |
|------|------|--------|
| 접수 | 고장 신고 접수 | 운전원 |
| 접수완료 | 중간관리자가 접수 확인 | 중간관리자 |
| 처리결정 | 자체/외부 수리 결정 | 중간관리자 |
| 완료 | 수리 완료 | 운전원/정비사 |
| 승인완료 | 수리 최종 승인 | 중간관리자 |

## 수리 유형 (vm_vehicle_repairs.repair_type)

| 한글 | 설명 |
|------|------|
| 자체수리 | 내부 정비사가 수리 |
| 외부수리 | 외부 업체에서 수리 |

## 자체 정비 상태 (vm_vehicle_maintenances.status)

| 한글 | 설명 | 처리자 |
|------|------|--------|
| 완료 | 정비 완료 | 운전원 |
| 승인완료 | 정비 최종 승인 | 중간관리자 |

## API 요청 예시 (업데이트)

### 차량 등록
```json
POST /api/vehicles
{
  "vehicle_number": "12가3456",
  "model": "포터2",
  "year": 2023,
  "department_id": 5,
  "driver_employee_id": 42,
  "status_code": "정상"
}
```

### 고장 신고
```json
POST /api/vehicles/breakdowns
{
  "vehicle_id": 1,
  "driver_employee_id": 42,
  "breakdown_item": "엔진 이상음",
  "description": "가속 시 큰 소리 발생",
  "mileage": 15000,
  "status": "접수"
}
```

### 수리 방법 결정
```json
POST /api/vehicles/breakdowns/1/decide
{
  "type": "자체수리"
}
```

### 수리 완료 등록
```json
POST /api/vehicles/breakdowns/1/complete
{
  "repair_type": "자체수리",
  "repair_item": "엔진 벨트 교체",
  "parts_used": "엔진 벨트 v-belt",
  "cost": 50000,
  "repairer_id": 30,
  "completed_at": "2025-11-22 14:30:00"
}
```

## 워크플로우 상태 변화

### 차량 수리 워크플로우
```
초기: 접수
  ↓ (중간관리자 확인)
접수완료
  ↓ (중간관리자 결정: 자체수리/외부수리)
처리결정
  ↓ (수리 완료)
완료
  ↓ (중간관리자 확인)
승인완료
```

### 자체 정비 워크플로우
```
초기: 완료
  ↓ (중간관리자 확인)
승인완료
```

## 주의사항

1. **상태 값은 반드시 정확한 한글 문자열을 사용**해야 합니다.
2. 대소문자 구분 없음 (MySQL utf8mb4_unicode_ci collation 사용)
3. API 요청 시 영문 상태 값을 사용하면 오류 발생 가능

## 변경 내역

- 2025-11-22: 모든 상태 값을 영문에서 한글로 변경
  - 차량 상태: NORMAL → 정상, REPAIRING → 수리중, DISPOSED → 폐차
  - 고장 상태: REGISTERED → 접수, RECEIVED → 접수완료, DECIDED → 처리결정, COMPLETED → 완료, APPROVED → 승인완료
  - 수리 유형: INTERNAL → 자체수리, EXTERNAL → 외부수리
