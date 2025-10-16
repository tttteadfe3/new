<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">직원 연차 내역 조회</h4>
            </div>

            <div class="card-body">
                <div class="row mb-3 bg-light p-3 rounded align-items-end">
                    <div class="col-md-3">
                        <label for="filter-year" class="form-label">연도</label>
                        <select class="form-select" id="filter-year">
                           <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                           <option value="<?= $i ?>" <?= $i == date('Y') ? 'selected' : '' ?>><?= $i ?>년</option>
                           <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filter-department" class="form-label">부서</label>
                        <select class="form-select" id="filter-department">
                            <option value="">전체 부서</option>
                            <!-- JS will populate this -->
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filter-status" class="form-label">상태</label>
                        <select class="form-select" id="filter-status">
                            <option value="">전체 상태</option>
                            <option value="approved">승인</option>
                            <option value="pending">대기중</option>
                            <option value="rejected">반려</option>
                            <option value="cancelled">취소</option>
                            <option value="cancellation_requested">취소 요청</option>
                        </select>
                    </div>
                     <div class="col-md-3">
                        <button class="btn btn-primary w-100" id="filter-btn">조회</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table align-middle table-nowrap table-striped mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>직원</th>
                            <th>부서</th>
                            <th>휴가 종류</th>
                            <th>기간</th>
                            <th>일수</th>
                            <th>상태</th>
                            <th>신청일</th>
                        </tr>
                        </thead>
                        <tbody id="leave-history-body">
                            <!-- Data loaded via JS -->
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
