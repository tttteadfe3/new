<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title mb-0">연차 관리</h5>
                    </div>
                    <div class="col-auto">
                        <a href="<?= BASE_URL ?>/leaves/admin-dashboard" class="btn btn-leave-secondary btn-sm">
                            <i class="bx bx-arrow-back me-1"></i>대시보드로
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- 관리 기능 탭 -->
                <ul class="nav nav-pills mb-4" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#grant-tab" role="tab">
                            <i class="bx bx-gift me-1"></i>연차 부여
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#adjust-tab" role="tab">
                            <i class="bx bx-edit me-1"></i>연차 조정
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#expire-tab" role="tab">
                            <i class="bx bx-time me-1"></i>연차 소멸
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#bulk-tab" role="tab">
                            <i class="bx bx-list-check me-1"></i>일괄 처리
                        </a>
                    </li>
                </ul>
                
                <div class="tab-content">
                    <!-- 연차 부여 탭 -->
                    <div class="tab-pane active" id="grant-tab" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">연차 부여 설정</h6>
                                        <form id="grant-settings-form">
                                            <div class="mb-3">
                                                <label for="grant-target-year" class="form-label">부여 연도</label>
                                                <select class="form-select" id="grant-target-year" name="year">
                                                    <?php for ($y = date('Y') + 1; $y >= date('Y') - 1; $y--): ?>
                                                        <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?>년</option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="grant-target-department" class="form-label">대상 부서</label>
                                                <select class="form-select" id="grant-target-department" name="department_id">
                                                    <option value="">전체 부서</option>
                                                    <!-- 부서 목록은 JS로 채웁니다 -->
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="grant-preview-mode" checked>
                                                    <label class="form-check-label" for="grant-preview-mode">
                                                        미리보기 모드 (실제 부여하지 않음)
                                                    </label>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="bx bx-calculator me-1"></i>연차 계산
                                            </button>
                                        </form>
                                        
                                        <hr class="my-3">
                                        
                                        <div class="alert alert-info">
                                            <h6 class="alert-heading">연차 부여 규칙</h6>
                                            <ul class="mb-0">
                                                <li>입사 1년 미만: 월차 (매월 1일)</li>
                                                <li>입사 1년 이상: 15일 기본</li>
                                                <li>근속 3년차부터 2년마다 1일 추가</li>
                                                <li>최대 25일까지 부여</li>
                                                <li>전년도 출근율 80% 이상 필요</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-8">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="card-title mb-0">연차 부여 대상자</h6>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-success btn-sm" id="execute-grant-btn" disabled>
                                                    <i class="bx bx-check me-1"></i>선택 항목 부여
                                                </button>
                                                <button class="btn btn-primary btn-sm" id="grant-all-btn" disabled>
                                                    <i class="bx bx-check-double me-1"></i>전체 부여
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>
                                                            <input type="checkbox" id="grant-select-all" class="form-check-input">
                                                        </th>
                                                        <th>직원명</th>
                                                        <th>부서</th>
                                                        <th>입사일</th>
                                                        <th>근속</th>
                                                        <th>기본</th>
                                                        <th>근속</th>
                                                        <th>월차</th>
                                                        <th>합계</th>
                                                        <th>상태</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="grant-target-list">
                                                    <tr>
                                                        <td colspan="10" class="text-center text-muted">연차 계산 버튼을 클릭해주세요</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 연차 조정 탭 -->
                    <div class="tab-pane" id="adjust-tab" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">연차 조정</h6>
                                        <form id="adjust-form">
                                            <div class="mb-3">
                                                <label for="adjust-employee" class="form-label">대상 직원</label>
                                                <select class="form-select" id="adjust-employee" name="employee_id" required>
                                                    <option value="">직원을 선택해주세요</option>
                                                    <!-- 직원 목록은 JS로 채웁니다 -->
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="adjust-amount" class="form-label">조정 일수</label>
                                                <input type="number" step="0.5" class="form-control" id="adjust-amount" name="amount" required>
                                                <div class="form-text">양수(+)는 추가, 음수(-)는 차감</div>
                                            </div>
                                            <div class="mb-3" id="adjust-grant-year-group">
                                                <label for="adjust-grant-year" class="form-label">부여연도 <span class="text-danger">*</span></label>
                                                <select class="form-select" id="adjust-grant-year" name="grant_year">
                                                    <?php for ($y = date('Y') + 1; $y >= date('Y') - 2; $y--): ?>
                                                        <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?>년</option>
                                                    <?php endfor; ?>
                                                </select>
                                                <div class="form-text">연차 추가 시 필수 (차감 시 선택사항)</div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="adjust-reason" class="form-label">조정 사유</label>
                                                <select class="form-select" id="adjust-reason-type" name="reason_type">
                                                    <option value="포상">포상</option>
                                                    <option value="징계">징계</option>
                                                    <option value="기타">기타</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="adjust-detail-reason" class="form-label">상세 사유</label>
                                                <textarea class="form-control" id="adjust-detail-reason" name="detail_reason" rows="3" required></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-warning w-100">
                                                <i class="bx bx-edit me-1"></i>연차 조정
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">최근 조정 내역</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>일시</th>
                                                        <th>직원명</th>
                                                        <th>조정량</th>
                                                        <th>부여연도</th>
                                                        <th>사유</th>
                                                        <th>처리자</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="adjust-history-list">
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted">조정 내역이 없습니다</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 연차 소멸 탭 -->
                    <div class="tab-pane" id="expire-tab" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">연차 소멸 처리</h6>
                                        <form id="expire-form">
                                            <div class="mb-3">
                                                <label for="expire-year" class="form-label">소멸 연도</label>
                                                <select class="form-select" id="expire-year" name="year">
                                                    <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                                                        <option value="<?= $y ?>" <?= $y == (date('Y') - 1) ? 'selected' : '' ?>><?= $y ?>년</option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="expire-department" class="form-label">대상 부서</label>
                                                <select class="form-select" id="expire-department" name="department_id">
                                                    <option value="">전체 부서</option>
                                                    <!-- 부서 목록은 JS로 채웁니다 -->
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="expire-preview-mode" checked>
                                                    <label class="form-check-label" for="expire-preview-mode">
                                                        미리보기 모드
                                                    </label>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-info w-100">
                                                <i class="bx bx-search me-1"></i>소멸 대상 조회
                                            </button>
                                        </form>
                                        
                                        <hr class="my-3">
                                        
                                        <div class="alert alert-warning">
                                            <h6 class="alert-heading">소멸 처리 주의사항</h6>
                                            <ul class="mb-0">
                                                <li>소멸된 연차는 복구할 수 없습니다</li>
                                                <li>퇴사자의 잔여 연차만 소멸 처리</li>
                                                <li>소멸 처리 전 반드시 확인 필요</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-8">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="card-title mb-0">소멸 대상자</h6>
                                            <button class="btn btn-danger btn-sm" id="execute-expire-btn" disabled>
                                                <i class="bx bx-trash me-1"></i>선택 항목 소멸
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>
                                                            <input type="checkbox" id="expire-select-all" class="form-check-input">
                                                        </th>
                                                        <th>직원명</th>
                                                        <th>부서</th>
                                                        <th>퇴사일</th>
                                                        <th>잔여 연차</th>
                                                        <th>상태</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="expire-target-list">
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted">소멸 대상 조회 버튼을 클릭해주세요</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 일괄 처리 탭 -->
                    <div class="tab-pane" id="bulk-tab" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">일괄 승인</h6>
                                    </div>
                                    <div class="card-body">
                                        <form id="bulk-approve-form">
                                            <div class="mb-3">
                                                <label for="bulk-department" class="form-label">대상 부서</label>
                                                <select class="form-select" id="bulk-department" name="department_id">
                                                    <option value="">전체 부서</option>
                                                    <!-- 부서 목록은 JS로 채웁니다 -->
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="bulk-date-from" class="form-label">신청일 범위</label>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <input type="date" class="form-control" id="bulk-date-from" name="date_from">
                                                    </div>
                                                    <div class="col-6">
                                                        <input type="date" class="form-control" id="bulk-date-to" name="date_to">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="bulk-approve-reason" class="form-label">승인 사유</label>
                                                <textarea class="form-control" id="bulk-approve-reason" name="reason" rows="2" 
                                                          placeholder="일괄 승인 사유를 입력해주세요"></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-success w-100">
                                                <i class="bx bx-check-double me-1"></i>일괄 승인 실행
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">데이터 내보내기</h6>
                                    </div>
                                    <div class="card-body">
                                        <form id="export-form">
                                            <div class="mb-3">
                                                <label for="export-type" class="form-label">내보내기 유형</label>
                                                <select class="form-select" id="export-type" name="type">
                                                    <option value="current_status">현재 연차 현황</option>
                                                    <option value="usage_history">연차 사용 내역</option>
                                                    <option value="application_history">신청 내역</option>
                                                    <option value="adjustment_history">조정 내역</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="export-department" class="form-label">대상 부서</label>
                                                <select class="form-select" id="export-department" name="department_id">
                                                    <option value="">전체 부서</option>
                                                    <!-- 부서 목록은 JS로 채웁니다 -->
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="export-year" class="form-label">대상 연도</label>
                                                <select class="form-select" id="export-year" name="year">
                                                    <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                                                        <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?>년</option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn-info w-100">
                                                <i class="bx bx-download me-1"></i>Excel 다운로드
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 확인 모달 -->
<div class="modal fade" id="confirm-action-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirm-modal-title">작업 확인</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="confirm-modal-content">
                    <!-- 확인 내용이 여기에 표시됩니다 -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-leave-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="confirm-action-btn">확인</button>
            </div>
        </div>
    </div>
</div>

<style>
/* 관리 탭 스타일 */
.nav-pills .nav-link {
    color: #6c757d;
    border-radius: 25px;
    padding: 0.5rem 1rem;
    margin-right: 0.5rem;
}

.nav-pills .nav-link.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

/* 계산 결과 테이블 스타일 */
.grant-result-row.preview {
    background: #e3f2fd;
}

.grant-result-row.success {
    background: #e8f5e8;
}

.grant-result-row.error {
    background: #ffebee;
}

.grant-status-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 3px;
}

.grant-status-badge.preview {
    background: #e3f2fd;
    color: #1976d2;
}

.grant-status-badge.success {
    background: #e8f5e8;
    color: #2e7d32;
}

.grant-status-badge.error {
    background: #ffebee;
    color: #c62828;
}

/* 조정 폼 스타일 */
.adjust-amount-input.positive {
    border-color: #28a745;
    background: #f8fff9;
}

.adjust-amount-input.negative {
    border-color: #dc3545;
    background: #fff8f8;
}

/* 소멸 대상 스타일 */
.expire-target-row {
    background: #fff3cd;
}

.expire-target-row.selected {
    background: #f8d7da;
}

/* 일괄 처리 결과 */
.bulk-result {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    padding: 1rem;
    margin-top: 1rem;
}

.bulk-result.success {
    background: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.bulk-result.error {
    background: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

/* 진행률 표시 */
.progress-container {
    margin: 1rem 0;
}

.progress {
    height: 8px;
    border-radius: 4px;
}

/* 로딩 오버레이 */
.processing-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.processing-content {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    text-align: center;
    min-width: 300px;
}

.processing-spinner {
    margin-bottom: 1rem;
}
</style>

<script>
// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    if (typeof LeaveAdminManagement !== 'undefined') {
        window.leaveAdminManagement = new LeaveAdminManagement();
    }
});
</script>
<?php \App\Core\View::getInstance()->endSection(); ?>