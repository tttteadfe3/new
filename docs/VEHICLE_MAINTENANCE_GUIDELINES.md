# 🚘 차량 유지관리 시스템 개발 가이드라인

## 1. 개요

본 문서는 차량 유지관리 시스템의 개발 및 유지보수를 위한 기술적인 가이드라인을 제공합니다. 시스템의 아키텍처, 코드 구조, 데이터베이스 스키마, 그리고 API 엔드포인트에 대한 명세를 포함합니다.

## 2. 코드 구조

시스템은 Model-Repository-Service-Controller 아키텍처 패턴을 따릅니다. 각 계층의 역할과 명명 규칙은 다음과 같습니다.

-   **Models** (`app/Models`): 데이터베이스 테이블과 일대일로 매핑되는 클래스입니다. `Vehicle`, `VehicleBreakdown`과 같이 단수형의 명사로 명명됩니다.
-   **Repositories** (`app/Repositories`): 데이터베이스와의 상호작용을 담당하며, 각 모델에 대한 CRUD 로직을 포함합니다. `VehicleRepository`와 같이 `ModelNameRepository` 패턴을 따릅니다.
-   **Services** (`app/Services`): 비즈니스 로직을 처리하며, 여러 리포지토리를 조합하여 복잡한 작업을 수행합니다. `VehicleService`, `VehicleBreakdownService`와 같이 기능별로 명명됩니다.
-   **Controllers** (`app/Controllers/Api`): API 요청을 받아 해당 서비스에 비즈니스 로직 처리를 위임하고, 결과를 JSON 형식으로 응답합니다. `VehicleBaseController`, `VehicleBreakdownController`와 같이 `Vehicle{Feature}Controller` 패턴을 따릅니다.

## 3. 데이터베이스 스키마

모든 차량 관련 테이블은 `vehicle_` 접두사를 가지며, 기본 차량 정보 테이블은 `vehicles`입니다.

-   `vehicles`: 차량 기본 정보
-   `vehicle_breakdowns`: 고장 신고 내역
-   `vehicle_repairs`: 수리 내역
-   `vehicle_self_maintenances`: 자체 정비 내역
-   `vehicle_consumables`: 소모품 마스터
-   `vehicle_consumable_logs`: 소모품 사용 기록
-   `vehicle_insurances`: 보험 정보
-   `vehicle_taxes`: 세금 납부 내역
-   `vehicle_inspections`: 정기 검사 내역
-   `vehicle_documents`: 관련 문서

## 4. API 엔드포인트 명세 (OpenAPI 3.0)

```yaml
openapi: 3.0.0
info:
  title: 차량 유지관리 시스템 API
  version: 1.0.0
servers:
  - url: /api

paths:
  /vehicles:
    get:
      summary: 모든 차량 목록 조회
      tags: [Vehicle]
      responses:
        '200':
          description: 성공
    post:
      summary: 새 차량 등록
      tags: [Vehicle]
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/Vehicle'
      responses:
        '200':
          description: 성공

  /vehicles/{id}:
    get:
      summary: 특정 차량 정보 조회
      tags: [Vehicle]
      parameters:
        - name: id
          in: path
          required: true
          schema:
            type: integer
      responses:
        '200':
          description: 성공
    put:
      summary: 차량 정보 수정
      tags: [Vehicle]
      parameters:
        - name: id
          in: path
          required: true
          schema:
            type: integer
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/Vehicle'
      responses:
        '200':
          description: 성공
    delete:
      summary: 차량 정보 삭제
      tags: [Vehicle]
      parameters:
        - name: id
          in: path
          required: true
          schema:
            type: integer
      responses:
        '200':
          description: 성공

  /vehicles/breakdowns:
    get:
      summary: 고장 신고 목록 조회
      tags: [Breakdown]
      parameters:
        - name: vehicle_id
          in: query
          schema:
            type: integer
      responses:
        '200':
          description: 성공
    post:
      summary: 새 고장 신고 등록
      tags: [Breakdown]
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/VehicleBreakdown'
      responses:
        '200':
          description: 성공

  /vehicles/breakdowns/{id}/status:
    put:
      summary: 고장 신고 상태 변경
      tags: [Breakdown]
      parameters:
        - name: id
          in: path
          required: true
          schema:
            type: integer
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              properties:
                status:
                  type: string
      responses:
        '200':
          description: 성공

  /vehicles/repairs:
    post:
      summary: 수리 내역 등록
      tags: [Maintenance]
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/VehicleRepair'
      responses:
        '200':
          description: 성공

  /vehicles/self-maintenances:
    post:
      summary: 자체 정비 내역 등록
      tags: [Maintenance]
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/VehicleSelfMaintenance'
      responses:
        '200':
          description: 성공

  /vehicles/consumables:
    get:
      summary: 소모품 목록 조회
      tags: [Consumable]
      responses:
        '200':
          description: 성공
    post:
      summary: 새 소모품 등록
      tags: [Consumable]
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/VehicleConsumable'
      responses:
        '200':
          description: 성공

  /vehicles/consumable-logs:
    post:
      summary: 소모품 사용 기록 등록
      tags: [Consumable]
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/VehicleConsumableLog'
      responses:
        '200':
          description: 성공

  /vehicles/insurances:
    post:
      summary: 보험 정보 등록
      tags: [Admin]
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/VehicleInsurance'
      responses:
        '200':
          description: 성공

  /vehicles/documents:
    post:
      summary: 문서 업로드
      tags: [Admin]
      requestBody:
        required: true
        content:
          multipart/form-data:
            schema:
              type: object
              properties:
                document_file:
                  type: string
                  format: binary
                vehicle_id:
                  type: integer
                document_type:
                  type: string
      responses:
        '200':
          description: 성공

components:
  schemas:
    Vehicle:
      type: object
      properties:
        vin: { type: string }
        license_plate: { type: string }
        make: { type: string }
        model: { type: string }
        year: { type: integer }
        department_id: { type: integer }
        status: { type: string }

    VehicleBreakdown:
      type: object
      properties:
        vehicle_id: { type: integer }
        breakdown_item: { type: string }
        description: { type: string }
        mileage: { type: integer }

    VehicleRepair:
      type: object
      properties:
        breakdown_id: { type: integer }
        repair_type: { type: string }
        repair_item: { type: string }
        parts_used: { type: string }
        cost: { type: number, format: float }
        repairer_id: { type: integer }

    VehicleSelfMaintenance:
      type: object
      properties:
        vehicle_id: { type: integer }
        maintenance_item: { type: string }
        description: { type: string }
        parts_used: { type: string }
        maintenance_date: { type: string, format: date }

    VehicleConsumable:
      type: object
      properties:
        name: { type: string }
        unit: { type: string }
        unit_price: { type: number, format: float }

    VehicleConsumableLog:
      type: object
      properties:
        vehicle_id: { type: integer }
        consumable_id: { type: integer }
        quantity: { type: integer }
        replacement_date: { type: string, format: date }

    VehicleInsurance:
      type: object
      properties:
        vehicle_id: { type: integer }
        insurer: { type: string }
        policy_number: { type: string }
        start_date: { type: string, format: date }
        end_date: { type: string, format: date }
        premium: { type: number, format: float }
```
