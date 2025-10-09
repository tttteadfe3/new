<?php \App\Core\View::startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">부적정배출 신고</h4>
            </div>
            <div class="card-body">
                <p class="text-muted">부적정배출 신고는 지도에서 직접 등록해주세요.</p>
                <a href="/littering/map" class="btn btn-primary">
                    <i class="ri-map-pin-add-line me-1"></i>지도에서 신고하기
                </a>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::endSection(); ?>