class MyPage extends BasePage {
    constructor() {
        super({
            API_URL_PROFILE: '/profile',
            API_URL_LEAVE: '/leaves'
        });
        this.elements = {};
        this.state = {
            ...this.state,
            profileData: null,
            leaveData: null
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
            leaveTypeSelect: document.getElementById('leave_type'),
            startDateInput: document.getElementById('start_date'),
            endDateInput: document.getElementById('end_date'),
            daysCountInput: document.getElementById('days_count'),
            feedbackDiv: document.getElementById('leave-date-feedback')
        };
    }

    setupEventListeners() {
        // Use event delegation on the container for dynamically added elements
        this.elements.container.addEventListener('click', (e) => {
            const editBtn = e.target.closest('#edit-btn');
            const cancelBtn = e.target.closest('#cancel-btn');
            const saveBtn = e.target.closest('#save-btn');
            const requestLeaveBtn = e.target.closest('#request-leave-btn');
            const cancelLeaveBtn = e.target.closest('.cancel-btn');

            if (editBtn) this.renderDashboard(true);
            else if (cancelBtn) this.renderDashboard(false);
            else if (saveBtn) this.handleProfileSave(e);
            else if (requestLeaveBtn) this.openRequestModal();
            else if (cancelLeaveBtn) this.handleHistoryClick(e);
        });

        // Event listeners for the modal form
        if (this.elements.requestForm) {
            this.elements.leaveTypeSelect.addEventListener('change', () => this.handleLeaveTypeChange());
            this.elements.startDateInput.addEventListener('change', () => this.handleDateChange());
            this.elements.endDateInput.addEventListener('change', () => this.handleDateChange());
            this.elements.requestForm.addEventListener('submit', (e) => this.handleRequestSubmit(e));
        }
    }

    async loadInitialData() {
        this.elements.container.innerHTML = `<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">데이터를 불러오는 중...</p></div>`;
        try {
            const [profileResponse, leaveResponse] = await Promise.all([
                this.apiCall(this.config.API_URL_PROFILE),
                this.apiCall(this.config.API_URL_LEAVE + `?year=${new Date().getFullYear()}`)
            ]);

            this.state.profileData = profileResponse.data;
            this.state.leaveData = leaveResponse.data;

            this.renderDashboard();
        } catch (error) {
            this.elements.container.innerHTML = `<div class="alert alert-danger">데이터를 불러오는 데 실패했습니다: ${error.message}</div>`;
        }
    }

    renderDashboard(isEditMode = false) {
        if (!this.state.profileData) return;

        const { user, employee } = this.state.profileData;
        const isPending = employee?.profile_update_status === '대기';
        const isRejected = employee?.profile_update_status === '반려';

        let statusMessage = '';
        if (isPending) {
            statusMessage = `<div class="alert alert-warning">프로필 변경사항이 관리자 승인을 기다리고 있습니다. 승인 전까지는 재수정할 수 없습니다.</div>`;
        } else if (isRejected) {
            statusMessage = `
                <div class="alert alert-danger">
                    <h5 class="alert-heading">프로필 수정 요청이 반려되었습니다.</h5>
                    <p><strong>반려 사유:</strong> ${this._sanitizeHTML(employee.profile_update_rejection_reason)}</p>
                    <hr><p class="mb-0">아래 내용을 수정하여 다시 요청해주세요.</p>
                </div>`;
        }

        const profileImageUrl = user.profile_image_url ? this._sanitizeHTML(user.profile_image_url) : 'https://via.placeholder.com/100?text=No+Image';

        const userCard = `
            <div class="card shadow mb-4">
                <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">계정 정보 (카카오 연동)</h6></div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto"><img src="${profileImageUrl}" alt="프로필 사진" class="rounded-circle" width="100" height="100"></div>
                        <div class="col"><h4 class="mb-1">${this._sanitizeHTML(user.nickname)}</h4><p class="text-muted mb-0">${this._sanitizeHTML(user.email)}</p></div>
                    </div>
                </div>
            </div>`;

        let employeeCard = '';
        if (employee) {
            const fields = [
                { label: '사번', key: 'employee_number', readonly: true }, { label: '입사일', key: 'hire_date', readonly: true },
                { label: '연락처', key: 'phone_number' }, { label: '주소', key: 'address' },
                { label: '비상연락처', key: 'emergency_contact_name' }, { label: '관계', key: 'emergency_contact_relation' },
                { label: '상의 사이즈', key: 'clothing_top_size' }, { label: '하의 사이즈', key: 'clothing_bottom_size' },
                { label: '신발 사이즈', key: 'shoe_size' },
            ];
            const employeeContent = isEditMode
                ? `<form id="profile-form">${fields.map(f => `
                       <div class="col-md-6 mb-3">
                           <label for="${f.key}" class="form-label">${f.label}</label>
                           <input type="text" class="form-control" id="${f.key}" name="${f.key}" value="${this._sanitizeHTML(employee[f.key] || '')}" ${f.readonly ? 'readonly' : ''}>
                       </div>`).join('')}</form>`
                : fields.map(f => `<div class="col-md-6 mb-3"><strong>${f.label}:</strong> ${this._sanitizeHTML(employee[f.key]) || '<i>-</i>'}</div>`).join('');

            employeeCard = `
                <div class="card shadow mb-4">
                    <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">직원 정보</h6></div>
                    <div class="card-body"><div class="row">${employeeContent}</div></div>
                </div>`;
        } else {
            employeeCard = `<div class="alert alert-secondary">연결된 직원 정보가 없습니다.</div>`;
        }

        const leaveCard = this.renderLeaveCard();

        this.elements.container.innerHTML = `
            ${statusMessage}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">마이페이지</h1>
                <div id="action-buttons"></div>
            </div>
            <div class="row">
                <div class="col-xl-4">
                    ${leaveCard}
                </div>
                <div class="col-xl-8">
                    ${employeeCard}
                    ${userCard}
                </div>
            </div>`;

        this.renderButtons(isEditMode, isPending, !!employee);
        this.renderLeaveData();
    }

    renderLeaveCard() {
        return `
            <div class="card shadow mb-4">
                <div class="card-header align-items-center d-flex py-3">
                    <h6 class="m-0 font-weight-bold text-primary">나의 연차 현황</h6>
                    <div class="ms-auto">
                        <button id="request-leave-btn" class="btn btn-primary btn-sm">
                            <i class="bx bx-plus"></i> 연차 신청
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <h5 class="card-title">잔여 연차</h5>
                        <p id="summary-remaining" class="display-5 fw-bold text-primary">--</p>
                        <small id="summary-total" class="text-muted">(총 --일 중 --일 사용)</small>
                    </div>
                    <hr>
                    <h6 class="card-title">연차 사용 내역 (<span id="current-year">${new Date().getFullYear()}</span>년)</h6>
                    <div id="leave-history-container" style="max-height: 250px; overflow-y: auto;">
                        <div id="leave-history-body">
                            <div class="text-center"><span class="spinner-border spinner-border-sm"></span></div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    renderLeaveData() {
        if (!this.state.leaveData) {
            document.getElementById('leave-history-body').innerHTML = '<div class="text-muted p-3 text-center">연차 정보가 없습니다.</div>';
            return;
        }

        const data = this.state.leaveData;

        const summaryRemainingEl = document.getElementById('summary-remaining');
        const summaryTotalEl = document.getElementById('summary-total');
        const historyBodyEl = document.getElementById('leave-history-body');

        if (data.entitlement) {
            const { total_days, used_days } = data.entitlement;
            const remaining_days = parseFloat(total_days) - parseFloat(used_days);
            summaryRemainingEl.textContent = `${remaining_days.toFixed(1)}일`;
            summaryTotalEl.textContent = `(총 ${total_days}일 중 ${used_days}일 사용)`;
        } else {
            summaryRemainingEl.textContent = '0일';
            summaryTotalEl.textContent = '(부여 내역 없음)';
        }

        if (!data.leaves || data.leaves.length === 0) {
            historyBodyEl.innerHTML = '<div class="text-center text-muted p-3">사용 내역이 없습니다.</div>';
            return;
        }

        const statusBadges = { '대기': 'bg-warning', '승인': 'bg-success', '반려': 'bg-danger', '취소': 'bg-secondary', '취소요청': 'bg-info' };

        const rowsHtml = data.leaves.map(leave => {
            const canCancel = leave.status === '대기' || leave.status === '승인';
            const cancelButton = canCancel ? `<button class="btn btn-link btn-sm p-0 cancel-btn" data-id="${leave.id}" data-status="${leave.status}">취소</button>` : '';

            let reasonText = '';
            if (leave.status === '반려' && leave.rejection_reason) reasonText = `(반려: ${leave.rejection_reason})`;
            else if (leave.status === '취소요청' && leave.cancellation_reason) reasonText = `(취소요청: ${leave.cancellation_reason})`;
            else if (leave.status === '승인' && leave.rejection_reason) reasonText = `(취소반려: ${leave.rejection_reason})`;
            else if (leave.reason) reasonText = `(사유: ${leave.reason})`;

            return `
                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                    <div>
                        <span class="fw-bold">${leave.start_date} ~ ${leave.end_date}</span> (${leave.days_count}일)
                        <small class="text-muted ms-2">${this._sanitizeHTML(reasonText)}</small>
                    </div>
                    <div>
                        <span class="badge ${statusBadges[leave.status] || 'bg-light text-dark'}">${leave.status}</span>
                        ${cancelButton}
                    </div>
                </div>`;
        }).join('');
        historyBodyEl.innerHTML = rowsHtml;
    }

    renderButtons(isEditMode, isPending, isEmployee) {
        const buttonsContainer = document.getElementById('action-buttons');
        if (!buttonsContainer) return;

        let buttonsHtml = '';
        if (isEmployee) {
            buttonsHtml = isEditMode
                ? `<button class="btn btn-secondary" id="cancel-btn">취소</button>
                   <button class="btn btn-primary" id="save-btn" form="profile-form">수정 요청</button>`
                : `<button class="btn btn-primary" id="edit-btn" ${isPending ? 'disabled' : ''}>정보 수정</button>`;
        }
        buttonsContainer.innerHTML = buttonsHtml;

        //This is handled by event delegation now
    }

    // All modal and leave related functions from my-leave.js are now here
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
            const response = await this.apiCall(`${this.config.API_URL_LEAVE}/calculate-days`, {
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
            const response = await this.apiCall(this.config.API_URL_LEAVE, { method: 'POST', body: data });
            Toast.success('연차 신청이 완료되었습니다.');
            this.state.requestModal.hide();
            this.loadInitialData(); // Reload all data
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
                const response = await this.apiCall(`${this.config.API_URL_LEAVE}/${leaveId}/cancel`, {
                    method: 'POST', body: { reason }
                });
                Swal.fire('처리 완료', response.message, 'success');
                this.loadInitialData();
            } catch (error) {
                Swal.fire('오류', `취소 처리 중 오류가 발생했습니다: ${error.message}`, 'error');
            }
        };

        if (status === '승인') {
            const { value: reason } = await Swal.fire({
                title: '승인된 연차 취소 요청',
                input: 'textarea', inputLabel: '취소 사유',
                inputPlaceholder: '취소 사유를 입력해주세요...',
                showCancelButton: true, confirmButtonText: '취소 요청',
                cancelButtonText: '닫기',
                inputValidator: (value) => !value && '취소 사유를 반드시 입력해야 합니다.'
            });
            if (reason) cancelRequest(reason);
        } else if (status === '대기') {
            const result = await Confirm.fire('연차 신청 취소', '이 신청을 취소하시겠습니까?');
            if (result.isConfirmed) cancelRequest();
        }
    }

    async handleProfileSave(e) {
        e.preventDefault();
        const form = document.getElementById('profile-form');
        const data = Object.fromEntries(new FormData(form).entries());
        try {
            const result = await this.apiCall(this.config.API_URL_PROFILE, { method: 'PUT', body: data });
            Toast.success(result.message);
            this.loadInitialData();
        } catch (error) {
            Toast.error('수정 요청 중 오류 발생: ' + error.message);
        }
    }

    _sanitizeHTML(str) {
        if (str === null || typeof str === 'undefined') return '';
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }
}

new MyPage();