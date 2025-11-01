class MyLeavesPage extends BasePage {
    constructor() {
        super();
        this.elements = {};
        this.API_URL_REQUESTS = '/api/leave-requests';
        this.API_URL_BALANCE = '/api/leave/balance';
    }

    initializeApp() {
        this.cacheDOMElements();
        this.setupEventListeners();
        this.loadInitialData();
    }

    cacheDOMElements() {
        this.elements.annualLeaveBalance = document.getElementById('annual-leave-balance');
        this.elements.monthlyLeaveBalance = document.getElementById('monthly-leave-balance');
        this.elements.leaveRequestForm = document.getElementById('leave-request-form');
        this.elements.leaveTypeSelect = document.getElementById('leave-type');
        this.elements.startDateInput = document.getElementById('start-date');
        this.elements.endDateInput = document.getElementById('end-date');
        this.elements.requestUnitSelect = document.getElementById('request-unit');
        this.elements.reasonTextarea = document.getElementById('reason');
        this.elements.requestsTableBody = document.getElementById('my-requests-tbody');
    }

    setupEventListeners() {
        this.elements.leaveRequestForm.addEventListener('submit', (e) => this.handleSubmitRequest(e));
        this.elements.requestsTableBody.addEventListener('click', (e) => {
            if (e.target.classList.contains('cancel-btn')) {
                const requestId = e.target.dataset.id;
                this.handleCancelRequest(requestId);
            }
        });
    }

    async loadInitialData() {
        this.loadLeaveBalance();
        this.loadMyRequests();
    }

    async loadLeaveBalance() {
        try {
            const response = await this.apiCall(this.API_URL_BALANCE);
            this.elements.annualLeaveBalance.textContent = response.data.annual.toFixed(1);
            this.elements.monthlyLeaveBalance.textContent = response.data.monthly.toFixed(1);
        } catch (error) {
            Toast.error('잔여 연차 정보를 불러오는데 실패했습니다.');
        }
    }

    async loadMyRequests() {
        try {
            const response = await this.apiCall(this.API_URL_REQUESTS);
            this.renderRequestsTable(response.data);
        } catch (error) {
            this.elements.requestsTableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">신청 목록 로딩 실패.</td></tr>`;
        }
    }

    renderRequestsTable(requests) {
        if (!requests || requests.length === 0) {
            this.elements.requestsTableBody.innerHTML = `<tr><td colspan="6" class="text-center">신청 내역이 없습니다.</td></tr>`;
            return;
        }
        this.elements.requestsTableBody.innerHTML = requests.map(req => `
            <tr>
                <td>${req.leave_type === 'annual' ? '연차' : '월차'}</td>
                <td>${req.start_date} ~ ${req.end_date}</td>
                <td>${req.days_count}</td>
                <td><span class="badge bg-info">${req.status}</span></td>
                <td>${new Date(req.created_at).toLocaleDateString()}</td>
                <td>${req.status === 'approved' ? `<button class="btn btn-warning btn-sm cancel-btn" data-id="${req.id}">취소 요청</button>` : ''}</td>
            </tr>
        `).join('');
    }

    async handleSubmitRequest(event) {
        event.preventDefault();
        const daysCount = this.calculateDays();
        if (daysCount <= 0) {
            Toast.error('기간을 올바르게 선택해주세요.');
            return;
        }
        const formData = {
            leave_type: this.elements.leaveTypeSelect.value,
            start_date: this.elements.startDateInput.value,
            end_date: this.elements.endDateInput.value,
            request_unit: this.elements.requestUnitSelect.value,
            reason: this.elements.reasonTextarea.value,
            days_count: daysCount
        };
        try {
            const response = await this.apiCall(this.API_URL_REQUESTS, { method: 'POST', body: formData });
            Toast.success(response.message);
            this.elements.leaveRequestForm.reset();
            this.loadMyRequests();
            this.loadLeaveBalance();
        } catch (error) {
            Toast.error(error.message);
        }
    }

    async handleCancelRequest(requestId) {
        const result = await Confirm.fire('휴가 취소 요청', '관리자 승인 후 최종 취소됩니다. 요청하시겠습니까?');
        if (!result.isConfirmed) return;
        try {
            const response = await this.apiCall(`${this.API_URL_REQUESTS}/${requestId}/cancel`, { method: 'POST' });
            Toast.success(response.message);
            this.loadMyRequests();
        } catch (error) {
            Toast.error(error.message);
        }
    }

    calculateDays() {
        const start = new Date(this.elements.startDateInput.value);
        const end = new Date(this.elements.endDateInput.value);
        const unit = this.elements.requestUnitSelect.value;
        if (unit === 'half_am' || unit === 'half_pm') {
            this.elements.endDateInput.value = this.elements.startDateInput.value;
            return 0.5;
        }
        if (start > end) return 0;
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        return diffDays;
    }
}
new MyLeavesPage();
