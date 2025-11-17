<?php

namespace App\Services;

use App\Repositories\SupplyDistributionRepository;
use App\Repositories\SupplyStockRepository;
use App\Repositories\SupplyPlanRepository;
use App\Repositories\SupplyPurchaseRepository;
use App\Repositories\SupplyItemRepository;
use App\Repositories\DepartmentRepository;

class SupplyReportService
{
    private SupplyDistributionRepository $distributionRepository;
    private SupplyStockRepository $stockRepository;
    private SupplyPlanRepository $planRepository;
    private SupplyPurchaseRepository $purchaseRepository;
    private SupplyItemRepository $itemRepository;
    private DepartmentRepository $departmentRepository;

    public function __construct(
        SupplyDistributionRepository $distributionRepository,
        SupplyStockRepository $stockRepository,
        SupplyPlanRepository $planRepository,
        SupplyPurchaseRepository $purchaseRepository,
        SupplyItemRepository $itemRepository,
        DepartmentRepository $departmentRepository
    ) {
        $this->distributionRepository = $distributionRepository;
        $this->stockRepository = $stockRepository;
        $this->planRepository = $planRepository;
        $this->purchaseRepository = $purchaseRepository;
        $this->itemRepository = $itemRepository;
        $this->departmentRepository = $departmentRepository;
    }

    /**
     * 지급 현황 보고서를 조회합니다.
     */
    public function getDistributionReport(array $filters = []): array
    {
        $sql = "SELECT 
                    sd.id,
                    sd.distribution_date,
                    si.item_code,
                    si.item_name,
                    sc.category_name,
                    he.name as employee_name,
                    hd.name as department_name,
                    sd.quantity,
                    si.unit,
                    sd.notes,
                    sd.is_cancelled,
                    distributor.name as distributed_by_name
                FROM supply_distributions sd
                JOIN supply_items si ON sd.item_id = si.id
                LEFT JOIN supply_categories sc ON si.category_id = sc.id
                JOIN hr_employees he ON sd.employee_id = he.id
                JOIN hr_departments hd ON sd.department_id = hd.id
                JOIN hr_employees distributor ON sd.distributed_by = distributor.id
                WHERE 1=1";
        
        $params = [];

        // 필터 적용
        if (!empty($filters['year'])) {
            $sql .= " AND YEAR(sd.distribution_date) = :year";
            $params[':year'] = $filters['year'];
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $sql .= " AND sd.distribution_date BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $filters['start_date'];
            $params[':end_date'] = $filters['end_date'];
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND si.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }

        if (!empty($filters['item_id'])) {
            $sql .= " AND sd.item_id = :item_id";
            $params[':item_id'] = $filters['item_id'];
        }

        if (!empty($filters['department_id'])) {
            $sql .= " AND sd.department_id = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }

        if (isset($filters['is_cancelled'])) {
            $sql .= " AND sd.is_cancelled = :is_cancelled";
            $params[':is_cancelled'] = $filters['is_cancelled'] ? 1 : 0;
        }

        $sql .= " ORDER BY sd.distribution_date DESC, sd.created_at DESC";

        $result = $this->db->query($sql, $params);
        
        // 활동 로그 기록
        $this->activityLogger->logSupplyReportView('distribution', $filters);
        
        return $result;
    }

    /**
     * 재고 현황 보고서를 조회합니다.
     */
    public function getStockReport(array $filters = []): array
    {
        $sql = "SELECT 
                    si.id,
                    si.item_code,
                    si.item_name,
                    sc.category_name,
                    si.unit,
                    COALESCE(ss.total_purchased, 0) as total_purchased,
                    COALESCE(ss.total_distributed, 0) as total_distributed,
                    COALESCE(ss.current_stock, 0) as current_stock,
                    ss.last_updated
                FROM supply_items si
                LEFT JOIN supply_categories sc ON si.category_id = sc.id
                LEFT JOIN supply_stocks ss ON si.id = ss.item_id
                WHERE si.is_active = 1";
        
        $params = [];

        // 필터 적용
        if (!empty($filters['category_id'])) {
            $sql .= " AND si.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }

        if (!empty($filters['item_id'])) {
            $sql .= " AND si.id = :item_id";
            $params[':item_id'] = $filters['item_id'];
        }

        if (isset($filters['low_stock']) && $filters['low_stock']) {
            $threshold = $filters['threshold'] ?? 10;
            $sql .= " AND COALESCE(ss.current_stock, 0) <= :threshold";
            $params[':threshold'] = $threshold;
        }

        if (isset($filters['out_of_stock']) && $filters['out_of_stock']) {
            $sql .= " AND COALESCE(ss.current_stock, 0) = 0";
        }

        $sql .= " ORDER BY sc.category_name ASC, si.item_name ASC";

        return $this->db->query($sql, $params);
    }

    /**
     * 예산 집행률 보고서를 조회합니다.
     */
    public function getBudgetExecutionReport(int $year): array
    {
        $sql = "SELECT 
                    sp.id as plan_id,
                    sp.year,
                    si.item_code,
                    si.item_name,
                    sc.category_name,
                    si.unit,
                    sp.planned_quantity,
                    sp.unit_price,
                    (sp.planned_quantity * sp.unit_price) as planned_budget,
                    COALESCE(SUM(spu.quantity), 0) as purchased_quantity,
                    COALESCE(SUM(spu.quantity * spu.unit_price), 0) as purchased_amount,
                    COALESCE(SUM(sd.quantity), 0) as distributed_quantity,
                    ROUND((COALESCE(SUM(spu.quantity * spu.unit_price), 0) / (sp.planned_quantity * sp.unit_price)) * 100, 2) as execution_rate
                FROM supply_plans sp
                JOIN supply_items si ON sp.item_id = si.id
                LEFT JOIN supply_categories sc ON si.category_id = sc.id
                LEFT JOIN supply_purchases spu ON sp.item_id = spu.item_id AND YEAR(spu.purchase_date) = sp.year
                LEFT JOIN supply_distributions sd ON sp.item_id = sd.item_id AND YEAR(sd.distribution_date) = sp.year AND sd.is_cancelled = 0
                WHERE sp.year = :year
                GROUP BY sp.id, sp.year, si.item_code, si.item_name, sc.category_name, si.unit, sp.planned_quantity, sp.unit_price
                ORDER BY sc.category_name ASC, si.item_name ASC";
        
        return $this->db->query($sql, [':year' => $year]);
    }

    /**
     * 부서별 사용 현황 보고서를 조회합니다.
     */
    public function getDepartmentUsageReport(int $departmentId, int $year): array
    {
        $sql = "SELECT 
                    si.item_code,
                    si.item_name,
                    sc.category_name,
                    si.unit,
                    COUNT(sd.id) as distribution_count,
                    SUM(sd.quantity) as total_quantity,
                    COUNT(DISTINCT sd.employee_id) as employee_count,
                    MIN(sd.distribution_date) as first_distribution,
                    MAX(sd.distribution_date) as last_distribution
                FROM supply_distributions sd
                JOIN supply_items si ON sd.item_id = si.id
                LEFT JOIN supply_categories sc ON si.category_id = sc.id
                WHERE sd.department_id = :department_id 
                AND YEAR(sd.distribution_date) = :year 
                AND sd.is_cancelled = 0
                GROUP BY si.id, si.item_code, si.item_name, sc.category_name, si.unit
                ORDER BY total_quantity DESC, si.item_name ASC";
        
        return $this->db->query($sql, [
            ':department_id' => $departmentId,
            ':year' => $year
        ]);
    }

    /**
     * 부서별 사용 현황 요약을 조회합니다.
     */
    public function getDepartmentUsageSummary(int $year): array
    {
        $sql = "SELECT 
                    hd.id as department_id,
                    hd.name as department_name,
                    COUNT(DISTINCT sd.item_id) as item_count,
                    COUNT(sd.id) as distribution_count,
                    SUM(sd.quantity) as total_quantity,
                    COUNT(DISTINCT sd.employee_id) as employee_count
                FROM hr_departments hd
                LEFT JOIN supply_distributions sd ON hd.id = sd.department_id 
                    AND YEAR(sd.distribution_date) = :year 
                    AND sd.is_cancelled = 0
                WHERE hd.is_active = 1
                GROUP BY hd.id, hd.name
                ORDER BY total_quantity DESC, hd.name ASC";
        
        return $this->db->query($sql, [':year' => $year]);
    }

    /**
     * 품목별 지급 현황을 조회합니다.
     */
    public function getItemDistributionSummary(int $year): array
    {
        $sql = "SELECT 
                    si.id as item_id,
                    si.item_code,
                    si.item_name,
                    sc.category_name,
                    si.unit,
                    COUNT(sd.id) as distribution_count,
                    SUM(sd.quantity) as total_distributed,
                    COUNT(DISTINCT sd.department_id) as department_count,
                    COUNT(DISTINCT sd.employee_id) as employee_count,
                    COALESCE(ss.current_stock, 0) as current_stock
                FROM supply_items si
                LEFT JOIN supply_categories sc ON si.category_id = sc.id
                LEFT JOIN supply_distributions sd ON si.id = sd.item_id 
                    AND YEAR(sd.distribution_date) = :year 
                    AND sd.is_cancelled = 0
                LEFT JOIN supply_stocks ss ON si.id = ss.item_id
                WHERE si.is_active = 1
                GROUP BY si.id, si.item_code, si.item_name, sc.category_name, si.unit, ss.current_stock
                ORDER BY total_distributed DESC, si.item_name ASC";
        
        return $this->db->query($sql, [':year' => $year]);
    }

    /**
     * 보고서를 엑셀 파일로 내보냅니다.
     */
    public function exportReportToExcel(string $reportType, array $data, array $headers): string
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('내보낼 데이터가 없습니다.');
        }

        // 임시 파일 생성
        $filename = "{$reportType}_" . date('YmdHis') . '.csv';
        $filepath = sys_get_temp_dir() . '/' . $filename;
        
        $handle = fopen($filepath, 'w');
        if (!$handle) {
            throw new \RuntimeException('파일을 생성할 수 없습니다.');
        }

        // UTF-8 BOM 추가 (Excel에서 한글 깨짐 방지)
        fwrite($handle, "\xEF\xBB\xBF");

        // 헤더 작성
        fputcsv($handle, $headers);

        // 데이터 작성
        foreach ($data as $row) {
            $csvRow = [];
            foreach ($headers as $header) {
                // 헤더를 키로 변환 (예: "품목코드" -> "item_code")
                $key = $this->getKeyFromHeader($header, $row);
                $csvRow[] = $row[$key] ?? '';
            }
            fputcsv($handle, $csvRow);
        }

        fclose($handle);

        // 활동 로그 기록
        $this->activityLogger->logSupplyReportExport($reportType, $filters ?? []);

        return $filepath;
    }

    /**
     * 지급 현황 보고서를 엑셀로 내보냅니다.
     */
    public function exportDistributionReport(array $filters = []): string
    {
        $data = $this->getDistributionReport($filters);
        
        $headers = [
            '지급일자', '품목코드', '품목명', '분류', '부서', '직원', 
            '수량', '단위', '지급자', '취소여부', '비고'
        ];

        $csvData = array_map(function($row) {
            return [
                'distribution_date' => $row['distribution_date'],
                'item_code' => $row['item_code'],
                'item_name' => $row['item_name'],
                'category_name' => $row['category_name'] ?? '',
                'department_name' => $row['department_name'],
                'employee_name' => $row['employee_name'],
                'quantity' => $row['quantity'],
                'unit' => $row['unit'],
                'distributed_by_name' => $row['distributed_by_name'],
                'is_cancelled' => $row['is_cancelled'] ? '취소' : '정상',
                'notes' => $row['notes'] ?? ''
            ];
        }, $data);

        return $this->exportReportToExcel('distribution_report', $csvData, $headers);
    }

    /**
     * 재고 현황 보고서를 엑셀로 내보냅니다.
     */
    public function exportStockReport(array $filters = []): string
    {
        $data = $this->getStockReport($filters);
        
        $headers = [
            '품목코드', '품목명', '분류', '단위', '총구매량', '총지급량', '현재고', '최종업데이트'
        ];

        $csvData = array_map(function($row) {
            return [
                'item_code' => $row['item_code'],
                'item_name' => $row['item_name'],
                'category_name' => $row['category_name'] ?? '',
                'unit' => $row['unit'],
                'total_purchased' => $row['total_purchased'],
                'total_distributed' => $row['total_distributed'],
                'current_stock' => $row['current_stock'],
                'last_updated' => $row['last_updated'] ?? ''
            ];
        }, $data);

        return $this->exportReportToExcel('stock_report', $csvData, $headers);
    }

    /**
     * 예산 집행률 보고서를 엑셀로 내보냅니다.
     */
    public function exportBudgetExecutionReport(int $year): string
    {
        $data = $this->getBudgetExecutionReport($year);
        
        $headers = [
            '품목코드', '품목명', '분류', '단위', '계획수량', '단가', '계획예산',
            '구매수량', '구매금액', '지급수량', '집행률(%)'
        ];

        $csvData = array_map(function($row) {
            return [
                'item_code' => $row['item_code'],
                'item_name' => $row['item_name'],
                'category_name' => $row['category_name'] ?? '',
                'unit' => $row['unit'],
                'planned_quantity' => $row['planned_quantity'],
                'unit_price' => $row['unit_price'],
                'planned_budget' => $row['planned_budget'],
                'purchased_quantity' => $row['purchased_quantity'],
                'purchased_amount' => $row['purchased_amount'],
                'distributed_quantity' => $row['distributed_quantity'],
                'execution_rate' => $row['execution_rate']
            ];
        }, $data);

        return $this->exportReportToExcel('budget_execution_report', $csvData, $headers);
    }

    /**
     * 부서별 사용 현황 보고서를 엑셀로 내보냅니다.
     */
    public function exportDepartmentUsageReport(int $departmentId, int $year): string
    {
        $data = $this->getDepartmentUsageReport($departmentId, $year);
        
        $headers = [
            '품목코드', '품목명', '분류', '단위', '지급횟수', '총수량', 
            '직원수', '최초지급일', '최종지급일'
        ];

        $csvData = array_map(function($row) {
            return [
                'item_code' => $row['item_code'],
                'item_name' => $row['item_name'],
                'category_name' => $row['category_name'] ?? '',
                'unit' => $row['unit'],
                'distribution_count' => $row['distribution_count'],
                'total_quantity' => $row['total_quantity'],
                'employee_count' => $row['employee_count'],
                'first_distribution' => $row['first_distribution'],
                'last_distribution' => $row['last_distribution']
            ];
        }, $data);

        return $this->exportReportToExcel('department_usage_report', $csvData, $headers);
    }

    /**
     * 헤더에서 데이터 키를 찾습니다.
     */
    private function getKeyFromHeader(string $header, array $row): string
    {
        // 헤더와 키 매핑
        $mapping = [
            '지급일자' => 'distribution_date',
            '품목코드' => 'item_code',
            '품목명' => 'item_name',
            '분류' => 'category_name',
            '부서' => 'department_name',
            '직원' => 'employee_name',
            '수량' => 'quantity',
            '단위' => 'unit',
            '지급자' => 'distributed_by_name',
            '취소여부' => 'is_cancelled',
            '비고' => 'notes',
            '총구매량' => 'total_purchased',
            '총지급량' => 'total_distributed',
            '현재고' => 'current_stock',
            '최종업데이트' => 'last_updated',
            '계획수량' => 'planned_quantity',
            '단가' => 'unit_price',
            '계획예산' => 'planned_budget',
            '구매수량' => 'purchased_quantity',
            '구매금액' => 'purchased_amount',
            '지급수량' => 'distributed_quantity',
            '집행률(%)' => 'execution_rate',
            '지급횟수' => 'distribution_count',
            '총수량' => 'total_quantity',
            '직원수' => 'employee_count',
            '최초지급일' => 'first_distribution',
            '최종지급일' => 'last_distribution'
        ];

        return $mapping[$header] ?? $header;
    }

    /**
     * 활동 로그를 기록합니다.
     */
    private function logActivity(string $action, string $details): void
    {
        // ActivityLogger는 AuthService를 통해 자동으로 현재 사용자 정보를 가져옵니다
    }
}
