<?php \App\Core\View::getInstance()->startSection('content'); ?>
<!-- 관리자 대시보드 헤더 -->
<div class="admin-dashboard-header">
    <div class="admin-dashboard-title">연차 관리 대시보드</div>
    <div class="admin-dashboard-subtitle">팀별 연차 현황 및 승인 관리</div>
</div>

<!-- 전체 통계 카드 -->
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="stats-card">
            <div class="stats-number text-primary" id="total-employees">0</div>
            <div class="stats-label">전체 직원 수</div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stats-card warning">
            <div class="stats-number text-warning" id="pending-approvals">0</div>
            <div class="stats-label">승인 대기</div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stats-card success">
            <div class="stats-number text-success" id="this-month-leaves">0</div>
            <div class="stats-label">이번 달 연차</div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stats-card danger">
            <div class="stats-number text-danger" id="low-balance-count">0</div>
            <div class="stats-label">연차 부족 직원</div>
        </div>
    </div>
</div>

<!-- 관리자 컨트롤 패널 -->
<div class="row">
    <div class="col-12">
        <div class="admin-controls">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h6 class="mb-2">빠른 관리 기능</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-primary btn-sm" id="grant-annual-leave-btn">
                            <i class="bx bx-gift me-1"></i>연차 부여
                        </button>
                        <button class="btn btn-warning btn-sm" id="bulk-approve-btn">
                            <i class="bx bx-check-circle me-1"></i>일괄 승인
                        </button>
                        <button class="btn btn-info btn-sm" id="export-report-btn">
                            <i class="bx bx-download me-1"></i>현황 내보내기
                        </button>
                        <a href="<?= BASE_URL ?>/leaves/admin-management" class="btn btn-success btn-sm">
                            <i class="bx bx-cog me-1"></i>연차 관리
                        </a>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-2 justify-content-md-end">
                        <label for="dashboard-department-filter" class="form-label mb-0">부서:</label>
                        <select class="form-select form-select-sm" id="dashboard-department-filter" style="width: 150px;">
                            <option value="">전체 부서</option>
                            <!-- 부서 목록은 JS로 채웁니다 -->
                        </select>
                        <label for="dashboard-year-filter" class="form-label mb-0">연도:</label>
                        <select class="form-select form-select-sm" id="dashboard-year-filter" style="width: 100px;">
                            <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                                <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?>년</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- 팀별 연차 소진율 차트 -->
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">부서별 연차 소진율</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="department-usage-chart" class="chart-canvas"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 월별 연차 사용 추이 -->
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">월별 사용 추이</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="monthly-trend-chart" class="chart-canvas"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- 승인 대기 목록 -->
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">승인 대기 목록</h5>
                    <a href="<?= BASE_URL ?>/leaves/pending-approvals" class="btn btn-sm btn-outline-primary">
                        전체 보기
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>신청자</th>
                                <th>기간</th>
                                <th>일수</th>
                                <th>잔여</th>
                                <th>관리</th>
                            </tr>
                        </thead>
                        <tbody id="dashboard-pending-list">
                            <tr>
                                <td colspan="5" class="text-center text-muted">승인 대기 중인 신청이 없습니다</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 연차 부족 직원 목록 -->
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">연차 부족 직원 (5일 이하)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>직원명</th>
                                <th>부서</th>
                                <th>잔여</th>
                                <th>사용률</th>
                            </tr>
                        </thead>
                        <tbody id="low-balance-list">
                            <tr>
                                <td colspan="4" class="text-center text-muted">연차 부족 직원이 없습니다</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 팀별 상세 현황 -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">팀별 상세 현황</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle table-nowrap table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>직원명</th>
                                <th>부서</th>
                                <th>직급</th>
                                <th>입사일</th>
                                <th>부여</th>
                                <th>사용</th>
                                <th>잔여</th>
                                <th>사용률</th>
                                <th>상태</th>
                                <th>관리</th>
                            </tr>
                        </thead>
                        <tbody id="team-detail-list">
                            <tr>
                                <td colspan="10" class="text-center text-muted">직원 정보를 불러오는 중...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 빠른 승인 모달 -->
<div class="modal fade" id="quick-approve-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">연차 신청 승인/반려</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="quick-approve-form">
                    <input type="hidden" id="approve-application-id" name="application_id">
                    <div class="mb-3">
                        <label class="form-label">신청 정보</label>
                        <div class="bg-light p-3 rounded" id="approve-application-info">
                            <!-- 신청 정보가 여기에 표시됩니다 -->
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">처리 결과</label>
                        <div class="d-flex gap-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="action" id="approve-action" value="approve" checked>
                                <label class="form-check-label text-success" for="approve-action">
                                    <i class="bx bx-check-circle me-1"></i>승인
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="action" id="reject-action" value="reject">
                                <label class="form-check-label text-danger" for="reject-action">
                                    <i class="bx bx-x-circle me-1"></i>반려
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="approve-reason" class="form-label">처리 사유 (선택)</label>
                        <textarea class="form-control" id="approve-reason" name="reason" rows="3" 
                                  placeholder="승인 또는 반려 사유를 입력해주세요"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-leave-secondary" data-bs-dismiss="modal">취소</button>
                <button type="submit" form="quick-approve-form" class="btn btn-success" id="submit-approve-btn">승인</button>
            </div>
        </div>
    </div>
</div>

<!-- 연차 부여 모달 -->
<div class="modal fade" id="grant-leave-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">연차 부여</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="grant-leave-form">
                    <div class="mb-3">
                        <label for="grant-year" class="form-label">부여 연도 <span class="text-danger">*</span></label>
                        <select class="form-select" id="grant-year" name="year" required>
                            <?php for ($y = date('Y') + 1; $y >= date('Y') - 1; $y--): ?>
                                <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?>년</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="grant-department" class="form-label">대상 부서</label>
                        <select class="form-select" id="grant-department" name="department_id">
                            <option value="">전체 부서</option>
                            <!-- 부서 목록은 JS로 채웁니다 -->
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-1"></i>
                        <strong>연차 부여 규칙:</strong>
                        <ul class="mb-0 mt-2">
                            <li>입사 1년 미만: 월차 (매월 1일)</li>
                            <li>입사 1년 이상: 15일 + 근속연차</li>
                            <li>근속연차: 3년차부터 2년마다 1일씩 추가 (최대 25일)</li>
                        </ul>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-leave-secondary" data-bs-dismiss="modal">취소</button>
                <button type="submit" form="grant-leave-form" class="btn btn-primary">연차 부여 실행</button>
            </div>
        </div>
    </div>
</div>

<style>
/* 관리자 대시보드 특화 스타일 */
.employee-status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 0.5rem;
}

.employee-status-indicator.active {
    background: #28a745;
}

.employee-status-indicator.warning {
    background: #ffc107;
}

.employee-status-indicator.danger {
    background: #dc3545;
}

.usage-progress {
    width: 100%;
    height: 6px;
    background: #e9ecef;
    border-radius: 3px;
    overflow: hidden;
}

.usage-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #28a745 0%, #ffc107 70%, #dc3545 100%);
    transition: width 0.3s ease;
}

.quick-action-btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 3px;
}

/* 차트 컨테이너 개선 */
.chart-container {
    position: relative;
    min-height: 250px;
}

.chart-loading {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: #6c757d;
}

/* 통계 카드 호버 효과 */
.stats-card {
    cursor: pointer;
    transition: all 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

/* 승인 대기 목록 스타일 */
.pending-item {
    border-left: 3px solid #ffc107;
    background: #fff8e1;
    margin-bottom: 0.5rem;
    padding: 0.75rem;
    border-radius: 0 5px 5px 0;
}

.pending-item .employee-name {
    font-weight: 600;
    color: #333;
}

.pending-item .leave-period {
    font-size: 0.85rem;
    color: #6c757d;
}

/* 연차 부족 직원 스타일 */
.low-balance-item {
    border-left: 3px solid #dc3545;
    background: #ffebee;
    margin-bottom: 0.5rem;
    padding: 0.75rem;
    border-radius: 0 5px 5px 0;
}

.balance-critical {
    color: #dc3545;
    font-weight: bold;
}

.balance-warning {
    color: #ffc107;
    font-weight: bold;
}

.balance-normal {
    color: #28a745;
    font-weight: bold;
}
</style>

<script>
// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    if (typeof LeaveAdminDashboard !== 'undefined') {
        window.leaveAdminDashboard = new LeaveAdminDashboard();
    }
});
</script>
<?php \App\Core\View::getInstance()->endSection(); ?>