class LeaveApprovalPage extends BasePage {
    constructor() {
        super({
            API_URL: '/leaves_admin'
        });
        this.elements = {};
    }

    initializeApp() {
        this.cacheDOMElements();
        this.setupEventListeners();
        this.loadInitialData();
    }

    cacheDOMElements() {
        this.elements.tabs = document.querySelectorAll('a[data-bs-toggle="tab"]');
        this.elements.bodies = {
            pending: document.getElementById('pending-requests-body'),
            approved: document.getElementById('approved-requests-body'),
            rejected: document.getElementById('rejected-requests-body'),
            cancellation: document.getElementById('cancellation-requests-body')
        };
    }

    setupEventListeners() {
        this.elements.tabs.forEach(tab => {
            tab.addEventListener('shown.bs.tab', (event) => {
                const status = this.getStatusFromTab(event.target);
                this.loadRequestsByStatus(status);
            });
        });

        Object.values(this.elements.bodies).forEach(body => {
            if(body) {
                body.addEventListener('click', (e) => this.handleTableClick(e));
            }
        });
    }

    loadInitialData() {
        this.loadRequestsByStatus('pending');
    }

    async loadRequestsByStatus(status) {
        const tableBody = this.elements.bodies[status];
        if (!tableBody) return;
        tableBody.innerHTML = `<tr><td colspan="7" class="text-center"><span class="spinner-border spinner-border-sm"></span> 목록을 불러오는 중...</td></tr>`;

        try {
            const response = await this.apiCall(`${this.config.API_URL}/requests?status=${status}`);
            this.renderTable(status, response.data);
        } catch (error) {
            console.error(`Error loading ${status} requests:`, error);
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">목록 로딩 실패: ${error.message}</td></tr>`;
        }
    }

    renderTable(status, data) {
        const tableBody = this.elements.bodies[status];
        if (!tableBody) return;

        if (data.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center">해당 상태의 신청 내역이 없습니다.</td></tr>`;
            return;
        }

        tableBody.innerHTML = data.map(item => this.getTableRowHTML(status, item)).join('');
    }

    getTableRowHTML(status, item) {
        let actionButtons = '';
        let detailsColumn = '';

        switch(status) {
            case 'pending':
                detailsColumn = `<td>${new Date(item.created_at).toLocaleDateString()}</td><td>${item.reason || ''}</td>`;
                actionButtons = `
                    <button class="btn btn-success btn-sm approve-btn" data-id="${item.id}">승인</button>
                    <button class="btn btn-danger btn-sm reject-btn ms-1" data-id="${item.id}">반려</button>`;
                break;
            case 'approved':
                detailsColumn = `<td>${new Date(item.updated_at).toLocaleDateString()}</td><td>${item.approver_name || 'N/A'}</td>`;
                break;
            case 'rejected':
                detailsColumn = `<td>${new Date(item.updated_at).toLocaleDateString()}</td><td>${item.rejection_reason || ''}</td><td>${item.approver_name || 'N/A'}</td>`;
                break;
            case 'cancellation':
                detailsColumn = `<td>${item.cancellation_reason || ''}</td>`;
                actionButtons = `
                    <button class="btn btn-success btn-sm approve-cancel-btn" data-id="${item.id}">취소 승인</button>
                    <button class="btn btn-danger btn-sm reject-cancel-btn ms-1" data-id="${item.id}">취소 반려</button>`;
                break;
        }

        return `
            <tr>
                <td>${item.employee_name}</td>
                <td>${item.department_name || '<i>미지정</i>'}</td>
                <td>${item.start_date} ~ ${item.end_date}</td>
                <td>${item.days_count}일</td>
                ${detailsColumn}
                ${actionButtons ? `<td>${actionButtons}</td>` : ''}
            </tr>
        `;
    }

    async handleTableClick(e) {
        const button = e.target.closest('button');
        if (!button) return;

        const leaveId = button.dataset.id;
        if (!leaveId) return;

        if (button.classList.contains('approve-btn')) {
            const result = await Confirm.fire('연차 신청 승인', '이 연차 신청을 승인하시겠습니까?');
            if (result.isConfirmed) {
                this.handleAction(`${this.config.API_URL}/requests/${leaveId}/approve`);
            }
        } else if (button.classList.contains('reject-btn')) {
            const { value: reason } = await Swal.fire({
                title: '연차 신청 반려', input: 'text', inputLabel: '반려 사유',
                inputPlaceholder: '반려 사유를 입력하세요...', showCancelButton: true,
                confirmButtonText: '반려', cancelButtonText: '취소',
                inputValidator: (value) => !value && '반려 사유는 필수입니다.'
            });
            if (reason) {
                this.handleAction(`${this.config.API_URL}/requests/${leaveId}/reject`, { reason });
            }
        } else if (button.classList.contains('approve-cancel-btn')) {
            const result = await Confirm.fire('연차 취소 승인', '이 연차 취소 요청을 승인하시겠습니까?');
            if (result.isConfirmed) {
                this.handleAction(`${this.config.API_URL}/cancellations/${leaveId}/approve`);
            }
        } else if (button.classList.contains('reject-cancel-btn')) {
             const { value: reason } = await Swal.fire({
                title: '연차 취소 반려', input: 'text', inputLabel: '반려 사유',
                inputPlaceholder: '사용자에게 전달될 반려 사유를 입력하세요...', showCancelButton: true,
                confirmButtonText: '반려', cancelButtonText: '취소',
                inputValidator: (value) => !value && '반려 사유는 필수입니다.'
            });
            if (reason) {
                this.handleAction(`${this.config.API_URL}/cancellations/${leaveId}/reject`, { reason });
            }
        }
    }

    async handleAction(url, body = null) {
        try {
            const response = await this.apiCall(url, { method: 'POST', body });
            Toast.success(response.message || '처리가 완료되었습니다.');

            const activeTab = document.querySelector('.nav-link.active');
            const status = this.getStatusFromTab(activeTab);
            this.loadRequestsByStatus(status);
        } catch (error) {
             Toast.error(`처리 중 오류 발생: ${error.message}`);
        }
    }

    getStatusFromTab(tabElement) {
        return tabElement.getAttribute('href').replace('-tab', '').substring(1);
    }
}

new LeaveApprovalPage();