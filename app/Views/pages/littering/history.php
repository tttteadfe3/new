<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row mt-n4 mx-n4" style="margin:0;">
    <div class="col-12" style=" height: calc(100vh - 70px - 60px);padding:0;">
        <div id="map" style="width:100%;height:100%;position:relative;overflow:hidden;">
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="markerOffcanvas">
    <div class="d-flex align-items-center bg-primary bg-gradient p-3 offcanvas-header">
        <h5 class="offcanvas-title m-0 me-2 text-white">처리 내역 상세</h5>
        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="p-3 bg-light border-bottom">
            <p class="mb-0 small text-muted">주소</p>
            <h6 class="mb-0 fw-bold" id="offcanvasAddress"></h6>
        </div>
        <div id="processList" class="p-3" style="overflow-y: auto; height: calc(100% - 80px);">

        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
