class LeaveGrantingPage extends BasePage {
    constructor() {
        super();
        this.state = {
            adjustmentModal: null,
            currentEmployee: null
        };
    }

    initializeApp() {
        this.cacheDOMElements();
        if (this.elements.adjustmentModalEl) {
            this.state.adjustmentModal = new bootstrap.Modal(this.elements.adjustmentModalEl);
        }
        this.setupEventListeners();
        this.loadInitialData();
    }

    cacheDOMElements() {
        this.elements = {
            yearFilter: document.getElementById('filter-year'),
            departmentFilter: document.getElementById('filter-department'),
            filterBtn: document.getElementById('filter-btn'),
            grantAllBtn: document.getElementById('grant-all-btn'),
            expireAllBtn: document.getElementById('expire-all-btn'),
            tableBody: document.getElementById('balances-table-body'),
            adjustmentModalEl: document.getElementById('adjustment-modal'),
            adjustmentForm: document.getElementById('adjustment-form'),
            adjustmentTitle: document.getElementById('adjustment-modal-title')
        };
    }

    setupEventListeners() {
        this.elements.filterBtn.addEventListener('click', () => this.loadBalances());
        this.elements.grantAllBtn.addEventListener('click', () => this.handleGrantAll());
        this.elements.expireAllBtn.addEventListener('click', () => this.handleExpireAll());
        this.elements.tableBody.addEventListener('click', (e) => this.handleTableClick(e));
        if (this.elements.adjustmentForm) {
            this.elements.adjustmentForm.addEventListener('submit', (e) => this.handleAdjustmentSubmit(e));
        }
    }

    async loadInitialData() {
        this.populateYearFilter();
        await this.loadDepartments();
        await this.loadBalances();
    }

    populateYearFilter() {
        const currentYear = new Date().getFullYear();
        for (let i = -1; i < 5; i++) { // Allow selecting next year for pre-granting
            const year = currentYear - i;
            this.elements.yearFilter.add(new Option(year, year));
        }
    }

    async loadDepartments() {
        try {
            const response = await this.apiCall('/organization/managable-departments');
            this.elements.departmentFilter.innerHTML = '<option value="">전체 부서</option>';
            response.data.forEach(dep => {
                this.elements.departmentFilter.add(new Option(dep.name, dep.id));
            });
        } catch (error) { Toast.error('부서 목록 로딩 실패'); }
    }

    async loadBalances() {
        const year = this.elements.yearFilter.value;
        const departmentId = this.elements.departmentFilter.value;
        const params = new URLSearchParams({ year, department_id: departmentId });

        try {
            const response = await this.apiCall(`/admin/leaves/balances?${params}`);
            this.renderTable(response.data);
        } catch (error) {
            Toast.error('연차 현황 로딩 실패: ' + error.message);
            this.elements.tableBody.innerHTML = '<tr><td colspan="9" class="text-center">데이터 로딩 실패</td></tr>';
        }
    }

    renderTable(data) {
        if (data.length === 0) {
            this.elements.tableBody.innerHTML = `<tr><td colspan="9" class="text-center">해당 직원이 없습니다.</td></tr>`;
            return;
        }

        this.elements.tableBody.innerHTML = data.map(emp => {
            const balance = emp.leave_balance || {}; // Assuming leave_balance is nested
            const total = (balance.base_leave || 0) + (balance.seniority_leave || 0) + (balance.monthly_leave || 0) + (balance.adjustment_leave || 0);
            const used = balance.used_leave || 0;
            const remaining = total - used;
            return `
                <tr>
                    <td>${emp.employee_name}</td>
                    <td>${emp.department_name}</td>
                    <td>${total.toFixed(1)}</td>
                    <td>${used.toFixed(1)}</td>
                    <td>${remaining.toFixed(1)}</td>
                    <td>${(balance.base_leave || 0)}</td>
                    <td>${(balance.seniority_leave || 0)}</td>
                    <td>${(balance.monthly_leave || 0)}</td>
                    <td>
                        <button class="btn btn-sm btn-primary adjust-btn" data-employee-id="${emp.id}" data-employee-name="${emp.employee_name}">조정</button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    async handleGrantAll() {
        const year = this.elements.yearFilter.value;
        if (!await confirm(`${year}년 연차를 모든 활성 직원에게 부여하시겠습니까?`)) return;

        try {
            const response = await this.apiCall('/admin/leaves/grant-annual', 'POST', { year });
            Toast.success(response.message);
            this.loadBalances();
        } catch (error) {
            Toast.error('일괄 부여 실패: ' + error.message);
        }
    }

    async handleExpireAll() {
        const year = this.elements.yearFilter.value;
        if (!await confirm(`${year}년 미사용 연차를 모두 소멸 처리하시겠습니까?`)) return;

        try {
            const response = await this.apiCall('/admin/leaves/expire-unused', 'POST', { year });
            Toast.success(response.message);
            this.loadBalances();
        } catch (error) {
            Toast.error('일괄 소멸 실패: ' + error.message);
        }
    }

    handleTableClick(e) {
        if (e.target.classList.contains('adjust-btn')) {
            this.state.currentEmployee = {
                id: e.target.dataset.employeeId,
                name: e.target.dataset.employeeName
            };
            this.elements.adjustmentTitle.textContent = `${this.state.currentEmployee.name} 연차 조정`;
            this.elements.adjustmentForm.reset();
            this.state.adjustmentModal.show();
        }
    }

    async handleAdjustmentSubmit(e) {
        e.preventDefault();
        const formData = new FormData(this.elements.adjustmentForm);
        const data = {
            employee_id: this.state.currentEmployee.id,
            year: formData.get('year'),
            days: formData.get('days'),
            reason: formData.get('reason')
        };

        if (!data.days || !data.reason) {
            Toast.error('일수와 사유를 모두 입력하세요.');
            return;
        }

        try {
            const response = await this.apiCall('/admin/leaves/adjust', 'POST', data);
            Toast.success(response.message);
            this.state.adjustmentModal.hide();
            this.loadBalances();
        } catch (error) {
            Toast.error('조정 실패: ' + error.message);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new LeaveGrantingPage().initializeApp();
});
