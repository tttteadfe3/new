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
            'approved': document.getElementById('approved-requests-body'),
            'rejected': document.getElementById('rejected-requests-body'),
            'cancellation_requested': document.getElementById('cancellation-requests-body')
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
        // ... (부서 목록 로딩, 변경 없음)
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
            // 통합된 새 API 엔드포인트 사용
            const response = await this.apiCall(`/api/admin/leaves/requests?status=${status}&year=${year}&department_id=${departmentId}`);
            this.renderTable(status, response.data);
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">목록 로딩 실패</td></tr>`;
        }
    }

    renderTable(status, data) {
        // ... (getTableRowHTML을 호출하는 부분은 변경 없음)
    }

    getTableRowHTML(status, item) {
        // ... (이전과 동일한 HTML 구조 반환, 약간의 필드명 변경만 적용)
        return `...`;
    }

    async handleTableClick(e) {
        const button = e.target.closest('button');
        if (!button) return;
        const id = button.dataset.id;
        if (!id) return;

        if (button.classList.contains('approve-btn')) {
            if (await Confirm.fire('연차 신청을 승인하시겠습니까?')) {
                this.handleAction('approve', id);
            }
        } else if (button.classList.contains('reject-btn')) {
            const { value: reason } = await Swal.fire({ title: '연차 신청 반려', input: 'text', inputLabel: '반려 사유', showCancelButton: true, inputValidator: v => !v && '사유 필수' });
            if (reason) this.handleAction('reject', id, { reason });
        } else if (button.classList.contains('approve-cancel-btn')) {
            if (await Confirm.fire('연차 취소를 승인하시겠습니까?')) {
                this.handleAction('approve-cancellation', id);
            }
        } else if (button.classList.contains('reject-cancel-btn')) {
            if (await Confirm.fire('연차 취소를 반려하시겠습니까?')) {
                this.handleAction('reject-cancellation', id);
            }
        }
    }

    async handleAction(action, id, body = null) {
        try {
            const url = `/api/admin/leaves/requests/${id}/${action}`;
            const response = await this.apiCall(url, { method: 'POST', body });
            Toast.success(response.message || '처리가 완료되었습니다.');
            this.loadAllTabs(); // 모든 탭 새로고침
        } catch (error) {
             Toast.error(`처리 중 오류 발생: ${error.message}`);
        }
    }

    getStatusFromTab(tabElement) {
        return tabElement.getAttribute('href').replace('#', '').replace('-tab-pane', '');
    }
}

new LeaveApprovalPage();
