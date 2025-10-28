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
            historyContainer: document.getElementById('change-history-container'),
            // View Modal elements
            viewModal: new bootstrap.Modal(document.getElementById('view-employee-modal')),
            viewModalBody: document.getElementById('view-employee-modal-body'),
            viewModalEditBtn: document.getElementById('modal-edit-btn'),
            viewModalTerminateBtn: document.getElementById('modal-terminate-btn'),
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

            const terminationDate = employee.termination_date ? `<span class="text-danger">${this.sanitizeHTML(employee.termination_date)}</span>` : '';

            let actionButtonsHtml = `<button class="btn btn-primary btn-sm view-btn" data-id="${employee.id}">정보 보기</button>`;

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
                    <td class="text-nowrap">${actionButtonsHtml}</td>
                </tr>`;
        }).join('');
        this.elements.tableBody.innerHTML = rowsHtml;
    }

    openEmployeeModal(employeeData = null) {
        this.elements.form.reset();
        this.elements.historyContainer.classList.add('d-none');
        this.elements.historySeparator.classList.add('d-none');
        this.elements.historyList.innerHTML = '';

        const basicInfoFields = ['name', 'employee_number', 'department_id', 'position_id', 'hire_date'];

        if (employeeData) { // 수정 모드
            this.elements.modalTitle.textContent = '직원 정보 수정';

            basicInfoFields.forEach(field => {
                if(this.elements.form[field]) this.elements.form[field].disabled = true;
            });

            Object.keys(employeeData).forEach(key => {
                const formElement = this.elements.form[key];
                if (formElement) {
                    formElement.value = employeeData[key] || '';
                }
            });
            this.elements.form.id.value = employeeData.id;

        } else { // 신규 등록 모드
            this.elements.modalTitle.textContent = '신규 직원 정보 등록';
            this.elements.form.id.value = '';

            [...this.elements.form.elements].forEach(el => el.disabled = false);
            this.elements.form.employee_number.readOnly = true;
            this.elements.form.employee_number.placeholder = '입사일 지정 후 자동 생성';
        }

        this.state.employeeModal.show();
    }

    async handleTableClick(e) {
        const target = e.target;
        const employeeId = target.dataset.id;
        if (!employeeId) return;

        if (target.classList.contains('view-btn')) {
            this.openViewModal(employeeId);
        } else if (target.classList.contains('approve-btn')) {
            this.approveProfileUpdate(employeeId);
        } else if (target.classList.contains('reject-btn')) {
            this.rejectProfileUpdate(employeeId);
        }
    }

    async openViewModal(employeeId) {
        try {
            const response = await this.apiCall(`/employees/${employeeId}`);
            const employee = response.data;
            this.state.currentViewingEmployee = employee; // 현재 직원 정보 저장

            const modalBody = this.elements.viewModalBody;
            modalBody.innerHTML = `
                <dl class="row">
                    <dt class="col-sm-3">이름</dt><dd class="col-sm-9">${this.sanitizeHTML(employee.name)}</dd>
                    <dt class="col-sm-3">사번</dt><dd class="col-sm-9">${this.sanitizeHTML(employee.employee_number)}</dd>
                    <dt class="col-sm-3">부서</dt><dd class="col-sm-9">${this.sanitizeHTML(employee.department_name)}</dd>
                    <dt class="col-sm-3">직급</dt><dd class="col-sm-9">${this.sanitizeHTML(employee.position_name)}</dd>
                    <dt class="col-sm-3">입사일</dt><dd class="col-sm-9">${this.sanitizeHTML(employee.hire_date)}</dd>
                    <hr class="my-2">
                    <dt class="col-sm-3">연락처</dt><dd class="col-sm-9">${this.sanitizeHTML(employee.phone_number) || '<i>-</i>'}</dd>
                    <dt class="col-sm-3">주소</dt><dd class="col-sm-9">${this.sanitizeHTML(employee.address) || '<i>-</i>'}</dd>
                    <hr class="my-2">
                    <dt class="col-sm-3">비상연락처</dt><dd class="col-sm-9">${this.sanitizeHTML(employee.emergency_contact_name)} (${this.sanitizeHTML(employee.emergency_contact_relation) || '관계 미지정'})</dd>
                     <hr class="my-2">
                    <dt class="col-sm-3">상의 사이즈</dt><dd class="col-sm-9">${this.sanitizeHTML(employee.clothing_top_size) || '<i>-</i>'}</dd>
                    <dt class="col-sm-3">하의 사이즈</dt><dd class="col-sm-9">${this.sanitizeHTML(employee.clothing_bottom_size) || '<i>-</i>'}</dd>
                    <dt class="col-sm-3">신발 사이즈</dt><dd class="col-sm-9">${this.sanitizeHTML(employee.shoe_size) || '<i>-</i>'}</dd>
                </dl>
            `;

            // 퇴사한 직원이면 수정/퇴사 버튼 비활성화
            const isTerminated = !!employee.termination_date;
            this.elements.viewModalEditBtn.disabled = isTerminated;
            this.elements.viewModalTerminateBtn.disabled = isTerminated;

            this.elements.viewModalEditBtn.onclick = () => this.handleEditFromViewModal();
            this.elements.viewModalTerminateBtn.onclick = () => this.handleTerminateClick(employeeId);

            this.elements.viewModal.show();
        } catch (error) {
            Toast.error('직원 상세 정보를 불러오는 데 실패했습니다.');
        }
    }

    async handleEditFromViewModal() {
        if (!this.state.currentViewingEmployee) return;
        const employeeId = this.state.currentViewingEmployee.id;

        try {
            const historyRes = await this.apiCall(`/employees/${employeeId}/history`);
            this.elements.viewModal.hide();
            this.openEmployeeModal(this.state.currentViewingEmployee);
            this.renderHistory(historyRes.data);
        } catch (error) {
             Toast.error('직원 변경 이력을 불러오는 데 실패했습니다.');
        }
    }

    async handleTerminateClick(employeeId) {
        this.elements.viewModal.hide(); // 정보 모달 먼저 닫기

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
             const result = await Confirm.fire({
                title: '퇴사 처리 확인',
                text: `정말로 이 직원을 ${terminationDate}일자로 퇴사 처리하시겠습니까? 퇴사 처리 후에는 복구할 수 없습니다.`
            });
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
            this.state.employeeModal.hide();
            this.loadEmployees();
        } catch (error) {
            Toast.error(`저장 처리 중 오류: ${error.message}`);
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

new EmployeesPage();