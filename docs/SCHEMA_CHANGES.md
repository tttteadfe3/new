# 스키마 변경 이력

## 2025-10-24: 스키마 전체 재작성

- **목표**: 코드베이스와의 일관성 확보 및 가독성 향상
- **주요 변경 사항**:
    - **스키마 재정렬 및 주석 개선**: `database/schema.sql`의 모든 테이블에 대해 필드 주석을 명확하게 수정하고, 필드 순서를 논리적 그룹으로 재배열했습니다.
    - **미사용 필드 제거**: `illegal_disposal_cases2` 테이블과 `Littering` 모델에서 실제 사용되지 않는 `rejection_reason`, `mixed` 필드를 최종적으로 제거했습니다.
    - **필드명 표준화 (waste_collections)**: `waste_collections` 테이블의 `updated_at`, `updated_by`를 `completed_at`, `completed_by`로 변경하여 '완료' 상태를 명확히 표현했습니다.
    - **참조 기준 변경 (user_id -> employee_id)**: 데이터의 행위 주체를 시스템 계정(`user`)에서 실제 직원(`employee`)으로 명확히 하기 위해, `hr_leaves`, `hr_employee_change_logs` 등 데이터 관련 테이블의 담당자 참조 컬럼(`approved_by` 등)을 `employee_id`를 참조하도록 수정했습니다.
    - **코드 및 시더 동기화**: 위의 모든 스키마 변경 사항을 관련 모델, Repository, 시더 파일에 모두 반영하여 시스템 전반의 정합성을 유지했습니다.
- **영향**: 데이터 모델의 역할(시스템 접근 vs. 업무 처리)이 명확해지고, 스키마의 일관성과 유지보수성이 크게 향상되었습니다.
