/**
 * LeaveApplication - 연차 관리 메인 페이지 클래스
 * 
 * 주요 기능:
 * - 연차 현황 대시보드 표시
 * - 연차 신청 폼 관리 및 유효성 검증
 * - 실시간 일수 계산 및 잔여량 확인
 * - 연차 현황 및 신청 내역 표시
 * - 신청 취소 기능
 * 
 * 요구사항: 5.1, 5.2, 5.3, 5.4, 9.4, 12.1
 */
class LeaveApplication extends BasePage {
    constructor() {
        super({
            API_URL: '/leaves'
        });
        
        this.elements = {};
        this.state = {
            currentBalance: 0,
            totalGranted: 0,
            totalUsed: 0,
            pendingCount: 0,
            applications: []
        };
    }

    initializeApp() {
        this.cacheDOMElements();
        this.setupEventListeners();
        this.loadInitialData();
    }

    cacheDOMElements() {
        // 대시보드 현황 카드
        this.elements.currentBalance = document.getElementById('current-balance');
        this.elements.totalGranted = document.getElementById('total-granted');
        this.elements.totalUsed = document.getElementById('total-used');
        this.elements.pendingCount = document.getElementById('pending-count');
        
        // 헤더 잔여량 표시
        this.elements.headerBalance = document.getElementById('header-balance');
        
        // 신청 폼 요소들
        this.elements.applyForm = document.getElementById('detailed-leave-application-form');
        this.elements.startDate = document.getElementById('apply-start-date');
        this.elements.endDate = document.getElementById('apply-end-date');
        this.elements.dayType = document.getElementById('apply-day-type');
        this.elements.reason = document.getElementById('apply-reason');
        this.elements.calculatedDays = document.getElementById('apply-calculated-days');
        this.elements.resetBtn = document.getElementById('reset-form-btn');
        
        // 사이드바 현황 표시
        this.elements.sidebarGranted = document.getElementById('sidebar-granted');
        this.elements.sidebarUsed = document.getElementById('sidebar-used');
        this.elements.sidebarPending = document.getElementById('sidebar-pending');
        this.elements.sidebarBalance = document.getElementById('sidebar-balance');
        this.elements.recentApplications = document.getElementById('sidebar-recent-applications');
        this.elements.holidays = document.getElementById('sidebar-holidays');
        
        // 신청 내역 테이블
        this.elements.historyYearFilter = document.getElementById('history-year-filter');
        this.elements.historyStatusFilter = document.getElementById('history-status-filter');
        this.elements.applicationHistoryBody = document.getElementById('application-history-body');
        
        // 취소 모달
        this.elements.cancelModal = document.getElementById('cancel-application-modal');
        this.elements.cancelModalTitle = document.getElementById('cancel-modal-title');
        this.elements.cancelDetails = document.getElementById('cancel-application-details');
        this.elements.cancelConfirmationText = document.getElementById('cancel-confirmation-text');
        this.elements.confirmCancelBtn = document.getElementById('confirm-cancel-btn');
    }

    setupEventListeners() {
        // 폼 제출
        if (this.elements.applyForm) {
            this.elements.applyForm.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }
        
        // 날짜 변경 시 일수 계산
        if (this.elements.startDate) {
            this.elements.startDate.addEventListener('change', () => this.calculateDays());
        }
        if (this.elements.endDate) {
            this.elements.endDate.addEventListener('change', () => this.calculateDays());
        }
        if (this.elements.dayType) {
            this.elements.dayType.addEventListener('change', () => this.calculateDays());
        }
        
        // 폼 초기화
        if (this.elements.resetBtn) {
            this.elements.resetBtn.addEventListener('click', () => this.resetForm());
        }
        
        // 필터 변경
        if (this.elements.historyYearFilter) {
            this.elements.historyYearFilter.addEventListener('change', () => this.loadApplicationHistory());
        }
        if (this.elements.historyStatusFilter) {
            this.elements.historyStatusFilter.addEventListener('change', () => this.loadApplicationHistory());
        }
        
        // 취소 확인
        if (this.elements.confirmCancelBtn) {
            this.elements.confirmCancelBtn.addEventListener('click', () => this.confirmCancelApplication());
        }
    }

    async loadInitialData() {
        try {
            await Promise.all([
                this.loadLeaveBalance(),
                this.loadApplicationHistory(),
                this.loadHolidays()
            ]);
        } catch (error) {
            console.error('초기 데이터 로드 실패:', error);
            this.showError('데이터를 불러오는 중 오류가 발생했습니다.');
        }
    }

    async loadLeaveBalance() {
        try {
            const response = await this.apiCall(`${this.config.API_URL}/balance`);
            
            this.state.currentBalance = response.data.balance;
            this.state.grantedLeave = response.data.granted;
            this.state.usedLeave = response.data.used;
            this.state.pendingLeave = response.data.pending;
            
            this.updateBalanceDisplay();
        } catch (error) {
            console.error('잔여량 로드 실패:', error);
            Toast.error('연차 잔여량을 불러오는데 실패했습니다.');
        }
    }

    updateBalanceDisplay() {
        if (this.elements.currentBalance) {
            this.elements.currentBalance.textContent = this.state.currentBalance;
        }
        if (this.elements.totalGranted) {
            this.elements.totalGranted.textContent = this.state.grantedLeave;
        }
        if (this.elements.totalUsed) {
            this.elements.totalUsed.textContent = this.state.usedLeave;
        }
        if (this.elements.pendingCount) {
            this.elements.pendingCount.textContent = this.state.pendingLeave;
        }
        if (this.elements.headerBalance) {
            this.elements.headerBalance.textContent = `${this.state.currentBalance}일`;
        }
        if (this.elements.sidebarGranted) {
            this.elements.sidebarGranted.textContent = `${this.state.grantedLeave}일`;
        }
        if (this.elements.sidebarUsed) {
            this.elements.sidebarUsed.textContent = `${this.state.usedLeave}일`;
        }
        if (this.elements.sidebarPending) {
            this.elements.sidebarPending.textContent = `${this.state.pendingLeave}일`;
        }
        if (this.elements.sidebarBalance) {
            this.elements.sidebarBalance.textContent = `${this.state.currentBalance}일`;
        }
    }

    async calculateDays() {
        const startDate = this.elements.startDate?.value;
        const endDate = this.elements.endDate?.value;
        const dayType = this.elements.dayType?.value;
        
        if (!startDate || !endDate || !dayType) {
            this.updateCalculatedDays(0);
            return;
        }
        
        const start = new Date(startDate);
        const end = new Date(endDate);
        
        if (start > end) {
            this.updateCalculatedDays(0, '종료일이 시작일보다 빠릅니다');
            return;
        }
        
        if (dayType === '반차') {
            if (start.getTime() !== end.getTime()) {
                this.updateCalculatedDays(0, '반차는 시작일과 종료일이 같아야 합니다');
                return;
            }
            this.updateCalculatedDays(0.5);
            return;
        }
        
        try {
            // 백엔드 API를 통해 정확한 일수 계산 (공휴일 고려)
            const response = await this.apiCall(`${this.config.API_URL}/calculate-days`, {
                method: 'POST',
                body: {
                    start_date: startDate,
                    end_date: endDate,
                    is_half_day: false
                }
            });
            
            this.updateCalculatedDays(response.data.days);
        } catch (error) {
            console.error('일수 계산 실패:', error);
            // 실패 시 간단한 계산으로 대체
            let days = 0;
            const current = new Date(start);
            
            while (current <= end) {
                const dayOfWeek = current.getDay();
                if (dayOfWeek !== 0 && dayOfWeek !== 6) { // 일요일(0), 토요일(6) 제외
                    days++;
                }
                current.setDate(current.getDate() + 1);
            }
            
            this.updateCalculatedDays(days);
        }
    }

    updateCalculatedDays(days, error = null) {
        if (!this.elements.calculatedDays) return;
        
        if (error) {
            this.elements.calculatedDays.textContent = error;
            this.elements.calculatedDays.className = 'calculated-days-display invalid';
        } else {
            this.elements.calculatedDays.textContent = `${days}일`;
            this.elements.calculatedDays.className = days > 0 ? 'calculated-days-display has-value' : 'calculated-days-display';
        }
    }

    async handleFormSubmit(e) {
        e.preventDefault();
        
        if (!this.validateForm()) {
            return;
        }
        
        // 필요한 데이터만 명시적으로 전송 (days는 백엔드에서 계산)
        const data = {
            start_date: this.elements.startDate.value,
            end_date: this.elements.endDate.value,
            day_type: this.elements.dayType.value,
            reason: this.elements.reason.value || null
        };
        
        try {
            const response = await this.apiCall(`${this.config.API_URL}/apply`, {
                method: 'POST',
                body: data
            });
            
            Toast.success('연차 신청이 완료되었습니다.');
            this.resetForm();
            await this.loadLeaveBalance();
            await this.loadApplicationHistory();
            
        } catch (error) {
            console.error('신청 제출 실패:', error);
            Toast.error(`신청 처리 실패: ${error.message}`);
        }
    }

    validateForm() {
        let isValid = true;
        
        // 필수 필드 검증
        const requiredFields = [
            { element: this.elements.startDate, message: '시작일을 선택해주세요' },
            { element: this.elements.endDate, message: '종료일을 선택해주세요' },
            { element: this.elements.dayType, message: '휴가 유형을 선택해주세요' }
        ];
        
        requiredFields.forEach(field => {
            if (!field.element?.value) {
                this.showFieldError(field.element, field.message);
                isValid = false;
            } else {
                this.clearFieldError(field.element);
            }
        });
        
        return isValid;
    }

    showFieldError(element, message) {
        if (!element) return;
        
        element.classList.add('is-invalid');
        
        let feedback = element.parentNode.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            element.parentNode.appendChild(feedback);
        }
        feedback.textContent = message;
    }

    clearFieldError(element) {
        if (!element) return;
        
        element.classList.remove('is-invalid');
        const feedback = element.parentNode.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.remove();
        }
    }

    resetForm() {
        if (this.elements.applyForm) {
            this.elements.applyForm.reset();
            this.updateCalculatedDays(0);
            
            // 에러 상태 초기화
            const invalidElements = this.elements.applyForm.querySelectorAll('.is-invalid');
            invalidElements.forEach(el => this.clearFieldError(el));
        }
    }

    async loadApplicationHistory() {
        const year = this.elements.historyYearFilter?.value || new Date().getFullYear();
        const status = this.elements.historyStatusFilter?.value || '';
        
        try {
            const params = new URLSearchParams({ year });
            if (status) params.append('status', status);
            
            const response = await this.apiCall(`${this.config.API_URL}/history?${params}`);
            
            // state에 applications 저장
            this.state.applications = response.data || [];
            
            this.renderApplicationHistory(response.data);
            this.updateRecentApplications(response.data.slice(0, 3));
            
        } catch (error) {
            console.error('신청 내역 로드 실패:', error);
            Toast.error('신청 내역을 불러오는데 실패했습니다.');
        }
    }

    renderApplicationHistory(applications) {
        if (!this.elements.applicationHistoryBody) return;
        
        if (applications.length === 0) {
            this.elements.applicationHistoryBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-muted">신청 내역이 없습니다</td>
                </tr>
            `;
            return;
        }
        
        this.elements.applicationHistoryBody.innerHTML = applications.map(app => `
            <tr>
                <td>${this.formatDate(app.created_at)}</td>
                <td>${this.formatDate(app.start_date)} ~ ${this.formatDate(app.end_date)}</td>
                <td>${app.day_type === '반차' ? '반차' : '전일'}</td>
                <td>${app.days}일</td>
                <td>${this.getStatusBadge(app.status)}</td>
                <td>${app.approver_name || '-'}</td>
                <td>
                    ${app.status === '대기' ? `
                        <button class="btn btn-sm btn-outline-danger" onclick="showCancelModal('${app.id}')">
                            취소
                        </button>
                    ` : app.status === '승인' && !this.isLeaveUsed(app) ? (
                        app.cancellation_id && app.cancellation_status === '대기' ? `
                            <span class="badge bg-warning">취소 신청 중</span>
                        ` : `
                            <button class="btn btn-sm btn-outline-warning" onclick="showCancelModal('${app.id}')">
                                취소 신청
                            </button>
                        `
                    ) : '-'}
                </td>
            </tr>
        `).join('');
    }

    updateRecentApplications(applications) {
        if (!this.elements.recentApplications) return;
        
        if (applications.length === 0) {
            this.elements.recentApplications.innerHTML = '<div class="text-muted text-center">내역이 없습니다</div>';
            return;
        }
        
        this.elements.recentApplications.innerHTML = applications.map(app => `
            <div class="sidebar-application-item">
                <div class="date">${this.formatDate(app.start_date)}</div>
                <div class="d-flex justify-content-between align-items-center">
                    <span>${app.days}일</span>
                    <span class="status ${this.getStatusClass(app.status)}">${this.getStatusText(app.status)}</span>
                </div>
            </div>
        `).join('');
    }

    async loadHolidays() {
        try {
            const currentMonth = new Date().getMonth() + 1;
            const currentYear = new Date().getFullYear();
            
            const response = await this.apiCall(`/holidays?year=${currentYear}&month=${currentMonth}`);
            
            if (this.elements.holidays) {
                this.renderHolidays(response.data);
            }
        } catch (error) {
            console.error('휴일 정보 로드 실패:', error);
            // 휴일 정보는 필수가 아니므로 에러 토스트는 표시하지 않음
        }
    }

    renderHolidays(holidays) {
        if (!this.elements.holidays) return;
        
        if (holidays.length === 0) {
            this.elements.holidays.innerHTML = '<div class="text-muted text-center">이번 달 휴일이 없습니다</div>';
            return;
        }
        
        this.elements.holidays.innerHTML = holidays.map(holiday => `
            <div class="holiday-item">
                <span class="holiday-date">${this.formatDate(holiday.date, 'MM/DD')}</span>
                <span class="holiday-name">${holiday.name}</span>
            </div>
        `).join('');
    }

    showCancelModal(applicationId) {
        // 문자열을 숫자로 변환
        const numId = parseInt(applicationId);
        const application = this.state.applications.find(app => app.id == numId);
        
        if (!application) {
            console.error('Application not found:', applicationId, 'Available:', this.state.applications);
            Toast.error('신청 정보를 찾을 수 없습니다.');
            return;
        }
        
        // 신청 정보 표시
        if (this.elements.cancelDetails) {
            const isApproved = application.status === '승인' || application.status === 'APPROVED';
            
            this.elements.cancelDetails.innerHTML = `
                <div class="mb-3">
                    <strong>신청 기간:</strong> ${this.formatDate(application.start_date)} ~ ${this.formatDate(application.end_date)}<br>
                    <strong>신청 일수:</strong> ${application.days}일<br>
                    <strong>현재 상태:</strong> ${this.getStatusText(application.status)}
                </div>
                ${isApproved ? `
                    <div class="alert alert-info">
                        <i class="bx bx-info-circle me-1"></i>
                        승인된 연차는 취소 신청 후 관리자 승인이 필요합니다.
                    </div>
                ` : `
                    <div class="alert alert-warning">
                        <i class="bx bx-info-circle me-1"></i>
                        승인 전 신청은 즉시 취소됩니다.
                    </div>
                `}
            `;
        }
        
        // 모달 제목과 버튼 텍스트 변경
        const isApproved = application.status === '승인' || application.status === 'APPROVED';
        
        if (this.elements.cancelModalTitle) {
            this.elements.cancelModalTitle.textContent = isApproved ? '연차 취소 신청' : '연차 신청 취소';
        }
        
        if (this.elements.cancelConfirmationText) {
            this.elements.cancelConfirmationText.textContent = isApproved 
                ? '관리자에게 취소 신청을 하시겠습니까?' 
                : '정말로 이 연차 신청을 취소하시겠습니까?';
        }
        
        if (this.elements.confirmCancelBtn) {
            this.elements.confirmCancelBtn.textContent = isApproved ? '취소 신청' : '예, 취소합니다';
            this.elements.confirmCancelBtn.className = isApproved ? 'btn btn-warning' : 'btn btn-danger';
        }
        
        const modal = new bootstrap.Modal(this.elements.cancelModal);
        this.currentCancelId = numId;  // 숫자로 저장
        modal.show();
    }

    async confirmCancelApplication() {
        if (!this.currentCancelId) return;
        
        try {
            // 신청 상태 확인 후 적절한 API 호출
            const application = this.state.applications.find(app => app.id == this.currentCancelId);
            
            if (!application) {
                console.error('Application not found for cancel:', this.currentCancelId);
                Toast.error('신청 정보를 찾을 수 없습니다.');
                return;
            }
            
            if (application.status === '대기') {
                // 승인 전 취소 - 직접 취소 처리
                await this.apiCall(`${this.config.API_URL}/applications/${this.currentCancelId}/cancel`, {
                    method: 'POST'
                });
                Toast.success('신청이 취소되었습니다.');
            } else if (application.status === '승인') {
                // 승인 후 취소 - 취소 신청 (관리자 승인 필요)
                const reason = prompt('취소 사유를 입력해주세요:');
                if (reason === null) {
                    return;
                }
                
                if (!reason || reason.trim() === '') {
                    Toast.error('취소 사유를 입력해주세요.');
                    return;
                }
                
                await this.apiCall(`${this.config.API_URL}/applications/${this.currentCancelId}/request-cancel`, {
                    method: 'POST',
                    body: { reason: reason.trim() }
                });
                Toast.success('취소 신청이 완료되었습니다. 관리자 승인을 기다려주세요.');
            } else {
                Toast.error('취소할 수 없는 상태입니다.');
                return;
            }
            
            await this.loadLeaveBalance();
            await this.loadApplicationHistory();
            
            const modal = bootstrap.Modal.getInstance(this.elements.cancelModal);
            if (modal) {
                modal.hide();
            }
            
        } catch (error) {
            console.error('취소 처리 실패:', error);
            Toast.error(`취소 처리 실패: ${error.message}`);
        }
    }

    // BasePage의 apiCall 메서드를 사용 (ApiService 활용)

    // 유틸리티 메서드들
    isLeaveUsed(application) {
        // 연차 시작일이 오늘 이전이면 사용된 것으로 간주
        const today = new Date();
        const startDate = new Date(application.start_date);
        return startDate < today;
    }

    formatDate(dateString, format = 'YYYY-MM-DD') {
        const date = new Date(dateString);
        if (format === 'MM/DD') {
            return `${String(date.getMonth() + 1).padStart(2, '0')}/${String(date.getDate()).padStart(2, '0')}`;
        }
        return date.toISOString().split('T')[0];
    }

    getStatusBadge(status) {
        const badges = {
            '대기': '<span class="badge bg-warning">승인 대기</span>',
            '승인': '<span class="badge bg-success">승인</span>',
            '반려': '<span class="badge bg-danger">반려</span>',
            '취소': '<span class="badge bg-secondary">취소</span>'
        };
        return badges[status] || status;
    }

    getStatusClass(status) {
        const classes = {
            '대기': 'bg-warning',
            '승인': 'bg-success',
            '반려': 'bg-danger',
            '취소': 'bg-secondary'
        };
        return classes[status] || '';
    }

    getStatusText(status) {
        const texts = {
            '대기': '대기',
            '승인': '승인',
            '반려': '반려',
            '취소': '취소'
        };
        return texts[status] || status;
    }
}

// 페이지 로드 시 인스턴스 생성
const leaveApplicationInstance = new LeaveApplication();
window.leaveApplication = leaveApplicationInstance;

// 전역 함수로 취소 모달 호출 (HTML onclick에서 사용)
window.showCancelModal = function(applicationId) {
    if (leaveApplicationInstance) {
        leaveApplicationInstance.showCancelModal(applicationId);
    }
};