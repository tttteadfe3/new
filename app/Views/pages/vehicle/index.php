<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">차량 관리</h5>
                    <button type="button" class="btn btn-success" id="btn-create-vehicle">
                        <i class="ri-add-line align-bottom me-1"></i> 신규 등록
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- 필터 -->
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label">부서</label>
                        <select class="form-select" id="filter-department">
                            <option value="">전체</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">상태</label>
                        <select class="form-select" id="filter-status">
                            <option value="">전체</option>
                            <option value="정상">정상</option>
                            <option value="수리중">수리중</option>
                            <option value="폐차">폐차</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">검색</label>
                        <input type="text" class="form-control" id="search-input" placeholder="차량번호 또는 모델 검색...">
                    </div>
                </div>

                <!-- DataTable -->
                <div class="table-responsive">
                    <table id="vehicles-table" class="table table-bordered table-striped align-middle" style="width:100%">
                        <thead>
                            <tr>
                                <th>차량번호</th>
                                <th>모델</th>
                                <th>차종</th>
                                <th>연식</th>
                                <th>출고일자</th>
                                <th>부서</th>
                                <th>담당운전원</th>
                                <th>상태</th>
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

<!-- 차량 등록/수정 모달 -->
<div class="modal fade" id="vehicleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">차량 등록</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="vehicleForm">
                    <input type="hidden" id="vehicle_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">차량번호 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="vehicle_number" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">모델 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="model" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">차종</label>
                            <select class="form-select" id="vehicle_type">
                                <option value="">선택하세요</option>
                                <option value="압착">압착</option>
                                <option value="압축">압축</option>
                                <option value="음식물전용">음식물전용</option>
                                <option value="재활용">재활용</option>
                                <option value="대형.집게">대형.집게</option>
                                <option value="카고">카고</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">적재량</label>
                            <input type="text" class="form-control" id="payload_capacity" placeholder="예: 1톤">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">연식</label>
                            <input type="number" class="form-control" id="year" min="1900" max="2100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">출고일자</label>
                            <input type="date" class="form-control" id="release_date">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">배정 부서</label>
                            <select class="form-select" id="department_id">
                                <option value="">미배정</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">담당 운전원</label>
                            <select class="form-select" id="driver_employee_id">
                                <option value="">미배정</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">상태</label>
                            <select class="form-select" id="status_code">
                                <option value="정상">정상</option>
                                <option value="수리중">수리중</option>
                                <option value="폐차">폐차</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="btn-save-vehicle">저장</button>
            </div>
        </div>
    </div>
</div>

<!-- 차량 상세 모달 -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">차량 상세</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="vehicle-detail-content"></div>
            </div>
        </div>
    </div>
</div>

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">차량 삭제</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>정말 이 차량을 삭제하시겠습니까?</p>
                <div id="delete-vehicle-info"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-danger" id="btn-confirm-delete">삭제</button>
            </div>
        </div>
    </div>
</div>

<?php \App\Core\View::getInstance()->endSection(); ?>
