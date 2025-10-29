<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="split-wrapper d-lg-flex gap-1 mx-n4 mt-n4 p-1">
    <div class="split-layout-leftsidebar minimal-border">
        <div class="px-4 pt-4 mb-3">
            <div class="d-flex align-items-start">
                <div class="flex-grow-1">
                    <h5 class="mb-4">삭제된 무단투기</h5>
                </div>
            </div>
        </div> <!-- .p-4 -->

        <div class="chat-room-list pt-3" data-simplebar>
            <div class="d-flex align-items-center px-4 mb-2">
                <div class="flex-grow-1">
                    <h6 class="mb-0 text-muted">삭제된 목록</h6>
                </div>
            </div>
            <div class="chat-message-list">
                <div class="list-group list-group-flush" id="deleted-list">
                    <!-- Deleted items will be dynamically inserted here via AJAX -->
                </div>
            </div>
        </div>
    </div>
    <!-- end chat leftsidebar -->
    <!-- Start User chat -->
    <div class="split-layout-right-content user-chat w-100 overflow-hidden minimal-border">
        <div class="chat-content d-lg-flex">
            <div class="w-100 overflow-hidden position-relative">
                <div class="position-relative">
                    <div class="position-relative" id="users-chat" style="display: block;">
                        <div class="chat-conversation" id="chat-conversation" data-simplebar="init">
                            <div id="map" class="w-100 h-100"></div>
                            <div id="detail-view" class="card d-none" style="position: absolute; top: 10px; left: 10px; right: 10px; z-index: 1; max-width: 500px;">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 d-block d-lg-none me-3">
                                        <a href="javascript: void(0);" class="user-chat-remove fs-18 p-1"><i class="ri-arrow-left-s-line align-bottom"></i></a>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1 overflow-hidden">
                                                <h5 class="text-truncate mb-0 fs-16">상세보기</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="photo-container" class="d-flex justify-content-center gap-2 mb-3">
                                        <!-- Photos will be dynamically inserted here -->
                                    </div>
                                    <p id="registrant-info" class="mt-3 mb-1"></p>
                                    <form id="action-form">
                                        <input type="hidden" id="case-id">
                                        <div class="mb-3">
                                            <label class="form-label">주소</label>
                                            <p id="address" class="form-control-plaintext"></p>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">주성상</label>
                                                <p id="waste_type" class="form-control-plaintext"></p>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">부성상</label>
                                                <p id="waste_type2" class="form-control-plaintext"></p>
                                            </div>
                                        </div>
                                         <div class="d-flex justify-content-end gap-2">
                                            <button type="button" class="btn btn-success btn-sm" id="restore-btn">
                                                <i class="ri-arrow-go-back-line me-1"></i>복원
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm" id="permanent-delete-btn">
                                                <i class="ri-delete-bin-2-line me-1"></i>영구 삭제
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Photo Modal -->
<div class="modal fade" id="photoViewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="photoViewModalLabel">사진 보기</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" id="photoViewModalImage" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
