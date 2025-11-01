<?php \App\Core\View::getInstance()->startSection('content'); ?>
    <h1>환영합니다, <?= htmlspecialchars($nickname ?? '사용자') ?>님!</h1>
    <p>대시보드입니다.</p>

    <!-- 잔여 연차 현황 위젯 -->
    <div class="row">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    나의 잔여 연차 현황
                </div>
                <div class="card-body">
                    <p><strong>연차 잔여일:</strong> <span id="dashboard-annual-leave">...</span>일</p>
                    <p><strong>월차 잔여일:</strong> <span id="dashboard-monthly-leave">...</span>일</p>
                    <a href="/leaves/my">자세히 보기 &raquo;</a>
                </div>
            </div>
        </div>
    </div>

<?php \App\Core\View::getInstance()->endSection(); ?>
