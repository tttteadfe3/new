// js/employees.js
document.addEventListener('DOMContentLoaded', () => {
    // DOM 요소 캐싱
    const employeeTableBody = document.getElementById('employee-table-body');
    const addEmployeeBtn = document.getElementById('add-employee-btn');
    const employeeModal = new bootstrap.Modal(document.getElementById('employee-modal'));
    const employeeForm = document.getElementById('employee-form');
    const modalTitle = document.getElementById('modal-title');
    const deleteBtn = document.getElementById('delete-btn');
    const historyContainer = document.getElementById('change-history-container');
    const historySeparator = document.getElementById('history-separator');
    const historyList = document.getElementById('history-log-list');
    const filterDepartment = document.getElementById('filter-department');
    const filterPosition = document.getElementById('filter-position');
    const filterStatus = document.getElementById('filter-status');

    let allDepartments = [];
    let allPositions = [];

    const fetchOptions = (options = {}) => {
        const defaultHeaders = {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        };
        return { ...options, headers: { ...defaultHeaders, ...options.headers } };
    };

    const sanitizeHTML = (str) => {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    };

    const renderEmployeeTable = (employeeList) => {
        employeeTableBody.innerHTML = '';
        if (employeeList.length === 0) {
            employeeTableBody.innerHTML = `<tr><td colspan="8" class="text-center">해당 조건의 직원이 없습니다.</td></tr>`;
            return;
        }

        employeeList.forEach(employee => {
            const linkedUser = employee.nickname ? `<span class="badge bg-primary">${sanitizeHTML(employee.nickname)}</span>` : '<span class="text-muted"><i>없음</i></span>';
            const statusInfo = employee.profile_update_status === 'pending' ? `<span class="badge bg-warning ms-2">수정 요청</span>`
                            : (employee.profile_update_status === 'rejected' ? `<span class="badge bg-danger ms-2">반려됨</span>` : '');

            const actionButtons = employee.profile_update_status === 'pending'
                ? `<button class="btn btn-success btn-sm approve-btn" data-id="${employee.id}">승인</button>
                   <button class="btn btn-danger btn-sm reject-btn ms-1" data-id="${employee.id}">반려</button>`
                : '';
            
            const terminationDate = employee.termination_date ? `<span class="text-danger">${sanitizeHTML(employee.termination_date)}</span>` : '';

            const row = `
                <tr>
                    <td>${sanitizeHTML(employee.name)} ${statusInfo}</td>
                    <td>${sanitizeHTML(employee.department_name) || '<i>미지정</i>'}</td>
                    <td>${sanitizeHTML(employee.position_name) || '<i>미지정</i>'}</td>
                    <td>${sanitizeHTML(employee.employee_number)}</td>
                    <td>${sanitizeHTML(employee.hire_date)}</td>
                    <td>${terminationDate}</td>
                    <td>${linkedUser}</td>
                    <td class="text-nowrap">
                        <button class="btn btn-secondary btn-sm edit-btn" data-id="${employee.id}">수정</button>
                        ${actionButtons}
                    </td>
                </tr>`;
            employeeTableBody.insertAdjacentHTML('beforeend', row);
        });
    };

    const populateDropdowns = (selects, data, defaultOptionText) => {
        selects.forEach(select => {
            select.innerHTML = `<option value="">${defaultOptionText}</option>`;
            data.forEach(item => {
                select.insertAdjacentHTML('beforeend', `<option value="${item.id}">${sanitizeHTML(item.name)}</option>`);
            });
        });
    };

    const loadEmployees = async () => {
        const deptId = filterDepartment.value;
        const posId = filterPosition.value;
        const status = filterStatus.value;

        let url = '../api/employees.php?action=list';
        if (deptId) url += `&department_id=${deptId}`;
        if (posId) url += `&position_id=${posId}`;
        if (status) url += `&status=${status}`;

        try {
            const response = await fetch(url, fetchOptions());
            const result = await response.json();
            if (!result.success) throw new Error(result.message);
            renderEmployeeTable(result.data);
        } catch (error) {
            console.error('Error loading employees:', error);
            employeeTableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">목록 로딩 실패: ${error.message}</td></tr>`;
        }
    };

    const loadInitialData = async () => {
        try {
            const status = filterStatus.value;
            let url = '../api/employees.php?action=get_initial_data';
            if (status) url += `&status=${status}`;
            const response = await fetch(url, fetchOptions());
            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            const { employees, departments, positions } = result.data;

            allDepartments = departments;
            allPositions = positions;

            renderEmployeeTable(employees);
            populateDropdowns([document.getElementById('department_id'), filterDepartment], allDepartments, '부서 선택');
            populateDropdowns([document.getElementById('position_id'), filterPosition], allPositions, '직급 선택');

        } catch (error) {
            console.error('Error loading initial data:', error);
            employeeTableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">목록 로딩 실패: ${error.message}</td></tr>`;
        }
    };

    addEmployeeBtn.addEventListener('click', () => {
        employeeForm.reset();
        modalTitle.textContent = '신규 직원 정보 등록';
        deleteBtn.classList.add('d-none');
        historyContainer.classList.add('d-none');
        historySeparator.classList.add('d-none');
        employeeForm.id.value = '';
        employeeForm.employee_number.placeholder = '입사일 지정 후 자동 생성';
        employeeForm.employee_number.readOnly = true;
        employeeForm.hire_date.readOnly = false;
        employeeModal.show();
    });

    employeeTableBody.addEventListener('click', async (e) => {
        const target = e.target;
        const employeeId = target.dataset.id;
        if (!employeeId) return;

        if (target.classList.contains('edit-btn')) {
            try {
                const [detailsRes, historyRes] = await Promise.all([
                    fetch(`../api/employees.php?action=get_one&id=${employeeId}`, fetchOptions()),
                    fetch(`../api/employees.php?action=get_change_history&id=${employeeId}`, fetchOptions())
                ]);
                const detailsResult = await detailsRes.json();
                const historyResult = await historyRes.json();
                if (!detailsResult.success || !historyResult.success) throw new Error('데이터 로딩 실패');

                employeeForm.reset();
                modalTitle.textContent = '직원 정보 수정';
                deleteBtn.classList.remove('d-none');
                employeeForm.employee_number.readOnly = false;
                employeeForm.hire_date.readOnly = true;

                const employee = detailsResult.data;
                employeeForm.id.value = employee.id;
                employeeForm.name.value = employee.name;
                employeeForm.department_id.value = employee.department_id || '';
                employeeForm.position_id.value = employee.position_id || '';
                employeeForm.employee_number.value = employee.employee_number || '';
                employeeForm.hire_date.value = employee.hire_date || '';
                employeeForm.phone_number.value = employee.phone_number || '';
                employeeForm.address.value = employee.address || '';
                employeeForm.emergency_contact_name.value = employee.emergency_contact_name || '';
                employeeForm.emergency_contact_relation.value = employee.emergency_contact_relation || '';
                employeeForm.clothing_top_size.value = employee.clothing_top_size || '';
                employeeForm.clothing_bottom_size.value = employee.clothing_bottom_size || '';
                employeeForm.shoe_size.value = employee.shoe_size || '';

                historyList.innerHTML = '';
                if (historyResult.data.length > 0) {
                    historyResult.data.forEach(log => {
                        const item = `
                            <div class="list-group-item">
                                <p class="mb-1"><strong>${sanitizeHTML(log.field_name)}:</strong> <span class="text-danger text-decoration-line-through">${sanitizeHTML(log.old_value)}</span> → <span class="text-success fw-bold">${sanitizeHTML(log.new_value)}</span></p>
                                <small class="text-muted">${log.changed_at} by ${sanitizeHTML(log.changer_name) || 'System'}</small>
                            </div>`;
                        historyList.insertAdjacentHTML('beforeend', item);
                    });
                    historySeparator.classList.remove('d-none');
                    historyContainer.classList.remove('d-none');
                } else {
                    historySeparator.classList.add('d-none');
                    historyContainer.classList.add('d-none');
                }

                employeeModal.show();
            } catch (error) {
                console.error('Error fetching employee details:', error);
                Toast.error('직원 정보를 불러오는 데 실패했습니다.');
            }
        }

        if (target.classList.contains('approve-btn')) {
            const result = await Confirm.fire('승인 확인', '이 사용자의 프로필 변경 요청을 승인하시겠습니까?');
            if (!result.isConfirmed) return;

            try {
                const response = await fetch('../api/employees.php?action=approve_update', fetchOptions({
                    method: 'POST', body: JSON.stringify({ id: employeeId })
                }));
                const result = await response.json();
                if (!result.success) throw new Error(result.message);
                Toast.success(result.message);
                loadEmployees();
            } catch (error) {
                console.error('Error approving update:', error);
                Toast.error('승인 처리 중 오류가 발생했습니다.');
            }
        }

        if (target.classList.contains('reject-btn')) {
            const { value: reason } = await Swal.fire({
                title: '프로필 변경 요청 반려',
                input: 'text',
                inputPlaceholder: '반려 사유를 입력해주세요.',
                showCancelButton: true,
                cancelButtonText: '취소',
                confirmButtonText: '확인',
                inputValidator: (value) => {
                    if (!value) {
                        return '반려 사유를 반드시 입력해야 합니다.'
                    }
                }
            });

            if (reason) {
                try {
                    const response = await fetch('../api/employees.php?action=reject_update', fetchOptions({
                        method: 'POST', body: JSON.stringify({ id: employeeId, reason: reason })
                    }));
                    const result = await response.json();
                    if (!result.success) throw new Error(result.message);
                    Toast.success(result.message);
                    loadEmployees();
                } catch (error) {
                    console.error('Error rejecting update:', error);
                    Toast.error(`반려 처리 중 오류: ${error.message}`);
                }
            }
        }
    });

    employeeForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const action = e.submitter && e.submitter.id === 'delete-btn' ? 'delete' : 'save';
        
        if (action === 'delete') {
            const result = await Confirm.fire('삭제 확인', '정말로 이 직원의 정보를 삭제하시겠습니까? 사용자 계정과의 연결도 해제됩니다.');
            if (!result.isConfirmed) return;
        }

        const formData = new FormData(employeeForm);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('../api/employees.php?action=' + action, fetchOptions({
                method: 'POST', body: JSON.stringify(data)
            }));
            const result = await response.json();
            if (!result.success) throw new Error(result.message);
            employeeModal.hide();
            loadEmployees();
            Toast.success(result.message);
        } catch (error) {
            console.error(`Error ${action} employee:`, error);
            Toast.error(`작업 처리 중 오류가 발생했습니다: ${error.message}`);
        }
    });

    filterDepartment.addEventListener('change', loadEmployees);
    filterPosition.addEventListener('change', loadEmployees);
    filterStatus.addEventListener('change', loadEmployees);

    loadInitialData();
});