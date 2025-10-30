class MyPage extends BasePage {
    constructor() {
        super();
        this.elements = {};
        this.state = {
            profileData: null,
            leaveBalance: null,
            leaveRequests: [],
            requestModal: null,
            isEditMode: false
        };
        this.debouncedCalculateDays = debounce(() => this.calculateDays(), 300);
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
            this.elements.startDateInput.addEventListener('change', () => this.debouncedCalculateDays());
            this.elements.endDateInput.addEventListener('change', () => this.debouncedCalculateDays());
            this.elements.requestForm.addEventListener('submit', (e) => this.handleRequestSubmit(e));
        }
    }

    async loadInitialData() {
        try {
            const [balanceRes, requestsRes] = await Promise.all([
                this.apiCall('/leaves/my-balance'),
                this.apiCall('/leaves')
            ]);
            this.state.leaveBalance = balanceRes.data;
            this.state.leaveRequests = requestsRes.data;
            this.renderLeaveData();
        } catch (error) {
            Toast.error('연차 정보를 불러오는데 실패했습니다.');
        }
    }

    renderLeaveData() {
        const balance = this.state.leaveBalance;
        if (!balance) return;

        const totalGranted = (balance.base_leave + balance.seniority_leave + balance.monthly_leave + balance.adjustment_leave).toFixed(1);
        const used = parseFloat(balance.used_leave).toFixed(1);
        const remaining = (totalGranted - used).toFixed(1);

        document.getElementById('leave-total').textContent = totalGranted;
        document.getElementById('leave-used').textContent = used;
        document.getElementById('leave-remaining').textContent = remaining;

        const historyBody = document.getElementById('leave-history-body');
        if (this.state.leaveRequests.length === 0) {
            historyBody.innerHTML = '<tr><td colspan="6" class="text-center">연차 사용 내역이 없습니다.</td></tr>';
            return;
        }

        historyBody.innerHTML = this.state.leaveRequests.map(req => `
            <tr>
                <td>${{full_day:'연차', half_day_am:'오전반차', half_day_pm:'오후반차'}[req.leave_subtype]}</td>
                <td>${req.start_date} ~ ${req.end_date}</td>
                <td>${req.days_count}</td>
                <td><span class="badge bg-${this.getStatusColor(req.status)}">${this.translateStatus(req.status)}</span></td>
                <td>${req.approver_name || '-'}</td>
                <td>${this.renderActionButtons(req)}</td>
            </tr>
        `).join('');
    }

    renderActionButtons(request) {
        if (request.status === 'pending' || request.status === 'approved') {
            return `<button class="btn btn-sm btn-warning cancel-leave-btn" data-id="${request.id}" data-status="${request.status}">취소</button>`;
        }
        return '';
    }

    handleSubtypeChange() {
        const isHalfDay = this.elements.leaveSubtypeSelect.value.startsWith('half_day');
        this.elements.endDateInput.disabled = isHalfDay;
        this.elements.daysCountInput.readOnly = true;

        if (isHalfDay) {
            if (this.elements.startDateInput.value) this.elements.endDateInput.value = this.elements.startDateInput.value;
            this.elements.daysCountInput.value = 0.5;
            this.elements.feedbackDiv.textContent = '';
        } else {
            this.debouncedCalculateDays();
        }
    }

    async calculateDays() {
        const startDate = this.elements.startDateInput.value;
        const endDate = this.elements.endDateInput.value;
        const subtype = this.elements.leaveSubtypeSelect.value;

        if (subtype.startsWith('half_day')) {
            this.elements.daysCountInput.value = 0.5;
            this.elements.endDateInput.value = startDate;
            this.elements.feedbackDiv.textContent = '';
            return;
        }

        if (!startDate || !endDate || new Date(startDate) > new Date(endDate)) {
            this.elements.daysCountInput.value = '';
            this.elements.feedbackDiv.textContent = '';
            return;
        }

        try {
            const response = await this.apiCall('/leaves/calculate-days', 'POST', { start_date: startDate, end_date: endDate });
            this.elements.daysCountInput.value = response.data.days;
            this.elements.feedbackDiv.textContent = `(주말/공휴일 제외, ${response.data.total_days}일)`;
            this.elements.feedbackDiv.classList.remove('text-danger');
        } catch (error) {
            this.elements.daysCountInput.value = '';
            this.elements.feedbackDiv.textContent = error.message || '일수 계산 실패';
            this.elements.feedbackDiv.classList.add('text-danger');
        }
    }

    openRequestModal() {
        this.elements.requestForm.reset();
        this.handleSubtypeChange();
        this.state.requestModal.show();
    }

    async handleRequestSubmit(e) {
        e.preventDefault();
        const formData = new FormData(this.elements.requestForm);
        const data = Object.fromEntries(formData.entries());
        data.days_count = parseFloat(this.elements.daysCountInput.value);

        try {
            await this.apiCall('/leaves', 'POST', data);
            Toast.success('연차 신청이 완료되었습니다.');
            this.state.requestModal.hide();
            this.loadInitialData();
        } catch(error) {
            Toast.error(error.message || '신청 실패');
        }
    }

    async handleCancelClick(e) {
        const button = e.target.closest('.cancel-leave-btn');
        const leaveId = button.dataset.id;
        const status = button.dataset.status;

        if (status === 'approved') {
            const { value: reason } = await Swal.fire({
                title: '승인된 연차 취소 요청',
                input: 'textarea',
                inputLabel: '취소 사유를 입력해주세요. (관리자 승인 필요)',
                showCancelButton: true,
                confirmButtonText: '요청',
                cancelButtonText: '닫기'
            });
            if (reason) {
                try {
                    await this.apiCall(`/leaves/${leaveId}/cancel`, 'POST', { reason });
                    Toast.success('취소 요청이 완료되었습니다.');
                    this.loadInitialData();
                } catch(error) {
                    Toast.error(error.message || '요청 실패');
                }
            }
        } else if (status === 'pending') {
            if (await confirm('이 신청을 취소하시겠습니까?')) {
                try {
                    await this.apiCall(`/leaves/${leaveId}/cancel`, 'POST');
                    Toast.success('신청이 취소되었습니다.');
                    this.loadInitialData();
                } catch(error) {
                    Toast.error(error.message || '취소 실패');
                }
            }
        }
    }

    getStatusColor(status) {
        const map = {
            'pending': 'secondary', 'approved': 'success', 'rejected': 'danger',
            'cancelled': 'warning', 'cancellation_requested': 'info'
        };
        return map[status] || 'dark';
    }

    translateStatus(status) {
        const map = {
            'pending': '대기', 'approved': '승인', 'rejected': '반려',
            'cancelled': '취소됨', 'cancellation_requested': '취소요청'
        };
        return map[status] || status;
    }
}

// Helper debounce function
function debounce(fn, delay) {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
}

document.addEventListener('DOMContentLoaded', () => {
    new MyPage().initializeApp();
});
