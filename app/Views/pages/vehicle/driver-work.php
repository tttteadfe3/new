<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">내 작업 관리</h5>
            </div>
            <div class="card-body">
                <!-- 탭 -->
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#tab-breakdown">수리 요청/등록</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-maintenance">정비 등록</a>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- 수리 요청/등록 탭 -->
                    <div class="tab-pane fade show active" id="tab-breakdown">
                        <button type="button" class="btn btn-primary mb-3" id="btn-report-breakdown">
                            <i class="ri-add-line"></i> 고장 신고
                        </button>
                        <table id="breakdown-table" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>차량</th>
                                    <th>작업항목</th>
                                    <th>구분</th>
                                    <th>상태</th>
                                    <th>신고일</th>
                                    <th>작업</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <!-- 정비 등록 탭 -->
                    <div class="tab-pane fade" id="tab-maintenance">
                        <button type="button" class="btn btn-primary mb-3" id="btn-report-maintenance">
                            <i class="ri-add-line"></i> 정비 등록
                        </button>
                        <table id="maintenance-table" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>차량</th>
                                    <th>작업항목</th>
                                    <th>상태</th>
                                    <th>등록일</th>
                                    <th>작업</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 작업 신고 모달 -->
<div class="modal fade" id="workModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="work-modal-title">작업 신고</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="workForm">
                    <input type="hidden" id="work_type">
                    <input type="hidden" id="work_id">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">차량 <span class="text-danger">*</span></label>
                            <select class="form-select" id="vehicle_id" required>
                                <option value="">선택하세요</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">작업 항목 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="work_item" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">상세 내용</label>
                            <textarea class="form-control" id="description" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">주행거리 (km)</label>
                            <input type="number" class="form-control" id="mileage">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">사진 1</label>
                            <input type="file" class="form-control" id="photo" accept="image/*">
                        </div>
                        <div class="col-md-6 photo-extra d-none">
                            <label class="form-label">사진 2</label>
                            <input type="file" class="form-control" id="photo2" accept="image/*">
                        </div>
                        <div class="col-md-6 photo-extra d-none">
                            <label class="form-label">사진 3</label>
                            <input type="file" class="form-control" id="photo3" accept="image/*">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="btn-save-work">신고</button>
            </div>
        </div>
    </div>
</div>

<!-- 수리 등록 모달 (접수된 건 처리용) -->
<div class="modal fade" id="repairModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">수리 등록</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="repairForm">
                    <input type="hidden" id="repair_work_id">
                    <div class="mb-3">
                        <label class="form-label">정비소 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="repair_shop" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">수리 비용</label>
                        <input type="number" class="form-control" id="repair_cost">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">수리 내용</label>
                        <textarea class="form-control" id="repair_description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">수리 일자 <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="repair_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">사진 1</label>
                        <input type="file" class="form-control" id="repair_photo" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">사진 2</label>
                        <input type="file" class="form-control" id="repair_photo2" accept="image/*">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="btn-save-repair">등록</button>
            </div>
        </div>
    </div>
</div>

<!-- 상세 정보 모달 -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">작업 상세 정보</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detail-content">
                <!-- 상세 내용이 여기에 로드됩니다 -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<?php \App\Core\View::getInstance()->endSection(); ?>
