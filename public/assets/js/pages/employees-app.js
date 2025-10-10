class EmployeesApp extends BaseApp {
    constructor() {
        super({
            API_URL: '/employees'
        });

        this.state = {
            ...this.state,
            allDepartments: [],
            allPositions: [],
            employeeModal: null
        };
    }

    initializeApp() {
        this.cacheDOMElements();
        this.state.employeeModal = new bootstrap.Modal(document.getElementById('employee-modal'));
        this.setupEventListeners();
        this.loadInitialData();
    }

    cacheDOMElements() {
        this.elements = {
            tableBody: document.getElementById('employee-table-body'),
            addBtn: document.getElementById('add-employee-btn'),
            form: document.getElementById('employee-form'),
            modalTitle: document.getElementById('modal-title'),
            deleteBtn: document.getElementById('delete-btn'),
            historyContainer: document.getElementById('change-history-container'),
            historySeparator: document.getElementById('history-separator'),
            historyList: document.getElementById('history-log-list'),
            filterDepartment: document.getElementById('filter-department'),
            filterPosition: document.getElementById('filter-position'),
            filterStatus: document.getElementById('filter-status')
        };
    }

    setupEventListeners() {
        this.elements.addBtn.addEventListener('click', () => this.openEmployeeModal());
        this.elements.tableBody.addEventListener('click', (e) => this.handleTableClick(e));
        this.elements.form.addEventListener('submit', (e) => this.handleFormSubmit(e));

        this.elements.filterDepartment.addEventListener('change', () => this.loadEmployees());
        this.elements.filterPosition.addEventListener('change', () => this.loadEmployees());
        this.elements.filterStatus.addEventListener('change', () => this.loadEmployees());
    }

    async loadInitialData() {
        try {
            const status = this.elements.filterStatus.value;
            let url = `${this.config.API_URL}?action=get_initial_data`;
            if (status) url += `&status=${status}`;

            const response = await this.apiCall(url);
            const { employees, departments, positions } = response.data;

            this.state.allDepartments = departments;
            this.state.allPositions = positions;

            this.renderEmployeeTable(employees);
            this.populateDropdowns([this.elements.form.department_id, this.elements.filterDepartment], this.state.allDepartments, '부서 선택');
            this.populateDropdowns([this.elements.form.position_id, this.elements.filterPosition], this.state.allPositions, '직급 선택');
        } catch (error) {
            console.error('Error loading initial data:', error);
            this.elements.tableBody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">목록 로딩 실패: ${error.message}</td></tr>`;
        }
    }

    async loadEmployees() {
        const deptId = this.elements.filterDepartment.value;
        const posId = this.elements.filterPosition.value;
        const status = this.elements.filterStatus.value;

        let url = `${this.config.API_URL}?action=list`;
        if (deptId) url += `&department_id=${deptId}`;
        if (posId) url += `&position_id=${posId}`;
        if (status) url += `&status=${status}`;

        try {
            const response = await this.apiCall(url);
            this.renderEmployeeTable(response.data);
        } catch (error) {
            console.error('Error loading employees:', error);
            this.elements.tableBody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">목록 로딩 실패: ${error.message}</td></tr>`;
        }
    }

    renderEmployeeTable(employeeList) {
        this.elements.tableBody.innerHTML = '';
        if (employeeList.length === 0) {
            this.elements.tableBody.innerHTML = `<tr><td colspan="8" class="text-center">해당 조건의 직원이 없습니다.</td></tr>`;
            return;
        }

        const rowsHtml = employeeList.map(employee => {
            const linkedUser = employee.nickname ? `<span class="badge bg-primary">${this.sanitizeHTML(employee.nickname)}</span>` : '<span class="text-muted"><i>없음</i></span>';
            const statusInfo = employee.profile_update_status === 'pending' ? `<span class="badge bg-warning ms-2">수정 요청</span>`
                            : (employee.profile_update_status === 'rejected' ? `<span class="badge bg-danger ms-2">반려됨</span>` : '');

            const actionButtons = employee.profile_update_status === 'pending'
                ? `<button class="btn btn-success btn-sm approve-btn" data-id="${employee.id}">승인</button>
                   <button class="btn btn-danger btn-sm reject-btn ms-1" data-id="${employee.id}">반려</button>`
                : '';

            const terminationDate = employee.termination_date ? `<span class="text-danger">${this.sanitizeHTML(employee.termination_date)}</span>` : '';

            return `
                <tr>
                    <td>${this.sanitizeHTML(employee.name)} ${statusInfo}</td>
                    <td>${this.sanitizeHTML(employee.department_name) || '<i>미지정</i>'}</td>
                    <td>${this.sanitizeHTML(employee.position_name) || '<i>미지정</i>'}</td>
                    <td>${this.sanitizeHTML(employee.employee_number)}</td>
                    <td>${this.sanitizeHTML(employee.hire_date)}</td>
                    <td>${terminationDate}</td>
                    <td>${linkedUser}</td>
                    <td class="text-nowrap">
                        <button class="btn btn-secondary btn-sm edit-btn" data-id="${employee.id}">수정</button>
                        ${actionButtons}
                    </td>
                </tr>`;
        }).join('');
        this.elements.tableBody.innerHTML = rowsHtml;
    }

    openEmployeeModal(employeeData = null) {
        this.elements.form.reset();
        if (employeeData) {
            this.elements.modalTitle.textContent = '직원 정보 수정';
            this.elements.deleteBtn.classList.remove('d-none');
            this.elements.form.employee_number.readOnly = false;
            this.elements.form.hire_date.readOnly = true;

            Object.keys(employeeData).forEach(key => {
                if (this.elements.form[key]) {
                    this.elements.form[key].value = employeeData[key] || '';
                }
            });
        } else {
            this.elements.modalTitle.textContent = '신규 직원 정보 등록';
            this.elements.deleteBtn.classList.add('d-none');
            this.elements.form.id.value = '';
            this.elements.form.employee_number.placeholder = '입사일 지정 후 자동 생성';
            this.elements.form.employee_number.readOnly = true;
            this.elements.form.hire_date.readOnly = false;
        }

        this.elements.historyContainer.classList.add('d-none');
        this.elements.historySeparator.classList.add('d-none');
        this.elements.historyList.innerHTML = '';

        this.state.employeeModal.show();
    }

    async handleTableClick(e) {
        const target = e.target;
        const employeeId = target.dataset.id;
        if (!employeeId) return;

        if (target.classList.contains('edit-btn')) {
            try {
                const [detailsRes, historyRes] = await Promise.all([
                    this.apiCall(`${this.config.API_URL}?action=get_one&id=${employeeId}`),
                    this.apiCall(`${this.config.API_URL}?action=get_change_history&id=${employeeId}`)
                ]);
                this.openEmployeeModal(detailsRes.data);
                this.renderHistory(historyRes.data);
            } catch (error) {
                Toast.error('직원 정보를 불러오는 데 실패했습니다.');
            }
        }

        if (target.classList.contains('approve-btn')) {
            this.approveProfileUpdate(employeeId);
        }

        if (target.classList.contains('reject-btn')) {
            this.rejectProfileUpdate(employeeId);
        }
    }

    renderHistory(historyData) {
        if (historyData.length > 0) {
            const historyHtml = historyData.map(log => `
                <div class="list-group-item">
                    <p class="mb-1"><strong>${this.sanitizeHTML(log.field_name)}:</strong> <span class="text-danger text-decoration-line-through">${this.sanitizeHTML(log.old_value)}</span> → <span class="text-success fw-bold">${this.sanitizeHTML(log.new_value)}</span></p>
                    <small class="text-muted">${log.changed_at} by ${this.sanitizeHTML(log.changer_name) || 'System'}</small>
                </div>`
            ).join('');
            this.elements.historyList.innerHTML = historyHtml;
            this.elements.historySeparator.classList.remove('d-none');
            this.elements.historyContainer.classList.remove('d-none');
        }
    }

    async approveProfileUpdate(employeeId) {
        const result = await Confirm.fire('승인 확인', '이 사용자의 프로필 변경 요청을 승인하시겠습니까?');
        if (!result.isConfirmed) return;

        try {
            const response = await this.apiCall(`${this.config.API_URL}?action=approve_update`, {
                method: 'POST', body: { id: employeeId }
            });
            Toast.success(response.message);
            this.loadEmployees();
        } catch (error) {
            Toast.error('승인 처리 중 오류가 발생했습니다.');
        }
    }

    async rejectProfileUpdate(employeeId) {
        const { value: reason } = await Swal.fire({
            title: '프로필 변경 요청 반려',
            input: 'text',
            inputPlaceholder: '반려 사유를 입력해주세요.',
            showCancelButton: true,
            cancelButtonText: '취소',
            confirmButtonText: '확인',
            inputValidator: (value) => !value && '반려 사유를 반드시 입력해야 합니다.'
        });

        if (reason) {
            try {
                const response = await this.apiCall(`${this.config.API_URL}?action=reject_update`, {
                    method: 'POST', body: { id: employeeId, reason: reason }
                });
                Toast.success(response.message);
                this.loadEmployees();
            } catch (error) {
                Toast.error(`반려 처리 중 오류: ${error.message}`);
            }
        }
    }

    async handleFormSubmit(e) {
        e.preventDefault();
        const action = e.submitter && e.submitter.id === 'delete-btn' ? 'delete' : 'save';

        if (action === 'delete') {
            const result = await Confirm.fire('삭제 확인', '정말로 이 직원의 정보를 삭제하시겠습니까? 사용자 계정과의 연결도 해제됩니다.');
            if (!result.isConfirmed) return;
        }

        const formData = new FormData(this.elements.form);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await this.apiCall(`${this.config.API_URL}?action=${action}`, {
                method: 'POST', body: data
            });
            this.state.employeeModal.hide();
            this.loadEmployees();
            Toast.success(response.message);
        } catch (error) {
            Toast.error(`작업 처리 중 오류가 발생했습니다: ${error.message}`);
        }
    }

    populateDropdowns(selects, data, defaultOptionText) {
        selects.forEach(select => {
            const currentValue = select.value;
            select.innerHTML = `<option value="">${defaultOptionText}</option>`;
            data.forEach(item => {
                select.insertAdjacentHTML('beforeend', `<option value="${item.id}">${this.sanitizeHTML(item.name)}</option>`);
            });
            select.value = currentValue;
        });
    }

    sanitizeHTML = (str) => {
        if (str === null || str === undefined) return '';
        const temp = document.createElement('div');
        temp.textContent = str;
        return temp.innerHTML;
    };
}

new EmployeesApp();