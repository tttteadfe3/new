<?php \App\Core\View::getInstance()->startSection('content'); ?>
                    <div class="split-wrapper d-lg-flex gap-1 mx-n4 mt-n4 p-1">
                        <div class="split-layout-leftsidebar minimal-border">
                            <div class="px-4 pt-4 mb-3">
                                <div class="d-flex align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="mb-4">무단투기 관리</h5>
                                    </div>
                                </div>
                            </div> <!-- .p-4 -->

                            <div class="chat-room-list pt-3" data-simplebar>
                                <div class="chat-message-list">
                                    <div class="list-group list-group-flush" id="pending-list">
                                        <!-- Pending items will be dynamically inserted here -->
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
                                    <div id="carouselPhoto" class="carousel carousel-dark slide" data-bs-ride="carousel">
                                        <div class="carousel-inner" id="photoSwiperWrapper">
                                            <!-- Photos will be dynamically inserted here -->
                                        </div>
                                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselPhoto" data-bs-slide="prev">
                                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Previous</span>
                                        </button>
                                        <button class="carousel-control-next" type="button" data-bs-target="#carouselPhoto" data-bs-slide="next">
                                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Next</span>
                                        </button>
                                    </div>
                                    <p id="registrant-info" class="mt-3 mb-1"></p>
                                    <form id="confirm-form">
                                        <input type="hidden" id="case-id">
                                        <div class="mb-3">
                                            <label for="address" class="form-label">주소</label>
                                            <input type="text" class="form-control form-control-sm" id="address">
                                        </div>
                                        <div class="row d-none">
                                            <div class="col-md-6 mb-3">
                                                <label for="latitude" class="form-label">위도</label>
                                                <input type="text" class="form-control form-control-sm" id="latitude" readonly>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="longitude" class="form-label">경도</label>
                                                <input type="text" class="form-control form-control-sm" id="longitude" readonly>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="waste_type" class="form-label">주성상</label>
                                                <select class="form-select form-select-sm" id="waste_type">
                                                    <option value="생활폐기물">생활폐기물</option>
                                                    <option value="음식물">음식물</option>
                                                    <option value="재활용">재활용</option>
                                                    <option value="대형">대형</option>
                                                    <option value="소각">소각</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="waste_type2" class="form-label">부성상 (혼합 시)</label>
                                                <select class="form-select form-select-sm" id="waste_type2">
                                                    <option value="">없음</option>
                                                    <option value="생활폐기물">생활폐기물</option>
                                                    <option value="음식물">음식물</option>
                                                    <option value="재활용">재활용</option>
                                                    <option value="대형">대형</option>
                                                    <option value="소각">소각</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end gap-2">
                                            <button type="button" class="btn btn-danger btn-sm" id="delete-btn">
                                                <i class="ri-delete-bin-line me-1"></i>삭제
                                            </button>
                                            <button type="button" class="btn btn-primary btn-sm" id="confirm-btn">
                                                <i class="ri-check-double-line me-1"></i>확인 및 저장
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>


                                            </div>
                                            
                                        </div>
                                        <!-- end chat-conversation -->
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
