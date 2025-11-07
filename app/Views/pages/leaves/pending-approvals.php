<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title mb-0">승인 대기 목록</h5>
                    </div>
                    <div class="col-auto">
                        <div class="d-flex align-items-center gap-2">
                            <select class="form-select form-select-sm" id="department-filter" style="width: 150px;">
                                <option value="">전체 부서</option>
                                <!-- 부서 목록은 JS로 채웁니다 -->
                            </select>
                            <a href="<?= BASE_URL ?>/leaves/admin-dashboard" class="btn btn-leave-secondary btn-sm">
                                <i class="bx bx-arrow-back me-1"></i>대시보드로
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- 승인 대기 탭 -->
                <div class="pending-requests-container">
                    <ul class="nav nav-pills pending-requests-tabs mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#leave-requests-tab" role="tab">
                                <i class="bx bx-calendar me-1"></i>연차 신청 <span class="badge bg-warning ms-1" id="leave-requests-count">0</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#cancellation-requests-tab" role="tab">
                                <i class="bx bx-x-circle me-1"></i>취소 신청 <span class="badge bg-danger ms-1" id="cancellation-requests-count">0</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#processed-tab" role="tab">
                                <i class="bx bx-check-circle me-1"></i>처리 완료
                            </a>
                        </li>
                    </ul>
                    
                    <div class="tab-content">
                        <!-- 연차 신청 승인 대기 -->
                        <div class="tab-pane active" id="leave-requests-tab" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <button class="btn btn-success btn-sm" id="bulk-approve-selected-btn" disabled>
                                        <i class="bx bx-check me-1"></i>선택 항목 승인
                                    </button>
                                    <button class="btn btn-danger btn-sm" id="bulk-reject-selected-btn" disabled>
                                        <i class="bx bx-x me-1"></i>선택 항목 반려
                                    </button>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <label for="sort-by" class="form-label mb-0">정렬:</label>
                                    <select class="form-select form-select-sm" id="sort-by" style="width: 120px;">
                                        <option value="created_at">신청일순</option>
                                        <option value="start_date">시작일순</option>
                                        <option value="employee_name">직원명순</option>
                                        <option value="department">부서순</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table align-middle table-nowrap table-striped mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 40px;">
                                                <input type="checkbox" id="select-all-requests" class="form-check-input">
                                            </th>
                                            <th>신청자</th>
                                            <th>부서</th>
                                            <th>신청일</th>
                                            <th>휴가 기간</th>
                                            <th>유형</th>
                                            <th>일수</th>
                                            <th>사유</th>
                                            <th>잔여 연차</th>
                                            <th style="width: 120px;">관리</th>
                                        </tr>
                                    </thead>
                                    <tbody id="leave-requests-body">
                                        <tr>
                                            <td colspan="10" class="text-center text-muted">승인 대기 중인 연차 신청이 없습니다</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- 취소 신청 승인 대기 -->
                        <div class="tab-pane" id="cancellation-requests-tab" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <button class="btn btn-success btn-sm" id="bulk-approve-cancellations-btn" disabled>
                                        <i class="bx bx-check me-1"></i>선택 항목 승인
                                    </button>
                                    <button class="btn btn-danger btn-sm" id="bulk-reject-cancellations-btn" disabled>
                                        <i class="bx bx-x me-1"></i>선택 항목 반려
                                    </button>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table align-middle table-nowrap table-striped mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 40px;">
                                                <input type="checkbox" id="select-all-cancellations" class="form-check-input">
                                            </th>
                                            <th>신청자</th>
                                            <th>부서</th>
                                            <th>원본 휴가</th>
                                            <th>일수</th>
                                            <th>취소 신청일</th>
                                            <th>취소 사유</th>
                                            <th style="width: 120px;">관리</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cancellation-requests-body">
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">승인 대기 중인 취소 신청이 없습니다</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- 처리 완료 -->
                        <div class="tab-pane" id="processed-tab" role="tabpanel">
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <select class="form-select form-select-sm" id="processed-type-filter">
                                        <option value="">전체 유형</option>
                                        <option value="leave_approved">연차 승인</option>
                                        <option value="leave_rejected">연차 반려</option>
                                        <option value="cancellation_approved">취소 승인</option>
                                        <option value="cancellation_rejected">취소 반려</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="date" class="form-control form-control-sm" id="processed-date-from" placeholder="처리일 시작">
                                </div>
                                <div class="col-md-3">
                                    <input type="date" class="form-control form-control-sm" id="processed-date-to" placeholder="처리일 종료">
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-primary btn-sm w-100" id="search-processed-btn">
                                        <i class="bx bx-search me-1"></i>조회
                                    </button>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table align-middle table-nowrap table-striped mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>처리일</th>
                                            <th>신청자</th>
                                            <th>부서</th>
                                            <th>유형</th>
                                            <th>휴가 기간</th>
                                            <th>일수</th>
                                            <th>결과</th>
                                            <th>처리자</th>
                                            <th>사유</th>
                                        </tr>
                                    </thead>
                                    <tbody id="processed-requests-body">
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">처리 완료된 신청이 없습니다</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 개별 승인/반려 모달 -->
<div class="modal fade" id="approval-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approval-modal-title">연차 신청 처리</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="approval-form">
                    <input type="hidden" id="approval-request-id" name="request_id">
                    <input type="hidden" id="approval-request-type" name="request_type">
                    
                    <!-- 신청 정보 표시 -->
                    <div class="mb-4">
                        <h6 class="mb-3">신청 정보</h6>
                        <div class="bg-light p-3 rounded" id="approval-request-info">
                            <!-- 신청 정보가 여기에 표시됩니다 -->
                        </div>
                    </div>
                    
                    <!-- 처리 결과 선택 -->
                    <div class="mb-3">
                        <label class="form-label">처리 결과 <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="action" id="approve-radio" value="approve" checked>
                                <label class="form-check-label text-success" for="approve-radio">
                                    <i class="bx bx-check-circle me-1"></i>승인
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="action" id="reject-radio" value="reject">
                                <label class="form-check-label text-danger" for="reject-radio">
                                    <i class="bx bx-x-circle me-1"></i>반려
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 처리 사유 -->
                    <div class="mb-3">
                        <label for="approval-reason" class="form-label">처리 사유</label>
                        <textarea class="form-control" id="approval-reason" name="reason" rows="3" 
                                  placeholder="승인 또는 반려 사유를 입력해주세요 (선택사항)"></textarea>
                    </div>
                    
                    <!-- 연차 잔여량 확인 (연차 신청인 경우만) -->
                    <div id="balance-check-section" class="mb-3" style="display: none;">
                        <div class="alert alert-info">
                            <div class="d-flex justify-content-between">
                                <span>신청자 현재 잔여 연차:</span>
                                <strong id="applicant-balance">0일</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>신청 일수:</span>
                                <strong id="requested-days">0일</strong>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between">
                                <span>승인 후 잔여 연차:</span>
                                <strong id="balance-after" class="text-primary">0일</strong>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-leave-secondary" data-bs-dismiss="modal">취소</button>
                <button type="submit" form="approval-form" class="btn btn-success" id="submit-approval-btn">승인</button>
            </div>
        </div>
    </div>
</div>

<!-- 일괄 처리 모달 -->
<div class="modal fade" id="bulk-action-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulk-modal-title">일괄 처리</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="bulk-action-form">
                    <input type="hidden" id="bulk-action-type" name="action_type">
                    <input type="hidden" id="bulk-request-ids" name="request_ids">
                    
                    <div class="mb-3">
                        <label class="form-label">선택된 항목</label>
                        <div class="bg-light p-3 rounded">
                            <div id="bulk-selected-count">0개 항목이 선택되었습니다</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bulk-reason" class="form-label">처리 사유</label>
                        <textarea class="form-control" id="bulk-reason" name="reason" rows="3" 
                                  placeholder="일괄 처리 사유를 입력해주세요"></textarea>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="bx bx-info-circle me-1"></i>
                        일괄 처리된 결과는 개별적으로 되돌릴 수 없습니다. 신중하게 처리해주세요.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-leave-secondary" data-bs-dismiss="modal">취소</button>
                <button type="submit" form="bulk-action-form" class="btn btn-primary" id="submit-bulk-action-btn">처리 실행</button>
            </div>
        </div>
    </div>
</div>



<script>
// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    if (typeof PendingApprovals !== 'undefined') {
        window.pendingApprovals = new PendingApprovals();
    }
});
</script>
<?php \App\Core\View::getInstance()->endSection(); ?>