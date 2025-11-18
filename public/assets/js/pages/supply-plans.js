class SupplyPlansPage extends BasePage {
    constructor() {
        super();
        this.state = {
            currentYear: new Date().getFullYear(),
            plansTable: null,
            activeItems: []
        };
        const yearParam = new URLSearchParams(window.location.search).get('year');
        if (yearParam) {
            this.state.currentYear = parseInt(yearParam);
        }
    }

    initializeApp() {
        this.setupEventListeners();
        this.loadInitialData();
        this.initializeYearSelector();
    }

    initializeYearSelector() {
        const yearSelector = document.getElementById('year-selector');
        const currentServerYear = new Date().getFullYear();
        for (let y = currentServerYear + 1; y >= currentServerYear - 5; y--) {
            const option = new Option(`${y}년`, y, y === this.state.currentYear);
            yearSelector.add(option);
        }
    }

    setupEventListeners() {
        document.getElementById('year-selector').addEventListener('change', (e) => {
            this.state.currentYear = parseInt(e.target.value);
            window.history.pushState({}, '', `?year=${this.state.currentYear}`);
            this.loadAllData(this.state.currentYear);
        });

        document.getElementById('add-plan-btn').addEventListener('click', () => this.openPlanModal());

        document.getElementById('save-plan-btn').addEventListener('click', () => this.handleSavePlan());

        $('#plans-table tbody').on('click', '.edit-plan-btn', (e) => {
            const planId = e.target.dataset.id;
            this.openPlanModal(planId);
        });

        document.getElementById('modal-item-id').addEventListener('change', () => this.updateItemUnit());

        document.getElementById('modal-planned-quantity').addEventListener('input', () => this.calculateTotalBudget());
        document.getElementById('modal-unit-price').addEventListener('input', () => this.calculateTotalBudget());

    }

    loadInitialData() {
        this.loadAllData(this.state.currentYear);
        this.loadActiveItems();
    }

    loadAllData(year) {
        document.getElementById('plans-table-title').textContent = `${year}년 지급품 계획 목록`;
        document.getElementById('plan-year').value = year;
        this.loadBudgetSummary(year);
        if (this.state.plansTable) {
            this.state.plansTable.ajax.url(`/api/supply/plans?year=${year}`).load();
        } else {
            this.initializeDataTable(year);
        }
    }

    async loadBudgetSummary(year) {
        try {
            const response = await this.apiCall(`/api/supply/plans/budget-summary?year=${year}`);
            if (response.success && response.data) {
                this.updateBudgetSummary(response.data);
            }
        } catch (error) {
            console.error('Failed to load budget summary:', error);
        }
    }

    updateBudgetSummary(data) {
        document.getElementById('budget-summary-container').innerHTML = `
            <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">총 계획 품목</p></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0"><span class="counter-value">${data.total_items}</span>개</h4></div></div></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">총 계획 수량</p></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0"><span class="counter-value">${data.total_quantity}</span>개</h4></div></div></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">총 예산</p></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0">₩<span class="counter-value">${data.total_budget.toLocaleString()}</span></h4></div></div></div></div></div>
            <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">평균 단가</p></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0">₩<span class="counter-value">${parseInt(data.avg_unit_price).toLocaleString()}</span></h4></div></div></div></div></div>
        `;
    }

    initializeDataTable(year) {
        this.state.plansTable = $('#plans-table').DataTable({
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
                    render: (data, type, row) => {
                        return `<button class="btn btn-sm btn-primary edit-plan-btn" data-id="${data}">수정</button>
                                <button class="btn btn-sm btn-danger delete-plan-btn" data-id="${data}">삭제</button>`;
                    }
                }
            ],
            // ... other datatable options
        });
    }

    async loadActiveItems(selectedItemId = null) {
        try {
            const response = await this.apiCall('/api/supply/items/active');
            if (response.success) {
                this.state.activeItems = response.data;
                const itemSelect = document.getElementById('modal-item-id');
                itemSelect.innerHTML = '<option value="">품목을 선택하세요</option>';
                this.state.activeItems.forEach(item => {
                    const option = new Option(`${item.name} (${item.code})`, item.id);
                    itemSelect.add(option);
                });
                if (selectedItemId) {
                    itemSelect.value = selectedItemId;
                    this.updateItemUnit();
                }
            }
        } catch (error) {
            console.error('Failed to load active items:', error);
        }
    }

    updateItemUnit() {
        const selectedId = document.getElementById('modal-item-id').value;
        const selectedItem = this.state.activeItems.find(item => item.id == selectedId);
        document.getElementById('modal-item-unit').value = selectedItem ? selectedItem.unit : '';
    }

    calculateTotalBudget() {
        const quantity = parseInt(document.getElementById('modal-planned-quantity').value) || 0;
        const price = parseFloat(document.getElementById('modal-unit-price').value) || 0;
        document.getElementById('modal-total-budget').value = `₩${(quantity * price).toLocaleString()}`;
    }

    async openPlanModal(planId = null) {
        const form = document.getElementById('planForm');
        form.reset();
        document.getElementById('plan-id').value = '';
        document.getElementById('planModalLabel').textContent = planId ? '계획 수정' : '신규 계획 등록';

        if (planId) {
            try {
                const response = await this.apiCall(`/api/supply/plans/${planId}`);
                if (response.success) {
                    const plan = response.data;
                    document.getElementById('plan-id').value = plan.id;
                    await this.loadActiveItems(plan.item_id);
                    document.getElementById('modal-planned-quantity').value = plan.planned_quantity;
                    document.getElementById('modal-unit-price').value = plan.unit_price;
                    document.getElementById('modal-notes').value = plan.notes;
                }
            } catch (error) {
                console.error(`Failed to load plan ${planId}:`, error);
                return;
            }
        } else {
            await this.loadActiveItems();
        }

        this.calculateTotalBudget();
        const planModal = new bootstrap.Modal(document.getElementById('planModal'));
        planModal.show();
    }

    async handleSavePlan() {
        const form = document.getElementById('planForm');
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        const planId = document.getElementById('plan-id').value;
        const url = planId ? `/api/supply/plans/${planId}` : '/api/supply/plans';
        const method = planId ? 'PUT' : 'POST';

        const formData = {
            year: this.state.currentYear,
            item_id: document.getElementById('modal-item-id').value,
            planned_quantity: document.getElementById('modal-planned-quantity').value,
            unit_price: document.getElementById('modal-unit-price').value,
            notes: document.getElementById('modal-notes').value
        };

        try {
            const response = await this.apiCall(url, {
                method: method,
                body: JSON.stringify(formData),
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (response.success) {
                const planModal = bootstrap.Modal.getInstance(document.getElementById('planModal'));
                planModal.hide();
                this.state.plansTable.ajax.reload();
                this.loadBudgetSummary(this.state.currentYear);
            }
        } catch (error) {
            console.error('Failed to save plan:', error);
        }
    }
}

new SupplyPlansPage();
