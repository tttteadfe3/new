document.addEventListener('DOMContentLoaded', () => {
    // Filters
    const yearFilter = document.getElementById('filter-year');
    const departmentFilter = document.getElementById('filter-department');

    // Buttons
    const calculateBtn = document.getElementById('calculate-btn');
    const saveBtn = document.getElementById('save-btn');

    // Table
    const tableBody = document.getElementById('entitlement-table-body');

    // Modal
    const adjustmentModal = new bootstrap.Modal(document.getElementById('adjustment-modal'));
    const adjustmentForm = document.getElementById('adjustment-form');

    // In-memory data store to hold employee and leave data
    let employeeDataStore = [];

    const fetchOptions = (options = {}) => {
        const defaultHeaders = {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        };
        return { ...options, headers: { ...defaultHeaders, ...options.headers } };
    };

    const renderTable = () => {
        tableBody.innerHTML = '';
        if (!employeeDataStore || employeeDataStore.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="11" class="text-center">표시할 직원이 없습니다.</td></tr>`;
            return;
        }

        employeeDataStore.forEach(emp => {
            const isGranted = emp.entitlement_id !== null && emp.entitlement_id !== undefined;
            const isCalculated = emp.is_calculated === true;

            let status_badge;
            let rowClass = '';
            if (isCalculated) {
                status_badge = '<span class="badge bg-info">계산됨</span>';
                rowClass = 'table-info';
            } else if (isGranted) {
                status_badge = `<span class="badge bg-success">부여됨</span>`;
            } else {
                status_badge = `<span class="badge bg-secondary">미부여</span>`;
            }

            const breakdown = emp.calculated_leave_data || emp.leave_breakdown;

            const base_days = breakdown ? breakdown.base_days.toFixed(1) : '-';
            const long_service_days = breakdown ? breakdown.long_service_days.toFixed(1) : '-';
            const adjustments = parseFloat(emp.adjusted_days) || 0;

            let total_days = parseFloat(emp.total_days) || 0;
            if (isCalculated && breakdown) {
                // If we are showing a new calculation, the total is the new base+long_service plus any existing adjustments
                total_days = (breakdown.total_days || 0) + adjustments;
            }

            const used_days = parseFloat(emp.used_days) || 0;
            const remaining_days = total_days - used_days;

            const row = `
                <tr data-employee-id="${emp.employee_id}" class="${rowClass}">
                    <td>${emp.employee_name}</td>
                    <td>${emp.department_name || '<i>미지정</i>'}</td>
                    <td>${emp.hire_date || '<i>미지정</i>'}</td>
                    <td>${status_badge}</td>
                    <td>${base_days}</td>
                    <td>${long_service_days}</td>
                    <td>${adjustments.toFixed(1)}</td>
                    <td><strong>${total_days.toFixed(1)}</strong></td>
                    <td>${used_days.toFixed(1)}</td>
                    <td class="fw-bold ${remaining_days < 0 ? 'text-danger' : ''}">${remaining_days.toFixed(1)}</td>
                    <td>
                        <button class="btn btn-sm btn-light adjust-btn" data-employee-id="${emp.employee_id}" title="수동 조정"><i class="bx bx-edit"></i></button>
                    </td>
                </tr>`;
            tableBody.insertAdjacentHTML('beforeend', row);
        });
    };

    const loadInitialData = async () => {
        saveBtn.disabled = true;
        calculateBtn.disabled = false;
        tableBody.innerHTML = `<tr><td colspan="11" class="text-center">목록을 불러오는 중...</td></tr>`;

        try {
            const year = yearFilter.value;
            const departmentId = departmentFilter.value;
            const response = await fetch(`../api/leaves_admin.php?action=list_entitlements&year=${year}&department_id=${departmentId}`, fetchOptions());
            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            employeeDataStore = result.data;
            renderTable();
        } catch (error) {
            console.error('Error loading data:', error);
            tableBody.innerHTML = `<tr><td colspan="11" class="text-center text-danger">목록 로딩 실패: ${error.message}</td></tr>`;
        }
    };

    const loadDepartments = async () => {
        try {
            const response = await fetch('../api/organization.php?action=list&type=department', fetchOptions());
            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            departmentFilter.innerHTML = '<option value="">전체 부서</option>';
            result.data.forEach(dep => {
                departmentFilter.insertAdjacentHTML('beforeend', `<option value="${dep.id}">${dep.name}</option>`);
            });
        } catch (error) {
            console.error('Error loading departments:', error);
            departmentFilter.innerHTML = '<option value="">부서 로딩 실패</option>';
        }
    };

    calculateBtn.addEventListener('click', async () => {
        const confirmResult = await Confirm.fire('연차 계산', '현재 필터링된 모든 직원의 연차를 다시 계산합니다. 저장되지 않은 변경사항은 덮어씌워집니다. 계속하시겠습니까?');
        if (!confirmResult.isConfirmed) return;

        calculateBtn.disabled = true;
        calculateBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> 계산 중...';

        try {
            const response = await fetch('../api/leaves_admin.php?action=calculate_leaves', fetchOptions({
                method: 'POST',
                body: JSON.stringify({
                    year: yearFilter.value,
                    department_id: departmentFilter.value
                })
            }));
            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            // Merge calculated data into the store
            result.data.forEach(calculatedEmp => {
                const empInStore = employeeDataStore.find(e => e.employee_id === calculatedEmp.id);
                if (empInStore) {
                    empInStore.calculated_leave_data = calculatedEmp.leave_data;
                    empInStore.is_calculated = true;
                }
            });

            renderTable();
            saveBtn.disabled = false;
            Toast.info('계산이 완료되었습니다. 결과를 확인하고 저장 버튼을 눌러주세요.');

        } catch (error) {
            Toast.error(`오류: ${error.message}`);
        } finally {
            calculateBtn.disabled = false;
            calculateBtn.innerHTML = '전체 계산';
        }
    });

    saveBtn.addEventListener('click', async () => {
        const employeesToSave = employeeDataStore.filter(e => e.is_calculated);
        if (employeesToSave.length === 0) {
            Toast.error('저장할 계산된 데이터가 없습니다.');
            return;
        }

        const confirmResult = await Confirm.fire('저장 확인', `${employeesToSave.length}명의 계산된 연차 정보를 데이터베이스에 저장하시겠습니까?`);
        if (!confirmResult.isConfirmed) return;

        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> 저장 중...';

        try {
            const response = await fetch('../api/leaves_admin.php?action=save_leaves', fetchOptions({
                method: 'POST',
                body: JSON.stringify({
                    year: yearFilter.value,
                    employees: employeesToSave.map(e => ({ id: e.employee_id, name: e.employee_name }))
                })
            }));
            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            Toast.success(result.message || '성공적으로 저장되었습니다.');
            loadInitialData(); // Reload data from DB

        } catch (error) {
            Toast.error(`오류: ${error.message}`);
        } finally {
            saveBtn.disabled = true; // Should be disabled after save until next calculation
            saveBtn.innerHTML = '계산 결과 저장';
        }
    });

    tableBody.addEventListener('click', (e) => {
        const adjustBtn = e.target.closest('.adjust-btn');
        if (adjustBtn) {
            const employeeId = adjustBtn.dataset.employeeId;
            const employee = employeeDataStore.find(emp => emp.employee_id == employeeId);
            if (employee) {
                document.getElementById('adjustment_employee_id').value = employee.employee_id;
                document.getElementById('adjustment-employee-name').textContent = employee.employee_name;
                adjustmentForm.reset();
                adjustmentModal.show();
            }
        }
    });

    adjustmentForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(adjustmentForm);
        const data = {
            employee_id: formData.get('employee_id'),
            year: yearFilter.value,
            adjustment_days: formData.get('adjustment_days'),
            reason: formData.get('reason')
        };

        try {
            const response = await fetch('../api/leaves_admin.php?action=manual_adjustment', fetchOptions({
                method: 'POST',
                body: JSON.stringify(data)
            }));
            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            Toast.success('수동 조정이 완료되었습니다.');
            adjustmentModal.hide();
            loadInitialData();
        } catch (error) {
            Toast.error(`오류: ${error.message}`);
        }
    });

    // Event Listeners
    yearFilter.addEventListener('change', loadInitialData);
    departmentFilter.addEventListener('change', loadInitialData);

    const init = async () => {
        await loadDepartments();
        await loadInitialData();
    };

    init();
});