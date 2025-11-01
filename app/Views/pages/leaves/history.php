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
                        <label for="filter-status" class="form-label">유형</label>
                        <select class="form-select" id="filter-status">
                            <option value="">전체 유형</option>
                            <option value="부여">부여</option>
                            <option value="사용">사용</option>
                            <option value="사용취소">사용취소</option>
                            <option value="소멸">소멸</option>
                            <option value="포상">포상</option>
                            <option value="징계">징계</option>
                            <option value="기타조정">기타조정</option>
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
                            <th>직원명</th>
                            <th>유형</th>
                            <th>일수</th>
                            <th>사유</th>
                            <th>처리자</th>
                            <th>처리일시</th>
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
