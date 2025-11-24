<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">작업 처리 관리</h5>
            </div>
            <div class="card-body">
                <!-- 탭 -->
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#tab-breakdown">접수 대기</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-maintenance">승인 대기</a>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- 접수 대기 탭 -->
                    <div class="tab-pane fade show active" id="tab-breakdown">
                        <table id="breakdown-table" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>차량</th>
                                    <th>작업항목</th>
                                    <th>신고자</th>
                                    <th>상태</th>
                                    <th>신고일</th>
                                    <th>작업</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <!-- 승인 대기 탭 -->
                    <div class="tab-pane fade" id="tab-maintenance">
                        <table id="maintenance-table" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>차량</th>
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
