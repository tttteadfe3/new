<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div id="profile-container" class="mt-2">
    <!-- my-page.js will render the full dashboard here -->
    <div class="text-center">
        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">로딩...</span></div>
        <p class="mt-2">사용자 정보를 불러오는 중...</p>
    </div>
</div>

<!-- Leave Request Modal -->
<div class="modal fade" id="leave-request-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">연차 신청</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="leave-request-form">
                    <div class="mb-3">
                        <label for="leave_subtype" class="form-label">휴가 종류</label>
                        <select class="form-select" id="leave_subtype" name="leave_subtype">
                            <option value="full_day">연차 (하루)</option>
                            <option value="half_day_am">오전 반차</option>
                            <option value="half_day_pm">오후 반차</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">시작일</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">종료일</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="days_count" class="form-label">사용 일수</label>
                        <input type="number" step="0.5" class="form-control" id="days_count" name="days_count" readonly required>
                        <div id="leave-date-feedback" class="form-text"></div>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">사유 (필수)</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                <button type="submit" form="leave-request-form" class="btn btn-primary">제출하기</button>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
