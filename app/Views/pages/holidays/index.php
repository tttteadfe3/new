<?php
use App\Core\View;

\App\Core\View::getInstance()->startSection('content');
?>

<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">휴일/근무일 설정</h4>
            <div class="page-title-right">
                <button type="button" class="btn btn-primary" id="add-holiday-btn">
                    <i class="ri-add-line align-bottom me-1"></i> 신규 등록
                </button>
            </div>
        </div>
    </div>
</div>
<!-- end page title -->

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">설정 목록</h5>
            </div>
            <div class="card-body">
                <table id="holidays-table" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                    <thead>
                        <tr>
                            <th>명칭</th>
                            <th>날짜</th>
                            <th>유형</th>
                            <th>적용 부서</th>
                            <th>연차 차감</th>
                            <th>관리</th>
                        </tr>
                    </thead>
                    <tbody id="holidays-table-body">
                        <!-- Data is populated by holiday_admin.js -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Holiday Modal -->
<div class="modal fade" id="holidayModal" tabindex="-1" aria-labelledby="holidayModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="holidayModalLabel">휴일/근무일 등록</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="holidayForm" onsubmit="return false;">
                    <input type="hidden" id="holidayId" name="id">

                    <div class="mb-3">
                        <label for="holidayName" class="form-label">명칭</label>
                        <input type="text" class="form-control" id="holidayName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="holidayDate" class="form-label">날짜</label>
                        <input type="text" class="form-control" id="holidayDate" name="date" required>
                    </div>
                    <div class="mb-3">
                        <label for="holidayType" class="form-label">유형</label>
                        <select class="form-select" id="holidayType" name="type" required>
                            <option value="holiday">휴일</option>
                            <option value="workday">특정 근무일</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="departmentId" class="form-label">적용 부서</label>
                        <select class="form-select" id="departmentId" name="department_id">
                            <option value="">전체 부서</option>
                        </select>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="deductLeave" name="deduct_leave">
                        <label class="form-check-label" for="deductLeave">연차 차감 여부 (휴일인 경우)</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">닫기</button>
                <button type="button" class="btn btn-primary" id="saveHolidayBtn">저장</button>
            </div>
        </div>
    </div>
</div>

<?php \App\Core\View::getInstance()->endSection(); ?>