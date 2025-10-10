class LeaveHistoryAdminApp extends BaseApp {
    constructor() {
        super({
            API_URL: '/leaves_admin/history'
        });
        this.elements = {};
    }

    initializeApp() {
        this.cacheDOMElements();
        this.setupEventListeners();
        // Initial load is triggered by the employee dropdown change, so no initial data load here.
    }

    cacheDOMElements() {
        this.elements = {
            employeeSelect: document.getElementById('employee-select'),
            yearSelect: document.getElementById('year-select'),
            historyDisplay: document.getElementById('history-display'),
            entitlementSummary: document.getElementById('entitlement-summary'),
            leaveHistoryBody: document.getElementById('leave-history-body'),
        };
    }

    setupEventListeners() {
        this.elements.employeeSelect.addEventListener('change', () => this.loadHistory());
        this.elements.yearSelect.addEventListener('change', () => this.loadHistory());
    }

    async loadHistory() {
        const employeeId = this.elements.employeeSelect.value;
        const year = this.elements.yearSelect.value;

        if (!employeeId) {
            this.elements.historyDisplay.classList.add('d-none');
            return;
        }

        this.elements.historyDisplay.classList.remove('d-none');
        this.elements.entitlementSummary.innerHTML = `<span class="spinner-border spinner-border-sm"></span> 불러오는 중...`;
        this.elements.leaveHistoryBody.innerHTML = `<tr><td colspan="6" class="text-center"><span class="spinner-border spinner-border-sm"></span></td></tr>`;

        try {
            const response = await this.apiCall(`${this.config.API_URL}/${employeeId}?year=${year}`);
            this.renderHistory(response.data);
        } catch (error) {
            console.error('Error loading history:', error);
            this.elements.entitlementSummary.innerHTML = `<span class="text-danger">오류: ${error.message}</span>`;
            this.elements.leaveHistoryBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">내역 로딩 실패</td></tr>`;
        }
    }

    renderHistory(data) {
        if (data.entitlement) {
            const { total_days, used_days } = data.entitlement;
            const remaining_days = parseFloat(total_days) - parseFloat(used_days);
            this.elements.entitlementSummary.innerHTML = `
                총 <strong>${total_days}</strong>일 부여 /
                <strong>${used_days}</strong>일 사용 /
                <span class="fw-bold ${remaining_days < 0 ? 'text-danger' : 'text-primary'}">${remaining_days.toFixed(1)}</span>일 남음
            `;
        } else {
            this.elements.entitlementSummary.innerHTML = `<span class="text-muted">${this.elements.yearSelect.value}년 부여 내역 없음</span>`;
        }

        if (!data.leaves || data.leaves.length === 0) {
            this.elements.leaveHistoryBody.innerHTML = `<tr><td colspan="6" class="text-center">사용 내역이 없습니다.</td></tr>`;
            return;
        }

        const statusBadges = { pending: 'bg-warning', approved: 'bg-success', rejected: 'bg-danger', cancelled: 'bg-secondary', cancellation_requested: 'bg-info' };
        const statusText = { pending: '대기', approved: '승인', rejected: '반려', cancelled: '취소', cancellation_requested: '취소요청' };

        const rowsHtml = data.leaves.map(leave => `
            <tr>
                <td>${leave.leave_type}</td>
                <td>${leave.start_date} ~ ${leave.end_date}</td>
                <td>${leave.days_count}</td>
                <td><span class="badge ${statusBadges[leave.status] || 'bg-light text-dark'}">${statusText[leave.status] || leave.status}</span></td>
                <td>${new Date(leave.created_at).toLocaleDateString()}</td>
                <td>${leave.reason || ''}</td>
            </tr>
        `).join('');
        this.elements.leaveHistoryBody.innerHTML = rowsHtml;
    }
}

new LeaveHistoryAdminApp();