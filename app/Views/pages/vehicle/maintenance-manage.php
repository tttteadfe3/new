<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">작업 처리 관리</h5>
                <div class="d-flex gap-2">
                    <select id="filter-vehicle" class="form-select form-select-sm" style="width: 200px;">
                        <option value="">전체 차량</option>
                    </select>
                    <select id="filter-type" class="form-select form-select-sm" style="width: auto;">
                        <option value="">전체 유형</option>
                        <option value="고장">고장</option>
                        <option value="정비">정비</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <table id="work-table" class="table table-bordered">
                    <thead>
                        <tr>
                            <th>차량</th>
                            <th>작업유형</th>
                            <th>작업항목</th>
                            <th>신고자</th>
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

<!-- 작업 상세 + 워크플로우 모달 -->
<div class="modal fade" id="workDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">작업 상세</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="work-detail-content"></div>
                <div id="workflow-buttons" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- 접수 모달 -->
<div class="modal fade" id="acceptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">고장 접수</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="accept_work_id">
                <p>수리 방법을 선택하세요.</p>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="repair_type" id="repair_internal" value="자체수리" checked>
                    <label class="form-check-label" for="repair_internal">자체 수리</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="repair_type" id="repair_external" value="외부수리">
                    <label class="form-check-label" for="repair_external">외부 수리</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="btn-confirm-accept">접수</button>
            </div>
        </div>
    </div>
</div>

<?php \App\Core\View::getInstance()->endSection(); ?>
