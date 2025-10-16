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
                                <th scope="col">삭제일</th>
                                <th scope="col">작업</th>
                            </tr>
                        </thead>
                        <tbody id="deleted-items-list">
                            <!-- Deleted items will be dynamically inserted here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
