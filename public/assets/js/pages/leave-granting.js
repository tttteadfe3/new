class LeaveGrantingPage extends BasePage {
    constructor() {
        super();
        this.state = { adjustmentModal: null };
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
            adjustmentForm: document.getElementById('adjustment-form')
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
        await this.loadDepartments();
        await this.loadBalances();
    }

    async loadDepartments() {
        // ... (부서 목록 로딩, 변경 없음)
    }

    async loadBalances() {
        this.elements.tableBody.innerHTML = `<tr><td colspan="11" class="text-center"><span class="spinner-border spinner-border-sm"></span>...</td></tr>`;
        try {
            const year = this.elements.yearFilter.value;
            const departmentId = this.elements.departmentFilter.value;
            // 참고: 이 API는 아직 만들지 않았지만, 이러한 기능이 필요하다는 가정하에 작성합니다.
            // 실제로는 findRequestsByAdmin을 확장하여 balance 정보도 함께 가져올 수 있습니다.
            const response = await this.apiCall(`/api/admin/leaves/balances?year=${year}&department_id=${departmentId}`);
            this.renderTable(response.data);
        } catch (error) {
            this.elements.tableBody.innerHTML = `<tr><td colspan="11" class="text-center text-danger">목록 로딩 실패</td></tr>`;
        }
    }

    renderTable(data) {
        if (!data || data.length === 0) {
            this.elements.tableBody.innerHTML = `<tr><td colspan="11" class="text-center">데이터가 없습니다.</td></tr>`;
            return;
        }
        const rows = data.map(balance => {
            const totalGranted = parseFloat(balance.base_leave) + parseFloat(balance.seniority_leave) + parseFloat(balance.monthly_leave) + parseFloat(balance.adjustment_leave);
            const used = parseFloat(balance.used_leave);
            const remaining = totalGranted - used;
            return `
                <tr>
                    <td>${balance.employee_name}</td>
                    <td>${balance.department_name}</td>
                    <td>${balance.hire_date}</td>
                    <td>${totalGranted.toFixed(2)}</td>
                    <td>${used.toFixed(2)}</td>
                    <td class="fw-bold">${remaining.toFixed(2)}</td>
                    <td><button class="btn btn-sm btn-light adjust-btn" data-employee-id="${balance.employee_id}" data-employee-name="${balance.employee_name}"><i class="bx bx-edit"></i> 조정</button></td>
                </tr>
            `;
        }).join('');
        this.elements.tableBody.innerHTML = rows;
    }

    async handleGrantAll() {
        const year = this.elements.yearFilter.value;
        if (await Confirm.fire(`${year}년 연차를 일괄 부여하시겠습니까?`)) {
            try {
                const response = await this.apiCall('/api/admin/leaves/grant-annual', { method: 'POST', body: { year } });
                Toast.success(response.message);
                this.loadBalances();
            } catch (error) {
                Toast.error(`부여 실패: ${error.message}`);
            }
        }
    }

    async handleExpireAll() {
        const year = this.elements.yearFilter.value;
        if (await Confirm.fire(`${year}년 미사용 연차를 일괄 소멸시키겠습니까?`, '이 작업은 되돌릴 수 없습니다.')) {
            try {
                const response = await this.apiCall('/api/admin/leaves/expire-unused', { method: 'POST', body: { year } });
                Toast.success(response.message);
                this.loadBalances();
            } catch (error) {
                Toast.error(`소멸 실패: ${error.message}`);
            }
        }
    }

    handleTableClick(e) {
        const adjustBtn = e.target.closest('.adjust-btn');
        if (adjustBtn) {
            const employeeId = adjustBtn.dataset.employeeId;
            const employeeName = adjustBtn.dataset.employeeName;
            document.getElementById('adjustment_employee_id').value = employeeId;
            document.getElementById('adjustment-employee-name').textContent = employeeName;
            this.elements.adjustmentForm.reset();
            this.state.adjustmentModal.show();
        }
    }

    async handleAdjustmentSubmit(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this.elements.adjustmentForm));
        data.year = this.elements.yearFilter.value;
        data.employee_id = parseInt(data.employee_id);
        data.days = parseFloat(data.days);

        try {
            const response = await this.apiCall(`/api/admin/leaves/adjust`, { method: 'POST', body: data });
            Toast.success('수동 조정이 완료되었습니다.');
            this.state.adjustmentModal.hide();
            this.loadBalances();
        } catch (error) {
            Toast.error(`오류: ${error.message}`);
        }
    }
}

new LeaveGrantingPage();
