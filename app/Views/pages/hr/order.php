<?php
// app/Views/pages/hr/order.php
\App\Core\View::getInstance()->startSection('content');
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">인사 발령 등록</h5>
                </div>
                <div class="card-body">
                    <form id="hr-order-form">
                        <div class="mb-3">
                            <label for="employee_id" class="form-label">발령 대상 직원</label>
                            <select class="form-select" id="employee_id" name="employee_id" required>
                                <option value="">직원을 선택하세요</option>
                                <!-- JS로 채워짐 -->
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="department_id" class="form-label">새 부서</label>
                                <select class="form-select" id="department_id" name="department_id">
                                    <option value="">부서를 선택하세요 (변경 시)</option>
                                    <!-- JS로 채워짐 -->
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="position_id" class="form-label">새 직급</label>
                                <select class="form-select" id="position_id" name="position_id">
                                    <option value="">직급을 선택하세요 (변경 시)</option>
                                    <!-- JS로 채워짐 -->
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="order_date" class="form-label">발령일</label>
                            <input type="date" class="form-control" id="order_date" name="order_date" required>
                        </div>

                        <div class="alert alert-info">
                            부서와 직급 중 변경할 항목만 선택하세요. 둘 다 선택하지 않으면 발령이 기록되지 않습니다.
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">인사 발령 등록</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                     <h5 class="card-title mb-0">현재 정보</h5>
                </div>
                <div class="card-body" id="current-employee-info">
                    <p class="text-muted">직원을 선택하면 현재 정보가 표시됩니다.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
