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
        // ... (이전과 동일)
    }

    setupEventListeners() {
        // ... (이전과 동일)
    }

    async loadInitialData() {
        this.elements.container.innerHTML = `<div class="text-center"><div class="spinner-border text-primary"></div><p>데이터 로딩 중...</p></div>`;
        try {
            const [profileRes, balanceRes, requestsRes] = await Promise.all([
                this.apiCall('/profile'), // '/api' 제거
                this.apiCall(`/leaves/my-balance?year=${new Date().getFullYear()}`), // '/api' 제거
                this.apiCall(`/leaves?year=${new Date().getFullYear()}`) // '/api' 제거
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
        // ... (이전과 동일)
    }

    renderLeaveCard() {
        // ... (이전과 동일)
    }

    renderLeaveData() {
        // ... (이전과 동일)
    }

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
            await this.apiCall('/leaves', { method: 'POST', body: data }); // '/api' 제거
            Toast.success('연차 신청이 완료되었습니다.');
            this.state.requestModal.hide();
            this.loadInitialData();
        } catch (error) {
            Toast.error(`신청 실패: ${error.message}`);
        }
    }

    // ... (handleHistoryClick 등 다른 메소드들도 마찬가지로 수정 필요)
}

new MyPage();
