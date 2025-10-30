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
        // ... (이전과 동일)
    }

    setupEventListeners() {
        // ... (이전과 동일)
    }

    async loadInitialData() {
        await this.loadDepartments();
        await this.loadBalances();
    }

    async loadDepartments() {
        // ... (부서 목록 로딩, '/api' 제거 필요)
    }

    async loadBalances() {
        this.elements.tableBody.innerHTML = `<tr><td colspan="11" class="text-center"><span class="spinner-border spinner-border-sm"></span>...</td></tr>`;
        try {
            const year = this.elements.yearFilter.value;
            const departmentId = this.elements.departmentFilter.value;
            const response = await this.apiCall(`/admin/leaves/balances?year=${year}&department_id=${departmentId}`); // '/api' 제거
            this.renderTable(response.data);
        } catch (error) {
            this.elements.tableBody.innerHTML = `<tr><td colspan="11" class="text-center text-danger">목록 로딩 실패</td></tr>`;
        }
    }

    renderTable(data) {
        // ... (이전과 동일)
    }

    async handleGrantAll() {
        const year = this.elements.yearFilter.value;
        if (await Confirm.fire(`${year}년 연차를 일괄 부여하시겠습니까?`)) {
            try {
                const response = await this.apiCall('/admin/leaves/grant-annual', { method: 'POST', body: { year } }); // '/api' 제거
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
                const response = await this.apiCall('/admin/leaves/expire-unused', { method: 'POST', body: { year } }); // '/api' 제거
                Toast.success(response.message);
                this.loadBalances();
            } catch (error) {
                Toast.error(`소멸 실패: ${error.message}`);
            }
        }
    }

    handleTableClick(e) {
        // ... (이전과 동일)
    }

    async handleAdjustmentSubmit(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this.elements.adjustmentForm));
        data.year = this.elements.yearFilter.value;
        data.employee_id = parseInt(data.employee_id);
        data.days = parseFloat(data.days);

        try {
            const response = await this.apiCall(`/admin/leaves/adjust`, { method: 'POST', body: data }); // '/api' 제거
            Toast.success('수동 조정이 완료되었습니다.');
            this.state.adjustmentModal.hide();
            this.loadBalances();
        } catch (error) {
            Toast.error(`오류: ${error.message}`);
        }
    }
}

new LeaveGrantingPage();
