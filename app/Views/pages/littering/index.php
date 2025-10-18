<?php \App\Core\View::getInstance()->startSection('content'); ?>

<div class="row mt-n4 mx-n4" style="margin:0;">
    <div class="col-12" style=" height: calc(100vh - 70px - 60px);padding:0;">
        <div id="map" style="width:100%;height:100%;position:relative;overflow:hidden;">
            <!-- 지도 중심점 -->
            <div id="map-crosshair"></div>
            <!-- 지도 컨트롤 버튼들 -->
            <div style="position: absolute; bottom: 25px; right: 25px; z-index: 1000; display: flex; flex-direction: column; gap: 10px;">
                <!-- 지도 중심 마커 추가 버튼 -->
                <button id="addCenterMarkerBtn" class="btn btn-info" style="
                    border-radius: 50%;
                    width: 50px;
                    height: 50px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                " title="지도 중심에 등록">
                    <i class="ri-add-line" style="font-size: 24px;"></i>
                </button>
                <!-- 현재 위치 버튼 -->
                <button id="currentLocationBtn" class="btn btn-primary" style="
                    border-radius: 50%;
                    width: 50px;
                    height: 50px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                " title="현재 위치로 이동">
                    <i class="ri-navigation-line" style="font-size: 20px;"></i>
                </button>
            </div>
        </div>
    </div>
</div>
<div class="offcanvas offcanvas-end" tabindex="-1" id="registerModal">
    <div class="offcanvas-header bg-light p-3">
        <h5 class="offcanvas-title">부적정배출 등록</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-4">
        <form onsubmit="return false;">
            <input type="hidden" id="lat">
            <input type="hidden" id="lng">
            <div class="mb-3">
                <label class="form-label text-uppercase fw-semibold mb-2">주소</label>
                <div class="form-control-plaintext border rounded p-2 bg-light" id="address">-</div>
            </div>
            <div class="row">
                <div class="col-6 mb-3" style="display:none;">
                    <label for="issueDate" class="form-label">배출일자</label>
                    <input type="date" class="form-control" id="issueDate" readonly>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label text-uppercase fw-semibold mb-2">주성상</label>
                <select class="form-select" id="waste_type">
                    <option value="">선택하세요</option>
                    <option value="생활폐기물">생활폐기물</option>
                    <option value="음식물">음식물</option>
                    <option value="재활용">재활용</option>
                    <option value="대형">대형</option>
                    <option value="소각">소각</option>
                </select>
            </div>
            <div class="row mb-3">
                <label for="mixed" class="col-3 col-form-label fw-semibold">혼합 배출</label>
                <div class="col-9 d-flex align-items-center  form-check form-switch form-switch-lg">
                    <input class="form-check-input ms-auto" type="checkbox" role="switch" id="mixed">
                </div>
            </div>
            <div class="row mb-3" id="waste_type2Container" style="display:none;">
                <label for="waste_type2" class="col-3 col-form-label fw-semibold">부성상</label>
                <div class="col-9">
                    <select class="form-select" id="waste_type2" disabled>
                        <option value="">선택하세요</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label for="regPhoto1" class="form-label">작업전</label>
                <input type="file" class="form-control mb-2" id="regPhoto1" accept="image/*">
                <div id="regPhoto1Status" class="form-text">
                    사진을 촬영하거나 갤러리에서 선택하세요
                </div>
            </div>
            <div class="mb-3">
                <label for="regPhoto2" class="form-label">작업후 <span class="text-danger">*</span></label>
                <input type="file" class="form-control mb-2" id="regPhoto2" accept="image/*">
                <div id="regPhoto2Status" class="form-text">
                    사진을 촬영하거나 갤러리에서 선택하세요
                </div>
            </div>
        </form>
    </div>
    <div class="offcanvas-footer bg-light p-3 d-flex justify-content-end">
        <button type="button" class="btn btn-light me-2" data-bs-dismiss="offcanvas">취소</button>
        <button type="button" class="btn btn-success" id="registerBtn">
            <i class="ri-save-line me-1"></i>등록
        </button>
    </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="processModal">
    <div class="offcanvas-header bg-light p-3">
        <h5 class="offcanvas-title"><i class="ri-settings-4-line me-2"></i>처리 등록</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="alert alert-warning rounded-0 mb-0" id="procStatusMessage" style="display: none;"></div>
    <div class="offcanvas-body p-4" id="processFormFields">
        <form onsubmit="return false;">
            <input type="hidden" id="procIndex">
            <div class="mb-3">
                <label for="procAddress" class="form-label">주소</label>
                <div class="form-control-plaintext border rounded p-2 bg-light" id="procAddress">-</div>
            </div>
            <div class="mb-3">
                <label for="procWasteType" class="form-label">성상</label>
                <div class="form-control-plaintext border rounded p-2 bg-light" id="procWasteType">-</div>
            </div>
            <div class="mb-3">
                <label class="form-label">사진</label>
                <div id="photo-container" class="d-flex justify-content-center gap-2 mb-3">
                    <!-- Photos will be dynamically inserted here -->
                </div>
            </div>
            <div class="mb-3" style="display:none;">
                <label for="collectDate" class="form-label">수거일자</label>
                <input type="date" class="form-control" id="collectDate" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">개선 여부</label>
                <div class="border rounded p-2 bg-light">
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="corrected" id="corrected_o" value="o">
                            <label class="form-check-label" for="corrected_o"><i class="ri-check-line text-success"></i> 개선</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="corrected" id="corrected_x" value="x">
                            <label class="form-check-label" for="corrected_x"><i class="ri-close-line text-danger"></i> 미개선</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="corrected" id="corrected_eq" value="=">
                            <label class="form-check-label" for="corrected_eq"><i class="ri-delete-bin-line text-warning"></i> 없어짐</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label for="note" class="form-label">비고</label>
                <textarea class="form-control" id="note" rows="2" placeholder="특이사항을 입력하세요"></textarea>
            </div>
            <div class="row">
                <label for="procPhoto" class="form-label">처리 사진</label> <span class="text-danger" id="procPhotoRequired" style="display:none;">*</span></label>
                <div>
                    <input type="file" class="form-control" id="procPhoto" accept="image/*">
                    <div class="form-text" id="procPhotoStatus">처리 완료 사진을 촬영해주세요</div>
                </div>
            </div>
       </form>
    </div>
    <div class="offcanvas-footer bg-light p-3 d-flex justify-content-end">
        <button type="button" class="btn btn-light me-2" data-bs-dismiss="offcanvas">취소</button>
        <button type="button" class="btn btn-primary" id="processBtn">
            <i class="ri-check-line me-1"></i>처리 등록
        </button>
    </div>
</div>

<div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-5">
                <div id="resultIcon"></div>
                <div class="mt-4">
                    <h4 class="mb-3" id="resultTitle"></h4>
                    <h5 class="mb-3" id="resultMessage"></h5>
                    <div class="hstack gap-2 justify-content-center">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">닫기</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
