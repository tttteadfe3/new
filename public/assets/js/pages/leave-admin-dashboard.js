/**
 * LeaveAdminDashboardPage - 연차 관리자 대시보드 페이지 클래스
 * 
 * 주요 기능:
 * - 팀별 연차 소진율 시각화
 * - 승인 대기 목록 관리 인터페이스 (신청 및 취소 신청)
 * - 연차 부여/조정/소멸 관리 기능
 * 
 * 요구사항: 6.1, 6.2, 6.4, 7.1, 7.2, 7.3, 7.4, 10.1, 10.2, 12.2, 12.3, 12.4
 */
class LeaveAdminDashboardPage extends BasePage {
    constructor() {
        super({
            API_URL: '/leaves_admin',
            CHART_COLORS: [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
                '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
            ]
        });
        
        this.elements = {};
        this.state = {
            currentYear: new Date().getFullYear(),
            selectedDepartment: null,
            charts: {},
            pendingRequests: [],
            cancellationRequests: []
        };
    }

    initializeApp() {
        console.log('LeaveAdminDashboardPage: initializeApp called');
        this.cacheDOMElements();
        this.setupEventListeners();
        this.loadInitialData();
    }

    cacheDOMElements() {
        // 필터 및 컨트롤 요소들
        this.elements.yearFilter = document.getElementById('dashboard-year-filter');
        this.elements.departmentFilter = document.getElementById('dashboard-department-filter');
        this.elements.refreshButton = document.getElementById('refresh-dashboard');
        
        // 통계 표시 요소들
        this.elements.totalEmployees = document.getElementById('total-employees');
        this.elements.pendingApprovals = document.getElementById('pending-approvals');
        this.elements.thisMonthLeaves = document.getElementById('this-month-leaves');
        this.elements.lowBalanceCount = document.getElementById('low-balance-count');
        
        // 차트 컨테이너들
        this.elements.departmentChart = document.getElementById('department-usage-chart');
        this.elements.monthlyTrendChart = document.getElementById('monthly-trend-chart');
        
        // 승인 대기 목록 관련 요소들
        this.elements.pendingListBody = document.getElementById('dashboard-pending-list');
        this.elements.lowBalanceListBody = document.getElementById('low-balance-list');
        this.elements.teamDetailListBody = document.getElementById('team-detail-list');
        
        // 연차 관리 기능 요소들
        this.elements.grantLeaveBtn = document.getElementById('grant-annual-leave-btn');
        this.elements.bulkApproveBtn = document.getElementById('bulk-approve-btn');
        this.elements.exportReportBtn = document.getElementById('export-report-btn');
        
        // 모달 관련 요소들
        this.elements.grantModal = document.getElementById('grant-leave-modal');
        this.elements.quickApproveModal = document.getElementById('quick-approve-modal');
        this.elements.grantForm = document.getElementById('grant-leave-form');
        this.elements.quickApproveForm = document.getElementById('quick-approve-form');
    }

    setupEventListeners() {
        // 필터 변경 이벤트
        [this.elements.yearFilter, this.elements.departmentFilter].forEach(filter => {
            if (filter) {
                filter.addEventListener('change', () => {
                    this.loadDashboardData();
                    this.loadPendingRequests();
                    this.loadLowBalanceEmployees();
                    this.loadTeamDetailStatus();
                });
            }
        });

        // 새로고침 버튼
        if (this.elements.refreshButton) {
            this.elements.refreshButton.addEventListener('click', () => {
                this.loadDashboardData();
            });
        }

        // 테이블 액션 이벤트
        if (this.elements.pendingListBody) {
            this.elements.pendingListBody.addEventListener('click', (e) => {
                this.handlePendingAction(e);
            });
        }

        // 연차 관리 버튼들
        if (this.elements.grantLeaveBtn) {
            this.elements.grantLeaveBtn.addEventListener('click', () => {
                this.showGrantModal();
            });
        }
        
        if (this.elements.bulkApproveBtn) {
            this.elements.bulkApproveBtn.addEventListener('click', () => {
                this.handleBulkApprove();
            });
        }
        
        if (this.elements.exportReportBtn) {
            this.elements.exportReportBtn.addEventListener('click', () => {
                this.handleExportReport();
            });
        }

        // 폼 제출 이벤트
        if (this.elements.grantForm) {
            this.elements.grantForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleGrantLeave();
            });
        }
        
        if (this.elements.quickApproveForm) {
            this.elements.quickApproveForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleQuickApprove();
            });
        }
    }

    loadInitialData() {
        this.loadFilterOptions();
        this.loadDashboardData();
        this.loadPendingRequests();
        this.loadLowBalanceEmployees();
        this.loadTeamDetailStatus();
    }

    /**
     * 필터 옵션 로드
     */
    async loadFilterOptions() {
        try {
            const response = await this.apiCall('/organization/managable-departments');
            
            if (this.elements.departmentFilter) {
                // 전체 옵션 추가
                const allOption = new Option('전체 부서', '');
                this.elements.departmentFilter.add(allOption);
                
                // 부서 옵션들 추가
                response.data.forEach(dept => {
                    const option = new Option(dept.name, dept.id);
                    this.elements.departmentFilter.add(option);
                });
            }
        } catch (error) {
            console.error('Failed to load filter options:', error);
            Toast.error('필터 옵션을 불러오는데 실패했습니다.');
        }
    }

    /**
     * 대시보드 데이터 로드
     */
    async loadDashboardData() {
        const year = this.elements.yearFilter?.value || this.state.currentYear;
        const departmentId = this.elements.departmentFilter?.value || '';

        try {
            console.log('Loading dashboard data...', { year, departmentId });
            const response = await this.apiCall(`${this.config.API_URL}/dashboard?year=${year}&department_id=${departmentId}`);
            
            console.log('Dashboard data response:', response);
            
            if (response && response.data) {
                if (response.data.statistics) {
                    this.updateStatistics(response.data.statistics);
                }
                if (response.data.charts) {
                    this.renderCharts(response.data.charts);
                }
            } else {
                console.warn('No data in dashboard response');
            }
            
        } catch (error) {
            console.error('Failed to load dashboard data:', error);
            Toast.error(`대시보드 데이터를 불러오는데 실패했습니다: ${error.message}`);
        }
    }

    /**
     * 통계 정보 업데이트
     */
    updateStatistics(statistics) {
        if (this.elements.totalEmployees) {
            this.elements.totalEmployees.textContent = statistics.total_employees || '0';
        }
        
        if (this.elements.pendingApprovals) {
            this.elements.pendingApprovals.textContent = statistics.pending_requests || '0';
        }
        
        if (this.elements.thisMonthLeaves) {
            this.elements.thisMonthLeaves.textContent = `${statistics.this_month_leaves || 0}일`;
        }
        
        if (this.elements.lowBalanceCount) {
            this.elements.lowBalanceCount.textContent = statistics.low_balance_count || '0';
        }
    }

    /**
     * 차트 렌더링
     */
    renderCharts(chartData) {
        this.renderDepartmentChart(chartData.department_summary);
        this.renderMonthlyTrendChart(chartData.monthly_trend);
    }



    /**
     * 부서별 현황 차트 렌더링
     */
    renderDepartmentChart(data) {
        if (!this.elements.departmentChart || !data || data.length === 0) return;

        // 기존 차트 제거
        if (this.state.charts.department) {
            this.state.charts.department.destroy();
        }

        const ctx = this.elements.departmentChart.getContext('2d');
        this.state.charts.department = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(item => item.department_name),
                datasets: [
                    {
                        label: '사용',
                        data: data.map(item => item.used_days),
                        backgroundColor: '#36A2EB'
                    },
                    {
                        label: '잔여',
                        data: data.map(item => item.remaining_days),
                        backgroundColor: '#FF6384'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: false
                    },
                    y: {
                        stacked: false,
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });
    }

    /**
     * 월별 트렌드 차트 렌더링
     */
    renderMonthlyTrendChart(data) {
        if (!this.elements.monthlyTrendChart || !data || data.length === 0) return;

        // 기존 차트 제거
        if (this.state.charts.monthlyTrend) {
            this.state.charts.monthlyTrend.destroy();
        }

        const ctx = this.elements.monthlyTrendChart.getContext('2d');
        this.state.charts.monthlyTrend = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(item => `${item.month}월`),
                datasets: [{
                    label: '월별 연차 사용량',
                    data: data.map(item => item.usage_count),
                    borderColor: '#36A2EB',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    /**
     * 승인 대기 목록 로드
     */
    async loadPendingRequests() {
        if (!this.elements.pendingListBody) return;

        this.elements.pendingListBody.innerHTML = `
            <tr><td colspan="5" class="text-center">
                <span class="spinner-border spinner-border-sm"></span> 로딩 중...
            </td></tr>
        `;

        try {
            const departmentId = this.elements.departmentFilter?.value || '';
            console.log('Loading pending requests...', { departmentId });
            
            const url = `${this.config.API_URL}/pending-requests${departmentId ? `?department_id=${departmentId}` : ''}`;
            console.log('Request URL:', url);
            
            const response = await this.apiCall(url);
            
            console.log('Pending requests response:', response);
            
            if (response && response.success) {
                const data = response.data || [];
                console.log('Pending requests data:', data);
                this.state.pendingRequests = data;
                this.renderPendingRequestsTable(data);
            } else {
                console.warn('No data in pending requests response or success=false', response);
                this.renderPendingRequestsTable([]);
            }
            
        } catch (error) {
            console.error('Failed to load pending requests:', error);
            console.error('Error details:', {
                message: error.message,
                status: error.status,
                response: error.response
            });
            this.elements.pendingListBody.innerHTML = `
                <tr><td colspan="5" class="text-center text-danger">
                    로딩 실패: ${error.message || '알 수 없는 오류'}
                    ${error.status ? `(상태: ${error.status})` : ''}
                </td></tr>
            `;
        }
    }

    /**
     * 승인 대기 목록 테이블 렌더링
     */
    renderPendingRequestsTable(requests) {
        if (!this.elements.pendingListBody) {
            console.error('pendingListBody element not found');
            return;
        }

        console.log('renderPendingRequestsTable called with:', requests);
        console.log('Is array?', Array.isArray(requests));
        console.log('Length:', requests?.length);

        if (!Array.isArray(requests) || requests.length === 0) {
            this.elements.pendingListBody.innerHTML = `
                <tr><td colspan="5" class="text-center text-muted">승인 대기 중인 신청이 없습니다</td></tr>
            `;
            return;
        }

        // 최대 5개만 표시
        const displayRequests = requests.slice(0, 5);
        console.log('Displaying requests:', displayRequests);
        
        this.elements.pendingListBody.innerHTML = displayRequests.map(request => {
            const remainingDays = request.current_balance || request.remaining_days || 0;
            let balanceClass = 'text-success';
            if (remainingDays <= 2) balanceClass = 'text-danger';
            else if (remainingDays <= 5) balanceClass = 'text-warning';
            
            return `
            <tr>
                <td>
                    <div class="fw-bold">${request.employee_name}</div>
                    <small class="text-muted">${request.department_name || '미지정'}</small>
                </td>
                <td>
                    <div>${request.start_date}</div>
                    <div>~ ${request.end_date}</div>
                </td>
                <td>
                    <span class="badge bg-primary">${request.days}일</span>
                    ${request.day_type === '반차' ? '<br><small class="text-muted">반차</small>' : ''}
                </td>
                <td>
                    <span class="${balanceClass} fw-bold">${remainingDays}일</span>
                </td>
                <td>
                    <button class="btn btn-success btn-sm quick-approve-btn me-1" data-id="${request.id}" data-action="approve">
                        <i class="bx bx-check"></i>
                    </button>
                    <button class="btn btn-danger btn-sm quick-approve-btn" data-id="${request.id}" data-action="reject">
                        <i class="bx bx-x"></i>
                    </button>
                </td>
            </tr>
            `;
        }).join('');
    }

    /**
     * 연차 부족 직원 목록 로드
     */
    async loadLowBalanceEmployees() {
        if (!this.elements.lowBalanceListBody) return;

        this.elements.lowBalanceListBody.innerHTML = `
            <tr><td colspan="4" class="text-center">
                <span class="spinner-border spinner-border-sm"></span> 로딩 중...
            </td></tr>
        `;

        try {
            const departmentId = this.elements.departmentFilter?.value || '';
            const response = await this.apiCall(`${this.config.API_URL}/team-status?department_id=${departmentId}`);
            
            // 연차 5일 이하인 직원들만 필터링
            const lowBalanceEmployees = response.data.filter(emp => 
                emp.current_balance !== null && emp.current_balance <= 5
            );
            
            this.renderLowBalanceTable(lowBalanceEmployees);
            
        } catch (error) {
            console.error('Failed to load low balance employees:', error);
            this.elements.lowBalanceListBody.innerHTML = `
                <tr><td colspan="4" class="text-center text-danger">
                    로딩 실패: ${error.message}
                </td></tr>
            `;
        }
    }

    /**
     * 연차 부족 직원 테이블 렌더링
     */
    renderLowBalanceTable(employees) {
        if (!this.elements.lowBalanceListBody) return;

        if (employees.length === 0) {
            this.elements.lowBalanceListBody.innerHTML = `
                <tr><td colspan="4" class="text-center text-muted">연차 부족 직원이 없습니다</td></tr>
            `;
            return;
        }

        // 최대 5개만 표시
        const displayEmployees = employees.slice(0, 5);
        
        this.elements.lowBalanceListBody.innerHTML = displayEmployees.map(emp => {
            const balance = parseFloat(emp.current_balance || 0);
            const usedDays = parseFloat(emp.used_days_this_year || 0);
            const totalDays = balance + usedDays;
            const usageRate = totalDays > 0 ? Math.round((usedDays / totalDays) * 100) : 0;
            
            let balanceClass = 'balance-normal';
            if (balance <= 2) balanceClass = 'balance-critical';
            else if (balance <= 5) balanceClass = 'balance-warning';
            
            return `
                <tr>
                    <td>
                        <div class="fw-bold">${emp.employee_name}</div>
                    </td>
                    <td>
                        <small class="text-muted">${emp.department_name || '미지정'}</small>
                    </td>
                    <td>
                        <span class="${balanceClass}">${balance}일</span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <span class="me-2">${usageRate}%</span>
                            <div class="usage-progress">
                                <div class="usage-progress-bar" style="width: ${usageRate}%"></div>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    /**
     * 팀 상세 현황 로드
     */
    async loadTeamDetailStatus() {
        if (!this.elements.teamDetailListBody) return;

        this.elements.teamDetailListBody.innerHTML = `
            <tr><td colspan="10" class="text-center">
                <span class="spinner-border spinner-border-sm"></span> 로딩 중...
            </td></tr>
        `;

        try {
            const departmentId = this.elements.departmentFilter?.value || '';
            const response = await this.apiCall(`${this.config.API_URL}/team-status?department_id=${departmentId}`);
            
            this.renderTeamDetailTable(response.data);
            
        } catch (error) {
            console.error('Failed to load team detail status:', error);
            this.elements.teamDetailListBody.innerHTML = `
                <tr><td colspan="10" class="text-center text-danger">
                    로딩 실패: ${error.message}
                </td></tr>
            `;
        }
    }

    /**
     * 팀 상세 현황 테이블 렌더링
     */
    renderTeamDetailTable(employees) {
        if (!this.elements.teamDetailListBody) return;

        if (employees.length === 0) {
            this.elements.teamDetailListBody.innerHTML = `
                <tr><td colspan="10" class="text-center text-muted">직원 정보가 없습니다</td></tr>
            `;
            return;
        }

        this.elements.teamDetailListBody.innerHTML = employees.map(emp => {
            const balance = parseFloat(emp.current_balance || 0);
            const usedDays = parseFloat(emp.used_days_this_year || 0);
            const totalDays = balance + usedDays;
            const usageRate = totalDays > 0 ? Math.round((usedDays / totalDays) * 100) : 0;
            
            let statusIndicator = 'active';
            let statusText = '정상';
            if (balance <= 2) {
                statusIndicator = 'danger';
                statusText = '부족';
            } else if (balance <= 5) {
                statusIndicator = 'warning';
                statusText = '주의';
            }
            
            return `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <span class="employee-status-indicator ${statusIndicator}"></span>
                            ${emp.employee_name}
                        </div>
                    </td>
                    <td>${emp.department_name || '<i>미지정</i>'}</td>
                    <td>${emp.position_name || '<i>미지정</i>'}</td>
                    <td>${emp.hire_date || '<i>미지정</i>'}</td>
                    <td>${totalDays}일</td>
                    <td>${usedDays}일</td>
                    <td>${balance}일</td>
                    <td>
                        <div class="d-flex align-items-center">
                            <span class="me-2">${usageRate}%</span>
                            <div class="usage-progress">
                                <div class="usage-progress-bar" style="width: ${usageRate}%"></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-${statusIndicator === 'active' ? 'success' : statusIndicator === 'warning' ? 'warning' : 'danger'}">
                            ${statusText}
                        </span>
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                            <button class="btn btn-sm btn-outline-primary quick-action-btn" 
                                    onclick="window.location.href='/employees/${emp.employee_id}'">
                                <i class="bx bx-user"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-info quick-action-btn" 
                                    onclick="window.location.href='/leaves/history?employee_id=${emp.employee_id}'">
                                <i class="bx bx-history"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    /**
     * 승인 대기 액션 처리
     */
    async handlePendingAction(e) {
        const button = e.target.closest('button');
        if (!button) return;

        const requestId = button.dataset.id;
        const action = button.dataset.action;
        
        if (action === 'approve') {
            await this.approveLeaveRequest(requestId);
        } else if (action === 'reject') {
            await this.rejectLeaveRequest(requestId);
        }
    }

    /**
     * 연차 신청 승인
     */
    async approveLeaveRequest(requestId) {
        const result = await Confirm.fire('연차 신청 승인', '이 연차 신청을 승인하시겠습니까?');
        
        if (result.isConfirmed) {
            try {
                await this.apiCall(`${this.config.API_URL}/requests/${requestId}/approve`, {
                    method: 'POST'
                });
                
                Toast.success('연차 신청이 승인되었습니다.');
                this.loadPendingRequests();
                this.loadDashboardData();
                
            } catch (error) {
                console.error('Failed to approve request:', error);
                Toast.error(`승인 실패: ${error.message}`);
            }
        }
    }

    /**
     * 연차 신청 반려
     */
    async rejectLeaveRequest(requestId) {
        const { value: reason } = await Swal.fire({
            title: '연차 신청 반려',
            input: 'textarea',
            inputLabel: '반려 사유',
            inputPlaceholder: '반려 사유를 입력하세요...',
            showCancelButton: true,
            confirmButtonText: '반려',
            cancelButtonText: '취소',
            inputValidator: (value) => {
                if (!value || value.trim().length < 5) {
                    return '반려 사유를 5자 이상 입력해주세요.';
                }
            }
        });

        if (reason) {
            try {
                await this.apiCall(`${this.config.API_URL}/requests/${requestId}/reject`, {
                    method: 'POST',
                    body: { reason: reason.trim() }
                });
                
                Toast.success('연차 신청이 반려되었습니다.');
                this.loadPendingRequests();
                
            } catch (error) {
                console.error('Failed to reject request:', error);
                Toast.error(`반려 실패: ${error.message}`);
            }
        }
    }

    /**
     * 일괄 승인 처리
     */
    async handleBulkApprove() {
        const result = await Confirm.fire(
            '일괄 승인 확인',
            '모든 승인 대기 중인 연차 신청을 승인하시겠습니까?'
        );

        if (result.isConfirmed) {
            try {
                const requestIds = this.state.pendingRequests.map(req => req.id);
                
                if (requestIds.length === 0) {
                    Toast.info('승인할 신청이 없습니다.');
                    return;
                }

                await this.apiCall(`${this.config.API_URL}/bulk-approve`, {
                    method: 'POST',
                    body: {
                        type: 'requests',
                        request_ids: requestIds
                    }
                });
                
                Toast.success(`${requestIds.length}건의 연차 신청이 일괄 승인되었습니다.`);
                this.loadDashboardData();
                this.loadPendingRequests();
                
            } catch (error) {
                console.error('Failed to bulk approve:', error);
                Toast.error(`일괄 승인 실패: ${error.message}`);
            }
        }
    }

    /**
     * 현황 내보내기 처리
     */
    async handleExportReport() {
        try {
            const year = this.elements.yearFilter?.value || this.state.currentYear;
            const departmentId = this.elements.departmentFilter?.value || '';
            
            // 엑셀 내보내기 타입 선택 모달 표시
            const exportType = await this.showExportTypeModal();
            if (!exportType) return;
            
            Toast.info('데이터를 준비하고 있습니다...');
            
            // AJAX로 데이터 가져오기
            const response = await fetch(`/api${this.config.API_URL}/export?type=${exportType}&year=${year}&department_id=${departmentId}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error('데이터 내보내기에 실패했습니다.');
            }
            
            const data = await response.json();
            
            if (!data.success || !data.data || data.data.length === 0) {
                Toast.warning('내보낼 데이터가 없습니다.');
                return;
            }
            
            // CSV 생성 및 다운로드
            this.downloadCSV(data.data, `leaves_${exportType}_${year}_${Date.now()}.csv`);
            
            Toast.success('데이터 내보내기가 완료되었습니다.');
            
        } catch (error) {
            console.error('Failed to export report:', error);
            Toast.error(`내보내기 실패: ${error.message}`);
        }
    }

    /**
     * CSV 파일 생성 및 다운로드
     */
    downloadCSV(data, filename) {
        if (!data || data.length === 0) return;
        
        // CSV 헤더 생성
        const headers = Object.keys(data[0]);
        const csvContent = [
            headers.join(','), // 헤더 행
            ...data.map(row => 
                headers.map(header => {
                    const value = row[header] ?? '';
                    // 쉼표나 따옴표가 있으면 따옴표로 감싸기
                    if (String(value).includes(',') || String(value).includes('"') || String(value).includes('\n')) {
                        return `"${String(value).replace(/"/g, '""')}"`;
                    }
                    return value;
                }).join(',')
            )
        ].join('\n');
        
        // UTF-8 BOM 추가 (엑셀 한글 깨짐 방지)
        const BOM = '\uFEFF';
        const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' });
        
        // 다운로드 링크 생성
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    /**
     * 내보내기 타입 선택 모달 표시
     */
    async showExportTypeModal() {
        return new Promise((resolve) => {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">데이터 내보내기</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">내보내기 유형 선택</label>
                                <select class="form-select" id="export-type-select">
                                    <option value="current_status">현재 연차 현황</option>
                                    <option value="usage_history">연차 사용 이력</option>
                                    <option value="application_history">연차 신청 이력</option>
                                    <option value="adjustment_history">연차 조정 이력</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                            <button type="button" class="btn btn-primary" id="confirm-export-btn">내보내기</button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            modal.querySelector('#confirm-export-btn').addEventListener('click', () => {
                const exportType = modal.querySelector('#export-type-select').value;
                bsModal.hide();
                resolve(exportType);
            });
            
            modal.addEventListener('hidden.bs.modal', () => {
                modal.remove();
                resolve(null);
            });
        });
    }

    /**
     * 빠른 승인 처리
     */
    async handleQuickApprove() {
        const formData = new FormData(this.elements.quickApproveForm);
        const applicationId = formData.get('application_id');
        const action = formData.get('action');
        const reason = formData.get('reason');

        try {
            if (action === 'approve') {
                await this.apiCall(`${this.config.API_URL}/requests/${applicationId}/approve`, {
                    method: 'POST',
                    body: { reason }
                });
                Toast.success('연차 신청이 승인되었습니다.');
            } else {
                if (!reason || reason.trim().length < 5) {
                    Toast.error('반려 사유를 5자 이상 입력해주세요.');
                    return;
                }
                
                await this.apiCall(`${this.config.API_URL}/requests/${applicationId}/reject`, {
                    method: 'POST',
                    body: { reason: reason.trim() }
                });
                Toast.success('연차 신청이 반려되었습니다.');
            }
            
            // 모달 닫기
            const modal = bootstrap.Modal.getInstance(this.elements.quickApproveModal);
            if (modal) {
                modal.hide();
            }
            
            this.loadDashboardData();
            this.loadPendingRequests();
            
        } catch (error) {
            console.error('Failed to process quick approve:', error);
            Toast.error(`처리 실패: ${error.message}`);
        }
    }

    /**
     * 취소 신청 승인
     */
    async approveCancellationRequest(cancellationId) {
        const result = await Confirm.fire('취소 신청 승인', '이 취소 신청을 승인하시겠습니까?');
        
        if (result.isConfirmed) {
            try {
                await this.apiCall(`${this.config.API_URL}/cancellations/${cancellationId}/approve`, {
                    method: 'POST'
                });
                
                Toast.success('취소 신청이 승인되었습니다.');
                this.loadCancellationRequests();
                this.loadDashboardData();
                
            } catch (error) {
                console.error('Failed to approve cancellation:', error);
                Toast.error(`승인 실패: ${error.message}`);
            }
        }
    }

    /**
     * 취소 신청 반려
     */
    async rejectCancellationRequest(cancellationId) {
        const { value: reason } = await Swal.fire({
            title: '취소 신청 반려',
            input: 'textarea',
            inputLabel: '반려 사유',
            inputPlaceholder: '반려 사유를 입력하세요...',
            showCancelButton: true,
            confirmButtonText: '반려',
            cancelButtonText: '취소',
            inputValidator: (value) => {
                if (!value || value.trim().length < 5) {
                    return '반려 사유를 5자 이상 입력해주세요.';
                }
            }
        });

        if (reason) {
            try {
                await this.apiCall(`${this.config.API_URL}/cancellations/${cancellationId}/reject`, {
                    method: 'POST',
                    body: { reason: reason.trim() }
                });
                
                Toast.success('취소 신청이 반려되었습니다.');
                this.loadCancellationRequests();
                
            } catch (error) {
                console.error('Failed to reject cancellation:', error);
                Toast.error(`반려 실패: ${error.message}`);
            }
        }
    }

    /**
     * 연차 부여 모달 표시
     */
    showGrantModal() {
        if (this.elements.grantModal) {
            const modal = new bootstrap.Modal(this.elements.grantModal);
            modal.show();
        }
    }

    /**
     * 연차 조정 모달 표시
     */
    showAdjustModal() {
        if (this.elements.adjustModal) {
            const modal = new bootstrap.Modal(this.elements.adjustModal);
            modal.show();
        }
    }

    /**
     * 연차 소멸 모달 표시
     */
    showExpireModal() {
        if (this.elements.expireModal) {
            const modal = new bootstrap.Modal(this.elements.expireModal);
            modal.show();
        }
    }

    /**
     * 연차 부여 처리
     */
    async handleGrantLeave() {
        const formData = new FormData(this.elements.grantForm);
        const year = formData.get('grant_year');
        
        if (!year) {
            Toast.error('부여 연도를 선택해주세요.');
            return;
        }

        const result = await Confirm.fire(
            '연차 부여 확인',
            `${year}년도 연차를 부여하시겠습니까? 이 작업은 되돌릴 수 없습니다.`
        );

        if (result.isConfirmed) {
            try {
                this.setButtonLoading('#grant-submit-btn', '부여 중...');
                
                await this.apiCall(`${this.config.API_URL}/grant-annual-leave`, {
                    method: 'POST',
                    body: { year: parseInt(year) }
                });
                
                Toast.success('연차 부여가 완료되었습니다.');
                
                // 모달 닫기
                const modal = bootstrap.Modal.getInstance(this.elements.grantModal);
                if (modal) {
                    modal.hide();
                }
                
                this.loadDashboardData();
                
            } catch (error) {
                console.error('Failed to grant leave:', error);
                Toast.error(`연차 부여 실패: ${error.message}`);
            } finally {
                this.resetButtonLoading('#grant-submit-btn');
            }
        }
    }

    /**
     * 연차 조정 처리
     */
    async handleAdjustLeave() {
        const formData = new FormData(this.elements.adjustForm);
        const employeeId = formData.get('employee_id');
        const amount = formData.get('adjust_amount');
        const reason = formData.get('adjust_reason');
        
        if (!employeeId || !amount || !reason) {
            Toast.error('모든 필드를 입력해주세요.');
            return;
        }

        if (reason.trim().length < 5) {
            Toast.error('조정 사유를 5자 이상 입력해주세요.');
            return;
        }

        try {
            this.setButtonLoading('#adjust-submit-btn', '조정 중...');
            
            await this.apiCall(`${this.config.API_URL}/adjust-leave`, {
                method: 'POST',
                body: {
                    employee_id: parseInt(employeeId),
                    amount: parseFloat(amount),
                    reason: reason.trim()
                }
            });
            
            Toast.success('연차 조정이 완료되었습니다.');
            
            // 모달 닫기 및 폼 초기화
            const modal = bootstrap.Modal.getInstance(this.elements.adjustModal);
            if (modal) {
                modal.hide();
            }
            this.elements.adjustForm.reset();
            
            this.loadDashboardData();
            
        } catch (error) {
            console.error('Failed to adjust leave:', error);
            Toast.error(`연차 조정 실패: ${error.message}`);
        } finally {
            this.resetButtonLoading('#adjust-submit-btn');
        }
    }

    /**
     * 연차 소멸 처리
     */
    async handleExpireLeave() {
        const formData = new FormData(this.elements.expireForm);
        const targetType = formData.get('expire_target');
        const employeeIds = formData.getAll('employee_ids');
        
        let confirmMessage = '선택된 직원들의 연차를 소멸시키시겠습니까?';
        if (targetType === 'all') {
            confirmMessage = '모든 직원의 연차를 소멸시키시겠습니까?';
        }

        const result = await Confirm.fire('연차 소멸 확인', confirmMessage);

        if (result.isConfirmed) {
            try {
                this.setButtonLoading('#expire-submit-btn', '소멸 처리 중...');
                
                const requestBody = targetType === 'all' ? {} : { employee_ids: employeeIds.map(id => parseInt(id)) };
                
                await this.apiCall(`${this.config.API_URL}/expire-leave`, {
                    method: 'POST',
                    body: requestBody
                });
                
                Toast.success('연차 소멸 처리가 완료되었습니다.');
                
                // 모달 닫기
                const modal = bootstrap.Modal.getInstance(this.elements.expireModal);
                if (modal) {
                    modal.hide();
                }
                
                this.loadDashboardData();
                
            } catch (error) {
                console.error('Failed to expire leave:', error);
                Toast.error(`연차 소멸 실패: ${error.message}`);
            } finally {
                this.resetButtonLoading('#expire-submit-btn');
            }
        }
    }

    /**
     * 리소스 정리
     */
    cleanup() {
        super.cleanup();
        
        // 차트 인스턴스 정리
        Object.values(this.state.charts).forEach(chart => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });
        
        this.state.charts = {};
    }
}

// 페이지 로드 시 인스턴스 생성
window.LeaveAdminDashboard = LeaveAdminDashboardPage;

// 디버깅을 위한 로그
console.log('LeaveAdminDashboardPage script loaded');

new LeaveAdminDashboardPage();