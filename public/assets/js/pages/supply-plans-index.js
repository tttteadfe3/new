/**
 * Supply Plans Index JavaScript
 */

class SupplyPlansIndexPage extends BasePage {
    constructor() {
        super({
            apiBaseUrl: '/api/supply/plans'
        });
        
        const urlParams = new URLSearchParams(window.location.search);
        this.currentYear = parseInt(urlParams.get('year'), 10) || new Date().getFullYear();

        this.currentDeleteId = null;
        this.deletePlanModal = null;
        this.dataTable = null;
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
    }

    loadInitialData() {
        this.populateYearSelector();
        this.loadBudgetSummary();
        this.initializeDataTable();
        
        const deleteModalElement = document.getElementById('deletePlanModal');
        if (deleteModalElement) {
            this.deletePlanModal = new bootstrap.Modal(deleteModalElement);
        }
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
            const response = await this.apiCall(`${this.config.apiBaseUrl}/budget-summary/${this.currentYear}`);
            const summary = response.data;
            container.innerHTML = `
                <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">총 계획 품목</p></div><div class="flex-shrink-0"><span class="avatar-title bg-success-subtle rounded fs-3"><i class="bx bx-package text-success"></i></span></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0"><span class="counter-value">${summary.total_items.toLocaleString()}</span>개</h4></div></div></div></div></div>
                <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">총 계획 수량</p></div><div class="flex-shrink-0"><span class="avatar-title bg-info-subtle rounded fs-3"><i class="bx bx-cube text-info"></i></span></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0"><span class="counter-value">${summary.total_quantity.toLocaleString()}</span>개</h4></div></div></div></div></div>
                <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">총 예산</p></div><div class="flex-shrink-0"><span class="avatar-title bg-warning-subtle rounded fs-3"><i class="bx bx-won text-warning"></i></span></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0">₩<span class="counter-value">${summary.total_budget.toLocaleString()}</span></h4></div></div></div></div></div>
                <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">평균 단가</p></div><div class="flex-shrink-0"><span class="avatar-title bg-primary-subtle rounded fs-3"><i class="bx bx-calculator text-primary"></i></span></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0">₩<span class="counter-value">${summary.avg_unit_price.toLocaleString()}</span></h4></div></div></div></div></div>
            `;
        } catch (error) {
            container.innerHTML = `<div class="col-12"><p class="text-danger">예산 요약 정보를 불러오는데 실패했습니다.</p></div>`;
        }
    }

    initializeDataTable() {
        const table = document.getElementById('plans-table');
        if (table && typeof $.fn.DataTable !== 'undefined') {
            this.dataTable = $(table).DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: `${this.config.apiBaseUrl}?year=${this.currentYear}`,
                    type: 'GET'
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
                                <li><a class="dropdown-item" href="/supply/plans/edit?id=${data}"><i class="ri-pencil-fill align-bottom me-2 text-muted"></i> 수정</a></li>
                                <li><button class="dropdown-item delete-plan-btn" data-id="${data}" data-name="${this.escapeHtml(row.item_name)}"><i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> 삭제</button></li>
                            </ul>
                        </div>`
                    }
                ],
                responsive: true,
                pageLength: 25,
                order: [[7, 'desc']],
                language: { url: '/assets/libs/datatables.net/i18n/Korean.json' }
            });
        }
    }

    updatePageForYear() {
        window.history.pushState({}, '', `/supply/plans?year=${this.currentYear}`);
        document.querySelector('.page-title-box h4').textContent = `연간 지급품 계획 (${this.currentYear}년)`;
        document.getElementById('plans-table-title').textContent = `${this.currentYear}년 지급품 계획 목록`;

        // Update button links
        document.querySelector('a[href^="/supply/plans/create"]').href = `/supply/plans/create?year=${this.currentYear}`;
        document.querySelector('a[href^="/supply/plans/import"]').href = `/supply/plans/import?year=${this.currentYear}`;
        document.querySelector('a[href^="/supply/plans/budget-summary"]').href = `/supply/plans/budget-summary?year=${this.currentYear}`;

        this.loadBudgetSummary();
        this.dataTable.ajax.url(`${this.config.apiBaseUrl}?year=${this.currentYear}`).load();
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
            await this.apiCall(`${this.config.apiBaseUrl}/${this.currentDeleteId}`, { method: 'DELETE' });
            Toast.success('계획이 성공적으로 삭제되었습니다.');
            this.deletePlanModal.hide();
            this.dataTable.ajax.reload();
            this.loadBudgetSummary();
        } catch (error) {
            this.handleApiError(error);
        } finally {
            this.resetButtonLoading('#confirm-delete-plan-btn', '삭제');
            this.currentDeleteId = null;
        }
    }

    exportToExcel() {
        window.open(`${this.config.apiBaseUrl}/export-excel/${this.currentYear}`, '_blank');
    }
}

// 인스턴스 생성
new SupplyPlansIndexPage();
