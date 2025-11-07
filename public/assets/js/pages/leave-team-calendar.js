/**
 * TeamCalendar - 팀 연차 캘린더 페이지 클래스
 * 
 * 주요 기능:
 * - 팀별 연차 캘린더 표시 (FullCalendar 사용)
 * - 중복 휴가자 표시
 * - 팀원 연차 현황 조회
 * - 월별 휴가 통계 (Chart.js 사용)
 * 
 * 요구사항: 8.1, 8.3, 8.4
 */
class TeamCalendar extends BasePage {
    constructor() {
        super({
            API_URL: '/leaves_admin'
        });
        
        this.elements = {};
        this.state = {
            currentDate: new Date(),
            selectedDepartment: null,
            calendarData: {},
            teamStatus: [],
            monthlyStats: {}
        };
        
        // FullCalendar 및 Chart.js 인스턴스
        this.calendar = null;
        this.statsChart = null;
    }

    initializeApp() {
        this.cacheDOMElements();
        this.setupEventListeners();
        this.loadInitialData();
    }

    cacheDOMElements() {
        // 필터 및 컨트롤 요소들
        this.elements.departmentFilter = document.getElementById('department-filter');
        
        // FullCalendar 컨테이너
        this.elements.calendarContainer = document.getElementById('team-calendar');
        
        // Chart.js 캔버스
        this.elements.statsChartCanvas = document.getElementById('leave-stats-chart');
        
        // 팀원 현황 관련 요소들
        this.elements.teamStatusBody = document.getElementById('team-status-body');
        
        // 월별 통계 관련 요소들
        this.elements.monthTotalLeaves = document.getElementById('month-total-leaves');
        this.elements.monthOverlapDays = document.getElementById('month-overlap-days');
        this.elements.monthActiveEmployees = document.getElementById('month-active-employees');
        this.elements.monthHolidays = document.getElementById('month-holidays');
        
        // 일별 상세 모달
        this.elements.dayDetailModal = document.getElementById('day-detail-modal');
        this.elements.dayDetailTitle = document.getElementById('day-detail-title');
        this.elements.dayDetailContent = document.getElementById('day-detail-content');
    }

    setupEventListeners() {
        // 부서 필터 변경
        if (this.elements.departmentFilter) {
            this.elements.departmentFilter.addEventListener('change', () => {
                this.state.selectedDepartment = this.elements.departmentFilter.value;
                this.loadCalendarData();
                this.loadTeamStatus();
                this.loadMonthlyStats();
            });
        }
    }

    loadInitialData() {
        this.loadDepartments();
        this.initializeCalendar();
        this.initializeChart();
        
        // 캘린더 초기화 후 약간의 지연을 두고 데이터 로드
        setTimeout(() => {
            this.loadTeamStatus();
            this.loadMonthlyStats();
            // loadCalendarData는 datesSet 콜백에서 자동으로 호출됨
        }, 200);
    }

    /**
     * 부서 목록 로드
     */
    async loadDepartments() {
        try {
            const response = await this.apiCall('/organization/managable-departments');
            
            if (this.elements.departmentFilter) {
                this.elements.departmentFilter.innerHTML = '<option value="">내 부서</option>';
                response.data.forEach(dept => {
                    const option = new Option(dept.name, dept.id);
                    this.elements.departmentFilter.add(option);
                });
            }
            
        } catch (error) {
            console.error('Failed to load departments:', error);
            console.error('부서 목록을 불러오는데 실패했습니다.');
        }
    }

    /**
     * FullCalendar 초기화
     */
    initializeCalendar() {
        if (!this.elements.calendarContainer || typeof FullCalendar === 'undefined') {
            console.warn('FullCalendar가 로드되지 않았거나 캘린더 컨테이너를 찾을 수 없습니다.');
            return;
        }

        try {
            this.calendar = new FullCalendar.Calendar(this.elements.calendarContainer, {
                initialView: 'dayGridMonth',
                locale: 'ko',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth'
                },
                height: 'auto',
                dayMaxEvents: 3,
                moreLinkClick: 'popover',
                eventClick: (info) => {
                    this.showDayDetail(info.event.startStr);
                },
                dateClick: (info) => {
                    this.showDayDetail(info.dateStr);
                },
                datesSet: (info) => {
                    this.state.currentDate = info.start;
                    // 약간의 지연을 두고 데이터 로드
                    setTimeout(() => {
                        this.loadCalendarData();
                        this.loadMonthlyStats();
                    }, 100);
                }
            });

            this.calendar.render();
        } catch (error) {
            console.error('FullCalendar 초기화 실패:', error);
        }
    }

    /**
     * Chart.js 초기화
     */
    initializeChart() {
        if (!this.elements.statsChartCanvas || typeof Chart === 'undefined') {
            console.warn('Chart.js가 로드되지 않았거나 캔버스 요소를 찾을 수 없습니다.');
            return;
        }

        try {
            const ctx = this.elements.statsChartCanvas.getContext('2d');
            
            this.statsChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['사용된 연차', '남은 연차', '중복 일수'],
                    datasets: [{
                        data: [0, 0, 0],
                        backgroundColor: [
                            '#007bff',
                            '#28a745', 
                            '#ffc107'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': ' + context.parsed + '일';
                                }
                            }
                        }
                    },
                    cutout: '60%'
                }
            });
        } catch (error) {
            console.error('Chart.js 초기화 실패:', error);
        }
    }

    /**
     * 캘린더 데이터 로드
     */
    async loadCalendarData() {
        if (!this.calendar) return;

        const calendarApi = this.calendar.getApi();

        const currentDate = calendarApi.getDate();
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth() + 1;
        
        const departmentId = this.state.selectedDepartment || '';

        try {
            const response = await this.apiCall(`${this.config.API_URL}/team-calendar?year=${year}&month=${month}&department_id=${departmentId}`);
            
            this.state.calendarData = response.data;
            this.renderCalendarEvents(response.data);
            
        } catch (error) {
            console.error('Failed to load calendar data:', error);
            console.error('캘린더 데이터를 불러오는데 실패했습니다.');
        }
    }

    /**
     * FullCalendar 이벤트 렌더링
     */
    renderCalendarEvents(calendarData) {
        if (!this.calendar || !calendarData) return;

        const calendarApi = this.calendar.getApi();
        
        // 기존 이벤트 제거
        calendarApi.removeAllEvents();

        // 휴가 이벤트 추가
        const leavesData = calendarData.leaves || calendarData || [];
        
        if (Array.isArray(leavesData)) {
            leavesData.forEach(leave => {
                if (!leave || !leave.start_date) return;
                
                const employeeName = leave.employee_name || leave.name || '이름 없음';
                const dayType = leave.day_type || 'FULL';
                
                const event = {
                    id: leave.id || 'leave-' + Math.random(),
                    title: employeeName + (dayType === 'HALF' ? ' (반차)' : ''),
                    start: leave.start_date,
                    end: leave.end_date || leave.start_date,
                    className: dayType === 'HALF' ? 'half-day' : 'full-day',
                    extendedProps: {
                        employee_name: employeeName,
                        department_name: leave.department_name || '',
                        day_type: dayType,
                        leave_type: leave.leave_type || 'ANNUAL'
                    }
                };

                calendarApi.addEvent(event);
            });
        }

        // 중복 휴가 표시
        if (Array.isArray(calendarData.overlaps)) {
            calendarData.overlaps.forEach(overlap => {
                if (!overlap || !overlap.date) return;
                
                const event = {
                    id: 'overlap-' + overlap.date,
                    title: `${overlap.count || 2}명 휴가`,
                    start: overlap.date,
                    allDay: true,
                    className: 'multiple-leaves',
                    display: 'background'
                };

                calendarApi.addEvent(event);
            });
        }

        // 휴일 표시
        if (Array.isArray(calendarData.holidays)) {
            calendarData.holidays.forEach(holiday => {
                if (!holiday || !holiday.date) return;
                
                const event = {
                    id: 'holiday-' + holiday.date,
                    title: holiday.name || '휴일',
                    start: holiday.date,
                    allDay: true,
                    className: 'holiday',
                    display: 'background'
                };

                calendarApi.addEvent(event);
            });
        }
    }

    /**
     * 팀원 현황 로드
     */
    async loadTeamStatus() {
        if (!this.elements.teamStatusBody) return;

        const departmentId = this.state.selectedDepartment || '';

        try {
            this.setTableLoading(this.elements.teamStatusBody, '팀원 현황 로딩 중...');
            
            const response = await this.apiCall(`${this.config.API_URL}/team-status?department_id=${departmentId}`);
            
            this.state.teamStatus = response.data;
            this.renderTeamStatusTable(response.data);
            
        } catch (error) {
            console.error('Failed to load team status:', error);
            this.setTableError(this.elements.teamStatusBody, `로딩 실패: ${error.message}`);
        }
    }

    /**
     * 팀원 현황 테이블 렌더링
     */
    renderTeamStatusTable(teamStatus) {
        if (!this.elements.teamStatusBody) return;

        if (teamStatus.length === 0) {
            this.elements.teamStatusBody.innerHTML = `
                <tr><td colspan="7" class="text-center">팀원 정보가 없습니다.</td></tr>
            `;
            return;
        }

        this.elements.teamStatusBody.innerHTML = teamStatus.map(member => {
            // 부여된 연차와 사용한 연차 정보 추출
            const grantedDays = parseFloat(member.granted_days) || 0;
            const usedDays = parseFloat(member.used_days_this_year) || 0;
            const remainingDays = parseFloat(member.remaining_days) || parseFloat(member.current_balance) || 0;
            
            // 사용률 계산 (부여된 연차가 0이면 0%로 표시)
            const usageRate = grantedDays > 0 ? Math.round((usedDays / grantedDays) * 100) : 0;
            
            const memberName = member.name || member.employee_name || '이름 없음';
            const avatarLetter = memberName.charAt(0).toUpperCase();
            
            return `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="employee-avatar">${avatarLetter}</div>
                            <div class="ms-2">
                                <div class="fw-bold">${memberName}</div>
                                <small class="text-muted">${member.position_name || '직급 미지정'}</small>
                            </div>
                        </div>
                    </td>
                    <td>${member.position_name || '<i>미지정</i>'}</td>
                    <td class="text-center">${grantedDays}일</td>
                    <td class="text-center">${usedDays}일</td>
                    <td class="text-center fw-bold text-primary">${remainingDays}일</td>
                    <td>
                        <div class="usage-rate-bar">
                            <div class="usage-rate-fill" style="width: ${usageRate}%"></div>
                        </div>
                        <small class="text-muted">${usageRate}%</small>
                    </td>
                    <td>
                        <div class="monthly-plan">
                            ${this.renderMonthlyPlan(member.monthly_plans || [])}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    /**
     * 월별 계획 렌더링
     */
    renderMonthlyPlan(plans) {
        if (!plans || plans.length === 0) return '<span class="text-muted">계획 없음</span>';

        return plans.map(plan => {
            const startDate = new Date(plan.start_date).toLocaleDateString('ko-KR', {month: 'short', day: 'numeric'});
            const statusClass = plan.status === '승인' ? 'text-success' : 'text-warning';
            const statusText = plan.status === '승인' ? '승인' : '대기';
            return `<span class="plan-item ${statusClass}" title="${statusText}">${startDate} (${plan.days}일)</span>`;
        }).join(' ');
    }

    /**
     * 월별 통계 로드
     */
    async loadMonthlyStats() {
        const year = this.state.currentDate.getFullYear();
        const month = this.state.currentDate.getMonth() + 1;
        const departmentId = this.state.selectedDepartment || '';

        try {
            const response = await this.apiCall(`${this.config.API_URL}/monthly-stats?year=${year}&month=${month}&department_id=${departmentId}`);
            
            this.state.monthlyStats = response.data;
            this.updateMonthlyStats(response.data);
            
        } catch (error) {
            console.error('Failed to load monthly stats:', error);
        }
    }

    /**
     * 월별 통계 업데이트
     */
    updateMonthlyStats(stats) {
        // 숫자 통계 업데이트
        if (this.elements.monthTotalLeaves) {
            this.elements.monthTotalLeaves.textContent = stats.total_leaves || '0';
        }
        
        if (this.elements.monthOverlapDays) {
            this.elements.monthOverlapDays.textContent = stats.overlap_days || '0';
        }
        
        if (this.elements.monthActiveEmployees) {
            this.elements.monthActiveEmployees.textContent = stats.active_employees || '0';
        }
        
        // Chart.js 업데이트
        this.updateStatsChart(stats);
        
        // 휴일 정보 업데이트
        if (this.elements.monthHolidays) {
            this.renderMonthlyHolidays(stats.holidays || []);
        }
    }

    /**
     * Chart.js 통계 차트 업데이트
     */
    updateStatsChart(stats) {
        if (!this.statsChart) return;

        const totalLeaves = stats.total_leaves || 0;
        const overlapDays = stats.overlap_days || 0;
        const activeEmployees = stats.active_employees || 0;
        
        // 간단한 통계 계산 (실제로는 더 복잡한 로직이 필요할 수 있음)
        const remainingCapacity = Math.max(0, activeEmployees * 2 - totalLeaves); // 가정: 직원당 월 2일 평균

        this.statsChart.data.datasets[0].data = [
            totalLeaves,
            remainingCapacity,
            overlapDays
        ];

        this.statsChart.update('none'); // 애니메이션 없이 업데이트
    }

    /**
     * 월별 휴일 정보 렌더링
     */
    renderMonthlyHolidays(holidays) {
        if (!this.elements.monthHolidays) return;

        if (holidays.length === 0) {
            this.elements.monthHolidays.innerHTML = '<div class="text-muted text-center">휴일이 없습니다</div>';
            return;
        }

        this.elements.monthHolidays.innerHTML = holidays.map(holiday => `
            <div class="holiday-info">
                <span class="holiday-date">${holiday.date}</span>
                <span class="holiday-name">${holiday.name}</span>
            </div>
        `).join('');
    }

    /**
     * 일별 상세 정보 표시
     */
    async showDayDetail(dateStr) {
        if (!this.elements.dayDetailModal) return;

        try {
            const departmentId = this.state.selectedDepartment || '';
            const response = await this.apiCall(`${this.config.API_URL}/day-detail?date=${dateStr}&department_id=${departmentId}`);
            
            this.renderDayDetail(dateStr, response.data);
            
            const modal = new bootstrap.Modal(this.elements.dayDetailModal);
            modal.show();
            
        } catch (error) {
            console.error('Failed to load day detail:', error);
            Toast.error('일별 상세 정보를 불러오는데 실패했습니다.');
        }
    }

    /**
     * 일별 상세 정보 렌더링
     */
    renderDayDetail(dateStr, dayData) {
        if (!this.elements.dayDetailTitle || !this.elements.dayDetailContent) return;

        const date = new Date(dateStr);
        this.elements.dayDetailTitle.textContent = `${date.getFullYear()}년 ${date.getMonth() + 1}월 ${date.getDate()}일 휴가 현황`;

        if (!dayData.leaves || dayData.leaves.length === 0) {
            this.elements.dayDetailContent.innerHTML = `
                <div class="text-center text-muted">이 날에는 휴가자가 없습니다.</div>
            `;
            return;
        }

        this.elements.dayDetailContent.innerHTML = `
            <div class="mb-3">
                <strong>총 ${dayData.leaves.length}명의 휴가자</strong>
            </div>
            ${dayData.leaves.map(leave => `
                <div class="day-detail-employee">
                    <div class="employee-info">
                        <div class="fw-bold">${leave.employee_name}</div>
                        <small class="text-muted">${leave.department_name || '부서 미지정'}</small>
                    </div>
                    <div class="leave-type ${leave.day_type === 'HALF' ? 'half' : 'full'}">
                        ${leave.day_type === 'HALF' ? '반차' : '전일'}
                    </div>
                </div>
            `).join('')}
        `;
    }

    /**
     * 날짜 포맷팅
     */
    formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    /**
     * 오늘 날짜인지 확인
     */
    isToday(date) {
        const today = new Date();
        return date.getDate() === today.getDate() &&
               date.getMonth() === today.getMonth() &&
               date.getFullYear() === today.getFullYear();
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
}

// 페이지 로드 시 인스턴스 생성
new TeamCalendar();