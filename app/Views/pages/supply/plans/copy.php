<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= e($pageTitle) ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/">홈</a></li>
                    <li class="breadcrumb-item"><a href="/supply">지급품 관리</a></li>
                    <li class="breadcrumb-item"><a href="/supply/plans">연간 계획</a></li>
                    <li class="breadcrumb-item active">계획 복사</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">연간 계획 복사</h5>
            </div>
            <div class="card-body">
                <div class="row align-items-center g-3">
                    <div class="col-md-5">
                        <div class="input-group">
                            <label class="input-group-text" for="source-year">원본 연도</label>
                            <select class="form-select" id="source-year">
                                <!-- Options will be populated by JS -->
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 text-center">
                        <i class="ri-arrow-right-line fs-3"></i>
                    </div>
                    <div class="col-md-5">
                        <div class="input-group">
                            <label class="input-group-text" for="target-year">대상 연도</label>
                            <select class="form-select" id="target-year">
                                <!-- Options will be populated by JS -->
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="loader" class="text-center py-5" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">데이터를 불러오는 중입니다...</p>
        </div>

        <div id="plan-list-container">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">복사할 계획 선택</h6>
                    <p class="text-muted mb-0">대상 연도에 이미 등록된 품목은 목록에 나타나지 않습니다.</p>
                </div>
                <div class="card-body">
                    <div id="no-plans-to-copy" style="display: none;" class="text-center py-4">
                        <i class="ri-information-line fs-1 text-muted"></i>
                        <p class="mt-3 text-muted">복사할 수 있는 계획이 없습니다.</p>
                    </div>

                    <form id="copy-plan-form">
                        <ul class="list-group" id="copyable-plans-list">
                            <!-- Plan items will be populated by JS -->
                        </ul>

                        <div id="copy-actions" class="mt-3" style="display: none;">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="select-all-checkbox">
                                <label class="form-check-label" for="select-all-checkbox">
                                    전체 선택
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" id="copy-plans-btn">
                                <i class="ri-file-copy-line me-1"></i> 선택한 계획 복사
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
