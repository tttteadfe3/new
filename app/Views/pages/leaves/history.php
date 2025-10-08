<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">직원 연차 내역 조회</h4>
            </div>

            <div class="card-body">
                <div class="row mb-3 bg-light p-3 rounded">
                    <div class="col-md-4">
                        <label for="employee-select" class="form-label">직원 선택</label>
                        <select class="form-select" id="employee-select">
                            <option value="">-- 직원을 선택하세요 --</option>
                            <?php foreach ($employees as $employee): ?>
                            <option value="<?= $employee['id'] ?>"><?= htmlspecialchars($employee['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="year-select" class="form-label">연도 선택</label>
                        <select class="form-select" id="year-select">
                           <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                           <option value="<?= $i ?>" <?= $i == date('Y') ? 'selected' : '' ?>><?= $i ?>년</option>
                           <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="history-display" class="d-none">
                    <div class="row">
                        <div class="col-lg-4">
                             <div class="card border">
                                <div class="card-body">
                                    <h5 class="card-title">연차 현황</h5>
                                    <p id="entitlement-summary" class="fs-5">--</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h5 class="mt-4">사용 내역</h5>
                    <div class="table-responsive table-card">
                        <table class="table align-middle table-nowrap table-striped mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>휴가 종류</th>
                                <th>기간</th>
                                <th>일수</th>
                                <th>상태</th>
                                <th>신청일</th>
                                <th>사유</th>
                            </tr>
                            </thead>
                            <tbody id="leave-history-body">
                                <!-- Data loaded via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>