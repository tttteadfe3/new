document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('a[data-bs-toggle="tab"]');
    const bodies = {
        pending: document.getElementById('pending-requests-body'),
        approved: document.getElementById('approved-requests-body'),
        rejected: document.getElementById('rejected-requests-body'),
        cancellation: document.getElementById('cancellation-requests-body')
    };

    const fetchOptions = (options = {}) => {
        const defaultHeaders = {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        };
        return { ...options, headers: { ...defaultHeaders, ...options.headers } };
    };

    const loadRequests = async (status) => {
        const tableBody = bodies[status];
        if (!tableBody) return;
        tableBody.innerHTML = `<tr><td colspan="7" class="text-center">목록을 불러오는 중...</td></tr>`;

        try {
            const response = await fetch(`../api/leaves_admin.php?action=list_requests&status=${status}`, fetchOptions());
            const result = await response.json();
            if (!result.success) throw new Error(result.message);
            renderTable(status, result.data);
        } catch (error) {
            console.error(`Error loading ${status} requests:`, error);
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">목록 로딩 실패: ${error.message}</td></tr>`;
        }
    };

    const renderTable = (status, data) => {
        const tableBody = bodies[status];
        tableBody.innerHTML = '';
        if (data.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center">해당 상태의 신청 내역이 없습니다.</td></tr>`;
            return;
        }

        data.forEach(item => {
            let row = `
                <tr>
                    <td>${item.employee_name}</td>
                    <td>${item.department_name || '<i>미지정</i>'}</td>
                    <td>${item.start_date} ~ ${item.end_date}</td>
                    <td>${item.days_count}일</td>
            `;
            if (status === 'pending') {
                row += `
                    <td>${new Date(item.created_at).toLocaleDateString()}</td>
                    <td>${item.reason || ''}</td>
                    <td>
                        <button class="btn btn-success btn-sm approve-btn" data-id="${item.id}">승인</button>
                        <button class="btn btn-danger btn-sm reject-btn ms-1" data-id="${item.id}">반려</button>
                    </td>`;
            } else if (status === 'approved') {
                row += `
                    <td>${new Date(item.updated_at).toLocaleDateString()}</td>
                    <td>${item.approver_name || 'N/A'}</td>`;
            } else if (status === 'rejected') {
                row += `
                    <td>${new Date(item.updated_at).toLocaleDateString()}</td>
                    <td>${item.rejection_reason || ''}</td>
                    <td>${item.approver_name || 'N/A'}</td>`;
            } else if (status === 'cancellation') {
                 row += `
                    <td>${item.cancellation_reason || ''}</td>
                    <td>
                        <button class="btn btn-success btn-sm approve-cancel-btn" data-id="${item.id}">취소 승인</button>
                        <button class="btn btn-danger btn-sm reject-cancel-btn ms-1" data-id="${item.id}">취소 반려</button>
                    </td>`;
            }
            row += `</tr>`;
            tableBody.insertAdjacentHTML('beforeend', row);
        });
    };

    const handleAction = async (action, leaveId, reason = null) => {
        const messages = {
            approve_request: '승인',
            reject_request: '반려',
            approve_cancellation: '취소 승인',
            reject_cancellation: '취소 반려'
        };

        try {
            const response = await fetch(`../api/leaves_admin.php?action=${action}`, fetchOptions({
                method: 'POST',
                body: JSON.stringify({ id: leaveId, reason: reason })
            }));
            const result = await response.json();
            if (!result.success) throw new Error(result.message);
            Toast.success(`${messages[action]} 처리가 완료되었습니다.`);
            // Find which tab is active and reload it
            const activeTab = document.querySelector('.nav-link.active');
            const status = activeTab.getAttribute('href').replace('-tab', '').substring(1);
            loadRequests(status);
        } catch (error) {
             Toast.error(`처리 중 오류 발생: ${error.message}`);
        }
    };

    bodies.pending.addEventListener('click', async (e) => {
        const button = e.target.closest('button');
        if (!button) return;
        const leaveId = button.dataset.id;

        if (button.classList.contains('approve-btn')) {
            const result = await Confirm.fire('연차 신청 승인', '이 연차 신청을 승인하시겠습니까?');
            if (result.isConfirmed) {
                handleAction('approve_request', leaveId);
            }
        }

        if (button.classList.contains('reject-btn')) {
            const { value: reason } = await Swal.fire({
                title: '연차 신청 반려',
                input: 'text',
                inputLabel: '반려 사유',
                inputPlaceholder: '반려 사유를 입력하세요...',
                showCancelButton: true,
                confirmButtonText: '반려',
                cancelButtonText: '취소',
                inputValidator: (value) => !value && '반려 사유는 필수입니다.'
            });
            if (reason) {
                handleAction('reject_request', leaveId, reason);
            }
        }
    });

    bodies.cancellation.addEventListener('click', async (e) => {
        const button = e.target.closest('button');
        if (!button) return;
        const leaveId = button.dataset.id;

        if (button.classList.contains('approve-cancel-btn')) {
            const result = await Confirm.fire('연차 취소 승인', '이 연차 취소 요청을 승인하시겠습니까? 사용일수가 복원됩니다.');
            if (result.isConfirmed) {
                handleAction('approve_cancellation', leaveId);
            }
        }

        if (button.classList.contains('reject-cancel-btn')) {
            const { value: reason } = await Swal.fire({
                title: '연차 취소 반려',
                input: 'text',
                inputLabel: '반려 사유',
                inputPlaceholder: '사용자에게 전달될 반려 사유를 입력하세요...',
                showCancelButton: true,
                confirmButtonText: '반려',
                cancelButtonText: '취소',
                inputValidator: (value) => !value && '반려 사유는 필수입니다.'
            });
            if (reason) {
                handleAction('reject_cancellation', leaveId, reason);
            }
        }
    });

    tabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', (event) => {
            const status = event.target.getAttribute('href').replace('-tab', '').substring(1);
            loadRequests(status);
        });
    });

    // Initial load
    loadRequests('pending');
});
