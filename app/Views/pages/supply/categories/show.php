<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">지급품 분류 상세</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/supply/categories">지급품 분류</a></li>
                    <li class="breadcrumb-item active">상세</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row" id="main-container">
    <div class="col-lg-8" id="details-col">
        <!-- Main content will be populated by JS -->
    </div>
    <div class="col-lg-4" id="sidebar-col">
        <!-- Sidebar will be populated by JS -->
    </div>
</div>

<!-- Loading Spinner -->
<div id="loading-container" class="text-center py-5">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">로딩 중...</span>
    </div>
</div>

<?php \App\Core\View::getInstance()->endSection(); ?>