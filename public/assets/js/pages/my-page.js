class MyPage extends BasePage {
    constructor() {
        super();
        this.elements = {};
        this.state = {
            profileData: null,
            leaveBalance: null,
            leaveRequests: []
        };
    }

    initializeApp() {
        this.cacheDOMElements();
        if (!this.elements.container) return;

        this.state.requestModal = new bootstrap.Modal(this.elements.requestModalEl);
        this.loadInitialData();
        this.setupEventListeners();
    }

    cacheDOMElements() {
        this.elements = {
            container: document.getElementById('profile-container'),
            requestModalEl: document.getElementById('leave-request-modal'),
            requestForm: document.getElementById('leave-request-form'),
            leaveSubtypeSelect: document.getElementById('leave_subtype'),
            startDateInput: document.getElementById('start_date'),
            endDateInput: document.getElementById('end_date'),
            daysCountInput: document.getElementById('days_count'),
            feedbackDiv: document.getElementById('leave-date-feedback')
        };
    }

    setupEventListeners() {
        // ... (이벤트 리스너 로직은 크게 변경되지 않음)
    }

    async loadInitialData() {
        this.elements.container.innerHTML = `<div class="text-center"><div class="spinner-border text-primary"></div><p>데이터 로딩 중...</p></div>`;
        try {
            const [profileRes, balanceRes, requestsRes] = await Promise.all([
                this.apiCall('/api/profile'),
                this.apiCall(`/api/leaves/my-balance?year=${new Date().getFullYear()}`),
                this.apiCall(`/api/leaves?year=${new Date().getFullYear()}`)
            ]);

            this.state.profileData = profileRes.data;
            this.state.leaveBalance = balanceRes.data;
            this.state.leaveRequests = requestsRes.data;

            this.renderDashboard();
        } catch (error) {
            this.elements.container.innerHTML = `<div class="alert alert-danger">데이터 로딩 실패: ${error.message}</div>`;
        }
    }

    renderDashboard(isEditMode = false) {
        // ... (프로필 렌더링 로직은 변경되지 않음)
        const leaveCard = this.renderLeaveCard();
        // ...
        this.elements.container.innerHTML = `... ${leaveCard} ...`; // Simplified
        this.renderLeaveData();
    }

    renderLeaveCard() {
        return `
            <div class="card shadow mb-4">
                <div class="card-header d-flex py-3 align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">나의 연차 현황</h6>
                    <button id="request-leave-btn" class="btn btn-primary btn-sm ms-auto"><i class="bx bx-plus"></i> 연차 신청</button>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <h5 class="card-title">잔여 연차</h5>
                        <p id="summary-remaining" class="display-5 fw-bold text-primary">--</p>
                        <small id="summary-total" class="text-muted">(총 --일 중 --일 사용)</small>
                    </div>
                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item d-flex justify-content-between align-items-center">기본+근속 연차<span id="details-base" class="badge bg-primary rounded-pill">--</span></li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">조정 연차<span id="details-adjustment" class="badge bg-info rounded-pill">--</span></li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">월차<span id="details-monthly" class="badge bg-secondary rounded-pill">--</span></li>
                    </ul>
                    <hr>
                    <h6 class="card-title">연차 사용 내역 (<span id="current-year">${new Date().getFullYear()}</span>년)</h6>
                    <div id="leave-history-container" style="max-height: 250px; overflow-y: auto;">
                        <div id="leave-history-body"><div class="text-center"><span class="spinner-border spinner-border-sm"></span></div></div>
                    </div>
                </div>
            </div>`;
    }

    renderLeaveData() {
        const balance = this.state.leaveBalance;
        if (balance) {
            const totalGranted = parseFloat(balance.base_leave) + parseFloat(balance.seniority_leave) + parseFloat(balance.adjustment_leave) + parseFloat(balance.monthly_leave);
            const used = parseFloat(balance.used_leave);
            const remaining = totalGranted - used;
            document.getElementById('summary-remaining').textContent = `${remaining.toFixed(2)}일`;
            document.getElementById('summary-total').textContent = `(총 ${totalGranted.toFixed(2)}일 중 ${used.toFixed(2)}일 사용)`;
            document.getElementById('details-base').textContent = `${(parseFloat(balance.base_leave) + parseFloat(balance.seniority_leave)).toFixed(2)}일`;
            document.getElementById('details-adjustment').textContent = `${parseFloat(balance.adjustment_leave).toFixed(2)}일`;
            document.getElementById('details-monthly').textContent = `${parseFloat(balance.monthly_leave).toFixed(2)}일`;
        } else {
            // ... (데이터 없는 경우 처리)
        }

        const requests = this.state.leaveRequests;
        const historyBodyEl = document.getElementById('leave-history-body');
        if (!requests || requests.length === 0) {
            historyBodyEl.innerHTML = '<div class="text-center text-muted p-3">사용 내역이 없습니다.</div>';
            return;
        }
        // ... (신청 내역 렌더링 로직 - 이전과 유사하게 구현)
    }

    // ... (handleRequestSubmit, handleHistoryClick 등 API 호출 로직 수정)
    async handleRequestSubmit(e) {
        e.preventDefault();
        const formData = new FormData(this.elements.requestForm);
        const data = {
            start_date: formData.get('start_date'),
            end_date: formData.get('end_date'),
            leave_subtype: formData.get('leave_subtype'),
            reason: formData.get('reason'),
            days_count: parseFloat(formData.get('days_count'))
        };

        // ... (유효성 검사)

        try {
            await this.apiCall('/api/leaves', { method: 'POST', body: data });
            Toast.success('연차 신청이 완료되었습니다.');
            this.state.requestModal.hide();
            this.loadInitialData();
        } catch (error) {
            Toast.error(`신청 실패: ${error.message}`);
        }
    }
}

new MyPage();
