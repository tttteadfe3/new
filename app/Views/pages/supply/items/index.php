<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">품목 관리</h5>
                    <button type="button" class="btn btn-success add-btn" id="create-item-btn">
                        <i class="ri-add-line align-bottom me-1"></i> 신규 등록
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label for="filter-category" class="form-label">분류</label>
                        <select class="form-select" id="filter-category">
                            <option value="">전체</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filter-status" class="form-label">상태</label>
                        <select class="form-select" id="filter-status">
                            <option value="">전체</option>
                            <option value="1" selected>활성</option>
                            <option value="0">비활성</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="search-input" class="form-label">검색</label>
                        <div class="search-box">
                            <input type="text" class="form-control" id="search-input" placeholder="품목명 검색...">
                            <i class="ri-search-line search-icon"></i>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="items-table" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                        <thead>
                            <tr>

                                <th>품목명</th>
                                <th>분류</th>
                                <th>단위</th>
                                <th>상태</th>
                                <th>등록일</th>
                                <th>작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables will populate this -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Toggle Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusModalLabel">상태 변경 확인</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="status-change-message"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="confirm-status-btn">확인</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">품목 삭제 확인</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>정말로 이 품목을 삭제하시겠습니까?</p>
                <p class="text-danger small">
                    <i class="ri-alert-line me-1"></i>
                    연관된 계획, 구매, 지급 데이터가 있는 경우 삭제할 수 없습니다.
                </p>
                <div id="delete-item-info"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-btn">삭제</button>
            </div>
        </div>
    </div>
</div>

<!-- Create Item Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createModalLabel">품목 등록</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="create-form">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="create-category-id" class="form-label">분류 <span class="text-danger">*</span></label>
                                <select class="form-select" id="create-category-id" name="category_id" required>
                                    <option value="">분류를 선택하세요</option>
                                </select>
                                <div class="invalid-feedback">분류를 선택해주세요.</div>
                            </div>
                        </div>

                    </div>

                    <div class="mb-3">
                        <label for="create-item-name" class="form-label">품목명 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="create-item-name" name="item_name" required maxlength="200">
                        <div class="invalid-feedback">품목명을 입력해주세요.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="create-unit" class="form-label">단위 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="create-unit" name="unit" required maxlength="20" placeholder="예: 개, 박스, 세트">
                                <div class="invalid-feedback">단위를 입력해주세요.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="create-min-stock-level" class="form-label">최소 재고 수준</label>
                                <input type="number" class="form-control" id="create-min-stock-level" name="min_stock_level" min="0" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="create-description" class="form-label">설명</label>
                        <textarea class="form-control" id="create-description" name="description" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="create-is-active" name="is_active" checked>
                            <label class="form-check-label" for="create-is-active">
                                활성 상태
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-success" id="confirm-create-btn">
                    <i class="ri-save-line me-1"></i> 등록
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Item Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">품목 수정</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="edit-form">
                    <input type="hidden" id="edit-item-id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit-category-id" class="form-label">분류 <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit-category-id" name="category_id" required>
                                    <option value="">분류를 선택하세요</option>
                                </select>
                                <div class="invalid-feedback">분류를 선택해주세요.</div>
                            </div>
                        </div>

                    </div>

                    <div class="mb-3">
                        <label for="edit-item-name" class="form-label">품목명 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit-item-name" name="item_name" required maxlength="200">
                        <div class="invalid-feedback">품목명을 입력해주세요.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit-unit" class="form-label">단위 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit-unit" name="unit" required maxlength="20" placeholder="예: 개, 박스, 세트">
                                <div class="invalid-feedback">단위를 입력해주세요.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit-min-stock-level" class="form-label">최소 재고 수준</label>
                                <input type="number" class="form-control" id="edit-min-stock-level" name="min_stock_level" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit-description" class="form-label">설명</label>
                        <textarea class="form-control" id="edit-description" name="description" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit-is-active" name="is_active">
                            <label class="form-check-label" for="edit-is-active">
                                활성 상태
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="confirm-edit-btn">
                    <i class="ri-save-line me-1"></i> 저장
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Show Item Modal -->
<div class="modal fade" id="showModal" tabindex="-1" aria-labelledby="showModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="showModalLabel">품목 상세 정보</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- 기본 정보 -->
                <h6 class="mb-3">기본 정보</h6>
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row mb-3">

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">분류</label>
                                <p class="form-control-plaintext" id="show-category-name">-</p>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">품목명</label>
                            <p class="form-control-plaintext" id="show-item-name">-</p>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">단위</label>
                                <p class="form-control-plaintext" id="show-unit">-</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">최소 재고 수준</label>
                                <p class="form-control-plaintext" id="show-min-stock-level">-</p>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">설명</label>
                            <p class="form-control-plaintext" id="show-description">-</p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">상태</label>
                            <p class="form-control-plaintext">
                                <span id="show-status-badge"></span>
                            </p>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">등록일</label>
                                <p class="form-control-plaintext" id="show-created-at">-</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">수정일</label>
                                <p class="form-control-plaintext" id="show-updated-at">-</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 재고 정보 -->
                <h6 class="mb-3">재고 정보</h6>
                <div class="card mb-3">
                    <div class="card-body">
                        <div id="stock-info-container">
                            <div class="text-center py-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">로딩 중...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 최근 거래 내역 -->
                <h6 class="mb-3">최근 거래 내역</h6>
                <div class="card">
                    <div class="card-body">
                        <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#purchases-tab" role="tab">
                                    구매 내역
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#distributions-tab" role="tab">
                                    지급 내역
                                </a>
                            </li>
                        </ul>
                        <div class="tab-content pt-3">
                            <div class="tab-pane active" id="purchases-tab" role="tabpanel">
                                <div id="purchases-container">
                                    <div class="text-center py-3">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">로딩 중...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane" id="distributions-tab" role="tabpanel">
                                <div id="distributions-container">
                                    <div class="text-center py-3">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">로딩 중...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>


<?php \App\Core\View::getInstance()->endSection(); ?>
