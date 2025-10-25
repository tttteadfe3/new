<?php
// app/Views/pages/admin/positions/edit.php
use App.core\View;
use App\Services\ViewDataService;

View::setLayout('layouts/app');
View::startSection('content');
$viewData = ViewDataService::getViewData();
?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">직급 수정</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/admin">관리자</a></li>
                    <li class="breadcrumb-item"><a href="/admin/positions">직급 관리</a></li>
                    <li class="breadcrumb-item active">수정</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">직급 정보 수정</h5>
            </div>
            <div class="card-body">
                <form action="/admin/positions/update/<?= $position['id'] ?>" method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">직급명</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($position['name'] ?? '') ?>" required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="text-danger mt-1"><?= $errors['name'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="level" class="form-label">레벨</label>
                        <input type="number" class="form-control" id="level" name="level" value="<?= htmlspecialchars($position['level'] ?? '10') ?>" required>
                        <small class="form-text text-muted">레벨이 낮을수록 높은 직급입니다. (예: 대표 1, 사원 10)</small>
                        <?php if (isset($errors['level'])): ?>
                            <div class="text-danger mt-1"><?= $errors['level'] ?></div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary">저장</button>
                        <a href="/admin/positions" class="btn btn-secondary">취소</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php View::endSection(); ?>
