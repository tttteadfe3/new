<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= e($pageTitle ?? '지급품 지급 관리') ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/">홈</a></li>
                    <li class="breadcrumb-item"><a href="/supply">지급품 관리</a></li>
                    <li class="breadcrumb-item active">지급 관리</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Create Document Modal -->
<div class="modal fade" id="createDocumentModal" tabindex="-1" aria-labelledby="createDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createDocumentModalLabel">새 지급 문서 작성</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="create-document-form">
                    <div class="mb-3">
                        <label for="document-title" class="form-label">문서 제목 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="document-title" placeholder="예: 2023년 4분기 사무용품 지급" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">품목 선택</label>
                                <div class="input-group">
                                    <select class="form-select" id="item-select">
                                        <option value="">불러오는 중...</option>
                                    </select>
                                    <input type="number" class="form-control" id="item-quantity" min="1" value="1" style="max-width: 80px;">
                                    <button class="btn btn-outline-primary" type="button" id="add-item-btn">추가</button>
                                </div>
                                <div class="form-text">지급할 품목과 수량을 선택하고 추가 버튼을 누르세요.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                             <div class="mb-3">
                                <label for="department-select" class="form-label">부서 선택</label>
                                <select class="form-select" id="department-select">
                                    <option value="">불러오는 중...</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">직원 선택</label>
                                 <div class="input-group">
                                    <select class="form-select" id="employee-select" disabled>
                                        <option value="">부서를 먼저 선택하세요</option>
                                    </select>
                                    <button class="btn btn-outline-primary" type="button" id="add-employee-btn">추가</button>
                                </div>
                                <div class="form-text">부서를 선택하면 해당 부서의 직원이 표시됩니다.</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h6>지급 품목 목록</h6>
                            <ul class="list-group" id="item-list"></ul>
                        </div>
                        <div class="col-md-6">
                            <h6>지급 대상 직원</h6>
                            <ul class="list-group" id="employee-list"></ul>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                <button type="button" class="btn btn-primary" id="save-document-btn">문서 저장</button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="card-title mb-0">지급 문서 목록</h5>
                    </div>
                    <div class="col-md-6 text-end">
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createDocumentModal">
                            <i class="ri-add-line align-bottom me-1"></i> 새 문서 작성
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-nowrap table-striped-columns mb-0" id="documents-table" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">문서 제목</th>
                                <th scope="col">작성자</th>
                                <th scope="col">생성일</th>
                                <th scope="col">상태</th>
                                <th scope="col">작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be populated by DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Distribution Modal -->
<div class="modal fade" id="cancelDistributionModal" tabindex="-1" aria-labelledby="cancelDistributionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelDistributionModalLabel">지급 취소</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="cancel-distribution-info"></div>
                <div class="mb-3">
                    <label for="cancel-reason" class="form-label">취소 사유 <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="cancel-reason" rows="3" placeholder="취소 사유를 입력하세요" required></textarea>
                </div>
                <div class="alert alert-warning" role="alert">
                    <i class="ri-alert-line me-2"></i>
                    지급을 취소하면 재고가 복원됩니다.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                <button type="button" class="btn btn-danger" id="confirm-cancel-distribution-btn">취소 처리</button>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
