class LeaveGrantingApp extends BaseApp {
    constructor() {
        super({
            API_URL: '/leaves_admin',
            ORG_API_URL: '/organization' // Secondary API endpoint
        });

        this.state = {
            ...this.state,
            employeeDataStore: [],
            adjustmentModal: null
        };
    }

    initializeApp() {
        this.cacheDOMElements();
        this.state.adjustmentModal = this.elements.adjustmentModalEl ? new bootstrap.Modal(this.elements.adjustmentModalEl) : null;
        this.setupEventListeners();
        this.loadInitialData();
    }

    cacheDOMElements() {
        this.elements = {
            yearFilter: document.getElementById('filter-year'),
            departmentFilter: document.getElementById('filter-department'),
            calculateBtn: document.getElementById('calculate-btn'),
            saveBtn: document.getElementById('save-btn'),
            tableBody: document.getElementById('entitlement-table-body'),
            adjustmentModalEl: document.getElementById('adjustment-modal'),
            adjustmentForm: document.getElementById('adjustment-form')
        };
    }

    setupEventListeners() {
        this.elements.yearFilter.addEventListener('change', () => this.loadEntitlements());
        this.elements.departmentFilter.addEventListener('change', () => this.loadEntitlements());
        this.elements.calculateBtn.addEventListener('click', () => this.calculateLeave());
        this.elements.saveBtn.addEventListener('click', () => this.saveCalculatedLeave());

        if (this.elements.adjustmentForm) {
            this.elements.tableBody.addEventListener('click', (e) => this.handleTableClick(e));
            this.elements.adjustmentForm.addEventListener('submit', (e) => this.handleAdjustmentSubmit(e));
        }
    }

    async loadInitialData() {
        await this.loadDepartments();
        await this.loadEntitlements();
    }

    async loadEntitlements() {
        this.elements.saveBtn.disabled = true;
        this.elements.calculateBtn.disabled = false;
        this.elements.tableBody.innerHTML = `<tr><td colspan="11" class="text-center"><span class="spinner-border spinner-border-sm"></span> 목록 로딩 중...</td></tr>`;

        try {
            const year = this.elements.yearFilter.value;
            const departmentId = this.elements.departmentFilter.value;
            let url = `${this.config.API_URL}/entitlements?year=${year}`;
            if (departmentId) url += `&department_id=${departmentId}`;

            const response = await this.apiCall(url);
            this.state.employeeDataStore = response.data;
            this.renderTable();
        } catch (error) {
            console.error('Error loading entitlement data:', error);
            this.elements.tableBody.innerHTML = `<tr><td colspan="11" class="text-center text-danger">목록 로딩 실패: ${error.message}</td></tr>`;
        }
    }

    async loadDepartments() {
        try {
            const response = await this.apiCall(`${this.config.ORG_API_URL}?type=department`);
            this.elements.departmentFilter.innerHTML = '<option value="">전체 부서</option>';
            response.data.forEach(dep => {
                this.elements.departmentFilter.insertAdjacentHTML('beforeend', `<option value="${dep.id}">${this._sanitizeHTML(dep.name)}</option>`);
            });
        } catch (error) {
            console.error('Error loading departments:', error);
            this.elements.departmentFilter.innerHTML = '<option value="">부서 로딩 실패</option>';
        }
    }

    renderTable() {
        this.elements.tableBody.innerHTML = '';
        if (!this.state.employeeDataStore || this.state.employeeDataStore.length === 0) {
            this.elements.tableBody.innerHTML = `<tr><td colspan="11" class="text-center">표시할 직원이 없습니다.</td></tr>`;
            return;
        }

        const rowsHtml = this.state.employeeDataStore.map(emp => {
            const isGranted = emp.entitlement_id != null;
            const isCalculated = emp.is_calculated === true;

            let statusBadge = isCalculated ? '<span class="badge bg-info">계산됨</span>' : (isGranted ? `<span class="badge bg-success">부여됨</span>` : `<span class="badge bg-secondary">미부여</span>`);
            const rowClass = isCalculated ? 'table-info' : '';

            const breakdown = emp.calculated_leave_data || emp.leave_breakdown;
            const base_days = breakdown ? parseFloat(breakdown.base_days).toFixed(1) : '-';
            const long_service_days = breakdown ? parseFloat(breakdown.long_service_days).toFixed(1) : '-';
            const adjustments = parseFloat(emp.adjusted_days) || 0;
            let total_days = parseFloat(emp.total_days) || 0;
            if (isCalculated && breakdown) {
                total_days = (parseFloat(breakdown.total_days) || 0) + adjustments;
            }
            const used_days = parseFloat(emp.used_days) || 0;
            const remaining_days = total_days - used_days;

            return `
                <tr data-employee-id="${emp.employee_id}" class="${rowClass}">
                    <td>${this._sanitizeHTML(emp.employee_name)}</td>
                    <td>${this._sanitizeHTML(emp.department_name) || '<i>미지정</i>'}</td>
                    <td>${emp.hire_date || '<i>미지정</i>'}</td>
                    <td>${statusBadge}</td>
                    <td>${base_days}</td>
                    <td>${long_service_days}</td>
                    <td>${adjustments.toFixed(1)}</td>
                    <td><strong>${total_days.toFixed(1)}</strong></td>
                    <td>${used_days.toFixed(1)}</td>
                    <td class="fw-bold ${remaining_days < 0 ? 'text-danger' : ''}">${remaining_days.toFixed(1)}</td>
                    <td><button class="btn btn-sm btn-light adjust-btn" data-employee-id="${emp.employee_id}" title="수동 조정"><i class="bx bx-edit"></i></button></td>
                </tr>`;
        }).join('');
        this.elements.tableBody.innerHTML = rowsHtml;
    }

    async calculateLeave() {
        const confirmResult = await Confirm.fire('연차 계산', '현재 필터링된 모든 직원의 연차를 다시 계산합니다. 계속하시겠습니까?');
        if (!confirmResult.isConfirmed) return;

        this.setButtonLoading('#calculate-btn', '계산 중...');
        try {
            const response = await this.apiCall(`${this.config.API_URL}/calculate`, {
                method: 'POST',
                body: { year: this.elements.yearFilter.value, department_id: this.elements.departmentFilter.value }
            });

            response.data.forEach(calculatedEmp => {
                const empInStore = this.state.employeeDataStore.find(e => e.employee_id === calculatedEmp.id);
                if (empInStore) {
                    empInStore.calculated_leave_data = calculatedEmp.leave_data;
                    empInStore.is_calculated = true;
                }
            });

            this.renderTable();
            this.elements.saveBtn.disabled = false;
            Toast.info('계산이 완료되었습니다. 결과를 확인하고 저장 버튼을 눌러주세요.');
        } catch (error) {
            Toast.error(`오류: ${error.message}`);
        } finally {
            this.resetButtonLoading('#calculate-btn', '전체 계산');
        }
    }

    async saveCalculatedLeave() {
        const employeesToSave = this.state.employeeDataStore.filter(e => e.is_calculated);
        if (employeesToSave.length === 0) {
            Toast.error('저장할 계산된 데이터가 없습니다.');
            return;
        }

        const confirmResult = await Confirm.fire('저장 확인', `${employeesToSave.length}명의 계산된 연차 정보를 저장하시겠습니까?`);
        if (!confirmResult.isConfirmed) return;

        this.setButtonLoading('#save-btn', '저장 중...');
        try {
            const response = await this.apiCall(`${this.config.API_URL}/save-entitlements`, {
                method: 'POST',
                body: {
                    year: this.elements.yearFilter.value,
                    employees: employeesToSave.map(e => ({ id: e.employee_id, name: e.employee_name }))
                }
            });
            Toast.success(response.message || '성공적으로 저장되었습니다.');
            this.loadEntitlements();
        } catch (error) {
            Toast.error(`오류: ${error.message}`);
        } finally {
            this.resetButtonLoading('#save-btn', '계산 결과 저장');
            this.elements.saveBtn.disabled = true;
        }
    }

    handleTableClick(e) {
        const adjustBtn = e.target.closest('.adjust-btn');
        if (adjustBtn) {
            const employeeId = adjustBtn.dataset.employeeId;
            const employee = this.state.employeeDataStore.find(emp => emp.employee_id == employeeId);
            if (employee) {
                document.getElementById('adjustment_employee_id').value = employee.employee_id;
                document.getElementById('adjustment-employee-name').textContent = employee.employee_name;
                this.elements.adjustmentForm.reset();
                this.state.adjustmentModal.show();
            }
        }
    }

    async handleAdjustmentSubmit(e) {
        e.preventDefault();
        const formData = new FormData(this.elements.adjustmentForm);
        const data = {
            employee_id: formData.get('employee_id'),
            year: this.elements.yearFilter.value,
            adjustment_days: formData.get('adjustment_days'),
            reason: formData.get('reason')
        };

        try {
            const response = await this.apiCall(`${this.config.API_URL}/adjust`, {
                method: 'POST', body: data
            });
            Toast.success('수동 조정이 완료되었습니다.');
            this.state.adjustmentModal.hide();
            this.loadEntitlements();
        } catch (error) {
            Toast.error(`오류: ${error.message}`);
        }
    }

    _sanitizeHTML(text) {
        if (text === null || typeof text === 'undefined') return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }
}

new LeaveGrantingApp();