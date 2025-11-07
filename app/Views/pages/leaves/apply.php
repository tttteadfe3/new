<?php \App\Core\View::getInstance()->startSection('content'); ?>
<!-- 연차 현황 대시보드 -->
<div class="row mb-4">
    <!-- 연차 현황 카드 -->
    <div class="col-xl-3 col-md-6">
        <div class="leave-balance-card">
            <div class="balance-number" id="current-balance">0.0</div>
            <div class="balance-label">잔여 연차</div>
        </div>
    </div>
    
    <!-- 통계 카드들 -->
    <div class="col-xl-3 col-md-6">
        <div class="stats-card success">
            <div class="stats-number" id="total-granted">0</div>
            <div class="stats-label">부여된 연차</div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stats-card warning">
            <div class="stats-number" id="total-used">0</div>
            <div class="stats-label">사용한 연차</div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stats-card danger">
            <div class="stats-number" id="pending-count">0</div>
            <div class="stats-label">승인 대기</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title mb-0">연차 관리</h5>
                    </div>
                    <div class="col-auto">
                        <div class="d-flex align-items-center gap-3">
                            <div class="text-end">
                                <small class="text-muted">잔여 연차</small>
                                <div class="fw-bold text-primary" id="header-balance">0.0일</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form id="detailed-leave-application-form" class="leave-application-form">
                    <div class="row">
                        <!-- 신청 정보 -->
                        <div class="col-lg-8">
                            <div class="form-section">
                                <h6 class="form-section-title">신청 정보</h6>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="apply-start-date" class="form-label">시작일 <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="apply-start-date" name="start_date" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="apply-end-date" class="form-label">종료일 <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="apply-end-date" name="end_date" required>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <label for="apply-day-type" class="form-label">휴가 유형 <span class="text-danger">*</span></label>
                                        <select class="form-select" id="apply-day-type" name="day_type" required>
                                            <option value="">선택해주세요</option>
                                            <option value="전일">전일</option>
                                            <option value="반차">반차 (0.5일)</option>
                                        </select>
                                        <div class="form-text">반차는 시작일과 종료일이 같은 날이어야 합니다</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">신청 일수</label>
                                        <div class="calculated-days-display" id="apply-calculated-days">0일</div>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <label for="apply-reason" class="form-label">신청 사유</label>
                                    <textarea class="form-control" id="apply-reason" name="reason" rows="4" 
                                              placeholder="연차 사용 사유를 입력해주세요 (선택사항)"></textarea>
                                </div>
                            </div>
                            
                            <!-- 주의사항 -->
                            <div class="form-section">
                                <h6 class="form-section-title">신청 시 주의사항</h6>
                                <div class="alert alert-info">
                                    <ul class="mb-0">
                                        <li>연차는 잔여량 범위 내에서만 신청 가능합니다</li>
                                        <li>반차는 0.5일 단위로 차감되며, 하루에 한 번만 신청 가능합니다</li>
                                        <li>승인 전까지는 신청을 취소할 수 있습니다</li>
                                        <li>승인된 연차는 별도의 취소 신청을 통해 취소할 수 있습니다</li>
                                        <li>연차 신청은 관리자 승인 후 확정됩니다</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <!-- 제출 버튼 -->
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-leave-primary">
                                    <i class="bx bx-calendar-plus me-1"></i>연차 신청
                                </button>
                                <button type="button" class="btn btn-leave-secondary" id="reset-form-btn">
                                    <i class="bx bx-refresh me-1"></i>초기화
                                </button>
                            </div>
                        </div>
                        
                        <!-- 사이드바 정보 -->
                        <div class="col-lg-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">내 연차 현황</h6>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between">
                                            <span>부여된 연차:</span>
                                            <span class="fw-bold" id="sidebar-granted">0일</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>사용한 연차:</span>
                                            <span class="fw-bold text-warning" id="sidebar-used">0일</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>승인 대기:</span>
                                            <span class="fw-bold text-info" id="sidebar-pending">0일</span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-bold">잔여 연차:</span>
                                            <span class="fw-bold text-primary" id="sidebar-balance">0일</span>
                                        </div>
                                    </div>
                                    
                                    <h6 class="card-title mt-4">최근 신청 내역</h6>
                                    <div id="sidebar-recent-applications">
                                        <div class="text-muted text-center">내역이 없습니다</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 휴일 정보 -->
                            <div class="card bg-light mt-3">
                                <div class="card-body">
                                    <h6 class="card-title">이번 달 휴일</h6>
                                    <div id="sidebar-holidays">
                                        <div class="text-muted text-center">휴일 정보를 불러오는 중...</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 신청 내역 테이블 -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card applications-table">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title mb-0">내 연차 신청 내역</h5>
                    </div>
                    <div class="col-auto">
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm" id="history-year-filter" style="width: 100px;">
                                <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                                    <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?>년</option>
                                <?php endfor; ?>
                            </select>
                            <select class="form-select form-select-sm" id="history-status-filter" style="width: 120px;">
                                <option value="">전체 상태</option>
                                <option value="PENDING">승인 대기</option>
                                <option value="APPROVED">승인</option>
                                <option value="REJECTED">반려</option>
                                <option value="CANCELLED">취소</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle table-nowrap table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>신청일</th>
                                <th>휴가 기간</th>
                                <th>유형</th>
                                <th>일수</th>
                                <th>상태</th>
                                <th>승인자</th>
                                <th>관리</th>
                            </tr>
                        </thead>
                        <tbody id="application-history-body">
                            <tr>
                                <td colspan="7" class="text-center text-muted">신청 내역을 불러오는 중...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 신청 취소 모달 -->
<div class="modal fade leave-modal" id="cancel-application-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancel-modal-title">연차 신청 취소</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="cancel-application-details"></div>
                <p class="mt-3 mb-0" id="cancel-confirmation-text">정말로 이 연차 신청을 취소하시겠습니까?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-leave-secondary" data-bs-dismiss="modal">아니오</button>
                <button type="button" class="btn btn-danger" id="confirm-cancel-btn">예, 취소합니다</button>
            </div>
        </div>
    </div>
</div>

<style>
/* 사이드바 스타일 */
.sidebar-application-item {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 5px;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    font-size: 0.85rem;
}

.sidebar-application-item .date {
    font-weight: 600;
    color: #333;
}

.sidebar-application-item .status {
    font-size: 0.75rem;
    padding: 0.2rem 0.4rem;
    border-radius: 3px;
}

.holiday-item {
    display: flex;
    justify-content: between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e9ecef;
    font-size: 0.85rem;
}

.holiday-item:last-child {
    border-bottom: none;
}

.holiday-date {
    font-weight: 600;
    color: #dc3545;
}

.holiday-name {
    color: #6c757d;
}

/* 폼 검증 스타일 */
.is-invalid {
    border-color: #dc3545;
}

.invalid-feedback {
    display: block;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #dc3545;
}

/* 연차 현황 카드 스타일 */
.leave-balance-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.balance-number {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.balance-label {
    font-size: 1rem;
    opacity: 0.9;
}

/* 통계 카드 스타일 */
.stats-card {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-left: 4px solid #e9ecef;
}

.stats-card.success {
    border-left-color: #28a745;
}

.stats-card.warning {
    border-left-color: #ffc107;
}

.stats-card.danger {
    border-left-color: #dc3545;
}

.stats-number {
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #333;
}

.stats-label {
    font-size: 0.9rem;
    color: #6c757d;
}

/* 계산된 일수 표시 강조 */
.calculated-days-display.has-value {
    background: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.calculated-days-display.invalid {
    background: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}
</style>

<script>
// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    if (typeof LeaveApplication !== 'undefined') {
        window.leaveApplication = new LeaveApplication();
    }
});
</script>
<?php \App\Core\View::getInstance()->endSection(); ?>