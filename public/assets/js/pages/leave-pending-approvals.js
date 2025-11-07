/**
 * PendingApprovals - 승인 대기 목록 페이지 클래스
 * 
 * 주요 기능:
 * - 연차 신청 승인/반려 관리
 * - 취소 신청 승인/반려 관리
 * - 일괄 처리 기능
 * - 처리 완료 내역 조회
 * 
 * 요구사항: 6.4, 12.2, 12.3, 12.4
 */
class PendingApprovals extends BasePage {
    constructor() {
        super({
            API_URL: '/leaves_admin'
        });
        
        this.elements = {};
        this.state = {
            currentTab: 'leave-requests',
            selectedRequests: [],
            selectedCancellations: [],
            departments: []
        };
    }

    initializeApp() {
        this.cacheDOMElements();
        this.setupEventListeners();
        this.loadInitialData();
    }

    cacheDOMElements() {
        // 필터 관련 요소들
        this.elements.departmentFilter = document.getElementById('department-filter');
        this.elements.sortBy = document.getElementById('sort-by');

        // 탭 관련 요소들
        this.elements.leaveRequestsTab = document.querySelector('[data-bs-toggle="tab"][href="#leave-requests-tab"]');
        this.elements.cancellationRequestsTab = document.querySelector('[data-bs-toggle="tab"][href="#cancellation-requests-tab"]');
        this.elements.processedTab = document.querySelector('[data-bs-toggle="tab"][href="#processed-tab"]');

        // 연차 신청 관련 요소들
        this.elements.leaveRequestsCount = document.getElementById('leave-requests-count');
        this.elements.leaveRequestsBody = document.getElementById('leave-requests-body');
        this.elements.selectAllRequests = document.getElementById('select-all-requests');
        this.elements.bulkApproveSelectedBtn = document.getElementById('bulk-approve-selected-btn');
        this.elements.bulkRejectSelectedBtn = document.getElementById('bulk-reject-selected-btn');

        // 취소 신청 관련 요소들
        this.elements.cancellationRequestsCount = document.getElementById('cancellation-requests-count');
        this.elements.cancellationRequestsBody = document.getElementById('cancellation-requests-body');
        this.elements.selectAllCancellations = document.getElementById('select-all-cancellations');
        this.elements.bulkApproveCancellationsBtn = document.getElementById('bulk-approve-cancellations-btn');
        this.elements.bulkRejectCancellationsBtn = document.getElementById('bulk-reject-cancellations-btn');

        // 처리 완료 관련 요소들
        this.elements.processedTypeFilter = document.getElementById('processed-type-filter');
        this.elements.processedDateFrom = document.getElementById('processed-date-from');
        this.elements.processedDateTo = document.getElementById('processed-date-to');
        this.elements.searchProcessedBtn = document.getElementById('search-processed-btn');
        this.elements.processedRequestsBody = document.getElementById('processed-requests-body');

        // 모달 관련 요소들
        this.elements.approvalModal = document.getElementById('approval-modal');
        this.elements.approvalModalTitle = document.getElementById('approval-modal-title');
        this.elements.approvalForm = document.getElementById('approval-form');
        this.elements.approvalRequestId = document.getElementById('approval-request-id');
        this.elements.approvalRequestType = document.getElementById('approval-request-type');
        this.elements.approvalRequestInfo = document.getElementById('approval-request-info');
        this.elements.approveRadio = document.getElementById('approve-radio');
        this.elements.rejectRadio = document.getElementById('reject-radio');
        this.elements.approvalReason = document.getElementById('approval-reason');
        this.elements.balanceCheckSection = document.getElementById('balance-check-section');
        this.elements.applicantBalance = document.getElementById('applicant-balance');
        this.elements.requestedDays = document.getElementById('requested-days');
        this.elements.balanceAfter = document.getElementById('balance-after');
        this.elements.submitApprovalBtn = document.getElementById('submit-approval-btn');

        // 일괄 처리 모달
        this.elements.bulkActionModal = document.getElementById('bulk-action-modal');
        this.elements.bulkModalTitle = document.getElementById('bulk-modal-title');
        this.elements.bulkActionForm = document.getElementById('bulk-action-form');
        this.elements.bulkActionType = document.getElementById('bulk-action-type');
        this.elements.bulkRequestIds = document.getElementById('bulk-request-ids');
        this.elements.bulkSelectedCount = document.getElementById('bulk-selected-count');
        this.elements.bulkReason = document.getElementById('bulk-reason');
        this.elements.submitBulkActionBtn = document.getElementById('submit-bulk-action-btn');
    }

    setupEventListeners() {
        // 필터 변경 이벤트
        if (this.elements.departmentFilter) {
            this.elements.departmentFilter.addEventListener('change', () => {
                this.loadCurrentTabData();
            });
        }

        if (this.elements.sortBy) {
            this.elements.sortBy.addEventListener('change', () => {
                this.loadCurrentTabData();
            });
        }

        // 탭 전환 이벤트
        [this.elements.leaveRequestsTab, this.elements.cancellationRequestsTab, this.elements.processedTab].forEach(tab => {
            if (tab) {
                tab.addEventListener('shown.bs.tab', (e) => {
                    const tabId = e.target.getAttribute('href').replace('#', '').replace('-tab', '');
                    this.state.currentTab = tabId;
                    this.loadCurrentTabData();
                });
            }
        });

        // 전체 선택 체크박스
        if (this.elements.selectAllRequests) {
            this.elements.selectAllRequests.addEventListener('change', (e) => {
                this.toggleAllRequestSelections(e.target.checked);
            });
        }

        if (this.elements.selectAllCancellations) {
            this.elements.selectAllCancellations.addEventListener('change', (e) => {
                this.toggleAllCancellationSelections(e.target.checked);
            });
        }

        // 일괄 처리 버튼들
        if (this.elements.bulkApproveSelectedBtn) {
            this.elements.bulkApproveSelectedBtn.addEventListener('click', () => {
                this.showBulkActionModal('approve', 'requests');
            });
        }

        if (this.elements.bulkRejectSelectedBtn) {
            this.elements.bulkRejectSelectedBtn.addEventListener('click', () => {
                this.showBulkActionModal('reject', 'requests');
            });
        }

        if (this.elements.bulkApproveCancellationsBtn) {
            this.elements.bulkApproveCancellationsBtn.addEventListener('click', () => {
                this.showBulkActionModal('approve', 'cancellations');
            });
        }

        if (this.elements.bulkRejectCancellationsBtn) {
            this.elements.bulkRejectCancellationsBtn.addEventListener('click', () => {
                this.showBulkActionModal('reject', 'cancellations');
            });
        }

        // 처리 완료 검색
        if (this.elements.searchProcessedBtn) {
            this.elements.searchProcessedBtn.addEventListener('click', () => {
                this.loadProcessedRequests();
            });
        }

        // 테이블 클릭 이벤트
        if (this.elements.leaveRequestsBody) {
            this.elements.leaveRequestsBody.addEventListener('click', (e) => {
                this.handleRequestAction(e);
            });
        }

        if (this.elements.cancellationRequestsBody) {
            this.elements.cancellationRequestsBody.addEventListener('click', (e) => {
                this.handleCancellationAction(e);
            });
        }

        // 모달 폼 제출
        if (this.elements.approvalForm) {
            this.elements.approvalForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitApproval();
            });
        }

        if (this.elements.bulkActionForm) {
            this.elements.bulkActionForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitBulkAction();
            });
        }

        // 승인/반려 라디오 버튼 변경
        [this.elements.approveRadio, this.elements.rejectRadio].forEach(radio => {
            if (radio) {
                radio.addEventListener('change', () => {
                    this.updateApprovalButtonText();
                });
            }
        });
    }

    loadInitialData() {
        this.loadDepartments();
        this.loadLeaveRequests();
    }

    /**
     * 부서 목록 로드
     */
    async loadDepartments() {
        try {
            const response = await this.apiCall('/organization/managable-departments');
            this.state.departments = response.data;
            
            if (this.elements.departmentFilter) {
                this.elements.departmentFilter.innerHTML = '<option value="">전체 부서</option>';
                this.state.departments.forEach(dept => {
                    const option = new Option(dept.name, dept.id);
                    this.elements.departmentFilter.add(option);
                });
            }
            
        } catch (error) {
            console.error('Failed to load departments:', error);
            Toast.error('부서 목록을 불러오는데 실패했습니다.');
        }
    }

    /**
     * 현재 탭 데이터 로드
     */
    loadCurrentTabData() {
        switch (this.state.currentTab) {
            case 'leave-requests':
                this.loadLeaveRequests();
                break;
            case 'cancellation-requests':
                this.loadCancellationRequests();
                break;
            case 'processed':
                this.loadProcessedRequests();
                break;
        }
    }

    /**
     * 연차 신청 목록 로드
     */
    async loadLeaveRequests() {
        if (!this.elements.leaveRequestsBody) return;

        const departmentId = this.elements.departmentFilter?.value || '';
        const sortBy = this.elements.sortBy?.value || 'created_at';

        try {
            this.setTableLoading(this.elements.leaveRequestsBody, '연차 신청 목록 로딩 중...');
            
            const response = await this.apiCall(`${this.config.API_URL}/pending-requests?department_id=${departmentId}&sort_by=${sortBy}`);
            
            this.renderLeaveRequestsTable(response.data);
            this.updateRequestsCount(response.data.length);
            
        } catch (error) {
            console.error('Failed to load leave requests:', error);
            this.setTableError(this.elements.leaveRequestsBody, `로딩 실패: ${error.message}`);
        }
    }

    /**
     * 연차 신청 테이블 렌더링
     */
    renderLeaveRequestsTable(requests) {
        if (!this.elements.leaveRequestsBody) return;

        if (requests.length === 0) {
            this.elements.leaveRequestsBody.innerHTML = `
                <tr><td colspan="10" class="text-center">승인 대기 중인 연차 신청이 없습니다</td></tr>
            `;
            return;
        }

        this.elements.leaveRequestsBody.innerHTML = requests.map(request => {
            // 안전한 숫자 변환
            const currentBalance = parseFloat(request.current_balance) || 0;
            const requestedDays = parseFloat(request.days) || 0;
            const balanceAfter = currentBalance - requestedDays;
            
            const balanceClass = this.getBalanceClass(currentBalance);
            const urgentClass = this.isUrgentRequest(request) ? 'request-urgent' : 'request-normal';
            
            return `
                <tr class="request-row ${urgentClass}">
                    <td>
                        <input type="checkbox" class="form-check-input bulk-checkbox request-checkbox" 
                               value="${request.id}" data-type="request">
                    </td>
                    <td>
                        <div class="applicant-info">
                            <div class="applicant-avatar">${request.employee_name.charAt(0)}</div>
                            <div class="applicant-details">
                                <div class="name">${request.employee_name}</div>
                                <div class="position">${request.position_name || '직급 미지정'}</div>
                            </div>
                        </div>
                    </td>
                    <td>${request.department_name || '<i>미지정</i>'}</td>
                    <td>${new Date(request.created_at).toLocaleDateString()}</td>
                    <td>
                        <div class="leave-period">
                            <div class="start-date">${request.start_date}</div>
                            <div class="end-date">${request.end_date}</div>
                            <div class="duration">${this.calculateDuration(request.start_date, request.end_date)}일간</div>
                        </div>
                    </td>
                    <td>
                        <span class="badge ${request.day_type === '반차' ? 'bg-warning' : 'bg-primary'}">
                            ${request.day_type === '반차' ? '반차' : '전일'}
                        </span>
                    </td>
                    <td class="fw-bold">${requestedDays}일</td>
                    <td>${request.reason || '<i>사유 없음</i>'}</td>
                    <td>
                        <div class="balance-display">
                            <div class="balance-amount ${balanceClass}">${currentBalance}일</div>
                            <div class="balance-label">잔여</div>
                            <div class="balance-after">승인 후: ${balanceAfter}일</div>
                        </div>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn approve" 
                                    data-id="${request.id}" 
                                    data-type="request" 
                                    data-action="approve"
                                    data-request='${JSON.stringify(request)}'>
                                승인
                            </button>
                            <button class="action-btn reject" 
                                    data-id="${request.id}" 
                                    data-type="request" 
                                    data-action="reject"
                                    data-request='${JSON.stringify(request)}'>
                                반려
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        // 체크박스 이벤트 리스너 추가
        this.elements.leaveRequestsBody.querySelectorAll('.request-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                this.updateRequestSelectionButtons();
            });
        });
    }

    /**
     * 취소 신청 목록 로드
     */
    async loadCancellationRequests() {
        if (!this.elements.cancellationRequestsBody) return;

        const departmentId = this.elements.departmentFilter?.value || '';

        try {
            this.setTableLoading(this.elements.cancellationRequestsBody, '취소 신청 목록 로딩 중...');
            
            const response = await this.apiCall(`${this.config.API_URL}/pending-cancellations?department_id=${departmentId}`);
            
            this.renderCancellationRequestsTable(response.data);
            this.updateCancellationsCount(response.data.length);
            
        } catch (error) {
            console.error('Failed to load cancellation requests:', error);
            this.setTableError(this.elements.cancellationRequestsBody, `로딩 실패: ${error.message}`);
        }
    }

    /**
     * 취소 신청 테이블 렌더링
     */
    renderCancellationRequestsTable(requests) {
        if (!this.elements.cancellationRequestsBody) return;

        if (requests.length === 0) {
            this.elements.cancellationRequestsBody.innerHTML = `
                <tr><td colspan="8" class="text-center">승인 대기 중인 취소 신청이 없습니다</td></tr>
            `;
            return;
        }

        this.elements.cancellationRequestsBody.innerHTML = requests.map(request => `
            <tr class="request-row">
                <td>
                    <input type="checkbox" class="form-check-input bulk-checkbox cancellation-checkbox" 
                           value="${request.id}" data-type="cancellation">
                </td>
                <td>
                    <div class="applicant-info">
                        <div class="applicant-avatar">${request.employee_name.charAt(0)}</div>
                        <div class="applicant-details">
                            <div class="name">${request.employee_name}</div>
                            <div class="position">${request.position || '직급 미지정'}</div>
                        </div>
                    </div>
                </td>
                <td>${request.department_name || '<i>미지정</i>'}</td>
                <td>
                    <div class="leave-period">
                        <div class="start-date">${request.start_date}</div>
                        <div class="end-date">${request.end_date}</div>
                    </div>
                </td>
                <td class="fw-bold">${request.days}일</td>
                <td>${new Date(request.created_at).toLocaleDateString()}</td>
                <td>${request.reason}</td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn approve" 
                                data-id="${request.id}" 
                                data-type="cancellation" 
                                data-action="approve"
                                data-request='${JSON.stringify(request)}'>
                            승인
                        </button>
                        <button class="action-btn reject" 
                                data-id="${request.id}" 
                                data-type="cancellation" 
                                data-action="reject"
                                data-request='${JSON.stringify(request)}'>
                            반려
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');

        // 체크박스 이벤트 리스너 추가
        this.elements.cancellationRequestsBody.querySelectorAll('.cancellation-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                this.updateCancellationSelectionButtons();
            });
        });
    }

    /**
     * 처리 완료 목록 로드
     */
    async loadProcessedRequests() {
        if (!this.elements.processedRequestsBody) return;

        const typeFilter = this.elements.processedTypeFilter?.value || '';
        const dateFrom = this.elements.processedDateFrom?.value || '';
        const dateTo = this.elements.processedDateTo?.value || '';
        const departmentId = this.elements.departmentFilter?.value || '';

        try {
            this.setTableLoading(this.elements.processedRequestsBody, '처리 완료 목록 로딩 중...');
            
            const params = new URLSearchParams({
                department_id: departmentId,
                type_filter: typeFilter,
                date_from: dateFrom,
                date_to: dateTo
            });
            
            const response = await this.apiCall(`${this.config.API_URL}/processed-requests?${params.toString()}`);
            
            this.renderProcessedRequestsTable(response.data);
            
        } catch (error) {
            console.error('Failed to load processed requests:', error);
            this.setTableError(this.elements.processedRequestsBody, `로딩 실패: ${error.message}`);
        }
    }

    /**
     * 처리 완료 테이블 렌더링
     */
    renderProcessedRequestsTable(requests) {
        if (!this.elements.processedRequestsBody) return;

        if (requests.length === 0) {
            this.elements.processedRequestsBody.innerHTML = `
                <tr><td colspan="9" class="text-center">처리 완료된 신청이 없습니다</td></tr>
            `;
            return;
        }

        this.elements.processedRequestsBody.innerHTML = requests.map(request => `
            <tr>
                <td>${new Date(request.processed_at).toLocaleDateString()}</td>
                <td>${request.employee_name}</td>
                <td>${request.department_name || '<i>미지정</i>'}</td>
                <td>
                    <span class="badge ${this.getRequestTypeBadgeClass(request.request_type)}">
                        ${this.getRequestTypeText(request.request_type)}
                    </span>
                </td>
                <td>${request.start_date} ~ ${request.end_date}</td>
                <td>${request.days}일</td>
                <td>
                    <span class="processed-status ${request.status}">
                        <i class="bx ${request.status === '승인' ? 'bx-check-circle' : 'bx-x-circle'}"></i>
                        ${request.status}
                    </span>
                </td>
                <td>${request.approver_name || '<i>미지정</i>'}</td>
                <td>${request.reason || '<i>사유 없음</i>'}</td>
            </tr>
        `).join('');
    }

    /**
     * 요청 액션 처리
     */
    handleRequestAction(e) {
        const button = e.target.closest('button');
        if (!button || !button.dataset.id) return;

        const requestId = button.dataset.id;
        const action = button.dataset.action;
        const type = button.dataset.type;

        this.showApprovalModal(requestId, type, action, button);
    }

    /**
     * 취소 신청 액션 처리
     */
    handleCancellationAction(e) {
        const button = e.target.closest('button');
        if (!button || !button.dataset.id) return;

        const requestId = button.dataset.id;
        const action = button.dataset.action;
        const type = button.dataset.type;

        this.showApprovalModal(requestId, type, action, button);
    }

    /**
     * 승인/반려 모달 표시
     */
    async showApprovalModal(requestId, type, action, buttonElement = null) {
        if (!this.elements.approvalModal) return;

        try {
            let requestData = null;
            
            // 버튼에서 데이터를 가져오기 시도 (테이블에서 호출된 경우)
            if (buttonElement && buttonElement.dataset.request) {
                try {
                    requestData = JSON.parse(buttonElement.dataset.request);
                } catch (e) {
                    console.warn('Failed to parse request data from button:', e);
                }
            }
            
            // 버튼에서 데이터를 가져오지 못한 경우 API 호출
            if (!requestData) {
                const response = await this.apiCall(`${this.config.API_URL}/${type}s/${requestId}`);
                requestData = response.data;
            }

            this.elements.approvalRequestId.value = requestId;
            this.elements.approvalRequestType.value = type;
            
            // 액션에 따른 모달 제목 및 스타일 설정
            const actionText = action === 'approve' ? '승인' : '반려';
            const typeText = type === 'request' ? '연차 신청' : '취소 신청';
            this.elements.approvalModalTitle.textContent = `${typeText} ${actionText}`;
            
            // 모달 헤더 색상 변경
            const modalHeader = this.elements.approvalModal.querySelector('.modal-header');
            if (modalHeader) {
                modalHeader.className = `modal-header ${action === 'approve' ? 'bg-success text-white' : 'bg-danger text-white'}`;
            }

            // 요청 정보 표시
            this.renderRequestInfo(requestData, type);

            // 액션에 따른 라디오 버튼 설정 및 UI 조정
            if (action === 'approve') {
                this.elements.approveRadio.checked = true;
                // 승인 시에는 사유를 선택사항으로
                const reasonLabel = this.elements.approvalModal.querySelector('label[for="approval-reason"]');
                if (reasonLabel) reasonLabel.textContent = '승인 사유 (선택사항)';
            } else {
                this.elements.rejectRadio.checked = true;
                // 반려 시에는 사유를 필수로
                const reasonLabel = this.elements.approvalModal.querySelector('label[for="approval-reason"]');
                if (reasonLabel) reasonLabel.textContent = '반려 사유 (필수)';
                this.elements.approvalReason.required = true;
            }

            // 연차 잔여량 확인 (연차 신청인 경우만)
            if (type === 'request') {
                this.showBalanceCheck(requestData);
            } else {
                this.elements.balanceCheckSection.style.display = 'none';
            }

            this.updateApprovalButtonText();

            const modal = new bootstrap.Modal(this.elements.approvalModal);
            modal.show();

        } catch (error) {
            console.error('Failed to load request details:', error);
            Toast.error('요청 상세 정보를 불러오는데 실패했습니다.');
        }
    }

    /**
     * 요청 정보 렌더링
     */
    renderRequestInfo(requestData, type) {
        if (!this.elements.approvalRequestInfo) return;

        if (type === 'request') {
            this.elements.approvalRequestInfo.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <strong>신청자:</strong> ${requestData.employee_name}<br>
                        <strong>부서:</strong> ${requestData.department_name || '미지정'}<br>
                        <strong>신청일:</strong> ${new Date(requestData.created_at).toLocaleDateString()}
                    </div>
                    <div class="col-md-6">
                        <strong>휴가 기간:</strong> ${requestData.start_date} ~ ${requestData.end_date}<br>
                        <strong>일수:</strong> ${requestData.days}일 (${requestData.day_type === 'HALF' ? '반차' : '전일'})<br>
                        <strong>사유:</strong> ${requestData.reason || '사유 없음'}
                    </div>
                </div>
            `;
        } else {
            this.elements.approvalRequestInfo.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <strong>신청자:</strong> ${requestData.employee_name}<br>
                        <strong>부서:</strong> ${requestData.department_name || '미지정'}<br>
                        <strong>취소 신청일:</strong> ${new Date(requestData.created_at).toLocaleDateString()}
                    </div>
                    <div class="col-md-6">
                        <strong>원본 휴가:</strong> ${requestData.start_date} ~ ${requestData.end_date}<br>
                        <strong>일수:</strong> ${requestData.days}일<br>
                        <strong>취소 사유:</strong> ${requestData.reason}
                    </div>
                </div>
            `;
        }
    }

    /**
     * 잔여량 확인 표시
     */
    showBalanceCheck(requestData) {
        if (!this.elements.balanceCheckSection) return;

        // 문자열을 숫자로 변환하여 계산 오류 방지
        const currentBalance = parseFloat(requestData.current_balance) || 0;
        const requestedDays = parseFloat(requestData.days) || 0;

        this.elements.applicantBalance.textContent = `${currentBalance}일`;
        this.elements.requestedDays.textContent = `${requestedDays}일`;
        
        const balanceAfter = currentBalance - requestedDays;
        this.elements.balanceAfter.textContent = `${balanceAfter}일`;
        this.elements.balanceAfter.className = balanceAfter >= 0 ? 'text-primary' : 'text-danger';

        this.elements.balanceCheckSection.style.display = 'block';
    }

    /**
     * 승인 버튼 텍스트 업데이트
     */
    updateApprovalButtonText() {
        if (!this.elements.submitApprovalBtn) return;

        const isApprove = this.elements.approveRadio?.checked;
        this.elements.submitApprovalBtn.textContent = isApprove ? '승인' : '반려';
        this.elements.submitApprovalBtn.className = `btn ${isApprove ? 'btn-success' : 'btn-danger'}`;
    }

    /**
     * 승인/반려 제출
     */
    async submitApproval() {
        const formData = new FormData(this.elements.approvalForm);
        const requestId = formData.get('request_id');
        const requestType = formData.get('request_type');
        const action = formData.get('action');
        const reason = formData.get('reason');

        // 반려 시 사유 필수 검증
        if (action === 'reject' && (!reason || reason.trim() === '')) {
            Toast.error('반려 사유를 입력해주세요.');
            this.elements.approvalReason.focus();
            return;
        }

        try {
            await this.apiCall(`${this.config.API_URL}/${requestType}s/${requestId}/${action}`, {
                method: 'POST',
                body: { reason: reason || null }
            });

            const actionText = action === 'approve' ? '승인' : '반려';
            const typeText = requestType === 'request' ? '연차 신청' : '취소 신청';
            Toast.success(`${typeText}이 ${actionText}되었습니다.`);

            // 모달 닫기
            const modal = bootstrap.Modal.getInstance(this.elements.approvalModal);
            if (modal) {
                modal.hide();
            }

            // 폼 초기화
            this.elements.approvalForm.reset();
            this.elements.approvalReason.required = false;
            
            // 모달 헤더 색상 초기화
            const modalHeader = this.elements.approvalModal.querySelector('.modal-header');
            if (modalHeader) {
                modalHeader.className = 'modal-header';
            }

            // 목록 새로고침
            this.loadCurrentTabData();

        } catch (error) {
            console.error('Failed to submit approval:', error);
            Toast.error(`처리 실패: ${error.message}`);
        }
    }

    /**
     * 일괄 처리 모달 표시
     */
    showBulkActionModal(action, type) {
        const selectedIds = this.getSelectedIds(type);
        
        if (selectedIds.length === 0) {
            Toast.error('처리할 항목을 선택해주세요.');
            return;
        }

        if (!this.elements.bulkActionModal) return;

        const actionText = action === 'approve' ? '승인' : '반려';
        const typeText = type === 'requests' ? '연차 신청' : '취소 신청';

        this.elements.bulkModalTitle.textContent = `일괄 ${actionText}`;
        this.elements.bulkActionType.value = `${type}_${action}`;
        this.elements.bulkRequestIds.value = selectedIds.join(',');
        this.elements.bulkSelectedCount.textContent = `${selectedIds.length}개의 ${typeText}이 선택되었습니다`;
        this.elements.submitBulkActionBtn.textContent = `일괄 ${actionText}`;
        this.elements.submitBulkActionBtn.className = `btn ${action === 'approve' ? 'btn-success' : 'btn-danger'}`;

        const modal = new bootstrap.Modal(this.elements.bulkActionModal);
        modal.show();
    }

    /**
     * 일괄 처리 제출
     */
    async submitBulkAction() {
        const formData = new FormData(this.elements.bulkActionForm);
        const actionType = formData.get('action_type');
        const requestIds = formData.get('request_ids').split(',').map(id => parseInt(id));
        const reason = formData.get('reason');

        const [type, action] = actionType.split('_');

        try {
            const response = await this.apiCall(`${this.config.API_URL}/bulk-${action}`, {
                method: 'POST',
                body: {
                    type: type,
                    request_ids: requestIds,
                    reason: reason || null
                }
            });

            const actionText = action === 'approve' ? '승인' : '반려';
            Toast.success(`${response.data.success_count}건의 ${actionText}이 완료되었습니다.`);

            if (response.data.failed_count > 0) {
                Toast.warning(`${response.data.failed_count}건의 처리에 실패했습니다.`);
            }

            // 모달 닫기
            const modal = bootstrap.Modal.getInstance(this.elements.bulkActionModal);
            if (modal) {
                modal.hide();
            }

            // 선택 해제 및 목록 새로고침
            this.clearAllSelections();
            this.loadCurrentTabData();

        } catch (error) {
            console.error('Failed to submit bulk action:', error);
            Toast.error(`일괄 처리 실패: ${error.message}`);
        }
    }

    /**
     * 전체 연차 신청 선택/해제
     */
    toggleAllRequestSelections(checked) {
        const checkboxes = this.elements.leaveRequestsBody?.querySelectorAll('.request-checkbox') || [];
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
        });
        this.updateRequestSelectionButtons();
    }

    /**
     * 전체 취소 신청 선택/해제
     */
    toggleAllCancellationSelections(checked) {
        const checkboxes = this.elements.cancellationRequestsBody?.querySelectorAll('.cancellation-checkbox') || [];
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
        });
        this.updateCancellationSelectionButtons();
    }

    /**
     * 연차 신청 선택 버튼 업데이트
     */
    updateRequestSelectionButtons() {
        const selectedCount = this.getSelectedIds('requests').length;
        
        if (this.elements.bulkApproveSelectedBtn) {
            this.elements.bulkApproveSelectedBtn.disabled = selectedCount === 0;
            this.elements.bulkApproveSelectedBtn.textContent = `선택 항목 승인 (${selectedCount})`;
        }
        
        if (this.elements.bulkRejectSelectedBtn) {
            this.elements.bulkRejectSelectedBtn.disabled = selectedCount === 0;
            this.elements.bulkRejectSelectedBtn.textContent = `선택 항목 반려 (${selectedCount})`;
        }
    }

    /**
     * 취소 신청 선택 버튼 업데이트
     */
    updateCancellationSelectionButtons() {
        const selectedCount = this.getSelectedIds('cancellations').length;
        
        if (this.elements.bulkApproveCancellationsBtn) {
            this.elements.bulkApproveCancellationsBtn.disabled = selectedCount === 0;
            this.elements.bulkApproveCancellationsBtn.textContent = `선택 항목 승인 (${selectedCount})`;
        }
        
        if (this.elements.bulkRejectCancellationsBtn) {
            this.elements.bulkRejectCancellationsBtn.disabled = selectedCount === 0;
            this.elements.bulkRejectCancellationsBtn.textContent = `선택 항목 반려 (${selectedCount})`;
        }
    }

    /**
     * 선택된 ID 목록 반환
     */
    getSelectedIds(type) {
        const selector = type === 'requests' ? '.request-checkbox:checked' : '.cancellation-checkbox:checked';
        const checkboxes = document.querySelectorAll(selector);
        return Array.from(checkboxes).map(cb => parseInt(cb.value));
    }

    /**
     * 모든 선택 해제
     */
    clearAllSelections() {
        const allCheckboxes = document.querySelectorAll('.bulk-checkbox');
        allCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        
        if (this.elements.selectAllRequests) {
            this.elements.selectAllRequests.checked = false;
        }
        
        if (this.elements.selectAllCancellations) {
            this.elements.selectAllCancellations.checked = false;
        }
        
        this.updateRequestSelectionButtons();
        this.updateCancellationSelectionButtons();
    }

    /**
     * 요청 수 업데이트
     */
    updateRequestsCount(count) {
        if (this.elements.leaveRequestsCount) {
            this.elements.leaveRequestsCount.textContent = count;
        }
    }

    /**
     * 취소 신청 수 업데이트
     */
    updateCancellationsCount(count) {
        if (this.elements.cancellationRequestsCount) {
            this.elements.cancellationRequestsCount.textContent = count;
        }
    }

    /**
     * 잔여량 클래스 반환
     */
    getBalanceClass(balance) {
        if (balance >= 10) return 'sufficient';
        if (balance >= 5) return 'warning';
        return 'insufficient';
    }

    /**
     * 긴급 요청 여부 확인
     */
    isUrgentRequest(request) {
        const startDate = new Date(request.start_date);
        const today = new Date();
        const diffDays = Math.ceil((startDate - today) / (1000 * 60 * 60 * 24));
        return diffDays <= 3; // 3일 이내 시작하는 요청은 긴급
    }

    /**
     * 기간 계산
     */
    calculateDuration(startDate, endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        const diffTime = Math.abs(end - start);
        return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
    }

    /**
     * 요청 유형 배지 클래스 반환
     */
    getRequestTypeBadgeClass(type) {
        const classMap = {
            'leave_request': 'bg-primary',
            'cancellation_request': 'bg-warning'
        };
        return classMap[type] || 'bg-secondary';
    }

    /**
     * 요청 유형 텍스트 반환
     */
    getRequestTypeText(type) {
        const textMap = {
            'leave_request': '연차 신청',
            'cancellation_request': '취소 신청'
        };
        return textMap[type] || type;
    }

    /**
     * 테이블 로딩 상태 설정
     */
    setTableLoading(tableBody, message) {
        if (tableBody) {
            const colCount = tableBody.closest('table').querySelectorAll('thead th').length;
            tableBody.innerHTML = `
                <tr><td colspan="${colCount}" class="text-center">
                    <span class="spinner-border spinner-border-sm me-2"></span>${message}
                </td></tr>
            `;
        }
    }

    /**
     * 테이블 에러 상태 설정
     */
    setTableError(tableBody, message) {
        if (tableBody) {
            const colCount = tableBody.closest('table').querySelectorAll('thead th').length;
            tableBody.innerHTML = `
                <tr><td colspan="${colCount}" class="text-center text-danger">${message}</td></tr>
            `;
        }
    }

    /**
     * 테이블에서 요청 데이터 찾기
     */
    findRequestDataInTable(requestId, type) {
        // 현재 로드된 데이터에서 찾기
        const tableBody = type === 'request' ? this.elements.leaveRequestsBody : this.elements.cancellationRequestsBody;
        if (!tableBody) return null;

        const row = tableBody.querySelector(`[data-id="${requestId}"]`)?.closest('tr');
        if (!row) return null;

        // 테이블 행에서 데이터 추출
        const cells = row.querySelectorAll('td');
        
        if (type === 'request') {
            return {
                id: requestId,
                employee_name: cells[1]?.querySelector('.name')?.textContent || '',
                department_name: cells[2]?.textContent || '',
                created_at: new Date().toISOString(), // 임시값
                start_date: cells[4]?.querySelector('.start-date')?.textContent || '',
                end_date: cells[4]?.querySelector('.end-date')?.textContent || '',
                days: parseFloat(cells[6]?.textContent || '0'),
                day_type: cells[5]?.textContent?.includes('반차') ? '반차' : '전일',
                reason: cells[7]?.textContent || '',
                current_balance: parseFloat(cells[8]?.querySelector('.balance-amount')?.textContent || '0')
            };
        } else {
            return {
                id: requestId,
                employee_name: cells[1]?.querySelector('.name')?.textContent || '',
                department_name: cells[2]?.textContent || '',
                created_at: new Date().toISOString(), // 임시값
                start_date: cells[3]?.querySelector('.start-date')?.textContent || '',
                end_date: cells[3]?.querySelector('.end-date')?.textContent || '',
                days: parseFloat(cells[4]?.textContent || '0'),
                reason: cells[6]?.textContent || ''
            };
        }
    }
}

// 페이지 로드 시 인스턴스 생성
new PendingApprovals();