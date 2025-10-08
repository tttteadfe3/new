<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">메뉴 관리</h1>
    <button class="btn btn-primary" id="add-root-menu-btn">
        <i class="bi bi-plus-circle"></i> 최상위 메뉴 추가
    </button>
</div>

<div class="card shadow">
    <div class="card-body">
        <p class="text-muted">메뉴 항목을 드래그하여 순서를 변경하거나 다른 메뉴의 하위로 이동할 수 있습니다.</p>
        <div id="menu-tree-container">
            <!-- 메뉴 트리가 여기에 동적으로 렌더링됩니다. -->
            <div class="text-center p-5">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Menu Edit/Create Modal -->
<div class="modal fade" id="menu-modal" tabindex="-1" aria-labelledby="menuModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="menu-modal-title">메뉴 편집</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="menu-form">
                <div class="modal-body">
                    <input type="hidden" id="menu-id" name="id">
                    <input type="hidden" id="parent-id" name="parent_id">

                    <div class="mb-3">
                        <label for="menu-name" class="form-label">메뉴 이름</label>
                        <input type="text" class="form-control" id="menu-name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="menu-url" class="form-label">URL</label>
                        <input type="text" class="form-control" id="menu-url" name="url" placeholder="/Controllers/example.php">
                    </div>
                    <div class="mb-3">
                        <label for="menu-icon" class="form-label">아이콘</label>
                        <input type="text" class="form-control" id="menu-icon" name="icon" placeholder="ri-home-line">
                        <small class="form-text text-muted">Remix Icon 클래스명을 입력하세요. (예: ri-home-line)</small>
                    </div>
                    <div class="mb-3">
                        <label for="menu-permission" class="form-label">필요 권한</label>
                        <input type="text" class="form-control" id="menu-permission" name="permission_key" placeholder="예: user_view">
                        <small class="form-text text-muted">비워두면 모든 사용자가 볼 수 있습니다.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger me-auto" id="delete-menu-btn">삭제</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
#menu-tree-container ul {
    list-style-type: none;
    padding-left: 20px;
}
.menu-item {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    margin-top: 5px;
    background-color: #f9f9f9;
    cursor: grab;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.menu-item:active {
    cursor: grabbing;
    background-color: #eef;
}
.menu-item .menu-actions button {
    visibility: hidden;
}
.menu-item:hover .menu-actions button {
    visibility: visible;
}
.drag-over {
    border-top: 2px dashed blue;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="<?= BASE_ASSETS_URL ?>/assets/js/pages/menu_admin.js"></script>
