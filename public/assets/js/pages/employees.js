class EmployeesPage extends BasePage {
    constructor() {
        super();
        this.state = {
            ...this.state,
            allDepartments: [],
            allPositions: [],
            currentEmployee: null,
            viewMode: 'welcome', // 'welcome', 'view', 'edit', 'create'
        };
    }

    initializeApp() {
        this.cacheDOMElements();
        this.setupEventListeners();
        this.loadInitialData();
    }

    cacheDOMElements() {
        this.elements = {
            listContainer: document.getElementById('employee-list-container'),
            detailsContainer: document.getElementById('employee-details-container'),
            addBtn: document.getElementById('add-employee-btn'),
            filterDepartment: document.getElementById('filter-department'),
            filterPosition: document.getElementById('filter-position'),
            filterStatus: document.getElementById('filter-status'),
            noResultDiv: document.getElementById('no-employee-result'),
        };
    }

    setupEventListeners() {
        this.elements.addBtn.addEventListener('click', () => this.handleCreateClick());
        this.elements.listContainer.addEventListener('click', (e) => this.handleListClick(e));
        this.elements.detailsContainer.addEventListener('click', (e) => this.handleDetailsClick(e));

        this.elements.filterDepartment.addEventListener('change', () => this.loadEmployees());
        this.elements.filterPosition.addEventListener('change', () => this.loadEmployees());
        this.elements.filterStatus.addEventListener('change', () => this.loadEmployees());
    }

    async loadInitialData() {
        try {
            const response = await this.apiCall('/employees/initial-data');
            const { departments, positions } = response.data;
            this.state.allDepartments = departments;
            this.state.allPositions = positions;

            this.populateDropdown(this.elements.filterDepartment, this.state.allDepartments, '모든 부서');
            this.populateDropdown(this.elements.filterPosition, this.state.allPositions, '모든 직급');

            await this.loadEmployees();
        } catch (error) {
            Toast.error('초기 데이터 로딩 실패');
            this.elements.listContainer.innerHTML = '<p class="text-danger p-3">목록 로딩에 실패했습니다.</p>';
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
            this.renderEmployeeList(response.data);
            this.renderWelcomeView();
        } catch (error) {
            Toast.error('직원 목록 로딩 실패');
            this.elements.listContainer.innerHTML = `<p class="text-danger p-3">목록 로딩 실패: ${error.message}</p>`;
        }
    }

    renderEmployeeList(employeeList) {
        this.elements.listContainer.innerHTML = '';
        if (!employeeList || employeeList.length === 0) {
            this.elements.noResultDiv.style.display = 'block';
            return;
        }
        this.elements.noResultDiv.style.display = 'none';

        const listHtml = employeeList.map(employee => `
            <a href="#" class="list-group-item list-group-item-action" data-id="${employee.id}">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">${this.sanitizeHTML(employee.name)}</h6>
                    <small>${this.sanitizeHTML(employee.position_name) || '미지정'}</small>
                </div>
                <p class="mb-1">${this.sanitizeHTML(employee.department_name) || '미지정'}</p>
                ${employee.termination_date ? `<small class="text-danger">퇴사: ${this.sanitizeHTML(employee.termination_date)}</small>` : ''}
            </a>
        `).join('');
        this.elements.listContainer.innerHTML = listHtml;
    }

    // --- View Rendering ---

    renderWelcomeView() {
        this.elements.detailsContainer.innerHTML = `
            <div class="text-center p-5">
                <i class="bi bi-person-circle fs-1 text-muted"></i>
                <p class="mt-3 text-muted">왼쪽 목록에서 직원을 선택하거나 '신규 등록' 버튼을 클릭하여 시작하세요.</p>
            </div>
        `;
    }

    async renderDetailsView(employeeId) {
        try {
            const response = await this.apiCall(`/employees/${employeeId}`);
            this.state.currentEmployee = response.data;
            const employee = this.state.currentEmployee;

            const isTerminated = !!employee.termination_date;
            const buttonsHtml = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <a href="/hr/order/create?employee_id=${employee.id}" class="btn btn-info" ${isTerminated ? 'disabled' : ''}>인사발령</a>
                        <button class="btn btn-warning terminate-btn" data-id="${employee.id}" ${isTerminated ? 'disabled' : ''}>퇴사 처리</button>
                    </div>
                    <div>
                        <button class="btn btn-secondary cancel-btn">목록으로</button>
                        <button class="btn btn-primary edit-btn" data-id="${employee.id}" ${isTerminated ? 'disabled' : ''}>수정하기</button>
                    </div>
                </div>
            `;

            this.elements.detailsContainer.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">${this.sanitizeHTML(employee.name)} (${this.sanitizeHTML(employee.employee_number)})</h4>
                    <div>${employee.nickname ? `<span class="badge bg-primary">${this.sanitizeHTML(employee.nickname)}</span>` : ''}</div>
                </div>
                <dl class="row">
                    <dt class="col-sm-3">부서</dt><dd class="col-sm-9">${this.sanitizeHTML(employee.department_name)}</dd>
                    <dt class="col-sm-3">직급</dt><dd class="col-sm-9">${this.sanitizeHTML(employee.position_name)}</dd>
                    <dt class="col-sm-3">입사일</dt><dd class="col-sm-9">${this.sanitizeHTML(employee.hire_date)}</dd>
                    ${isTerminated ? `<dt class="col-sm-3 text-danger">퇴사일</dt><dd class="col-sm-9 text-danger">${this.sanitizeHTML(employee.termination_date)}</dd>` : ''}
                    <hr class="my-2">
                    <dt class="col-sm-3">연락처</dt><dd class="col-sm-9">${this.sanitizeHTML(employee.phone_number) || '<i>-</i>'}</dd>
                    <dt class="col-sm-3">주소</dt><dd class="col-sm-9">${this.sanitizeHTML(employee.address) || '<i>-</i>'}</dd>
                </dl>
                <hr class="my-3">
                ${buttonsHtml}
            `;
            this.state.viewMode = 'view';
        } catch (error) {
            Toast.error('직원 상세 정보 로딩 실패');
        }
    }

    renderFormView(employee = null) {
        this.state.viewMode = employee ? 'edit' : 'create';
        const isCreate = !employee;

        const formHtml = `
            <h4>${isCreate ? '신규 직원 등록' : '직원 정보 수정'}</h4>
            <form id="employee-form">
                <input type="hidden" name="id" value="${employee?.id || ''}">

                <!-- 기본 정보 (신규 시에만 입력) -->
                <fieldset ${isCreate ? '' : 'disabled'}>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="name" class="form-label">이름</label><input type="text" class="form-control" name="name" value="${this.sanitizeHTML(employee?.name || '')}" required></div>
                        <div class="col-md-6 mb-3"><label for="employee_number" class="form-label">사번</label><input type="text" class="form-control" value="${this.sanitizeHTML(employee?.employee_number || '')}" readonly placeholder="자동 생성"></div>
                        <div class="col-md-6 mb-3"><label for="department_id" class="form-label">부서</label><select class="form-select" name="department_id" id="form-department-id" required></select></div>
                        <div class="col-md-6 mb-3"><label for="position_id" class="form-label">직급</label><select class="form-select" name="position_id" id="form-position-id" required></select></div>
                        <div class="col-md-12 mb-3"><label for="hire_date" class="form-label">입사일</label><input type="date" class="form-control" name="hire_date" value="${this.sanitizeHTML(employee?.hire_date || '')}" required></div>
                    </div>
                </fieldset>
                <hr>

                <!-- 수정 가능 정보 -->
                <div class="row">
                    <div class="col-md-6 mb-3"><label for="phone_number" class="form-label">연락처</label><input type="tel" class="form-control" name="phone_number" value="${this.sanitizeHTML(employee?.phone_number || '')}"></div>
                    <div class="col-md-6 mb-3"><label for="address" class="form-label">주소</label><input type="text" class="form-control" name="address" value="${this.sanitizeHTML(employee?.address || '')}"></div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary cancel-btn">취소</button>
                    <button type="submit" class="btn btn-primary">${isCreate ? '등록하기' : '저장하기'}</button>
                </div>
            </form>
        `;
        this.elements.detailsContainer.innerHTML = formHtml;

        // Populate dropdowns for the form
        if (isCreate) {
            this.populateDropdown(document.getElementById('form-department-id'), this.state.allDepartments, '부서 선택');
            this.populateDropdown(document.getElementById('form-position-id'), this.state.allPositions, '직급 선택');
            document.getElementById('form-department-id').value = employee?.department_id || '';
            document.getElementById('form-position-id').value = employee?.position_id || '';
        }
    }

    // --- Event Handlers ---

    handleListClick(e) {
        e.preventDefault();
        const target = e.target.closest('.list-group-item');
        if (target) {
            const employeeId = target.dataset.id;
            // Highlight active item
            document.querySelectorAll('#employee-list-container .list-group-item').forEach(el => el.classList.remove('active'));
            target.classList.add('active');
            this.renderDetailsView(employeeId);
        }
    }

    handleDetailsClick(e) {
        const target = e.target;
        const employeeId = target.dataset.id || this.state.currentEmployee?.id;

        if (target.classList.contains('edit-btn')) {
            this.renderFormView(this.state.currentEmployee);
        } else if (target.classList.contains('terminate-btn')) {
            this.handleTerminateClick(employeeId);
        } else if (target.classList.contains('cancel-btn')) {
            if (this.state.viewMode === 'edit') {
                 this.renderDetailsView(employeeId);
            } else {
                 this.renderWelcomeView();
                 document.querySelectorAll('#employee-list-container .list-group-item').forEach(el => el.classList.remove('active'));
            }
        } else if (target.closest('#employee-form')) {
            if (e.type === 'submit') {
                this.handleFormSubmit(e);
            }
        }
    }

    handleCreateClick() {
        document.querySelectorAll('#employee-list-container .list-group-item').forEach(el => el.classList.remove('active'));
        this.renderFormView();
    }

    async handleFormSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const rawData = Object.fromEntries(formData.entries());
        const employeeId = rawData.id;

        const method = employeeId ? 'PUT' : 'POST';
        const url = employeeId ? `/employees/${employeeId}` : '/employees';

        let dataToSend;
        if (method === 'PUT') {
            dataToSend = {
                id: employeeId,
                phone_number: rawData.phone_number,
                address: rawData.address,
            };
        } else {
            dataToSend = rawData;
        }

        try {
            const response = await this.apiCall(url, { method, body: dataToSend });
            Toast.success(response.message);
            await this.loadEmployees();
            if (response.data && response.data.id) {
                this.renderDetailsView(response.data.id);
            } else if (employeeId) {
                this.renderDetailsView(employeeId);
            }
        } catch (error) {
            Toast.error(`저장 실패: ${error.message}`);
        }
    }

    async handleTerminateClick(employeeId) {
        const { value: terminationDate } = await Swal.fire({
            title: '퇴사 처리',
            html: `<p>퇴사일을 지정해주세요.</p>`,
            input: 'date',
            showCancelButton: true,
            confirmButtonText: '확인',
            cancelButtonText: '취소',
            didOpen: () => {
                Swal.getInput().value = new Date().toISOString().split('T')[0];
            },
            inputValidator: (value) => !value && '퇴사일을 지정해야 합니다.'
        });

        if (terminationDate) {
            const result = await Confirm.fire({
                title: '퇴사 처리 확인',
                text: `정말로 이 직원을 ${terminationDate}일자로 퇴사 처리하시겠습니까?`
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
                Toast.error(`퇴사 처리 실패: ${error.message}`);
            }
        }
    }

    // --- Utilities ---
    populateDropdown(select, data, defaultOptionText) {
        const currentValue = select.value;
        select.innerHTML = `<option value="">${defaultOptionText}</option>`;
        data.forEach(item => {
            select.insertAdjacentHTML('beforeend', `<option value="${item.id}">${this.sanitizeHTML(item.name)}</option>`);
        });
        select.value = currentValue;
    }

    sanitizeHTML(str) {
        if (str === null || str === undefined) return '';
        const temp = document.createElement('div');
        temp.textContent = str;
        return temp.innerHTML;
    }
}

new EmployeesPage();
