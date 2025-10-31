<?php \App\Core\View::getInstance()->startSection('content'); ?>
<h2>대형폐기물 관리</h2>

<div class="row">
    <!-- 현장 등록 컬럼 -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3>현장 등록 리스트</h3>
            </div>
            <div class="card-body">
                <form id="fieldListForm">
                    <input type="hidden" name="type" value="field">
                    <div class="row">
                        <div class="col-md-8 mb-2">
                            <input name="searchAddress" placeholder="주소" class="form-control" type="text">
                        </div>
                        <div class="col-md-4 mb-2">
                            <select name="searchStatus" class="form-select">
                                <option value="">처리여부 (전체)</option>
                                <option value="미처리">미처리</option>
                                <option value="처리완료">처리완료</option>
                            </select>
                        </div>
                    </div>
                    <div class="text-center mt-2">
                        <button type="button" class="btn btn-primary search-btn" data-type="field">검색</button>
                        <button type="button" class="btn btn-secondary reset-btn" data-type="field">초기화</button>
                    </div>
                </form>

                <p class="mt-3">총 <span id="field-total-count" class="fw-bold">0</span>건</p>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover text-center align-middle">
                        <thead>
                            <tr>
                                <th>등록일자</th>
                                <th>등록자</th>
                                <th>주소</th>
                                <th>처리일자</th>
                                <th>처리자</th>
                                <th>품목</th>
                            </tr>
                        </thead>
                        <tbody id="field-data-table-body">
                            <tr><td colspan="6">데이터가 없습니다.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div id="field-pagination-container" class="d-flex justify-content-center mt-3"></div>
            </div>
        </div>
    </div>

    <!-- 온라인 등록 컬럼 -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3>온라인 등록</h3>
            </div>
            <div class="card-body">
                <form id="onlineListForm">
                    <input type="hidden" name="type" value="online">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <input name="searchDischargeNumber" placeholder="접수번호" class="form-control" type="text">
                        </div>
                        <div class="col-md-6 mb-2">
                            <input name="searchName" placeholder="이름" class="form-control" type="text">
                        </div>
                        <div class="col-md-6">
                            <input name="searchPhone" placeholder="전화번호" class="form-control" type="text">
                        </div>
                        <div class="col-md-6">
                            <select name="searchStatus" class="form-select">
                                <option value="">처리여부 (전체)</option>
                                <option value="미처리">미처리</option>
                                <option value="처리완료">처리완료</option>
                            </select>
                        </div>
                    </div>
                    <div class="text-center mt-2">
                        <button type="button" class="btn btn-primary search-btn" data-type="online">검색</button>
                        <button type="button" class="btn btn-secondary reset-btn" data-type="online">초기화</button>
                        <label for="htmlUpload" class="btn btn-success">파일에서 일괄 등록</label>
                        <input type="file" id="htmlUpload" accept=".html,.htm" style="display: none;">
                        <button type="button" id="clearInternetBtn" class="btn btn-danger">인터넷 배출 비우기</button>
                    </div>
                </form>

                <p class="mt-3">총 <span id="online-total-count" class="fw-bold">0</span>건</p>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover text-center align-middle">
                        <thead>
                            <tr>
                                <th>접수번호</th>
                                <th>신청자명</th>
                                <th>전화번호</th>
                                <th>지역</th>
                                <th>품목수</th>
                                <th>금액</th>
                                <th>배출일시</th>
                                <th>상태</th>
                                <th style="min-width: 300px;">품목 관리</th>
                            </tr>
                        </thead>
                        <tbody id="online-data-table-body">
                            <tr><td colspan="9">데이터가 없습니다.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div id="online-pagination-container" class="d-flex justify-content-center mt-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- File Parse Result Modal -->
<div class="modal fade" id="fileParseResultModal" tabindex="-1" aria-labelledby="fileParseResultModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fileParseResultModalLabel">파싱 결과 - 아래 내용으로 일괄 등록하시겠습니까?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>접수번호</th>
                                <th>신청자명</th>
                                <th>전화번호</th>
                                <th>지역</th>
                                <th>금액</th>
                                <th>배출일시</th>
                            </tr>
                        </thead>
                        <tbody id="parsed-data-tbody">
                            <!-- Parsed rows will be injected here -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="batchRegisterBtn">일괄 등록 실행</button>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
