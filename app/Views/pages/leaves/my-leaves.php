<?php include_once __DIR__ . '/../layout/header.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4">나의 연차 관리</h1>

    <!-- 잔여 연차 현황 -->
    <div class="row">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    나의 잔여 연차 현황
                </div>
                <div class="card-body">
                    <p><strong>연차 잔여일:</strong> <span id="annual-leave-balance">...</span>일</p>
                    <p><strong>월차 잔여일:</strong> <span id="monthly-leave-balance">...</span>일</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 연차 신청 폼 -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-edit me-1"></i>
            연차 신청
        </div>
        <div class="card-body">
            <form id="leave-request-form">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="leave-type" class="form-label">휴가 종류</label>
                        <select id="leave-type" class="form-select">
                            <option value="annual">연차</option>
                            <option value="monthly">월차</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="request-unit" class="form-label">신청 단위</label>
                        <select id="request-unit" class="form-select">
                            <option value="full">전일</option>
                            <option value="half_am">오전 반차</option>
                            <option value="half_pm">오후 반차</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="start-date" class="form-label">시작일</label>
                        <input type="date" id="start-date" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label for="end-date" class="form-label">종료일</label>
                        <input type="date" id="end-date" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="reason" class="form-label">사유</label>
                    <textarea id="reason" class="form-control" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">신청하기</button>
            </form>
        </div>
    </div>

    <!-- 나의 연차 신청 내역 -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            나의 연차 신청 내역
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>휴가 종류</th>
                        <th>기간</th>
                        <th>일수</th>
                        <th>상태</th>
                        <th>신청일</th>
                        <th>작업</th>
                    </tr>
                </thead>
                <tbody id="my-requests-tbody">
                    <!-- JavaScript will populate this -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="/assets/js/pages/my-leaves.js"></script>

<?php include_once __DIR__ . '/../layout/footer.php'; ?>
