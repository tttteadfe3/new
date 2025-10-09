<?php \App\Core\View::startSection('content'); ?>
<div class="row">
    <div class="col-lg-12">
        <div class="card" id="logViewerList">
            <div class="card-header border-bottom-dashed">
                <div class="row g-4 align-items-center">
                    <div class="col-sm">
                        <div>
                            <h5 class="card-title mb-0">사용 로그 뷰어</h5>
                        </div>
                    </div>
                    <div class="col-sm-auto">
                        <div class="d-flex flex-wrap align-items-start gap-2">
                            <button id="clear-logs-btn" class="btn btn-danger"><i class="ri-delete-bin-2-line"></i> 로그 비우기</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body border-bottom-dashed border-bottom">
                <form id="log-filter-form">
                    <div class="row g-3">
                        <div class="col-xl-3">
                            <label for="start_date" class="form-label">시작일</label>
                            <input type="date" class="form-control" id="start_date" name="start_date">
                        </div>
                        <div class="col-xl-3">
                            <label for="end_date" class="form-label">종료일</label>
                            <input type="date" class="form-control" id="end_date" name="end_date">
                        </div>
                        <div class="col-xl-2">
                            <label for="user_name" class="form-label">사용자명</label>
                            <input type="text" class="form-control" id="user_name" name="user_name" placeholder="일부 입력 가능">
                        </div>
                        <div class="col-xl-2">
                            <label for="action" class="form-label">활동 내용</label>
                            <input type="text" class="form-control" id="action" name="action" placeholder="일부 입력 가능">
                        </div>
                        <div class="col-xl-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100"> 검색</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <div>
                    <div class="table-responsive table-card mb-1">
                        <table class="table align-middle" id="logTable">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>시간</th>
                                    <th>사용자 ID</th>
                                    <th>사용자명</th>
                                    <th>활동</th>
                                    <th>상세</th>
                                    <th>IP 주소</th>
                                </tr>
                            </thead>
                            <tbody id="log-table-body" class="list form-check-all">
                            </tbody>
                        </table>
                        <div class="noresult" style="display: none">
                            <div class="text-center">
                                <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                <h5 class="mt-2">Sorry! No Result Found</h5>
                                <p class="text-muted mb-0">We've searched more than 150+ logs We did not find any logs for you search.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::endSection(); ?>