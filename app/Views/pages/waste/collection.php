<?php \App\Core\View::getInstance()->startSection('content'); ?>
<style type="text/css">
	
#map-crosshair {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 30px;
    height: 30px;
    transform: translate(-50%, -50%);
    background-image: 
        linear-gradient(to right, #333 0%, #333 30%, transparent 30%, transparent 70%, #333 70%, #333 100%),
        linear-gradient(to bottom, #333 0%, #333 30%, transparent 30%, transparent 70%, #333 70%, #333 100%);
    background-size: 100% 2px, 2px 100%;
    background-repeat: no-repeat;
    background-position: center;
    z-index: 1001; /* 컨트롤 버튼보다 위에 표시 */
    pointer-events: none; /* 클릭 이벤트 방지 */
}
</style>

<div class="row mt-n4 mx-n4" style="margin:0;">
    <div class="col-12" style="height: calc(100vh - 70px - 60px);padding:0;">
        <div id="map" style="width:100%;height:100%;position:relative;overflow:hidden;">
            <!-- 지도 중심점 -->
            <div id="map-crosshair"></div>
            <div style="position: absolute; bottom: 25px; right: 25px; z-index: 1000; display: flex; flex-direction: column; gap: 10px;">
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

<div class="offcanvas offcanvas-end" tabindex="-1" id="registerCollectionModal">
    <div class="offcanvas-header bg-light p-3">
        <h5 class="offcanvas-title"><i class="ri-add-circle-line me-2"></i>대형폐기물 수거 등록</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-4">
        <form id="wasteCollectionForm" onsubmit="return false;">
            <input type="hidden" id="lat">
            <input type="hidden" id="lng">
            <div class="mb-3">
                <label for="address" class="form-label text-uppercase fw-semibold mb-2">주소</label>
                <div class="form-control-plaintext border rounded p-2 bg-light" id="address">-</div>
            </div>
            <input type="hidden" class="form-control" id="issue_date">
            <div class="mb-3">
                 <label class="cform-label text-uppercase fw-semibold mb-2">품목</label>
                 <div >
                    <div id="item-list" class="row">
                        <!-- Items will be dynamically inserted here -->
                    </div>
                 </div>
            </div>
            <div class="">
                <label for="address" class="form-label text-uppercase fw-semibold mb-2">사진</label>
                <div>
                    <input type="file" class="form-control mb-2" id="photo" accept="image/*">
                    <div class="form-text mb-2" id="photoStatus">사진을 촬영하거나 갤러리에서 선택하세요</div>
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
