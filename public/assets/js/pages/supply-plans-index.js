/**
 * Supply Plans Index JavaScript
 */

class SupplyPlansIndexPage extends BasePage {
    constructor() {
        super({
            API_URL: '/supply/plans'
        });
        
        const urlParams = new URLSearchParams(window.location.search);
        this.currentYear = parseInt(urlParams.get('year'), 10) || new Date().getFullYear();

        this.currentDeleteId = null;
        this.deletePlanModal = null;
        this.planModal = null;
        this.dataTable = null;
        this.activeItems = [];
    }

    setupEventListeners() {
        const yearSelector = document.getElementById('year-selector');
        yearSelector?.addEventListener('change', () => {
            this.currentYear = parseInt(yearSelector.value, 10);
            this.updatePageForYear();
        });

        const exportExcelBtn = document.getElementById('export-excel-btn');
        exportExcelBtn?.addEventListener('click', () => this.exportToExcel());

        $(document).on('click', '.delete-plan-btn', (e) => this.handleDeleteClick(e));

        const confirmDeleteBtn = document.getElementById('confirm-delete-plan-btn');
        confirmDeleteBtn?.addEventListener('click', () => this.confirmDelete());

        // 검색 이벤트 추가
        $('#search-input').on('keyup', this.debounce(() => {
            this.loadPlans();
        }, 300));

        const addPlanBtn = document.getElementById('add-plan-btn');
        addPlanBtn?.addEventListener('click', () => this.openPlanModal());

        const savePlanBtn = document.getElementById('save-plan-btn');
        savePlanBtn?.addEventListener('click', () => this.handleSavePlan());

        $(document).on('click', '.edit-plan-btn', (e) => this.openPlanModal(e.currentTarget.dataset.id));

        const modalItemId = document.getElementById('modal-item-id');
        modalItemId?.addEventListener('change', () => this.updateItemUnit());

        const quantityInput = document.getElementById('modal-planned-quantity');
        quantityInput?.addEventListener('input', () => this.calculateTotalBudget());

        const priceInput = document.getElementById('modal-unit-price');
        priceInput?.addEventListener('input', () => this.calculateTotalBudget());
    }

    loadInitialData() {
        this.populateYearSelector();
        this.loadBudgetSummary();
        this.initializeDataTable();
        this.loadPlans();
        
        const deleteModalElement = document.getElementById('deletePlanModal');
        if (deleteModalElement) {
            this.deletePlanModal = new bootstrap.Modal(deleteModalElement);
        }

        const planModalElement = document.getElementById('planModal');
        if (planModalElement) {
            this.planModal = new bootstrap.Modal(planModalElement);
        }

        this.loadActiveItems();
    }

    populateYearSelector() {
        const selector = document.getElementById('year-selector');
        if (!selector) return;

        const startYear = new Date().getFullYear() + 1;
        for (let y = startYear; y >= 2020; y--) {
            const option = document.createElement('option');
            option.value = y;
            option.textContent = `${y}년`;
            if (y === this.currentYear) {
                option.selected = true;
            }
            selector.appendChild(option);
        }
    }

    async loadBudgetSummary() {
        const container = document.getElementById('budget-summary-container');
        try {
            const response = await this.apiCall(`/supply/plans/budget-summary?year=${this.currentYear}`);
            const summary = response.data;
            // ... (render a lot of budget summary html)
        } catch (error) {
            container.innerHTML = `<div class="col-12"><p class="text-danger">예산 요약 정보를 불러오는데 실패했습니다.</p></div>`;
        }
    }

    async loadPlans() {
        this.dataTable.ajax.reload();
    }

    initializeDataTable() {
        const table = document.getElementById('plans-table');
        if (table && typeof $.fn.DataTable !== 'undefined') {
            this.dataTable = $(table).DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: `/api/supply/plans?year=${this.currentYear}`,
                    dataSrc: 'data.plans'
                },
                columns: [
                    { data: 'item_code' },
                    { data: 'item_name', render: (data, type, row) => this.escapeHtml(data) + (row.notes ? `<p class="text-muted mb-0 fs-12">${this.escapeHtml(row.notes)}</p>` : '') },
                    { data: 'category_name', render: data => `<span class="badge badge-soft-primary">${this.escapeHtml(data)}</span>` },
                    { data: 'unit' },
                    { data: 'planned_quantity', className: 'text-end', render: data => Number(data).toLocaleString() },
                    { data: 'unit_price', className: 'text-end', render: data => `₩${Number(data).toLocaleString()}` },
                    { data: null, className: 'text-end', render: (data, type, row) => `<strong>₩${(row.planned_quantity * row.unit_price).toLocaleString()}</strong>` },
                    { data: 'created_at', render: data => new Date(data).toLocaleDateString() },
                    { data: 'id', orderable: false, render: (data, type, row) => `
                        <div class="dropdown">
                            <button class="btn btn-soft-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="ri-more-fill"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><button class="dropdown-item edit-plan-btn" data-id="${data}"><i class="ri-pencil-fill align-bottom me-2 text-muted"></i> 수정</button></li>
                                <li><button class="dropdown-item delete-plan-btn" data-id="${data}" data-name="${this.escapeHtml(row.item_name)}"><i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> 삭제</button></li>
                            </ul>
                        </div>`
                    }
                ],
                responsive: true,
                pageLength: 25,
                order: [[7, 'desc']],
                language: { url: '/assets/libs/datatables.net/i18n/Korean.json' },
                searching: false
            });
        }
    }

    updatePageForYear() {
        window.history.pushState({}, '', `/supply/plans?year=${this.currentYear}`);
        document.querySelector('.page-title-box h4').textContent = `연간 지급품 계획 (${this.currentYear}년)`;
        document.getElementById('plans-table-title').textContent = `${this.currentYear}년 지급품 계획 목록`;

        // Update button links
        document.querySelector('a[href^="/supply/plans/import"]').href = `/supply/plans/import?year=${this.currentYear}`;
        document.querySelector('a[href^="/supply/plans/budget-summary"]').href = `/supply/plans/budget-summary?year=${this.currentYear}`;

        this.loadBudgetSummary();
        this.loadPlans();
    }

    handleDeleteClick(e) {
        const btn = e.currentTarget;
        this.currentDeleteId = btn.getAttribute('data-id');
        const planName = btn.getAttribute('data-name');
        
        const deleteInfo = document.getElementById('delete-plan-info');
        deleteInfo.innerHTML = `<div class="alert alert-warning"><strong>삭제할 계획:</strong> ${planName}</div>`;
        
        this.deletePlanModal.show();
    }

    async confirmDelete() {
        if (!this.currentDeleteId) return;

        this.setButtonLoading('#confirm-delete-plan-btn', '삭제 중...');
        try {
            await this.apiCall(`/supply/plans/${this.currentDeleteId}`, { method: 'DELETE' });
            Toast.success('계획이 성공적으로 삭제되었습니다.');
            this.deletePlanModal.hide();
            this.loadPlans();
            this.loadBudgetSummary();
        } catch (error) {
            this.handleApiError(error);
        } finally {
            this.resetButtonLoading('#confirm-delete-plan-btn', '삭제');
            this.currentDeleteId = null;
        }
    }

    exportToExcel() {
        window.open(`/api/supply/plans/export-excel/${this.currentYear}`, '_blank');
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    async loadActiveItems(selectedItemId = null) {
        try {
            const response = await this.apiCall('/supply/items/active');
            if (response.success) {
                this.activeItems = response.data;
                const itemSelect = document.getElementById('modal-item-id');
                itemSelect.innerHTML = '<option value="">품목을 선택하세요</option>';
                this.activeItems.forEach(item => {
                    const option = new Option(`${item.item_name} (${item.item_code})`, item.id);
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
        const selectedItem = this.activeItems.find(item => item.id == selectedId);
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
        form.classList.remove('was-validated');
        document.getElementById('plan-id').value = '';
        document.getElementById('planModalLabel').textContent = planId ? '계획 수정' : '신규 계획 등록';

        if (planId) {
            try {
                const response = await this.apiCall(`/supply/plans/${planId}`);
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
                Toast.error('계획 정보를 불러오는데 실패했습니다.');
                return;
            }
        } else {
            await this.loadActiveItems();
        }

        this.calculateTotalBudget();
        this.planModal.show();
    }

    async handleSavePlan() {
        const form = document.getElementById('planForm');
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        const planId = document.getElementById('plan-id').value;
        const url = planId ? `/supply/plans/${planId}` : '/supply/plans';
        const method = planId ? 'PUT' : 'POST';

        const formData = {
            year: this.currentYear,
            item_id: document.getElementById('modal-item-id').value,
            planned_quantity: document.getElementById('modal-planned-quantity').value,
            unit_price: document.getElementById('modal-unit-price').value,
            notes: document.getElementById('modal-notes').value
        };

        this.setButtonLoading('#save-plan-btn');
        try {
            const response = await this.apiCall(url, {
                method: method,
                body: JSON.stringify(formData),
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (response.success) {
                this.planModal.hide();
                Toast.success('계획이 성공적으로 저장되었습니다.');
                this.loadPlans();
                this.loadBudgetSummary();
            } else {
                Toast.error(response.message || '계획 저장에 실패했습니다.');
            }
        } catch (error) {
            console.error('Failed to save plan:', error);
            Toast.error(error.message || '계획 저장 중 오류가 발생했습니다.');
        } finally {
            this.resetButtonLoading('#save-plan-btn', '저장');
        }
    }
}

// 인스턴스 생성
new SupplyPlansIndexPage();
