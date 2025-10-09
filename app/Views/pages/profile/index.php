<?php \App\Core\View::startSection('content'); ?>
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
<?php \App\Core\View::endSection(); ?>