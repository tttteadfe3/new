<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">삭제된 부적정배출 목록</h4>
                <p class="text-muted mb-0">여기서는 논리적으로 삭제된 항목들을 확인하고 영구적으로 삭제하거나 복원할 수 있습니다.</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">주소</th>
                                <th scope="col">주성상</th>
                                <th scope="col">등록자</th>
                                <th scope="col">삭제자</th>
                                <th scope="col">삭제일</th>
                                <th scope="col">작업</th>
                            </tr>
                        </thead>
                        <tbody id="deleted-items-list">
                            <?php if (empty($deletedItems)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">삭제된 항목이 없습니다.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($deletedItems as $item): ?>
                                    <tr data-id="<?= htmlspecialchars($item['id']) ?>">
                                        <td><?= htmlspecialchars($item['id']) ?></td>
                                        <td><?= htmlspecialchars($item['road_address'] ?: $item['jibun_address']) ?></td>
                                        <td><?= htmlspecialchars($item['waste_type']) ?></td>
                                        <td><?= htmlspecialchars($item['created_by_name']) ?></td>
                                        <td><?= htmlspecialchars($item['deleted_by_name']) ?></td>
                                        <td><?= htmlspecialchars(substr($item['deleted_at'], 0, 10)) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info view-btn">보기</button>
                                            <button class="btn btn-sm btn-success restore-btn">복원</button>
                                            <button class="btn btn-sm btn-danger permanent-delete-btn">영구삭제</button>
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
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
