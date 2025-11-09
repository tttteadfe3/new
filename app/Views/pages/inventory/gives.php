<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">지급 관리</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/dashboard">대시보드</a></li>
                    <li class="breadcrumb-item">지급품 관리</li>
                    <li class="breadcrumb-item active">지급 관리</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-wrap align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">지급 내역</h5>
                    <div class="d-flex gap-2 flex-wrap">
                        <input type="date" class="form-control" id="start-date-filter" style="width: 150px;">
                        <input type="date" class="form-control" id="end-date-filter" style="width: 150px;">
                        <select class="form-select" id="department-filter" style="width: 180px;"></select>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#give-modal">
                            <i class="ri-add-line align-bottom me-1"></i> 신규 지급 등록
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>지급일자</th>
                                <th>품목명</th>
                                <th>지급 대상</th>
                                <th>수량</th>
                                <th>비고</th>
                                <th>처리자</th>
                                <th>작업</th>
                            </tr>
                        </thead>
                        <tbody id="gives-table-body">
                            <!-- JS로 데이터 렌더링 -->
                        </tbody>
                    </table>
                </div>
                <div id="table-placeholder" class="text-center p-5"><p class="text-muted">조회 조건에 맞는 내역을 불러오는 중입니다...</p></div>
            </div>
        </div>
    </div>
</div>

<!-- Give Modal -->
<div class="modal fade" id="give-modal" tabindex="-1" aria-labelledby="give-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="give-form">
                <div class="modal-header">
                    <h5 class="modal-title" id="give-modal-label">신규 지급 등록</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="give-date" class="form-label">지급일자</label>
                            <input type="date" class="form-control" id="give-date" name="give_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="give-item" class="form-label">지급 품목</label>
                            <select class="form-select" id="give-item" name="item_id" required></select>
                        </div>
                    </div>

                    <div class="mb-3">
                         <label class="form-label">지급 대상</label>
                         <div class="d-flex gap-3">
                             <div class="form-check">
                                <input class="form-check-input" type="radio" name="give-target-type" id="target-dept" value="department" checked>
                                <label class="form-check-label" for="target-dept">부서</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="give-target-type" id="target-emp" value="employee">
                                <label class="form-check-label" for="target-emp">직원</label>
                            </div>
                         </div>
                    </div>

                    <div id="department-select-group" class="mb-3">
                        <label for="give-department" class="form-label">지급 부서</label>
                        <select class="form-select" id="give-department" name="department_id"></select>
                    </div>

                    <div id="employee-select-group" class="mb-3" style="display: none;">
                        <label for="give-employee" class="form-label">지급 직원</label>
                        <select class="form-select" id="give-employee" name="employee_id"></select>
                    </div>

                    <div class="mb-3">
                        <label for="give-quantity" class="form-label">지급 수량</label>
                        <input type="number" class="form-control" id="give-quantity" name="quantity" required min="1">
                    </div>

                    <div class="mb-3">
                        <label for="give-note" class="form-label">비고</label>
                        <textarea class="form-control" id="give-note" name="note" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">닫기</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
