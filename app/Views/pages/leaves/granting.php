<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">연차 관리</h4>
                <div class="flex-shrink-0 d-flex align-items-center gap-2">
                    <select class="form-select form-select-sm" id="filter-year" style="width: 100px;">
                        <?php for ($i = date('Y') + 1; $i >= date('Y') - 5; $i--): ?>
                        <option value="<?= $i ?>" <?= $i == date('Y') ? 'selected' : '' ?>><?= $i ?>년</option>
                        <?php endfor; ?>
                    </select>
                    <select class="form-select form-select-sm" id="filter-department" style="width: 150px;">
                        <option value="">전체 부서</option>
                    </select>
                    <button class="btn btn-sm btn-primary" id="filter-btn">조회</button>
                    <button class="btn btn-sm btn-info" id="preview-grant-btn">연차 일괄 부여 계산</button>
                    <button class="btn btn-sm btn-danger" id="expire-all-btn">연말 일괄 소멸</button>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table align-middle table-nowrap table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>직원</th>
                                <th>부서</th>
                                <th>총 부여</th>
                                <th>사용</th>
                                <th>잔여</th>
                                <th>기본</th>
                                <th>월차</th>
                                <th>근속</th>
                                <th>조정</th>
                                <th>관리</th>
                            </tr>
                        </thead>
                        <tbody id="balances-table-body">
                            <!-- Data loaded via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for grant preview -->
<div class="modal fade" id="grant-preview-modal" tabindex="-1" aria-labelledby="grant-preview-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="grant-preview-modal-label"><span id="preview-year"></span>년 연차 일괄 부여 미리보기</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><span id="employee-count"></span>명의 직원에 대해 아래와 같이 연차가 부여됩니다. 내용을 확인 후 실행 버튼을 눌러주세요.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>직원 ID</th>
                                <th>이름</th>
                                <th>부서</th>
                                <th>입사일</th>
                                <th>부여될 기본 연차</th>
                                <th>부여될 월차</th>
                                <th>부여될 근속 연차</th>
                                <th>총 부여될 연차</th>
                            </tr>
                        </thead>
                        <tbody id="grant-preview-table-body">
                            <!-- Preview data will be inserted here -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-success" id="execute-grant-btn">부여 실행</button>
            </div>
        </div>
    </div>
</div>


<!-- Modal for manual adjustment (unchanged) -->
<div class="modal fade" id="adjustment-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">연차 수동 조정</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="adjustment-form">
                    <input type="hidden" name="employee_id">
                    <div class="mb-3">
                        <label class="form-label">직원</label>
                        <p class="form-control-plaintext" id="adjustment-employee-name"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">조정할 일수</label>
                        <input type="number" step="0.1" class="form-control" name="days" required>
                        <div class="form-text">양수(+)는 포상, 음수(-)는 징계/차감. (예: -1.0)</div>
                    </div>
                     <div class="mb-3">
                        <label class="form-label">기준 연도</label>
                        <input type="number" class="form-control" name="year" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">조정 사유</label>
                        <input type="text" class="form-control" name="reason" required maxlength="100">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                <button type="submit" form="adjustment-form" class="btn btn-primary">저장</button>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
