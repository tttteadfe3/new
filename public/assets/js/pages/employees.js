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

        const listHtml = employeeList.map(employee => {
            const statusBadge = employee.profile_update_status === '대기'
                ? '<span class="badge bg-warning float-end">수정 요청</span>'
                : '';

            return `
            <a href="#" class="list-group-item list-group-item-action" data-id="${employee.id}">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1">${this.sanitizeHTML(employee.name)} ${statusBadge}</h6>
                    <small>${this.sanitizeHTML(employee.position_name) || '미지정'}</small>
                </div>
                <p class="mb-1">${this.sanitizeHTML(employee.department_name) || '미지정'}</p>
                ${employee.termination_date ? `<small class="text-danger">퇴사: ${this.sanitizeHTML(employee.termination_date)}</small>` : ''}
            </a>
            `;
        }).join('');
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
            const [detailsRes, historyRes] = await Promise.all([
                this.apiCall(`/employees/${employeeId}`),
                this.apiCall(`/employees/${employeeId}/history`)
            ]);

            this.state.currentEmployee = detailsRes.data;
            const employee = this.state.currentEmployee;
            const history = historyRes.data;

            // If there's a pending update, render the approval view instead
            if (employee.profile_update_status === '대기' && employee.pending_profile_data) {
                this.renderApprovalView(employee);
                return;
            }

            const isTerminated = !!employee.termination_date;
            const buttonsHtml = `
                <div class="d-flex justify-content-end align-items-center gap-2">
                    <button class="btn btn-warning terminate-btn" data-id="${employee.id}" ${isTerminated ? 'disabled' : ''}>퇴사 처리</button>
                    <button class="btn btn-primary edit-btn" data-id="${employee.id}" ${isTerminated ? 'disabled' : ''}>수정하기</button>
                </div>
            `;

            const tabbedInfoHtml = `
                <ul class="nav nav-tabs nav-justified mb-3" role="tablist">
                    <li class="nav-item" role="presentation"><a class="nav-link active" data-bs-toggle="tab" href="#view-contact-info" role="tab" aria-selected="true">연락처/주소</a></li>
                    <li class="nav-item" role="presentation"><a class="nav-link" data-bs-toggle="tab" href="#view-emergency-contact" role="tab" aria-selected="false">비상 연락처</a></li>
                    <li class="nav-item" role="presentation"><a class="nav-link" data-bs-toggle="tab" href="#view-clothing-sizes" role="tab" aria-selected="false">의류 사이즈</a></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="view-contact-info" role="tabpanel">
                        <dl class="row">
                            <dt class="col-sm-3">연락처</dt><dd class="col-sm-9">${this.sanitizeHTML(employee.phone_number) || '<i>-</i>'}</dd>
                            <dt class="col-sm-3">주소</dt><dd class="col-sm-9">${this.sanitizeHTML(employee.address) || '<i>-</i>'}</dd>
                        </dl>
                    </div>
                    <div class="tab-pane" id="view-emergency-contact" role="tabpanel">
                        <dl class="row">
                            <dt class="col-sm-3">비상연락처 이름</dt><dd class="col-sm-9">${this.sanitizeHTML(employee.emergency_contact_name) || '<i>-</i>'}</dd>
                            <dt class="col-sm-3">관계</dt><dd class="col-sm-9">${this.sanitizeHTML(employee.emergency_contact_relation) || '<i>-</i>'}</dd>
                        </dl>
                    </div>
                    <div class="tab-pane" id="view-clothing-sizes" role="tabpanel">
                        <dl class="row">
                             <dt class="col-sm-3">상의</dt><dd class="col-sm-3">${this.sanitizeHTML(employee.clothing_top_size) || '<i>-</i>'}</dd>
                             <dt class="col-sm-3">하의</dt><dd class="col-sm-3">${this.sanitizeHTML(employee.clothing_bottom_size) || '<i>-</i>'}</dd>
                             <dt class="col-sm-3">신발</dt><dd class="col-sm-3">${this.sanitizeHTML(employee.shoe_size) || '<i>-</i>'}</dd>
                        </dl>
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
                </dl>
                <hr class="my-2">
                ${tabbedInfoHtml}

                <div id="change-history-container" class="mt-4"></div>

                <hr class="my-3">
                ${buttonsHtml}
            `;

            this.renderHistory(history);
            this.state.viewMode = 'view';
        } catch (error) {
            Toast.error('직원 상세 정보 로딩 실패');
        }
    }

    renderHistory(historyData) {
        const container = document.getElementById('change-history-container');
        if (!container) return;

        if (!historyData || historyData.length === 0) {
            container.innerHTML = '';
            return;
        }

        const historyHtml = historyData.map(log => `
            <div class="list-group-item">
                <p class="mb-1"><strong>${this.sanitizeHTML(log.field_name)}:</strong> <span class="text-danger text-decoration-line-through">${this.sanitizeHTML(log.old_value || '없음')}</span> → <span class="text-success fw-bold">${this.sanitizeHTML(log.new_value || '없음')}</span></p>
                <small class="text-muted">${log.changed_at} by ${this.sanitizeHTML(log.changer_name) || 'System'}</small>
            </div>`
        ).join('');

        container.innerHTML = `
            <h5><i class="bi bi-clock-history"></i> 변경 이력</h5>
            <div class="list-group list-group-flush border" style="max-height: 200px; overflow-y: auto;">
                ${historyHtml}
            </div>
        `;
    }

    renderApprovalView(employee) {
        const pendingData = JSON.parse(employee.pending_profile_data);
        const fields = {
            phone_number: '연락처',
            address: '주소',
            emergency_contact_name: '비상연락처 이름',
            emergency_contact_relation: '비상연락처 관계',
            clothing_top_size: '상의 사이즈',
            clothing_bottom_size: '하의 사이즈',
            shoe_size: '신발 사이즈'
        };

        let changesHtml = '';
        for (const key in pendingData) {
            if (fields[key]) {
                const oldValue = (employee[key] === null || employee[key] === undefined) ? '<i>없음</i>' : this.sanitizeHTML(employee[key]);
                const newValue = this.sanitizeHTML(pendingData[key] || '<i>없음</i>');
                if (oldValue !== newValue) {
                     changesHtml += `
                        <dt class="col-sm-3">${fields[key]}</dt>
                        <dd class="col-sm-9">
                            <span class="text-danger text-decoration-line-through">${oldValue}</span> → <span class="text-success fw-bold">${newValue}</span>
                        </dd>
                    `;
                }
            }
        }

        const buttonsHtml = `
            <div class="d-flex justify-content-end gap-2 mt-4">
                <button class="btn btn-danger reject-btn" data-id="${employee.id}">반려</button>
                <button class="btn btn-success approve-btn" data-id="${employee.id}">승인</button>
            </div>
        `;

        this.elements.detailsContainer.innerHTML = `
            <h4 class="mb-3">프로필 변경 요청 승인</h4>
            <p><strong>${this.sanitizeHTML(employee.name)}</strong>님이 아래와 같이 정보 수정을 요청했습니다.</p>
            <div class="card">
                <div class="card-body">
                    <dl class="row mb-0">
                        ${changesHtml}
                    </dl>
                </div>
            </div>
            ${buttonsHtml}
        `;
        this.state.viewMode = 'approval';
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

                <!-- 수정 가능 정보 (탭 UI) -->
                <ul class="nav nav-tabs nav-justified mb-3" role="tablist">
                    <li class="nav-item" role="presentation"><a class="nav-link active" data-bs-toggle="tab" href="#contact-info" role="tab" aria-selected="true">연락처/주소</a></li>
                    <li class="nav-item" role="presentation"><a class="nav-link" data-bs-toggle="tab" href="#emergency-contact" role="tab" aria-selected="false">비상 연락처</a></li>
                    <li class="nav-item" role="presentation"><a class="nav-link" data-bs-toggle="tab" href="#clothing-sizes" role="tab" aria-selected="false">의류 사이즈</a></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="contact-info" role="tabpanel">
                        <div class="mb-3"><label for="phone_number" class="form-label">연락처</label><input type="tel" class="form-control" name="phone_number" value="${this.sanitizeHTML(employee?.phone_number || '')}"></div>
                        <div class="mb-3"><label for="address" class="form-label">주소</label><input type="text" class="form-control" name="address" value="${this.sanitizeHTML(employee?.address || '')}"></div>
                    </div>
                    <div class="tab-pane" id="emergency-contact" role="tabpanel">
                         <div class="row">
                            <div class="col-md-6 mb-3"><label for="emergency_contact_name" class="form-label">비상연락처 이름</label><input type="text" class="form-control" name="emergency_contact_name" value="${this.sanitizeHTML(employee?.emergency_contact_name || '')}"></div>
                            <div class="col-md-6 mb-3"><label for="emergency_contact_relation" class="form-label">관계</label><input type="text" class="form-control" name="emergency_contact_relation" value="${this.sanitizeHTML(employee?.emergency_contact_relation || '')}"></div>
                        </div>
                    </div>
                    <div class="tab-pane" id="clothing-sizes" role="tabpanel">
                        <div class="row">
                            <div class="col-md-4 mb-3"><label for="clothing_top_size" class="form-label">상의</label><input type="text" class="form-control" name="clothing_top_size" value="${this.sanitizeHTML(employee?.clothing_top_size || '')}"></div>
                            <div class="col-md-4 mb-3"><label for="clothing_bottom_size" class="form-label">하의</label><input type="text" class="form-control" name="clothing_bottom_size" value="${this.sanitizeHTML(employee?.clothing_bottom_size || '')}"></div>
                            <div class="col-md-4 mb-3"><label for="shoe_size" class="form-label">신발</label><input type="text" class="form-control" name="shoe_size" value="${this.sanitizeHTML(employee?.shoe_size || '')}"></div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button type="button" class="btn btn-secondary cancel-btn">취소</button>
                    <button type="submit" class="btn btn-primary">${isCreate ? '등록하기' : '저장하기'}</button>
                </div>
            </form>
        `;
        this.elements.detailsContainer.innerHTML = formHtml;

        // Populate dropdowns for the form in both create and edit modes
        this.populateDropdown(document.getElementById('form-department-id'), this.state.allDepartments, '부서 선택');
        this.populateDropdown(document.getElementById('form-position-id'), this.state.allPositions, '직급 선택');

        // Set the selected values if in edit mode
        if (employee) {
            document.getElementById('form-department-id').value = employee.department_id || '';
            document.getElementById('form-position-id').value = employee.position_id || '';
        }

        // Directly bind the submit event to the new form
        const form = document.getElementById('employee-form');
        form.addEventListener('submit', (e) => this.handleFormSubmit(e));
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
        } else if (target.classList.contains('approve-btn')) {
            this.approveProfileUpdate(employeeId);
        } else if (target.classList.contains('reject-btn')) {
            this.rejectProfileUpdate(employeeId);
        } else if (target.classList.contains('cancel-btn')) {
            if (this.state.viewMode === 'edit') {
                 this.renderDetailsView(employeeId);
            } else {
                 this.renderWelcomeView();
                 document.querySelectorAll('#employee-list-container .list-group-item').forEach(el => el.classList.remove('active'));
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
            // 수정 시에는 수정 가능한 모든 필드를 포함
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

    async approveProfileUpdate(employeeId) {
        const result = await Confirm.fire({
            title: '승인 확인',
            text: '이 사용자의 프로필 변경 요청을 승인하시겠습니까?'
        });
        if (!result.isConfirmed) return;

        try {
            const response = await this.apiCall(`/employees/${employeeId}/approve-update`, { method: 'POST' });
            Toast.success(response.message);
            await this.loadEmployees();
            this.renderDetailsView(employeeId); // Refresh details view
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
                await this.loadEmployees();
                this.renderDetailsView(employeeId); // Refresh details view
            } catch (error) {
                Toast.error(`반려 처리 중 오류: ${error.message}`);
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
