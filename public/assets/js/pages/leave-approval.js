class LeaveApprovalPage extends BasePage {
    constructor() {
        super();
        this.elements = {};
    }

    initializeApp() {
        this.cacheDOMElements();
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
        await this.loadFilterOptions();
        this.loadAllTabs();
    }

    loadAllTabs() {
        Object.keys(this.elements.bodies).forEach(status => this.loadRequestsByStatus(status));
    }

    async loadFilterOptions() {
        try {
            const response = await this.apiCall('/organization/managable-departments'); // '/api' 제거
            // ...
        } catch (error) {
            // ...
        }
    }

    handleFilterChange() {
        const activeTab = document.querySelector('.nav-link.active');
        const status = this.getStatusFromTab(activeTab);
        this.loadRequestsByStatus(status);
    }

    async loadRequestsByStatus(status) {
        const tableBody = this.elements.bodies[status];
        if (!tableBody) return;
        tableBody.innerHTML = `<tr><td colspan="7" class="text-center"><span class="spinner-border spinner-border-sm"></span>...</td></tr>`;

        const year = this.elements.yearFilter.value;
        const departmentId = this.elements.departmentFilter.value;

        try {
            const response = await this.apiCall(`/admin/leaves/requests?status=${status}&year=${year}&department_id=${departmentId}`); // '/api' 제거
            this.renderTable(status, response.data);
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">목록 로딩 실패</td></tr>`;
        }
    }

    renderTable(status, data) {
        // ... (이전과 동일)
    }

    getTableRowHTML(status, item) {
        // ... (이전과 동일)
    }

    async handleTableClick(e) {
        // ... (이전과 동일)
    }

    async handleAction(action, id, body = null) {
        try {
            const url = `/admin/leaves/requests/${id}/${action}`; // '/api' 제거
            const response = await this.apiCall(url, { method: 'POST', body });
            Toast.success(response.message || '처리가 완료되었습니다.');
            this.loadAllTabs();
        } catch (error) {
             Toast.error(`처리 중 오류 발생: ${error.message}`);
        }
    }

    getStatusFromTab(tabElement) {
        return tabElement.getAttribute('href').replace('#', '').replace('-tab-pane', '');
    }
}

new LeaveApprovalPage();
