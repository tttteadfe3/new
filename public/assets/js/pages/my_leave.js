document.addEventListener('DOMContentLoaded', () => {
    // Summary elements
    const summaryRemaining = document.getElementById('summary-remaining');
    const summaryTotal = document.getElementById('summary-total');
    const historyBody = document.getElementById('leave-history-body');
    const currentYearSpan = document.getElementById('current-year');

    // Modal and form elements
    const requestLeaveBtn = document.getElementById('request-leave-btn');
    const requestModal = new bootstrap.Modal(document.getElementById('leave-request-modal'));
    const requestForm = document.getElementById('leave-request-form');
    const leaveTypeSelect = document.getElementById('leave_type');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const daysCountInput = document.getElementById('days_count');
    const feedbackDiv = document.getElementById('leave-date-feedback');

    const fetchOptions = (options = {}) => {
        const defaultHeaders = { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' };
        return { ...options, headers: { ...defaultHeaders, ...options.headers } };
    };

    const loadMyStatus = async () => {
        const year = new Date().getFullYear();
        currentYearSpan.textContent = year;
        summaryRemaining.textContent = '--';
        summaryTotal.textContent = '(총 --일 중 --일 사용)';
        historyBody.innerHTML = `<tr><td colspan="4" class="text-center">불러오는 중...</td></tr>`;

        try {
            const response = await fetch(`../api/leaves.php?action=get_my_status&year=${year}`, fetchOptions());
            const result = await response.json();
            if (!result.success) throw new Error(result.message);
            renderStatus(result.data);
        } catch (error) {
            console.error('Error loading status:', error);
            summaryRemaining.textContent = '오류';
            summaryTotal.textContent = error.message;
        }
    };

    const renderStatus = (data) => {
        if (data.entitlement) {
            const { total_days, used_days } = data.entitlement;
            const remaining_days = parseFloat(total_days) - parseFloat(used_days);
            summaryRemaining.textContent = `${remaining_days}일`;
            summaryTotal.textContent = `(총 ${total_days}일 중 ${used_days}일 사용)`;
        } else {
            summaryRemaining.textContent = '0일';
            summaryTotal.textContent = '(부여 내역 없음)';
        }

        historyBody.innerHTML = '';
        if (!data.leaves || data.leaves.length === 0) {
            historyBody.innerHTML = '<div>사용 내역이 없습니다.</div>';
            return;
        }

        const statusBadges = { pending: 'bg-warning', approved: 'bg-success', rejected: 'bg-danger', cancelled: 'bg-secondary', cancellation_requested: 'bg-info' };
        const statusText = { pending: '대기', approved: '승인', rejected: '반려', cancelled: '취소', cancellation_requested: '취소요청' };

        data.leaves.forEach(leave => {
            // Allow cancellation for pending and approved leaves
            const canCancel = leave.status === 'pending' || leave.status === 'approved';
            const cancelButton = canCancel ? `<button class="btn btn-link btn-sm p-0 cancel-btn" data-id="${leave.id}" data-status="${leave.status}">취소</button>` : '';
            
            let reasonText = '';
            if (leave.status === 'rejected' && leave.rejection_reason) {
                reasonText = `(반려 사유: ${leave.rejection_reason})`;
            } else if (leave.status === 'cancellation_requested' && leave.cancellation_reason) {
                reasonText = `(취소 요청 사유: ${leave.cancellation_reason})`;
            } else if (leave.status === 'approved' && leave.rejection_reason) {
                reasonText = `(취소 반려 사유: ${leave.rejection_reason})`;
            } else if (leave.reason) {
                reasonText = `(신청 사유: ${leave.reason})`;
            }

            const item = `
                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                    <div>
                        <span class="fw-bold">${leave.start_date} ~ ${leave.end_date}</span> (${leave.days_count}일)
                        <small class="text-muted ms-2">${reasonText}</small>
                    </div>
                    <div>
                        <span class="badge ${statusBadges[leave.status] || 'bg-light text-dark'}">${statusText[leave.status] || leave.status}</span>
                        ${cancelButton}
                    </div>
                </div>`;
            historyBody.insertAdjacentHTML('beforeend', item);
        });
    };

    const checkAndCalculateDays = async () => {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        const leaveType = leaveTypeSelect.value;

        daysCountInput.value = '';
        feedbackDiv.textContent = '시작일과 종료일을 선택하면 사용일수가 자동으로 계산됩니다.';
        feedbackDiv.classList.remove('text-danger', 'text-success');

        if (leaveType === 'half_day') {
            daysCountInput.value = 0.5;
            if (startDate) endDateInput.value = startDate;
            return;
        }

        if (!startDate || !endDate) return;

        if (new Date(startDate) > new Date(endDate)) {
            feedbackDiv.textContent = '오류: 시작일은 종료일보다 늦을 수 없습니다.';
            feedbackDiv.classList.add('text-danger');
            return;
        }

        feedbackDiv.innerHTML = '<span class="spinner-border spinner-border-sm"></span> 확인 중...';

        try {
            // 1. Check for overlap
            const overlapResponse = await fetch(`../api/leaves.php?action=check_overlap&start_date=${startDate}&end_date=${endDate}`, fetchOptions());
            const overlapResult = await overlapResponse.json();
            if (overlapResult.success && overlapResult.data.is_overlapping) {
                feedbackDiv.textContent = '경고: 해당 기간에 이미 신청한 연차가 존재합니다.';
                feedbackDiv.classList.add('text-danger');
            } else {
                feedbackDiv.textContent = '사용 가능한 기간입니다.';
                feedbackDiv.classList.add('text-success');
            }

            // 2. Calculate days
            const daysResponse = await fetch(`../api/leaves.php?action=calculate_days&start_date=${startDate}&end_date=${endDate}`, fetchOptions());
            const daysResult = await daysResponse.json();
            if (daysResult.success) {
                daysCountInput.value = daysResult.data.days;
            } else {
                throw new Error(daysResult.message);
            }
        } catch (error) {
            feedbackDiv.textContent = `오류: ${error.message}`;
            feedbackDiv.classList.add('text-danger');
        }
    };

    const handleLeaveTypeChange = () => {
        const isHalfDay = leaveTypeSelect.value === 'half_day';
        endDateInput.disabled = isHalfDay;
        daysCountInput.readOnly = !isHalfDay;

        if (isHalfDay) {
            if(startDateInput.value) endDateInput.value = startDateInput.value;
            daysCountInput.value = 0.5;
        } else {
             daysCountInput.readOnly = true;
        }
        checkAndCalculateDays();
    };

    if (requestLeaveBtn) {
        requestLeaveBtn.addEventListener('click', () => {
            requestForm.reset();
            handleLeaveTypeChange();
            feedbackDiv.textContent = '시작일과 종료일을 선택하면 사용일수가 자동으로 계산됩니다.';
            feedbackDiv.classList.remove('text-danger', 'text-success');
            requestModal.show();
        });
    }

    leaveTypeSelect.addEventListener('change', handleLeaveTypeChange);
    startDateInput.addEventListener('change', checkAndCalculateDays);
    endDateInput.addEventListener('change', checkAndCalculateDays);


    requestForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(requestForm).entries());

        if (new Date(data.start_date) > new Date(data.end_date)) {
            alert('시작일은 종료일보다 늦을 수 없습니다.');
            return;
        }
        if (!data.days_count || parseFloat(data.days_count) <= 0) {
            alert('사용 일수를 계산하거나 입력해주세요.');
            return;
        }

        try {
            const response = await fetch('../api/leaves.php?action=submit_request', fetchOptions({
                method: 'POST', body: JSON.stringify(data)
            }));
            const result = await response.json();
            if (!result.success) throw new Error(result.message);
            alert('연차 신청이 완료되었습니다.');
            requestModal.hide();
            loadMyStatus();
        } catch (error) {
            alert(`신청 실패: ${error.message}`);
        }
    });

    historyBody.addEventListener('click', async (e) => {
        const button = e.target.closest('button.cancel-btn');
        if (!button) return;

        const leaveId = button.dataset.id;
        const status = button.dataset.status;

        const cancelRequest = async (reason = null) => {
            try {
                const payload = { id: leaveId, reason: reason };
                const response = await fetch('../api/leaves.php?action=cancel_request', fetchOptions({
                    method: 'POST',
                    body: JSON.stringify(payload)
                }));
                const result = await response.json();
                if (!result.success) throw new Error(result.message);
                
                Swal.fire('처리 완료', result.message, 'success');
                loadMyStatus();
            } catch (error) {
                Swal.fire('오류', `취소 처리 중 오류가 발생했습니다: ${error.message}`, 'error');
            }
        };

        if (status === 'approved') {
            Swal.fire({
                title: '승인된 연차 취소',
                input: 'textarea',
                inputLabel: '취소 사유',
                inputPlaceholder: '취소 사유를 입력해주세요...',
                inputAttributes: { 'aria-label': 'Type your message here' },
                showCancelButton: true,
                confirmButtonText: '취소 요청',
                cancelButtonText: '닫기',
                inputValidator: (value) => {
                    if (!value) {
                        return '취소 사유를 반드시 입력해야 합니다.'
                    }
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    cancelRequest(result.value);
                }
            });
        } else if (status === 'pending') {
            Swal.fire({
                title: '연차 신청 취소',
                text: "이 신청을 취소하시겠습니까?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '예, 취소합니다.',
                cancelButtonText: '아니오'
            }).then((result) => {
                if (result.isConfirmed) {
                    cancelRequest();
                }
            });
        }
    });

    loadMyStatus();
});
