class LeaveHistoryAdminPage extends BasePage {
    constructor() {
        super();
    }

    initializeApp() {
        this.elements = {
            yearFilter: document.getElementById('filter-year'),
            departmentFilter: document.getElementById('filter-department'),
            statusFilter: document.getElementById('filter-status'),
            filterBtn: document.getElementById('filter-btn'),
            leaveHistoryBody: document.getElementById('leave-history-body'),
        };
        this.setupEventListeners();
        this.loadInitialData();
    }

    setupEventListeners() {
        this.elements.filterBtn.addEventListener('click', () => this.loadHistory());
    }

    async loadInitialData() {
        await this.loadDepartments();
        await this.loadHistory();
    }

    async loadDepartments() {
        try {
            const response = await this.apiCall('/organization/managable-departments');
            response.data.forEach(dept => {
                const option = new Option(dept.name, dept.id);
                this.elements.departmentFilter.add(option);
            });
        } catch (error) {
            Toast.error('부서 목록을 불러오는데 실패했습니다.');
        }
    }

    async loadHistory() {
        const year = this.elements.yearFilter.value;
        const departmentId = this.elements.departmentFilter.value;
        const status = this.elements.statusFilter.value;

        this.elements.leaveHistoryBody.innerHTML = `<tr><td colspan="7" class="text-center"><span class="spinner-border spinner-border-sm"></span> 목록을 불러오는 중...</td></tr>`;

        try {
            // 새로운 API 엔드포인트와 파라미터를 사용합니다.
            const response = await this.apiCall(`/api/admin/leaves/requests?year=${year}&department_id=${departmentId}&status=${status}`);
            this.renderHistory(response.data);
        } catch (error) {
            this.elements.leaveHistoryBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">내역 로딩 실패: ${error.message}</td></tr>`;
        }
    }

    renderHistory(data) {
        if (!data || data.length === 0) {
            this.elements.leaveHistoryBody.innerHTML = `<tr><td colspan="7" class="text-center">조회된 내역이 없습니다.</td></tr>`;
            return;
        }

        const statusBadges = {
            'pending': 'bg-warning',
            'approved': 'bg-success',
            'rejected': 'bg-danger',
            'cancelled': 'bg-secondary',
            'cancellation_requested': 'bg-info'
        };
        const leaveSubtypeMap = {
            'full_day': '연차',
            'half_day_am': '오전 반차',
            'half_day_pm': '오후 반차'
        };

        const rowsHtml = data.map(leave => `
            <tr>
                <td>${leave.employee_name || ''}</td>
                <td>${leave.department_name || ''}</td>
                <td>${leaveSubtypeMap[leave.leave_subtype] || leave.leave_subtype}</td>
                <td>${leave.start_date} ~ ${leave.end_date}</td>
                <td>${leave.days_count}</td>
                <td><span class="badge ${statusBadges[leave.status] || 'bg-light text-dark'}">${leave.status}</span></td>
                <td>${new Date(leave.created_at).toLocaleDateString()}</td>
            </tr>
        `).join('');
        this.elements.leaveHistoryBody.innerHTML = rowsHtml;
    }
}

new LeaveHistoryAdminPage();
