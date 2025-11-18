<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= e($pageTitle ?? '연간 지급품 계획') ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/">홈</a></li>
                    <li class="breadcrumb-item"><a href="/supply">지급품 관리</a></li>
                    <li class="breadcrumb-item active">연간 계획</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <!-- Year Selection and Summary -->
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <h5 class="card-title mb-0 me-3">연도 선택</h5>
                            <select class="form-select" id="year-selector" style="width: auto;">
                                <!-- Options will be populated by JS -->
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-success" id="add-plan-btn">
                                <i class="ri-add-line align-bottom me-1"></i> 신규 계획
                            </button>
                            <a href="/supply/plans/import?year=<?= e($currentYear) ?>" class="btn btn-info">
                                <i class="ri-upload-2-line align-bottom me-1"></i> 엑셀 업로드
                            </a>
                            <button type="button" class="btn btn-primary" id="export-excel-btn">
                                <i class="ri-download-2-line align-bottom me-1"></i> 엑셀 다운로드
                            </button>
                            <a href="/supply/plans/budget-summary?year=<?= e($currentYear) ?>" class="btn btn-outline-primary">
                                <i class="ri-bar-chart-line align-bottom me-1"></i> 예산 요약
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Budget Summary Cards -->
                <div class="row" id="budget-summary-container">
                    <!-- Summary cards will be populated by JS -->
                    <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">총 계획 품목</p></div><div class="flex-shrink-0"><span class="avatar-title bg-success-subtle rounded fs-3"><i class="bx bx-package text-success"></i></span></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0"><span class="counter-value">...</span>개</h4></div></div></div></div></div>
                    <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">총 계획 수량</p></div><div class="flex-shrink-0"><span class="avatar-title bg-info-subtle rounded fs-3"><i class="bx bx-cube text-info"></i></span></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0"><span class="counter-value">...</span>개</h4></div></div></div></div></div>
                    <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">총 예산</p></div><div class="flex-shrink-0"><span class="avatar-title bg-warning-subtle rounded fs-3"><i class="bx bx-won text-warning"></i></span></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0">₩<span class="counter-value">...</span></h4></div></div></div></div></div>
                    <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">평균 단가</p></div><div class="flex-shrink-0"><span class="avatar-title bg-primary-subtle rounded fs-3"><i class="bx bx-calculator text-primary"></i></span></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0">₩<span class="counter-value">...</span></h4></div></div></div></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <!-- Plans Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0" id="plans-table-title"><?= e($currentYear) ?>년 지급품 계획 목록</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-nowrap table-striped-columns mb-0" id="plans-table" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">품목코드</th>
                                <th scope="col">품목명</th>
                                <th scope="col">분류</th>
                                <th scope="col">단위</th>
                                <th scope="col">계획수량</th>
                                <th scope="col">단가</th>
                                <th scope="col">총예산</th>
                                <th scope="col">등록일</th>
                                <th scope="col">작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be populated by DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deletePlanModal" tabindex="-1" aria-labelledby="deletePlanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deletePlanModalLabel">계획 삭제 확인</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>정말로 이 계획을 삭제하시겠습니까?</p>
                <p class="text-danger small">
                    <i class="ri-alert-line me-1"></i>
                    이미 구매나 지급 기록이 있는 계획은 삭제할 수 없습니다.
                </p>
                <div id="delete-plan-info"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-plan-btn">삭제</button>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Plan Modal -->
<div class="modal fade" id="planModal" tabindex="-1" aria-labelledby="planModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="planModalLabel">계획 등록/수정</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="planForm" class="needs-validation" novalidate>
                    <input type="hidden" id="plan-id" name="id">
                    <input type="hidden" id="plan-year" name="year" value="<?= e($currentYear) ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="modal-item-id" class="form-label">품목 선택 <span class="text-danger">*</span></label>
                            <select class="form-select" id="modal-item-id" name="item_id" required>
                                <option value="">품목을 선택하세요</option>
                            </select>
                            <div class="invalid-feedback">품목을 선택해주세요.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="modal-item-unit" class="form-label">단위</label>
                            <input type="text" class="form-control" id="modal-item-unit" readonly>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-4">
                            <label for="modal-planned-quantity" class="form-label">계획 수량 <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="modal-planned-quantity" name="planned_quantity" required min="1">
                            <div class="invalid-feedback">1 이상의 숫자를 입력해주세요.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="modal-unit-price" class="form-label">단가 <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="modal-unit-price" name="unit_price" required min="0">
                            <div class="invalid-feedback">0 이상의 숫자를 입력해주세요.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="modal-total-budget" class="form-label">총 예산</label>
                            <input type="text" class="form-control" id="modal-total-budget" readonly>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label for="modal-notes" class="form-label">비고</label>
                        <textarea class="form-control" id="modal-notes" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="save-plan-btn">저장</button>
            </div>
        </div>
    </div>
</div>

<?php \App\Core\View::getInstance()->startSection('scripts'); ?>
<script>
$(document).ready(function() {
    let currentYear = <?= e($currentYear) ?>;
    let plansTable;

    // Initialize year selector
    const yearSelector = $('#year-selector');
    const currentServerYear = new Date().getFullYear();
    for (let y = currentServerYear + 1; y >= currentServerYear - 5; y--) {
        yearSelector.append(new Option(y + '년', y, y === currentYear));
    }

    // Load data on year change
    yearSelector.on('change', function() {
        currentYear = parseInt($(this).val());
        window.history.pushState({}, '', `?year=${currentYear}`);
        loadAllData(currentYear);
    });

    function loadAllData(year) {
        $('#plans-table-title').text(`${year}년 지급품 계획 목록`);
        $('#plan-year').val(year);
        loadBudgetSummary(year);
        if (plansTable) {
            plansTable.ajax.url(`/api/supply/plans?year=${year}`).load();
        } else {
            initializeDataTable(year);
        }
    }

    function loadBudgetSummary(year) {
        $.ajax({
            url: `/api/supply/plans/budget-summary`,
            method: 'GET',
            data: { year: year },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    updateBudgetSummary(response.data);
                }
            }
        });
    }

    function updateBudgetSummary(data) {
        $('#budget-summary-container').html(`
            <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">총 계획 품목</p></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0"><span class="counter-value">${data.total_items}</span>개</h4></div></div></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">총 계획 수량</p></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0"><span class="counter-value">${data.total_quantity}</span>개</h4></div></div></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">총 예산</p></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0">₩<span class="counter-value">${data.total_budget.toLocaleString()}</span></h4></div></div></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">평균 단가</p></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0">₩<span class="counter-value">${parseInt(data.avg_unit_price).toLocaleString()}</span></h4></div></div></div></div></div>
        `);
    }

    function initializeDataTable(year) {
        plansTable = $('#plans-table').DataTable({
            ajax: {
                url: `/api/supply/plans?year=${year}`,
                dataSrc: 'data.plans'
            },
            columns: [
                { data: 'item_code' },
                { data: 'item_name' },
                { data: 'category_name' },
                { data: 'unit' },
                { data: 'planned_quantity' },
                { data: 'unit_price', render: $.fn.dataTable.render.number(',', '.', 0, '₩') },
                { data: 'total_budget', render: $.fn.dataTable.render.number(',', '.', 0, '₩') },
                { data: 'created_at' },
                {
                    data: 'id',
                    render: function(data, type, row) {
                        return `<button class="btn btn-sm btn-primary edit-plan-btn" data-id="${data}">수정</button>
                                <button class="btn btn-sm btn-danger delete-plan-btn" data-id="${data}">삭제</button>`;
                    }
                }
            ],
            // ... other datatable options
        });
    }

    // Modal handling
    const planModal = new bootstrap.Modal(document.getElementById('planModal'));
    let activeItems = [];

    function loadActiveItems(selectedItemId = null) {
        $.ajax({
            url: '/api/supply/items/active',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    activeItems = response.data;
                    const itemSelect = $('#modal-item-id');
                    itemSelect.empty().append('<option value="">품목을 선택하세요</option>');
                    activeItems.forEach(item => {
                        itemSelect.append(new Option(`${item.name} (${item.code})`, item.id));
                    });
                    if (selectedItemId) {
                        itemSelect.val(selectedItemId).trigger('change');
                    }
                }
            }
        });
    }

    $('#modal-item-id').on('change', function() {
        const selectedId = $(this).val();
        const selectedItem = activeItems.find(item => item.id == selectedId);
        $('#modal-item-unit').val(selectedItem ? selectedItem.unit : '');
    });

    function calculateTotalBudget() {
        const quantity = parseInt($('#modal-planned-quantity').val()) || 0;
        const price = parseFloat($('#modal-unit-price').val()) || 0;
        $('#modal-total-budget').val(`₩${(quantity * price).toLocaleString()}`);
    }

    $('#modal-planned-quantity, #modal-unit-price').on('input', calculateTotalBudget);

    $('#add-plan-btn').on('click', function() {
        $('#planForm')[0].reset();
        $('#plan-id').val('');
        $('#planModalLabel').text('신규 계획 등록');
        loadActiveItems();
        calculateTotalBudget();
        planModal.show();
    });

    $('#plans-table tbody').on('click', '.edit-plan-btn', function() {
        const planId = $(this).data('id');
        $.ajax({
            url: `/api/supply/plans/${planId}`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const plan = response.data;
                    $('#planForm')[0].reset();
                    $('#planModalLabel').text('계획 수정');
                    $('#plan-id').val(plan.id);
                    loadActiveItems(plan.item_id);
                    $('#modal-planned-quantity').val(plan.planned_quantity);
                    $('#modal-unit-price').val(plan.unit_price);
                    $('#modal-notes').val(plan.notes);
                    calculateTotalBudget();
                    planModal.show();
                }
            }
        });
    });

    $('#save-plan-btn').on('click', function() {
        if (!$('#planForm')[0].checkValidity()) {
            $('#planForm').addClass('was-validated');
            return;
        }

        const planId = $('#plan-id').val();
        const url = planId ? `/api/supply/plans/${planId}` : '/api/supply/plans';
        const method = planId ? 'PUT' : 'POST';

        const formData = {
            year: currentYear,
            item_id: $('#modal-item-id').val(),
            planned_quantity: $('#modal-planned-quantity').val(),
            unit_price: $('#modal-unit-price').val(),
            notes: $('#modal-notes').val()
        };

        $.ajax({
            url: url,
            method: method,
            contentType: 'application/json',
            data: JSON.stringify(formData),
            success: function(response) {
                if (response.success) {
                    planModal.hide();
                    plansTable.ajax.reload();
                    loadBudgetSummary(currentYear);
                }
            }
        });
    });

    loadAllData(currentYear);
});
</script>
<?php \App\Core\View::getInstance()->endSection(); ?>
