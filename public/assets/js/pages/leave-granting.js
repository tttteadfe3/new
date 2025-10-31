class LeaveGrantingPage extends BasePage {
    constructor() {
        super();
        this.state = {
            adjustmentModal: null,
            grantPreviewModal: null,
            currentEmployee: null
        };
    }

    initializeApp() {
        this.cacheDOMElements();
        this.state.adjustmentModal = new bootstrap.Modal(this.elements.adjustmentModalEl);
        this.state.grantPreviewModal = new bootstrap.Modal(this.elements.grantPreviewModalEl);
        this.setupEventListeners();
        this.loadInitialData();
    }

    cacheDOMElements() {
        this.elements = {
            yearFilter: document.getElementById('filter-year'),
            departmentFilter: document.getElementById('filter-department'),
            filterBtn: document.getElementById('filter-btn'),
            previewGrantBtn: document.getElementById('preview-grant-btn'),
            expireAllBtn: document.getElementById('expire-all-btn'),
            tableBody: document.getElementById('balances-table-body'),

            adjustmentModalEl: document.getElementById('adjustment-modal'),
            adjustmentForm: document.getElementById('adjustment-form'),
            adjustmentEmployeeName: document.getElementById('adjustment-employee-name'),

            grantPreviewModalEl: document.getElementById('grant-preview-modal'),
            grantPreviewYear: document.getElementById('preview-year'),
            grantPreviewEmployeeCount: document.getElementById('employee-count'),
            grantPreviewTableBody: document.getElementById('grant-preview-table-body'),
            executeGrantBtn: document.getElementById('execute-grant-btn')
        };
    }

    setupEventListeners() {
        this.elements.filterBtn.addEventListener('click', () => this.loadBalances());
        this.elements.previewGrantBtn.addEventListener('click', () => this.handlePreviewGrant());
        this.elements.expireAllBtn.addEventListener('click', () => this.handleExpireAll());
        this.elements.executeGrantBtn.addEventListener('click', () => this.handleExecuteGrant());
        this.elements.tableBody.addEventListener('click', (e) => this.handleTableClick(e));
        this.elements.adjustmentForm.addEventListener('submit', (e) => this.handleAdjustmentSubmit(e));
    }

    async loadInitialData() {
        await this.loadDepartments();
        await this.loadBalances();
    }

    async loadDepartments() {
        try {
            const response = await this.apiCall('/organization/managable-departments');
            this.elements.departmentFilter.innerHTML = '<option value="">전체 부서</option>';
            (response.data || []).forEach(dep => {
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
            this.elements.tableBody.innerHTML = '<tr><td colspan="10" class="text-center">데이터 로딩 실패</td></tr>';
        }
    }

    renderTable(data) {
        if (!data || data.length === 0) {
            this.elements.tableBody.innerHTML = `<tr><td colspan="10" class="text-center">해당 직원이 없습니다.</td></tr>`;
            return;
        }

        this.elements.tableBody.innerHTML = data.map(emp => {
            const total = (parseFloat(emp.base_leave) || 0) + (parseFloat(emp.monthly_leave) || 0) + (parseFloat(emp.seniority_leave) || 0) + (parseFloat(emp.adjustment_leave) || 0);
            const used = parseFloat(emp.used_leave) || 0;
            const remaining = total - used;
            return `
                <tr>
                    <td>${emp.employee_name}</td>
                    <td>${emp.department_name || ''}</td>
                    <td>${total.toFixed(1)}</td>
                    <td>${used.toFixed(1)}</td>
                    <td>${remaining.toFixed(1)}</td>
                    <td>${(parseFloat(emp.base_leave) || 0).toFixed(1)}</td>
                    <td>${(parseFloat(emp.monthly_leave) || 0).toFixed(1)}</td>
                    <td>${(parseFloat(emp.seniority_leave) || 0).toFixed(1)}</td>
                    <td>${(parseFloat(emp.adjustment_leave) || 0).toFixed(1)}</td>
                    <td>
                        <button class="btn btn-sm btn-primary adjust-btn" data-employee-id="${emp.id}" data-employee-name="${emp.employee_name}">조정</button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    async handlePreviewGrant() {
        const year = this.elements.yearFilter.value;
        const departmentId = this.elements.departmentFilter.value;

        const params = new URLSearchParams({ year });
        if (departmentId) {
            params.append('department_id', departmentId);
        }

        try {
            const response = await this.apiCall(`/admin/leaves/preview-grant-annual?${params.toString()}`);
            const data = response.data;

            this.elements.grantPreviewYear.textContent = year;
            this.elements.grantPreviewEmployeeCount.textContent = data.length;

            if (data.length === 0) {
                this.elements.grantPreviewTableBody.innerHTML = '<tr><td colspan="8" class="text-center">연차 부여 대상 직원이 없습니다.</td></tr>';
            } else {
                this.elements.grantPreviewTableBody.innerHTML = data.map(item => `
                    <tr>
                        <td>${item.employee_id}</td>
                        <td>${item.employee_name}</td>
                        <td>${item.department_name || ''}</td>
                        <td>${item.hire_date}</td>
                        <td>${item.base_leave_to_grant}</td>
                        <td>${item.monthly_leave_to_grant}</td>
                        <td>${item.seniority_leave_to_grant}</td>
                        <td><strong>${item.total_to_grant}</strong></td>
                    </tr>
                `).join('');
            }
            this.state.grantPreviewModal.show();
        } catch (error) {
            Toast.error('미리보기 계산 실패: ' + error.message);
        }
    }

    async handleExecuteGrant() {
        const year = this.elements.yearFilter.value;
        if (!await confirm(`${year}년 연차를 화면에 표시된 직원들에게 부여하시겠습니까?`)) return;

        try {
            const response = await this.apiCall('/admin/leaves/grant-annual', 'POST', { year });
            Toast.success(response.message || '일괄 부여가 완료되었습니다.');
            this.state.grantPreviewModal.hide();
            await this.loadBalances();
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
            await this.loadBalances();
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
            this.elements.adjustmentEmployeeName.textContent = this.state.currentEmployee.name;
            this.elements.adjustmentForm.reset();
            this.elements.adjustmentForm.querySelector('[name="employee_id"]').value = this.state.currentEmployee.id;
            this.elements.adjustmentForm.querySelector('[name="year"]').value = this.elements.yearFilter.value;
            this.state.adjustmentModal.show();
        }
    }

    async handleAdjustmentSubmit(e) {
        e.preventDefault();
        const formData = new FormData(this.elements.adjustmentForm);
        const data = Object.fromEntries(formData.entries());

        if (!data.days || !data.reason || !data.year) {
            Toast.error('모든 필드를 입력하세요.');
            return;
        }

        try {
            const response = await this.apiCall('/admin/leaves/adjust', 'POST', data);
            Toast.success(response.message);
            this.state.adjustmentModal.hide();
            await this.loadBalances();
        } catch (error) {
            Toast.error('조정 실패: ' + error.message);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new LeaveGrantingPage().initializeApp();
});
