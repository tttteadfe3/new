class EmployeesPage extends BasePage {
    constructor() {
        super(); // No specific config needed as we'll use resource-based URLs

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
            // Fetch dropdown data and then load the main employee list
            const response = await this.apiCall('/employees/initial-data');
            const { departments, positions } = response.data;

            this.state.allDepartments = departments;
            this.state.allPositions = positions;

            this.populateDropdowns([this.elements.form.department_id, this.elements.filterDepartment], this.state.allDepartments, '부서 선택');
            this.populateDropdowns([this.elements.form.position_id, this.elements.filterPosition], this.state.allPositions, '직급 선택');

            // Now, load the employees with the current filters
            await this.loadEmployees();
        } catch (error) {
            console.error('Error loading initial data:', error);
            this.elements.tableBody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">초기 정보 로딩 실패: ${error.message}</td></tr>`;
        }
    }

    async loadEmployees() {
        const filters = {
            department_id: this.elements.filterDepartment.value,
            position_id: this.elements.filterPosition.value,
            status: this.elements.filterStatus.value
        };
        const params = new URLSearchParams(Object.entries(filters).filter(([, value]) => value));

        try {
            const response = await this.apiCall(`/employees?${params.toString()}`);
            this.renderEmployeeTable(response.data);
        } catch (error) {
            console.error('Error loading employees:', error);
            this.elements.tableBody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">목록 로딩 실패: ${error.message}</td></tr>`;
        }
    }

    renderEmployeeTable(employeeList) {
        this.elements.tableBody.innerHTML = '';
        if (!employeeList || employeeList.length === 0) {
            this.elements.tableBody.innerHTML = `<tr><td colspan="8" class="text-center">해당 조건의 직원이 없습니다.</td></tr>`;
            return;
        }

        const rowsHtml = employeeList.map(employee => {
            const linkedUser = employee.nickname ? `<span class="badge bg-primary">${this.sanitizeHTML(employee.nickname)}</span>` : '<span class="text-muted"><i>없음</i></span>';
            const statusInfo = employee.profile_update_status === '대기' ? `<span class="badge bg-warning ms-2">수정 요청</span>`
                            : (employee.profile_update_status === '반려' ? `<span class="badge bg-danger ms-2">반려됨</span>` : '');

            const actionButtons = employee.profile_update_status === '대기'
                ? `<button class="btn btn-success btn-sm approve-btn" data-id="${employee.id}">승인</button>
                   <button class="btn btn-danger btn-sm reject-btn ms-1" data-id="${employee.id}">반려</button>`
                : '';

            const terminationDate = employee.termination_date ? `<span class="text-danger">${this.sanitizeHTML(employee.termination_date)}</span>` : '';
            let actionButtonsHtml = `<button class="btn btn-secondary btn-sm edit-btn" data-id="${employee.id}">수정</button>`;

            if (!employee.termination_date) {
                // 재직중인 직원에게만 인사 발령 및 퇴사 처리 버튼 표시
                actionButtonsHtml += `
                    <a href="/hr/order/create?employee_id=${employee.id}" class="btn btn-info btn-sm ms-1">인사발령</a>
                    <button class="btn btn-warning btn-sm ms-1 terminate-btn" data-id="${employee.id}">퇴사처리</button>
                `;
            }

            if (employee.profile_update_status === '대기') {
                 actionButtonsHtml += `
                    <button class="btn btn-success btn-sm approve-btn ms-1" data-id="${employee.id}">승인</button>
                    <button class="btn btn-danger btn-sm reject-btn ms-1" data-id="${employee.id}">반려</button>
                 `;
            }

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
                        ${actionButtonsHtml}
                    </td>
                </tr>`;
        }).join('');
        this.elements.tableBody.innerHTML = rowsHtml;
    }

    openEmployeeModal(employeeData = null) {
        this.elements.form.reset();
        this.elements.historyContainer.classList.add('d-none');
        this.elements.historySeparator.classList.add('d-none');
        this.elements.historyList.innerHTML = '';

        // 기본 정보 필드 목록
        const basicInfoFields = ['name', 'employee_number', 'department_id', 'position_id', 'hire_date'];

        if (employeeData) { // 수정 모드
            this.elements.modalTitle.textContent = '직원 정보 수정';
            this.elements.deleteBtn.classList.remove('d-none');

            // 기본 정보 필드 비활성화
            basicInfoFields.forEach(field => {
                if(this.elements.form[field]) this.elements.form[field].disabled = true;
            });

            // 데이터 채우기
            Object.keys(employeeData).forEach(key => {
                const formElement = this.elements.form[key];
                if (formElement) {
                    formElement.value = employeeData[key] || '';
                }
            });
            this.elements.form.id.value = employeeData.id;

        } else { // 신규 등록 모드
            this.elements.modalTitle.textContent = '신규 직원 정보 등록';
            this.elements.deleteBtn.classList.add('d-none');
            this.elements.form.id.value = '';

            // 모든 필드 활성화
            [...this.elements.form.elements].forEach(el => el.disabled = false);
            this.elements.form.employee_number.readOnly = true; // 사번은 항상 readonly
            this.elements.form.employee_number.placeholder = '입사일 지정 후 자동 생성';
        }

        this.state.employeeModal.show();
    }

    async handleTableClick(e) {
        const target = e.target;
        const employeeId = target.dataset.id;
        if (!employeeId) return;

        if (target.classList.contains('edit-btn')) {
            try {
                const [detailsRes, historyRes] = await Promise.all([
                    this.apiCall(`/employees/${employeeId}`),
                    this.apiCall(`/employees/${employeeId}/history`)
                ]);
                this.openEmployeeModal(detailsRes.data);
                this.renderHistory(historyRes.data);
            } catch (error) {
                Toast.error('직원 정보를 불러오는 데 실패했습니다.');
            }
        } else if (target.classList.contains('approve-btn')) {
            this.approveProfileUpdate(employeeId);
        } else if (target.classList.contains('reject-btn')) {
            this.rejectProfileUpdate(employeeId);
        } else if (target.classList.contains('terminate-btn')) {
            this.handleTerminateClick(employeeId);
        }
    }

    async handleTerminateClick(employeeId) {
        const { value: terminationDate } = await Swal.fire({
            title: '퇴사 처리',
            html: `<p>퇴사일을 지정해주세요.</p>`,
            input: 'date',
            inputPlaceholder: '퇴사일을 선택하세요',
            showCancelButton: true,
            cancelButtonText: '취소',
            confirmButtonText: '확인',
            didOpen: () => {
                const today = new Date().toISOString().split('T')[0];
                Swal.getInput().value = today;
            },
            inputValidator: (value) => {
                if (!value) {
                    return '퇴사일을 지정해야 합니다.';
                }
            }
        });

        if (terminationDate) {
            const result = await Confirm.fire('퇴사 처리 확인', `정말로 이 직원을 ${terminationDate}일자로 퇴사 처리하시겠습니까? 퇴사 처리 후에는 복구할 수 없습니다.`);
            if (!result.isConfirmed) return;

            try {
                const response = await this.apiCall(`/employees/${employeeId}/terminate`, {
                    method: 'POST',
                    body: { termination_date: terminationDate }
                });
                Toast.success(response.message);
                this.loadEmployees();
            } catch (error) {
                Toast.error(`퇴사 처리 중 오류: ${error.message}`);
            }
        }
    }

    renderHistory(historyData) {
        if (historyData && historyData.length > 0) {
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
            const response = await this.apiCall(`/employees/${employeeId}/approve-update`, { method: 'POST' });
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
                const response = await this.apiCall(`/employees/${employeeId}/reject-update`, {
                    method: 'POST', body: { reason: reason }
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
        const formData = new FormData(this.elements.form);
        const rawData = Object.fromEntries(formData.entries());
        const employeeId = rawData.id;

        const isDelete = e.submitter && e.submitter.id === 'delete-btn';

        if (isDelete) {
            // ... (delete logic remains the same)
            const result = await Confirm.fire('삭제 확인', '정말로 이 직원의 정보를 삭제하시겠습니까? 사용자 계정과의 연결도 해제됩니다.');
            if (!result.isConfirmed) return;

            try {
                const response = await this.apiCall(`/employees/${employeeId}`, { method: 'DELETE' });
                Toast.success(response.message);
            } catch (error) {
                Toast.error(`삭제 처리 중 오류: ${error.message}`);
            }
        } else {
            let dataToSend;
            const method = employeeId ? 'PUT' : 'POST';
            const url = employeeId ? `/employees/${employeeId}` : '/employees';

            if (method === 'PUT') {
                // 수정 시에는 수정 가능한 필드만 포함
                dataToSend = {
                    id: employeeId,
                    phone_number: rawData.phone_number,
                    address: rawData.address,
                    emergency_contact_name: rawData.emergency_contact_name,
                    emergency_contact_relation: rawData.emergency_contact_relation,
                    clothing_top_size: rawData.clothing_top_size,
                    clothing_bottom_size: rawData.clothing_bottom_size,
                    shoe_size: rawData.shoe_size,
                };
            } else {
                // 신규 등록 시에는 모든 필드 포함
                dataToSend = rawData;
            }

            try {
                const response = await this.apiCall(url, { method, body: dataToSend });
                Toast.success(response.message);
            } catch (error) {
                Toast.error(`저장 처리 중 오류: ${error.message}`);
            }
        }

        this.state.employeeModal.hide();
        this.loadEmployees();
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

new EmployeesPage();