<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-lg-12">
        <div class="card" id="leaveApprovalList">
            <div class="card-header border-bottom-dashed">
                <div class="row g-4 align-items-center">
                    <div class="col-sm"><h5 class="card-title mb-0">연차 신청 관리</h5></div>
                    <div class="col-sm-auto ms-auto">
                        <div class="d-flex gap-2">
                            <select class="form-select" id="year-filter" style="width: 100px;">
                                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                    <option value="<?php echo $y; ?>"><?php echo $y; ?>년</option>
                                <?php endfor; ?>
                            </select>
                            <select class="form-select" id="department-filter" style="width: 150px;">
                                <option value="">전체 부서</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <ul class="nav nav-pills nav-justified mb-3" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#pending-tab-pane" role="tab">대기중</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#cancellation_requested-tab-pane" role="tab">취소 요청</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#approved-tab-pane" role="tab">승인</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#rejected-tab-pane" role="tab">반려/취소</a></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="pending-tab-pane" role="tabpanel">
                        <table class="table align-middle table-nowrap table-striped mb-0">
                            <thead class="table-light">
                                <tr><th>신청자</th><th>부서</th><th>기간</th><th>일수</th><th>사유</th><th>신청일</th><th>관리</th></tr>
                            </thead>
                            <tbody id="pending-requests-body"></tbody>
                        </table>
                    </div>
                    <div class="tab-pane" id="cancellation_requested-tab-pane" role="tabpanel">
                        <table class="table align-middle table-nowrap table-striped mb-0">
                             <thead class="table-light">
                                <tr><th>신청자</th><th>부서</th><th>기간</th><th>일수</th><th>취소사유</th><th>신청일</th><th>관리</th></tr>
                            </thead>
                            <tbody id="cancellation_requested-requests-body"></tbody>
                        </table>
                    </div>
                    <div class="tab-pane" id="approved-tab-pane" role="tabpanel">
                       <table class="table align-middle table-nowrap table-striped mb-0">
                            <thead class="table-light">
                                <tr><th>신청자</th><th>부서</th><th>기간</th><th>일수</th><th>사유</th><th>처리일</th><th>처리자</th></tr>
                            </thead>
                            <tbody id="approved-requests-body"></tbody>
                        </table>
                    </div>
                    <div class="tab-pane" id="rejected-tab-pane" role="tabpanel">
                       <table class="table align-middle table-nowrap table-striped mb-0">
                            <thead class="table-light">
                                <tr><th>신청자</th><th>부서</th><th>기간</th><th>일수</th><th>사유</th><th>처리일</th><th>처리자</th></tr>
                            </thead>
                            <tbody id="rejected-requests-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
