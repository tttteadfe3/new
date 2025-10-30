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
                    <button class="btn btn-sm btn-success" id="grant-all-btn">연초 일괄 부여</button>
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
                                <th>입사일</th>
                                <th>총 부여</th>
                                <th>사용</th>
                                <th>잔여</th>
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

<!-- Modal for manual adjustment (내용 변경 없음) -->
<div class="modal fade" id="adjustment-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">연차 수동 조정</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="adjustment-form">
                    <input type="hidden" id="adjustment_employee_id" name="employee_id">
                    <div class="mb-3">
                        <label class="form-label">직원</label>
                        <p id="adjustment-employee-name" class="form-control-plaintext"></p>
                    </div>
                    <div class="mb-3">
                        <label for="adjustment_days" class="form-label">조정할 일수</label>
                        <input type="number" step="0.1" class="form-control" id="adjustment_days" name="days" required>
                        <div class="form-text">양수(+)는 포상, 음수(-)는 징계/차감. (예: -1.0)</div>
                    </div>
                    <div class="mb-3">
                        <label for="adjustment_reason" class="form-label">조정 사유</label>
                        <input type="text" class="form-control" id="adjustment_reason" name="reason" required maxlength="100">
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
