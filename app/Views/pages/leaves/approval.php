<?php \App\Core\View::startSection('content'); ?>
<div class="row">
    <div class="col-lg-12">
        <div class="card" id="leaveApprovalList">
            <div class="card-header border-bottom-dashed">
                <div class="row g-4 align-items-center">
                    <div class="col-sm">
                        <div>
                            <h5 class="card-title mb-0">연차 신청 승인/반려</h5>
                        </div>
                    </div>
                    <div class="col-sm-auto">
                        <ul class="nav nav-pills card-header-pills" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#pending-tab" role="tab">대기중</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#approved-tab" role="tab">승인</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#rejected-tab" role="tab">반려</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#cancellation-tab" role="tab">취소 요청</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane active" id="pending-tab" role="tabpanel">
                        <div class="table-responsive table-card">
                            <table class="table align-middle table-nowrap table-striped mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>신청자</th>
                                    <th>부서</th>
                                    <th>신청일</th>
                                    <th>휴가 기간</th>
                                    <th>일수</th>
                                    <th>사유</th>
                                    <th>관리</th>
                                </tr>
                                </thead>
                                <tbody id="pending-requests-body"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane" id="approved-tab" role="tabpanel">
                        <div class="table-responsive table-card">
                           <table class="table align-middle table-nowrap table-striped mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>신청자</th>
                                    <th>부서</th>
                                    <th>휴가 기간</th>
                                    <th>일수</th>
                                    <th>승인일</th>
                                    <th>처리자</th>
                                </tr>
                                </thead>
                                <tbody id="approved-requests-body"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane" id="rejected-tab" role="tabpanel">
                         <div class="table-responsive table-card">
                           <table class="table align-middle table-nowrap table-striped mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>신청자</th>
                                    <th>부서</th>
                                    <th>휴가 기간</th>
                                    <th>일수</th>
                                    <th>반려일</th>
                                    <th>반려 사유</th>
                                    <th>처리자</th>
                                </tr>
                                </thead>
                                <tbody id="rejected-requests-body"></tbody>
                            </table>
                        </div>
                    </div>
                     <div class="tab-pane" id="cancellation-tab" role="tabpanel">
                        <div class="table-responsive table-card">
                           <table class="table align-middle table-nowrap table-striped mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>신청자</th>
                                    <th>부서</th>
                                    <th>휴가 기간</th>
                                    <th>일수</th>
                                    <th>취소 사유</th>
                                    <th>관리</th>
                                </tr>
                                </thead>
                                <tbody id="cancellation-requests-body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::endSection(); ?>