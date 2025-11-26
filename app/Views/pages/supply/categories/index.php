<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <!-- Left Panel: Category List -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">분류 목록</h5>
                    <button type="button" class="btn btn-success add-btn" id="add-category-btn">
                        <i class="ri-add-line align-bottom me-1"></i> 신규 등록
                    </button>
                </div>
            </div>
            <div class="card-body border-bottom">
                <div class="row g-3">
                    <div class="col-12">
                        <select class="form-select" id="filter-level">
                            <option value="">모든 분류</option>
                            <option value="1">대분류</option>
                            <option value="2">소분류</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <select class="form-select" id="filter-status">
                            <option value="">모든 상태</option>
                            <option value="1" selected>활성</option>
                            <option value="0">비활성</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="search-box">
                            <input type="text" class="form-control" id="search-categories" placeholder="분류명 검색...">
                            <i class="ri-search-line search-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body px-0">
                <div id="category-list-container" class="list-group list-group-flush" style="max-height: 600px; overflow-y: auto;">
                    <!-- Category list will be populated by JS -->
                </div>
                <div id="no-category-result" class="text-center p-3" style="display: none;">
                    <p class="text-muted mb-0">검색 결과가 없습니다.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Panel: Category Details -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <div id="category-details-container">
                    <div class="text-center p-5">
                        <i class="ri-folder-line fs-1 text-muted"></i>
                        <p class="mt-3 text-muted">왼쪽 목록에서 분류를 선택하거나 '신규 등록' 버튼을 클릭하여 시작하세요.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Category Form Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryModalLabel">분류 등록</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="categoryForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="category-level" class="form-label">분류 레벨 <span class="text-danger">*</span></label>
                                    <select class="form-select" id="category-level" name="level" required>
                                        <option value="">선택하세요</option>
                                        <option value="1">대분류</option>
                                        <option value="2">소분류</option>
                                    </select>
                                </div>
                                <div class="col-md-6" id="parent-category-container" style="display: none;">
                                    <label for="parent-category" class="form-label">상위 분류 <span class="text-danger">*</span></label>
                                    <select class="form-select" id="parent-category" name="parent_id">
                                        <option value="">선택하세요</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="category-name" class="form-label">분류명 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="category-name" name="category_name" required maxlength="100">
                                </div>
                                <div class="col-md-6">
                                    <label for="display-order" class="form-label">표시 순서</label>
                                    <input type="number" class="form-control" id="display-order" name="display_order" value="0" min="0">
                                </div>
                                <div class="col-md-6">
                                    <label for="is-active" class="form-label">상태</label>
                                    <select class="form-select" id="is-active" name="is_active">
                                        <option value="1" selected>활성</option>
                                        <option value="0">비활성</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div id="category-form-help">
                                <!-- Help content will be injected by JS -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary" id="save-category-btn">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCategoryModalLabel">분류 삭제 확인</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>정말로 이 분류를 삭제하시겠습니까?</p>
                <p class="text-danger small">
                    <i class="ri-alert-line me-1"></i>
                    연관된 품목이나 하위 분류가 있는 경우 삭제할 수 없습니다.
                </p>
                <div id="delete-category-info"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-btn">삭제</button>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>