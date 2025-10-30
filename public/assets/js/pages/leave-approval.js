class LeaveApprovalPage extends BasePage {
    constructor() {
        super();
        this.elements = {};
        this.currentYear = new Date().getFullYear();
    }

    initializeApp() {
        this.cacheDOMElements();
        this.setupEventListeners();
        this.loadInitialData();
    }

    cacheDOMElements() {
        this.elements.tabs = document.querySelectorAll('a[data-bs-toggle="tab"]');
        this.elements.yearFilter = document.getElementById('year-filter');
        this.elements.departmentFilter = document.getElementById('department-filter');
        this.elements.bodies = {
            'pending': document.getElementById('pending-requests-body'),
            'cancellation_requested': document.getElementById('cancellation_requested-requests-body'),
            'approved': document.getElementById('approved-requests-body'),
            'rejected': document.getElementById('rejected-requests-body'),
            'cancelled': document.getElementById('cancelled-requests-body'),
        };
    }

    setupEventListeners() {
        this.elements.tabs.forEach(tab => {
            tab.addEventListener('shown.bs.tab', () => this.handleFilterChange());
        });
        [this.elements.yearFilter, this.elements.departmentFilter].forEach(filter => {
            filter.addEventListener('change', () => this.handleFilterChange());
        });
        Object.values(this.elements.bodies).forEach(body => {
            if(body) body.addEventListener('click', (e) => this.handleTableClick(e));
        });
    }

    async loadInitialData() {
        this.populateYearFilter();
        await this.loadFilterOptions();
        this.loadAllTabs();
    }

    populateYearFilter() {
        for (let i = 0; i < 5; i++) {
            const year = this.currentYear - i;
            this.elements.yearFilter.add(new Option(year, year));
        }
    }

    loadAllTabs() {
        Object.keys(this.elements.bodies).forEach(status => this.loadRequestsByStatus(status));
    }


    async loadFilterOptions() {
        try {
            const response = await this.apiCall('/organization/managable-departments');
            this.elements.departmentFilter.innerHTML = '<option value="">전체 부서</option>';
            response.data.forEach(dept => {
                this.elements.departmentFilter.add(new Option(dept.name, dept.id));
            });
        } catch (error) { Toast.error('부서 목록 로딩 실패'); }
    }

    handleFilterChange() {
        this.loadAllTabs();
    }

    async loadRequestsByStatus(status) {
        const year = this.elements.yearFilter.value;
        const departmentId = this.elements.departmentFilter.value;
        const params = new URLSearchParams({ status, year, department_id: departmentId });

        try {
            const response = await this.apiCall(`/admin/leaves/requests?${params}`);
            this.renderTable(status, response.data);
        } catch (error) {
            Toast.error(`${status} 목록 로딩 실패`);
            this.renderTable(status, []);
        }
    }

    renderTable(status, data) {
        const tableBody = this.elements.bodies[status];
        if (!tableBody) return;
        if (data.length === 0) {
            const colspan = status === 'pending' || status === 'cancellation_requested' ? 8 : 7;
            tableBody.innerHTML = `<tr><td colspan="${colspan}" class="text-center">내역 없음</td></tr>`;
            return;
        }
        tableBody.innerHTML = data.map(item => this.getTableRowHTML(status, item)).join('');
    }

    getTableRowHTML(status, item) {
        const approver = item.approver_name || 'N/A';
        const subtype = { 'full_day': '연차', 'half_day_am': '오전반차', 'half_day_pm': '오후반차' }[item.leave_subtype] || item.leave_subtype;

        let cols = `
            <td>${item.employee_name}</td>
            <td>${item.department_name || ''}</td>
            <td>${item.start_date} ~ ${item.end_date}</td>
            <td>${item.days_count}</td>
            <td>${subtype}</td>
        `;
        let actions = '';

        switch(status) {
            case 'pending':
                cols += `<td>${item.reason || ''}</td><td>${new Date(item.created_at).toLocaleDateString()}</td>`;
                actions = `<button class="btn btn-success btn-sm approve-btn" data-id="${item.id}">승인</button>
                           <button class="btn btn-danger btn-sm reject-btn ms-1" data-id="${item.id}">반려</button>`;
                break;
            case 'cancellation_requested':
                cols += `<td>${item.cancellation_reason || ''}</td><td>${new Date(item.updated_at).toLocaleDateString()}</td>`;
                actions = `<button class="btn btn-success btn-sm approve-cancel-btn" data-id="${item.id}">취소승인</button>
                           <button class="btn btn-danger btn-sm reject-cancel-btn ms-1" data-id="${item.id}">취소반려</button>`;
                break;
            case 'approved':
                cols += `<td>${new Date(item.updated_at).toLocaleDateString()}</td><td>${approver}</td>`;
                break;
            case 'rejected':
                cols += `<td>${item.rejection_reason || ''}</td><td>${approver}</td>`;
                break;
            case 'cancelled':
                cols += `<td>${new Date(item.updated_at).toLocaleDateString()}</td><td>${approver}</td>`;
                break;
        }

        return `<tr>${cols}${actions ? `<td>${actions}</td>` : ''}</tr>`;
    }

    async handleTableClick(e) {
        const target = e.target;
        const id = target.dataset.id;
        if (!id) return;

        if (target.classList.contains('approve-btn')) {
            this.handleAction('approve', id);
        } else if (target.classList.contains('reject-btn')) {
            this.handleAction('reject', id);
        } else if (target.classList.contains('approve-cancel-btn')) {
            this.handleAction('approve-cancel', id);
        } else if (target.classList.contains('reject-cancel-btn')) {
            this.handleAction('reject-cancel', id);
        }
    }

    async handleAction(action, id) {
        let confirmText, url, body = null, successMsg;

        switch(action) {
            case 'approve':
                confirmText = '연차 신청을 승인하시겠습니까?';
                url = `/admin/leaves/requests/${id}/approve`;
                successMsg = '승인 처리되었습니다.';
                break;
            case 'reject':
                const { value: reason } = await Swal.fire({
                    title: '연차 신청 반려',
                    input: 'text',
                    inputLabel: '반려 사유를 입력하세요',
                    inputPlaceholder: '반려 사유...',
                    showCancelButton: true,
                    confirmButtonText: '반려',
                    cancelButtonText: '취소'
                });
                if (!reason) return;
                confirmText = '연차 신청을 반려하시겠습니까?';
                url = `/admin/leaves/requests/${id}/reject`;
                body = { reason };
                successMsg = '반려 처리되었습니다.';
                break;
            case 'approve-cancel':
                confirmText = '연차 취소 요청을 승인하시겠습니까?';
                url = `/admin/leaves/requests/${id}/approve-cancellation`;
                successMsg = '취소 승인 처리되었습니다.';
                break;
            case 'reject-cancel':
                confirmText = '연차 취소 요청을 반려하시겠습니까?';
                url = `/admin/leaves/requests/${id}/reject-cancellation`;
                successMsg = '취소 반려 처리되었습니다.';
                break;
            default:
                return;
        }

        if (!await confirm(confirmText)) return;

        try {
            await this.apiCall(url, 'POST', body);
            Toast.success(successMsg);
            this.loadAllTabs();
        } catch (error) {
            Toast.error(error.message || '작업 실패');
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new LeaveApprovalPage().initializeApp();
});
