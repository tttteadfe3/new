<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">차량 관리</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/dashboard">대시보드</a></li>
                    <li class="breadcrumb-item active">차량 관리</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">차량 목록</h5>
                    <button type="button" class="btn btn-success add-btn" id="add-vehicle-btn">
                        <i class="ri-add-line align-bottom me-1"></i> 신규 등록
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="vehicle-list-container">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>차량번호</th>
                                <th>차종</th>
                                <th>연식</th>
                                <th>소속부서</th>
                                <th>운전자</th>
                                <th>상태</th>
                                <th>관리</th>
                            </tr>
                        </thead>
                        <tbody id="vehicle-table-body">
                            <!-- JavaScript로 동적 로드 -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 차량 정보 모달 -->
<div class="modal fade" id="vehicle-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="vehicle-modal-title">차량 정보</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="vehicle-form">
                <div class="modal-body">
                    <input type="hidden" id="vehicle-id" name="id">
                    <div class="mb-3">
                        <label for="vin" class="form-label">차대번호</label>
                        <input type="text" class="form-control" id="vin" name="vin" required>
                    </div>
                    <div class="mb-3">
                        <label for="license_plate" class="form-label">차량번호</label>
                        <input type="text" class="form-control" id="license_plate" name="license_plate" required>
                    </div>
                    <div class="mb-3">
                        <label for="make" class="form-label">제조사</label>
                        <input type="text" class="form-control" id="make" name="make">
                    </div>
                    <div class="mb-3">
                        <label for="model" class="form-label">모델</label>
                        <input type="text" class="form-control" id="model" name="model">
                    </div>
                    <div class="mb-3">
                        <label for="year" class="form-label">연식</label>
                        <input type="number" class="form-control" id="year" name="year">
                    </div>
                    <div class="mb-3">
                        <label for="department_id" class="form-label">소속부서</label>
                        <select class="form-select" id="department_id" name="department_id"></select>
                    </div>
                    <div class="mb-3">
                        <label for="driver_id" class="form-label">운전자</label>
                        <select class="form-select" id="driver_id" name="driver_id"></select>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">상태</label>
                        <input type="text" class="form-control" id="status" name="status">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
