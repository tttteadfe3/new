class MyLeavesPage extends BasePage {
    constructor() {
        super();
        this.elements = {};
        this.API_URL_REQUESTS = '/api/leave-requests';
        this.API_URL_BALANCE = '/api/leave/balance'; // TODO: Get balance API endpoint needs to be created
    }

    initializeApp() {
        this.cacheDOMElements();
        this.setupEventListeners();
        this.loadInitialData();
    }

    cacheDOMElements() {
        // 잔여 연차 표시 영역
        this.elements.annualLeaveBalance = document.getElementById('annual-leave-balance');
        this.elements.monthlyLeaveBalance = document.getElementById('monthly-leave-balance');

        // 연차 신청 폼
        this.elements.leaveRequestForm = document.getElementById('leave-request-form');
        this.elements.leaveTypeSelect = document.getElementById('leave-type');
        this.elements.startDateInput = document.getElementById('start-date');
        this.elements.endDateInput = document.getElementById('end-date');
        this.elements.requestUnitSelect = document.getElementById('request-unit');
        this.elements.reasonTextarea = document.getElementById('reason');

        // 신청 목록 테이블
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
            console.error('Failed to load leave balance:', error);
            Toast.error('잔여 연차 정보를 불러오는데 실패했습니다.');
            this.elements.annualLeaveBalance.textContent = 'N/A';
            this.elements.monthlyLeaveBalance.textContent = 'N/A';
        }
    }

    async loadMyRequests() {
        try {
            const response = await this.apiCall(this.API_URL_REQUESTS);
            this.renderRequestsTable(response.data);
        } catch (error) {
            console.error('Failed to load my requests:', error);
            this.elements.requestsTableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">신청 목록을 불러오는데 실패했습니다.</td></tr>`;
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
                <td>${req.days_count}일</td>
                <td><span class="badge bg-info">${req.status}</span></td>
                <td>${new Date(req.created_at).toLocaleDateString()}</td>
                <td>
                    ${req.status === 'approved' ? '<button class="btn btn-warning btn-sm cancel-btn" data-id="' + req.id + '">취소 요청</button>' : ''}
                </td>
            </tr>
        `).join('');
    }

    async handleSubmitRequest(event) {
        event.preventDefault();

        const daysCount = this.calculateDays();
        if (daysCount <= 0) {
            Toast.error('종료일은 시작일보다 빠를 수 없습니다.');
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
            Toast.success(response.message || '휴가 신청이 완료되었습니다.');
            this.elements.leaveRequestForm.reset();
            this.loadMyRequests(); // Refresh the list
            this.loadLeaveBalance(); // Refresh the balance
        } catch (error) {
            Toast.error(error.message || '휴가 신청 중 오류가 발생했습니다.');
        }
    }

    async handleCancelRequest(requestId) {
        const result = await Confirm.fire('휴가 취소 요청', '정말 이 휴가에 대한 취소를 요청하시겠습니까?');
        if (!result.isConfirmed) return;

        try {
            const url = `${this.API_URL_REQUESTS}/${requestId}/cancel`;
            const response = await this.apiCall(url, { method: 'POST' });
            Toast.success(response.message || '취소 요청이 접수되었습니다.');
            this.loadMyRequests();
        } catch (error) {
            Toast.error(error.message || '취소 요청 중 오류가 발생했습니다.');
        }
    }

    calculateDays() {
        const start = new Date(this.elements.startDateInput.value);
        const end = new Date(this.elements.endDateInput.value);
        const unit = this.elements.requestUnitSelect.value;

        if (unit === 'half_am' || unit === 'half_pm') {
            return 0.5;
        }

        // Add 1 to include both start and end dates
        const diffTime = end - start;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        return diffDays;
    }
}

new MyLeavesPage();
