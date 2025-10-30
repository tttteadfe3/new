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
        try {
            const response = await this.apiCall('/organization/managable-departments');
            this.elements.departmentFilter.innerHTML = '<option value="">전체 부서</option>';
            response.data.forEach(dep => {
                this.elements.departmentFilter.add(new Option(dep.name, dep.id));
            });
        } catch (error) { Toast.error('부서 목록 로딩 실패'); }
    }

    async loadBalances() {
        // ... (API 호출 로직, 이전과 동일)
    }

    renderTable(data) {
        // ... (테이블 렌더링 로직, 이전과 동일)
    }

    async handleGrantAll() {
        // ... (일괄 부여 로직, 이전과 동일)
    }

    async handleExpireAll() {
        // ... (일괄 소멸 로직, 이전과 동일)
    }

    handleTableClick(e) {
        // ... (테이블 클릭 이벤트 로직, 이전과 동일)
    }

    async handleAdjustmentSubmit(e) {
        // ... (수동 조정 제출 로직, 이전과 동일)
    }
}

new LeaveGrantingPage();
