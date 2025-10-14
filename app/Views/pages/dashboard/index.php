<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row mt-2">
    <div class="card">
        <div class="card-body">
            <div id="profile-container">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">프로필 정보를 불러오는 중...</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
<!-- Leave Request Modal -->
<div class="modal fade" id="leave-request-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">연차 신청</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
