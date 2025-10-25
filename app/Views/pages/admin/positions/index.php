<?php
// app/Views/pages/admin/positions/index.php
use App\Core\View;
use App\Services\ViewDataService;

View::setLayout('layouts/app');
View::startSection('css');
// Add any page-specific CSS here
View::endSection();

View::startSection('content');
$viewData = ViewDataService::getViewData();
?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">직급 관리</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/admin">관리자</a></li>
                    <li class="breadcrumb-item active">직급 관리</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">직급 목록</h5>
                <div class="d-flex justify-content-end mt-3">
                    <a href="/admin/positions/create" class="btn btn-primary">직급 추가</a>
                </div>
            </div>
            <div class="card-body">
                <table id="positions-table" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>직급명</th>
                            <th>레벨</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($positions)): ?>
                            <tr>
                                <td colspan="4" class="text-center">등록된 직급이 없습니다.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($positions as $position): ?>
                            <tr data-position-id="<?= $position['id'] ?>">
                                <td><?= htmlspecialchars($position['id']) ?></td>
                                <td><?= htmlspecialchars($position['name']) ?></td>
                                <td><?= htmlspecialchars($position['level']) ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <div class="edit">
                                            <a href="/admin/positions/edit/<?= $position['id'] ?>" class="btn btn-sm btn-success edit-item-btn">수정</a>
                                        </div>
                                        <div class="remove">
                                            <button class="btn btn-sm btn-danger remove-item-btn">삭제</button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php View::endSection(); ?>

<?php View::startSection('js'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('positions-table');
    table.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-item-btn')) {
            const row = e.target.closest('tr');
            const positionId = row.dataset.positionId;

            if (confirm('정말로 이 직급을 삭제하시겠습니까? 관련 직원들의 직급 정보가 NULL이 됩니다.')) {
                fetch(`/api/positions/${positionId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        row.remove();
                    } else {
                        alert(result.message || 'An error occurred while deleting the position.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the position.');
                });
            }
        }
    });
});
</script>
<?php View::endSection(); ?>
