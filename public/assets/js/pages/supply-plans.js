$(document).ready(function() {
    let currentYear = new Date().getFullYear();
    const yearParam = new URLSearchParams(window.location.search).get('year');
    if (yearParam) {
        currentYear = parseInt(yearParam);
    }

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
