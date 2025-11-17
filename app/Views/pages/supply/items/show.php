<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= e($pageTitle) ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/">홈</a></li>
                    <li class="breadcrumb-item"><a href="/supply">지급품 관리</a></li>
                    <li class="breadcrumb-item"><a href="/supply/items">품목 관리</a></li>
                    <li class="breadcrumb-item active">품목 상세</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 offset-lg-2">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">품목 상세 정보</h5>
                    <div class="flex-shrink-0">
                        <a href="/supply/items/edit?id=<?= e($itemId) ?>" class="btn btn-primary btn-sm">
                            <i class="ri-edit-line me-1"></i> 수정
                        </a>
                        <a href="/supply/items" class="btn btn-secondary btn-sm">
                            <i class="ri-arrow-left-line me-1"></i> 목록
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <input type="hidden" id="item-id" value="<?= e($itemId) ?>">
                
                <div id="item-details-container">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">로딩 중...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 재고 정보 카드 -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">재고 정보</h5>
            </div>
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
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">최근 거래 내역</h5>
            </div>
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
</div>

<?php \App\Core\View::getInstance()->endSection(); ?>
