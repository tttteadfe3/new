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
        this.elements.tabs = document.querySelectorAll('a[data-bs-toggle="tab"]');
        this.elements.yearFilter = document.getElementById('year-filter');
        this.elements.departmentFilter = document.getElementById('department-filter');
        this.elements.bodies = {
            'pending': document.getElementById('pending-requests-body'),
            'cancellation_requested': document.getElementById('cancellation_requested-requests-body'),
            'approved': document.getElementById('approved-requests-body'),
            'rejected': document.getElementById('rejected-requests-body'),
        };
    }

    setupEventListeners() {
        this.elements.tabs.forEach(tab => {
            tab.addEventListener('shown.bs.tab', (event) => this.handleFilterChange(event));
        });
        [this.elements.yearFilter, this.elements.departmentFilter].forEach(filter => {
            filter.addEventListener('change', (event) => this.handleFilterChange(event));
        });
        Object.values(this.elements.bodies).forEach(body => {
            if(body) body.addEventListener('click', (e) => this.handleTableClick(e));
        });
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
            const response = await this.apiCall('/organization/managable-departments');
            this.elements.departmentFilter.innerHTML = '<option value="">전체 부서</option>';
            response.data.forEach(dept => {
                this.elements.departmentFilter.add(new Option(dept.name, dept.id));
            });
        } catch (error) { Toast.error('부서 목록 로딩 실패'); }
    }

    handleFilterChange() {
        const activeTab = document.querySelector('.nav-link.active');
        if (activeTab) {
            const status = this.getStatusFromTab(activeTab);
            this.loadRequestsByStatus(status);
        }
    }

    async loadRequestsByStatus(status) {
        // ... (API 호출 로직, 이전과 동일)
    }

    renderTable(status, data) {
        const tableBody = this.elements.bodies[status];
        if (!tableBody) return;
        if (data.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center">내역 없음</td></tr>`;
            return;
        }
        tableBody.innerHTML = data.map(item => this.getTableRowHTML(status, item)).join('');
    }

    getTableRowHTML(status, item) {
        const approver = item.approver_name || 'N/A';
        const subtype = { 'full_day': '연차', 'half_day_am': '오전반차', 'half_day_pm': '오후반차' }[item.leave_subtype] || item.leave_subtype;

        let cols = `
            <td>${item.employee_name}</td>
            <td>${item.department_name || ''}</td>
            <td>${item.start_date} ~ ${item.end_date}</td>
            <td>${item.days_count}</td>
            <td>${subtype}</td>
        `;
        let actions = '';

        switch(status) {
            case 'pending':
                cols += `<td>${item.reason || ''}</td><td>${new Date(item.created_at).toLocaleDateString()}</td>`;
                actions = `<button class="btn btn-success btn-sm approve-btn" data-id="${item.id}">승인</button>
                           <button class="btn btn-danger btn-sm reject-btn ms-1" data-id="${item.id}">반려</button>`;
                break;
            case 'cancellation_requested':
                cols += `<td>${item.cancellation_reason || ''}</td><td>${new Date(item.updated_at).toLocaleDateString()}</td>`;
                actions = `<button class="btn btn-success btn-sm approve-cancel-btn" data-id="${item.id}">취소승인</button>
                           <button class="btn btn-danger btn-sm reject-cancel-btn ms-1" data-id="${item.id}">취소반려</button>`;
                break;
            case 'approved':
                cols += `<td>${new Date(item.updated_at).toLocaleDateString()}</td><td>${approver}</td>`;
                break;
            case 'rejected':
                cols += `<td>${item.rejection_reason || ''}</td><td>${approver}</td>`;
                break;
        }

        return `<tr>${cols}${actions ? `<td>${actions}</td>` : ''}</tr>`;
    }

    async handleTableClick(e) {
        // ... (이전과 동일)
    }

    async handleAction(action, id, body = null) {
        // ... (이전과 동일)
    }

    getStatusFromTab(tabElement) {
        return tabElement.getAttribute('href').replace('#', '').replace('-tab-pane', '');
    }
}

new LeaveApprovalPage();
