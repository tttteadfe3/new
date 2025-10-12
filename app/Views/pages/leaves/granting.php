<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">연차 부여/계산</h4>
                <div class="flex-shrink-0 d-flex align-items-center gap-2">
                    <select class="form-select form-select-sm" id="filter-year" style="width: 100px;">
                        <?php for ($i = date('Y') + 1; $i >= date('Y') - 5; $i--): ?>
                        <option value="<?= $i ?>" <?= $i == date('Y') ? 'selected' : '' ?>><?= $i ?>년</option>
                        <?php endfor; ?>
                    </select>
                    <select class="form-select form-select-sm" id="filter-department" style="width: 150px;">
                        <option value="">전체 부서</option>
                    </select>
                    <button class="btn btn-sm btn-info" id="calculate-btn">전체 계산</button>
                    <button class="btn btn-sm btn-primary" id="save-btn" disabled>계산 결과 저장</button>
                </div>
            </div><!-- end card header -->

            <div class="card-body">
                <div class="alert alert-info mb-0">
                    <p class="mb-0">
                        <i class="bx bx-info-circle"></i> <strong>연차 계산기:</strong> 직원의 입사일을 기준으로 연차를 자동 계산하고 부여할 수 있습니다.
                    </p>
                    <p class="mb-0 mt-1">
                        계산된 연차는 대한민국 근로기준법에 따르며, 1년 미만 근속자는 입사일로부터 1년이 되는 시점에 15일의 연차가 부여됩니다.
                        이후 2년마다 1일씩 가산되어 최대 25일까지 부여됩니다.
                    </p>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table align-middle table-nowrap table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 120px;">직원 이름</th>
                                <th>부서</th>
                                <th style="width: 110px;">입사일</th>
                                <th style="width: 80px;">상태</th>
                                <th>기본</th>
                                <th>근속</th>
                                <th>조정</th>
                                <th>합계</th>
                                <th>사용</th>
                                <th>잔여</th>
                                <th style="width: 100px;">관리</th>
                            </tr>
                        </thead>
                        <tbody id="entitlement-table-body">
                            <tr>
                                <td colspan="7" class="text-center">목록을 불러오는 중...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for manual adjustment -->
<div class="modal fade" id="adjustment-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="adjustment-modal-title">연차 수동 조정</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="adjustment-form">
                    <input type="hidden" id="adjustment_employee_id" name="employee_id">
                    <div class="mb-3">
                        <label class="form-label">직원</label>
                        <p id="adjustment-employee-name" class="form-control-plaintext"></p>
                    </div>
                    <div class="mb-3">
                        <label for="adjustment_days" class="form-label">조정할 일수</label>
                        <input type="number" step="0.1" class="form-control" id="adjustment_days" name="adjustment_days" required>
                        <div class="form-text">양수(+)를 입력하면 연차가 추가되고, 음수(-)를 입력하면 차감됩니다. (예: -1.0)</div>
                    </div>
                    <div class="mb-3">
                        <label for="adjustment_reason" class="form-label">조정 사유</label>
                        <input type="text" class="form-control" id="adjustment_reason" name="reason" required maxlength="100">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                <button type="submit" form="adjustment-form" class="btn btn-primary">조정 내용 저장</button>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
