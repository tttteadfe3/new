document.addEventListener('DOMContentLoaded', () => {
    const API_BASE_URL = '/api/leaves_admin/history';

    const employeeSelect = document.getElementById('employee-select');
    const yearSelect = document.getElementById('year-select');
    const historyDisplay = document.getElementById('history-display');
    const entitlementSummary = document.getElementById('entitlement-summary');
    const leaveHistoryBody = document.getElementById('leave-history-body');

    const fetchOptions = (options = {}) => {
        const defaultHeaders = { 'X-Requested-With': 'XMLHttpRequest' };
        return { ...options, headers: { ...defaultHeaders, ...options.headers } };
    };

    const loadHistory = async () => {
        const employeeId = employeeSelect.value;
        const year = yearSelect.value;

        if (!employeeId) {
            historyDisplay.classList.add('d-none');
            return;
        }

        historyDisplay.classList.remove('d-none');
        entitlementSummary.innerHTML = `<span class="spinner-border spinner-border-sm"></span> 불러오는 중...`;
        leaveHistoryBody.innerHTML = `<tr><td colspan="6" class="text-center"><span class="spinner-border spinner-border-sm"></span></td></tr>`;

        try {
            const response = await fetch(`${API_BASE_URL}/${employeeId}?year=${year}`, fetchOptions());
            const result = await response.json();
            if (!result.success) throw new Error(result.message);
            renderHistory(result.data);
        } catch (error) {
            console.error('Error loading history:', error);
            entitlementSummary.innerHTML = `<span class="text-danger">오류: ${error.message}</span>`;
            leaveHistoryBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">내역 로딩 실패</td></tr>`;
        }
    };

    const renderHistory = (data) => {
        if (data.entitlement) {
            const { total_days, used_days } = data.entitlement;
            const remaining_days = parseFloat(total_days) - parseFloat(used_days);
            entitlementSummary.innerHTML = `
                총 <strong>${total_days}</strong>일 부여 /
                <strong>${used_days}</strong>일 사용 /
                <span class="fw-bold ${remaining_days < 0 ? 'text-danger' : 'text-primary'}">${remaining_days.toFixed(1)}</span>일 남음
            `;
        } else {
            entitlementSummary.innerHTML = `<span class="text-muted">${yearSelect.value}년 부여 내역 없음</span>`;
        }

        leaveHistoryBody.innerHTML = '';
        if (!data.leaves || data.leaves.length === 0) {
            leaveHistoryBody.innerHTML = `<tr><td colspan="6" class="text-center">사용 내역이 없습니다.</td></tr>`;
            return;
        }

        const statusBadges = { pending: 'bg-warning', approved: 'bg-success', rejected: 'bg-danger', cancelled: 'bg-secondary', cancellation_requested: 'bg-info' };
        const statusText = { pending: '대기', approved: '승인', rejected: '반려', cancelled: '취소', cancellation_requested: '취소요청' };

        data.leaves.forEach(leave => {
            const row = `
                <tr>
                    <td>${leave.leave_type}</td>
                    <td>${leave.start_date} ~ ${leave.end_date}</td>
                    <td>${leave.days_count}</td>
                    <td><span class="badge ${statusBadges[leave.status] || 'bg-light text-dark'}">${statusText[leave.status] || leave.status}</span></td>
                    <td>${new Date(leave.created_at).toLocaleDateString()}</td>
                    <td>${leave.reason || ''}</td>
                </tr>
            `;
            leaveHistoryBody.insertAdjacentHTML('beforeend', row);
        });
    };

    employeeSelect.addEventListener('change', loadHistory);
    yearSelect.addEventListener('change', loadHistory);
});