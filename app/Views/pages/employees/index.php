<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <!-- Left Panel: Employee List -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">직원 목록</h5>
                    <button type="button" class="btn btn-success add-btn" id="add-employee-btn"><i class="ri-add-line align-bottom me-1"></i> 신규 등록</button>
                </div>
            </div>
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-12">
                        <select class="form-select" id="filter-department">
                            <option value="">모든 부서</option>
                        </select>
                    </div>
                    <div class="col-12">
                         <select class="form-select" id="filter-position">
                            <option value="">모든 직급</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <select class="form-select" id="filter-status">
                            <option value="">모든 직원</option>
                            <option value="재직중" selected>재직중</option>
                            <option value="퇴사">퇴사</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body px-0">
                <div id="employee-list-container" class="list-group list-group-flush" style="max-height: 600px; overflow-y: auto;">
                    <!-- Employee list will be populated by JS -->
                </div>
                 <div id="no-employee-result" class="text-center p-3" style="display: none;">
                    <p class="text-muted mb-0">검색 결과가 없습니다.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Panel: Employee Details -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <div id="employee-details-container">
                    <div class="text-center p-5">
                        <i class="bi bi-person-circle fs-1 text-muted"></i>
                        <p class="mt-3 text-muted">왼쪽 목록에서 직원을 선택하거나 '신규 등록' 버튼을 클릭하여 시작하세요.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
