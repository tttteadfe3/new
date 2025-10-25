<?php
// app/Views/pages/admin/positions/edit.php
use App\Core\View;
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
                <form id="edit-position-form" data-position-id="<?= $position['id'] ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label">직급명</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($position['name'] ?? '') ?>" required>
                        <div class="invalid-feedback" id="name-error"></div>
                    </div>
                    <div class="mb-3">
                        <label for="level" class="form-label">레벨</label>
                        <input type="number" class="form-control" id="level" name="level" value="<?= htmlspecialchars($position['level'] ?? '10') ?>" required>
                        <small class="form-text text-muted">레벨이 낮을수록 높은 직급입니다. (예: 대표 1, 사원 10)</small>
                        <div class="invalid-feedback" id="level-error"></div>
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

<?php View::startSection('js'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('edit-position-form');
    const positionId = form.dataset.positionId;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        fetch(`/api/positions/${positionId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                window.location.href = '/admin/positions';
            } else if (result.errors) {
                // Clear previous errors
                document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

                for (const [key, message] of Object.entries(result.errors)) {
                    const input = document.getElementById(key);
                    const errorDiv = document.getElementById(`${key}-error`);
                    if (input && errorDiv) {
                        input.classList.add('is-invalid');
                        errorDiv.textContent = message;
                    }
                }
            } else {
                alert(result.message || 'An unexpected error occurred.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while saving the position.');
        });
    });
});
</script>
<?php View::endSection(); ?>
