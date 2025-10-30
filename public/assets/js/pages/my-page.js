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
        this.elements.container.addEventListener('click', (e) => {
            const requestLeaveBtn = e.target.closest('#request-leave-btn');
            const cancelLeaveBtn = e.target.closest('.cancel-leave-btn');

            if (requestLeaveBtn) this.openRequestModal();
            else if (cancelLeaveBtn) this.handleCancelClick(e);
        });

        if (this.elements.requestForm) {
            this.elements.leaveSubtypeSelect.addEventListener('change', () => this.handleSubtypeChange());
            this.elements.startDateInput.addEventListener('change', () => this.handleDateChange());
            this.elements.endDateInput.addEventListener('change', () => this.handleDateChange());
            this.elements.requestForm.addEventListener('submit', (e) => this.handleRequestSubmit(e));
        }
    }

    async loadInitialData() {
        // ... 데이터 로딩 로직 (이전과 동일)
    }

    renderDashboard(isEditMode = false) {
        // ... 프로필 렌더링 로직 (이전과 동일)

        // 연차 카드 렌더링
        const leaveCard = this.renderLeaveCard();
        this.elements.container.innerHTML = `... ${leaveCard} ...`; // Simplified for brevity

        this.renderLeaveData();
    }

    renderLeaveCard() {
        // ... 연차 카드 HTML 구조 (이전과 동일)
    }

    renderLeaveData() {
        // ... 연차 데이터 렌더링 로직 (이전과 동일)
    }

    handleSubtypeChange() {
        const isHalfDay = this.elements.leaveSubtypeSelect.value.startsWith('half_day');
        this.elements.endDateInput.disabled = isHalfDay;
        this.elements.daysCountInput.readOnly = true;

        if (isHalfDay) {
            if (this.elements.startDateInput.value) this.elements.endDateInput.value = this.elements.startDateInput.value;
            this.elements.daysCountInput.value = 0.5;
        } else {
            this.calculateDays();
        }
    }

    handleDateChange() {
        if (this.elements.leaveSubtypeSelect.value.startsWith('half_day')) {
            this.elements.endDateInput.value = this.elements.startDateInput.value;
        }
        this.calculateDays();
    }

    calculateDays() {
        // 클라이언트 사이드에서 간단히 일수 계산 (주말 제외 등 복잡한 로직은 서버에서)
        const startDate = new Date(this.elements.startDateInput.value);
        const endDate = new Date(this.elements.endDateInput.value);
        if (startDate > endDate) {
            this.elements.daysCountInput.value = 0;
            return;
        }
        const diffTime = Math.abs(endDate - startDate);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        this.elements.daysCountInput.value = diffDays;
    }

    openRequestModal() {
        this.elements.requestForm.reset();
        this.handleSubtypeChange();
        this.state.requestModal.show();
    }

    async handleRequestSubmit(e) {
        // ... (API 호출 로직, 이전과 동일)
    }

    async handleCancelClick(e) {
        const button = e.target.closest('.cancel-leave-btn');
        const leaveId = button.dataset.id;
        const status = button.dataset.status;

        if (status === 'approved') {
            const { value: reason } = await Swal.fire({ title: '승인된 연차 취소 요청', input: 'textarea', inputLabel: '취소 사유', showCancelButton: true });
            if (reason) {
                await this.apiCall(`/leaves/${leaveId}/cancel`, { method: 'POST', body: { reason } });
                this.loadInitialData();
            }
        } else if (status === 'pending') {
            if (await Confirm.fire('이 신청을 취소하시겠습니까?')) {
                await this.apiCall(`/leaves/${leaveId}/cancel`, { method: 'POST' });
                this.loadInitialData();
            }
        }
    }
}

new MyPage();
