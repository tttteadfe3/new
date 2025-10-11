class MyLeavePage extends BasePage {
    constructor() {
        super({
            API_URL: '/leaves'
        });
        this.elements = {};
    }

    initializeApp() {
        this.cacheDOMElements();
        if (!this.elements.summaryRemaining) return; // This page might not be for all users

        this.state.requestModal = new bootstrap.Modal(this.elements.requestModalEl);
        this.setupEventListeners();
        this.loadInitialData();
    }

    cacheDOMElements() {
        this.elements = {
            summaryRemaining: document.getElementById('summary-remaining'),
            summaryTotal: document.getElementById('summary-total'),
            historyBody: document.getElementById('leave-history-body'),
            currentYearSpan: document.getElementById('current-year'),
            requestLeaveBtn: document.getElementById('request-leave-btn'),
            requestModalEl: document.getElementById('leave-request-modal'),
            requestForm: document.getElementById('leave-request-form'),
            leaveTypeSelect: document.getElementById('leave_type'),
            startDateInput: document.getElementById('start_date'),
            endDateInput: document.getElementById('end_date'),
            daysCountInput: document.getElementById('days_count'),
            feedbackDiv: document.getElementById('leave-date-feedback')
        };
    }

    setupEventListeners() {
        if (this.elements.requestLeaveBtn) {
            this.elements.requestLeaveBtn.addEventListener('click', () => this.openRequestModal());
        }
        if (this.elements.requestForm) {
            this.elements.leaveTypeSelect.addEventListener('change', () => this.handleLeaveTypeChange());
            this.elements.startDateInput.addEventListener('change', () => this.handleDateChange());
            this.elements.endDateInput.addEventListener('change', () => this.handleDateChange());
            this.elements.requestForm.addEventListener('submit', (e) => this.handleRequestSubmit(e));
        }
        this.elements.historyBody.addEventListener('click', (e) => this.handleHistoryClick(e));
    }

    async loadInitialData() {
        const year = new Date().getFullYear();
        this.elements.currentYearSpan.textContent = year;
        this.elements.summaryRemaining.textContent = '--';
        this.elements.summaryTotal.textContent = '(총 --일 중 --일 사용)';
        this.elements.historyBody.innerHTML = '<div><span class="spinner-border spinner-border-sm"></span> 불러오는 중...</div>';

        try {
            const response = await this.apiCall(`${this.config.API_URL}?year=${year}`);
            this.renderStatus(response.data);
        } catch (error) {
            console.error('Error loading status:', error);
            this.elements.summaryRemaining.textContent = '오류';
            this.elements.summaryTotal.textContent = `(${error.message})`;
            this.elements.historyBody.innerHTML = `<div class="text-danger">연차 정보를 불러오는 데 실패했습니다.</div>`;
        }
    }

    renderStatus(data) {
        if (data.entitlement) {
            const { total_days, used_days } = data.entitlement;
            const remaining_days = parseFloat(total_days) - parseFloat(used_days);
            this.elements.summaryRemaining.textContent = `${remaining_days.toFixed(1)}일`;
            this.elements.summaryTotal.textContent = `(총 ${total_days}일 중 ${used_days}일 사용)`;
        } else {
            this.elements.summaryRemaining.textContent = '0일';
            this.elements.summaryTotal.textContent = '(부여 내역 없음)';
        }

        if (!data.leaves || data.leaves.length === 0) {
            this.elements.historyBody.innerHTML = '<div>사용 내역이 없습니다.</div>';
            return;
        }

        const statusBadges = { pending: 'bg-warning', approved: 'bg-success', rejected: 'bg-danger', cancelled: 'bg-secondary', cancellation_requested: 'bg-info' };
        const statusText = { pending: '대기', approved: '승인', rejected: '반려', cancelled: '취소', cancellation_requested: '취소요청' };

        const rowsHtml = data.leaves.map(leave => {
            const canCancel = leave.status === 'pending' || leave.status === 'approved';
            const cancelButton = canCancel ? `<button class="btn btn-link btn-sm p-0 cancel-btn" data-id="${leave.id}" data-status="${leave.status}">취소</button>` : '';

            let reasonText = '';
            if (leave.status === 'rejected' && leave.rejection_reason) reasonText = `(반려: ${leave.rejection_reason})`;
            else if (leave.status === 'cancellation_requested' && leave.cancellation_reason) reasonText = `(취소요청: ${leave.cancellation_reason})`;
            else if (leave.status === 'approved' && leave.rejection_reason) reasonText = `(취소반려: ${leave.rejection_reason})`;
            else if (leave.reason) reasonText = `(사유: ${leave.reason})`;

            return `
                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                    <div>
                        <span class="fw-bold">${leave.start_date} ~ ${leave.end_date}</span> (${leave.days_count}일)
                        <small class="text-muted ms-2">${reasonText}</small>
                    </div>
                    <div>
                        <span class="badge ${statusBadges[leave.status] || 'bg-light text-dark'}">${statusText[leave.status] || leave.status}</span>
                        ${cancelButton}
                    </div>
                </div>`;
        }).join('');
        this.elements.historyBody.innerHTML = rowsHtml;
    }

    async calculateDays() {
        const startDate = this.elements.startDateInput.value;
        const endDate = this.elements.endDateInput.value;

        this.elements.feedbackDiv.textContent = '';
        if (!startDate || !endDate) return;

        if (new Date(startDate) > new Date(endDate)) {
            this.elements.feedbackDiv.textContent = '오류: 시작일은 종료일보다 늦을 수 없습니다.';
            return;
        }

        try {
            const response = await this.apiCall(`${this.config.API_URL}/calculate-days`, {
                method: 'POST',
                body: { start_date: startDate, end_date: endDate }
            });
            this.elements.daysCountInput.value = response.data.days;
        } catch (error) {
            this.elements.feedbackDiv.textContent = `오류: ${error.message}`;
        }
    }

    handleLeaveTypeChange() {
        const isHalfDay = this.elements.leaveTypeSelect.value === 'half_day';
        this.elements.endDateInput.disabled = isHalfDay;
        this.elements.daysCountInput.readOnly = isHalfDay;

        if (isHalfDay) {
            if (this.elements.startDateInput.value) this.elements.endDateInput.value = this.elements.startDateInput.value;
            this.elements.daysCountInput.value = 0.5;
        } else {
            this.elements.daysCountInput.readOnly = true;
            this.calculateDays();
        }
    }

    handleDateChange() {
        if (this.elements.leaveTypeSelect.value === 'half_day') {
            this.elements.endDateInput.value = this.elements.startDateInput.value;
        }
        this.calculateDays();
    }

    openRequestModal() {
        this.elements.requestForm.reset();
        this.handleLeaveTypeChange();
        this.elements.feedbackDiv.textContent = '시작일과 종료일을 선택하면 사용일수가 자동으로 계산됩니다.';
        this.state.requestModal.show();
    }

    async handleRequestSubmit(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this.elements.requestForm).entries());

        if (new Date(data.start_date) > new Date(data.end_date)) {
            Toast.error('시작일은 종료일보다 늦을 수 없습니다.');
            return;
        }
        if (!data.days_count || parseFloat(data.days_count) <= 0) {
            Toast.error('사용 일수를 계산하거나 입력해주세요.');
            return;
        }

        try {
            const response = await this.apiCall(this.config.API_URL, { method: 'POST', body: data });
            Toast.success('연차 신청이 완료되었습니다.');
            this.state.requestModal.hide();
            this.loadInitialData();
        } catch (error) {
            Toast.error(`신청 실패: ${error.message}`);
        }
    }

    async handleHistoryClick(e) {
        const button = e.target.closest('button.cancel-btn');
        if (!button) return;

        const leaveId = button.dataset.id;
        const status = button.dataset.status;

        const cancelRequest = async (reason = null) => {
            try {
                const response = await this.apiCall(`${this.config.API_URL}/${leaveId}/cancel`, {
                    method: 'POST', body: { reason }
                });
                Swal.fire('처리 완료', response.message, 'success');
                this.loadInitialData();
            } catch (error) {
                Swal.fire('오류', `취소 처리 중 오류가 발생했습니다: ${error.message}`, 'error');
            }
        };

        if (status === 'approved') {
            const { value: reason } = await Swal.fire({
                title: '승인된 연차 취소 요청',
                input: 'textarea', inputLabel: '취소 사유',
                inputPlaceholder: '취소 사유를 입력해주세요...',
                showCancelButton: true, confirmButtonText: '취소 요청',
                cancelButtonText: '닫기',
                inputValidator: (value) => !value && '취소 사유를 반드시 입력해야 합니다.'
            });
            if (reason) cancelRequest(reason);
        } else if (status === 'pending') {
            const result = await Confirm.fire('연차 신청 취소', '이 신청을 취소하시겠습니까?');
            if (result.isConfirmed) cancelRequest();
        }
    }
}

new MyLeavePage();