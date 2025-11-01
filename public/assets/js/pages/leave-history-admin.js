class LeaveHistoryAdminPage extends BasePage {
    constructor() {
        super({
            API_URL: '/admin/leaves/logs'
        });
        this.elements = {};
    }

    initializeApp() {
        this.cacheDOMElements();
        this.setupEventListeners();
        this.loadInitialData();
    }

    cacheDOMElements() {
        this.elements = {
            yearFilter: document.getElementById('filter-year'),
            departmentFilter: document.getElementById('filter-department'),
            statusFilter: document.getElementById('filter-status'),
            filterBtn: document.getElementById('filter-btn'),
            leaveHistoryBody: document.getElementById('leave-history-body'),
        };
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
            console.error('Failed to load departments:', error);
            Toast.error('부서 목록을 불러오는데 실패했습니다.');
        }
    }

    async loadHistory() {
        const year = this.elements.yearFilter.value;
        const departmentId = this.elements.departmentFilter.value;
        const status = this.elements.statusFilter.value;

        this.elements.leaveHistoryBody.innerHTML = `<tr><td colspan="7" class="text-center"><span class="spinner-border spinner-border-sm"></span> 목록을 불러오는 중...</td></tr>`;

        try {
            // Note: The API URL is now just /history, not /history/{id}
            const response = await this.apiCall(`${this.config.API_URL}?year=${year}&department_id=${departmentId}&status=${status}`);
            this.renderHistory(response.data);
        } catch (error) {
            console.error('Error loading history:', error);
            this.elements.leaveHistoryBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">내역 로딩 실패: ${error.message}</td></tr>`;
        }
    }

    renderHistory(data) {
        if (!data || data.length === 0) {
            this.elements.leaveHistoryBody.innerHTML = `<tr><td colspan="6" class="text-center">조회된 로그가 없습니다.</td></tr>`;
            return;
        }

        const typeBadges = {
            '부여': 'bg-success', '사용': 'bg-primary', '사용취소': 'bg-info',
            '소멸': 'bg-secondary', '포상': 'bg-success', '징계': 'bg-danger', '기타조정': 'bg-warning'
        };

        const rowsHtml = data.map(log => `
            <tr>
                <td>${log.employee_name || 'N/A'}</td>
                <td><span class="badge ${typeBadges[log.type] || 'bg-light text-dark'}">${log.type}</span></td>
                <td>${log.days}</td>
                <td>${log.reason || ''}</td>
                <td>${log.actor_name || '시스템'}</td>
                <td>${new Date(log.created_at).toLocaleString()}</td>
            </tr>
        `).join('');
        this.elements.leaveHistoryBody.innerHTML = rowsHtml;
    }
}

new LeaveHistoryAdminPage();