class UsersAdminApp extends BaseApp {
    constructor() {
        super({
            API_URL: '/users',
            ROLES_API_URL: '/roles',
            EMPLOYEES_API_URL: '/employees',
            ORGANIZATION_API_URL: '/organization'
        });
        this.elements = {};
        this.state = {
            ...this.state,
            userModal: null,
            mappingModal: null
        };
    }

    initializeApp() {
        this.cacheDOMElements();
        this.state.userModal = new bootstrap.Modal(this.elements.userModalEl);
        this.state.mappingModal = new bootstrap.Modal(this.elements.mappingModalEl);
        this.setupEventListeners();
        this.loadInitialData();
    }

    cacheDOMElements() {
        this.elements = {
            userTableBody: document.getElementById('user-table-body'),
            userModalEl: document.getElementById('user-modal'),
            userForm: document.getElementById('user-form'),
            userModalTitle: document.getElementById('user-modal-title'),
            mappingModalEl: document.getElementById('mapping-modal'),
            mappingForm: document.getElementById('mapping-form'),
            mappingModalTitle: document.getElementById('mapping-modal-title'),
            statusFilter: document.getElementById('status-filter'),
            nicknameFilter: document.getElementById('nickname-filter'),
            staffFilter: document.getElementById('staff-filter'),
            roleFilter: document.getElementById('role-filter'),
            searchBtn: document.getElementById('filter-search-btn'),
            resetBtn: document.getElementById('filter-reset-btn'),
            departmentFilter: document.getElementById('department_filter'),
            employeeSelect: document.getElementById('employee_id_select')
        };
    }

    setupEventListeners() {
        this.elements.searchBtn.addEventListener('click', () => this.loadUsers());
        this.elements.resetBtn.addEventListener('click', () => {
            this.elements.statusFilter.value = '';
            this.elements.nicknameFilter.value = '';
            this.elements.staffFilter.value = '';
            this.elements.roleFilter.value = '';
            this.loadUsers();
        });
        this.elements.userTableBody.addEventListener('click', (e) => this.handleTableClick(e));
        this.elements.userForm.addEventListener('submit', (e) => this.handleUserFormSubmit(e));
        this.elements.mappingForm.addEventListener('submit', (e) => this.handleMappingFormSubmit(e));
        this.elements.departmentFilter.addEventListener('change', () => this.loadUnlinkedEmployees());
    }

    loadInitialData() {
        this.loadUsers();
        this.loadRolesForFilter();
    }

    async loadUsers() {
        this.elements.userTableBody.innerHTML = '<tr><td colspan="6" class="text-center">사용자 목록을 불러오는 중...</td></tr>';

        const filters = {
            status: this.elements.statusFilter.value,
            nickname: this.elements.nicknameFilter.value,
            staff: this.elements.staffFilter.value,
            role_id: this.elements.roleFilter.value
        };
        const queryParams = new URLSearchParams(filters).toString();

        try {
            const result = await this.apiCall(`${this.config.API_URL}?${queryParams}`);
            this.renderUserTable(result.data);
        } catch (error) {
            console.error('Error loading user list:', error);
            this.elements.userTableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">목록 로딩 실패: ${error.message}</td></tr>`;
        }
    }

    renderUserTable(users) {
        this.elements.userTableBody.innerHTML = '';
        if (users.length === 0) {
            this.elements.userTableBody.innerHTML = '<tr><td colspan="6" class="text-center">검색 결과가 없습니다.</td></tr>';
            return;
        }
        const rowsHtml = users.map(user => {
            const statusBadgeClass = { 'active': 'bg-success', 'pending': 'bg-warning', 'blocked': 'bg-danger' }[user.status] || 'bg-secondary';
            const mappingInfo = user.employee_id
                ? `<span class="badge bg-info">${this._sanitizeHTML(user.employee_name)}</span>`
                : `<span class="text-muted"><i>연결 안됨</i></span>`;
            const mappingButton = user.employee_id
                ? `<button class="btn btn-warning btn-sm unlink-btn" data-id="${user.id}">연결 해제</button>`
                : `<button class="btn btn-success btn-sm link-btn" data-id="${user.id}">직원 연결</button>`;
            return `
                <tr>
                    <td>${user.id}</td>
                    <td>${this._sanitizeHTML(user.nickname)}</td>
                    <td>${mappingInfo}</td>
                    <td>${user.roles || '<i>역할 없음</i>'}</td>
                    <td><span class="badge ${statusBadgeClass}">${this._sanitizeHTML(user.status)}</span></td>
                    <td class="text-nowrap">${mappingButton}<button class="btn btn-secondary btn-sm edit-btn ms-1" data-id="${user.id}">역할/상태 수정</button></td>
                </tr>`;
        }).join('');
        this.elements.userTableBody.innerHTML = rowsHtml;
    }

    async loadRolesForFilter() {
        try {
            const result = await this.apiCall(this.config.ROLES_API_URL);
            this.elements.roleFilter.innerHTML = '<option value="">-- 전체 --</option>';
            result.data.forEach(role => {
                this.elements.roleFilter.insertAdjacentHTML('beforeend', `<option value="${role.id}">${this._sanitizeHTML(role.name)}</option>`);
            });
        } catch (error) {
            console.error('Error loading roles:', error);
        }
    }

    async handleTableClick(e) {
        const target = e.target;
        const userId = target.dataset.id;
        if (!userId) return;

        if (target.classList.contains('edit-btn')) this.openEditModal(userId);
        if (target.classList.contains('link-btn')) this.openMappingModal(userId, target.closest('tr').children[1].textContent);
        if (target.classList.contains('unlink-btn')) this.unlinkEmployee(userId);
    }

    async openEditModal(userId) {
        try {
            const [userResult, rolesResult] = await Promise.all([
                this.apiCall(`${this.config.API_URL}/${userId}`),
                this.apiCall(this.config.ROLES_API_URL)
            ]);
            const user = userResult.data;
            this.elements.userModalTitle.textContent = `'${this._sanitizeHTML(user.nickname)}' 정보 수정`;
            this.elements.userForm.user_id.value = user.id;
            this.elements.userForm.status.value = user.status;

            const rolesContainer = document.getElementById('roles-container');
            rolesContainer.innerHTML = rolesResult.data.map(role => `
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="roles[]" value="${role.id}" id="role_${role.id}" ${user.assigned_roles.includes(role.id) ? 'checked' : ''}>
                    <label class="form-check-label" for="role_${role.id}">${this._sanitizeHTML(role.name)}</label>
                </div>`
            ).join('');
            this.state.userModal.show();
        } catch (error) {
            Toast.error('사용자 정보를 불러오는 데 실패했습니다.');
        }
    }

    async openMappingModal(userId, userNickname) {
        this.elements.mappingForm.user_id.value = userId;
        this.elements.mappingModalTitle.textContent = `'${this._sanitizeHTML(userNickname)}' 사용자에게 직원 연결`;

        try {
            await Promise.all([this.loadDepartmentsForMapping(), this.loadUnlinkedEmployees()]);
            this.state.mappingModal.show();
        } catch (error) {
            Toast.error('직원 연결 정보를 불러오는 데 실패했습니다.');
        }
    }

    async loadDepartmentsForMapping() {
        const result = await this.apiCall(`${this.config.ORGANIZATION_API_URL}?type=department`);
        this.elements.departmentFilter.innerHTML = '<option value="">-- 전체 --</option>';
        result.data.forEach(dept => {
            this.elements.departmentFilter.insertAdjacentHTML('beforeend', `<option value="${dept.id}">${this._sanitizeHTML(dept.name)}</option>`);
        });
    }

    async loadUnlinkedEmployees(departmentId = '') {
        this.elements.employeeSelect.innerHTML = '<option value="">불러오는 중...</option>';
        try {
            const query = departmentId ? `?department_id=${departmentId}` : '';
            const result = await this.apiCall(`${this.config.EMPLOYEES_API_URL}/unlinked${query}`);
            this.elements.employeeSelect.innerHTML = '<option value="">-- 연결할 직원을 선택하세요 --</option>';
            result.data.forEach(emp => {
                this.elements.employeeSelect.insertAdjacentHTML('beforeend', `<option value="${emp.id}">${this._sanitizeHTML(emp.name)} (${this._sanitizeHTML(emp.employee_number) || '사번 없음'})</option>`);
            });
        } catch (error) {
            this.elements.employeeSelect.innerHTML = '<option value="">목록 로딩 실패</option>';
        }
    }

    async unlinkEmployee(userId) {
        const confirmResult = await Confirm.fire('연결 해제', '정말로 이 사용자의 직원 연결을 해제하시겠습니까?');
        if (!confirmResult.isConfirmed) return;
        try {
            const result = await this.apiCall(`${this.config.API_URL}/${userId}/unlink`, { method: 'POST' });
            Toast.success(result.message);
            this.loadUsers();
        } catch (error) {
            Toast.error('연결 해제 중 오류가 발생했습니다.');
        }
    }

    async handleUserFormSubmit(e) {
        e.preventDefault();
        const userId = this.elements.userForm.user_id.value;
        const data = {
            status: this.elements.userForm.status.value,
            roles: Array.from(this.elements.userForm.querySelectorAll('input[name="roles[]"]:checked')).map(el => Number(el.value))
        };
        try {
            const result = await this.apiCall(`${this.config.API_URL}/${userId}`, { method: 'PUT', body: data });
            this.state.userModal.hide();
            this.loadUsers();
            Toast.success(result.message);
        } catch (error) {
            Toast.error('저장 중 오류가 발생했습니다.');
        }
    }

    async handleMappingFormSubmit(e) {
        e.preventDefault();
        const userId = this.elements.mappingForm.user_id.value;
        const employeeId = this.elements.mappingForm.employee_id.value;
        if (!employeeId) {
            Toast.error('연결할 직원을 선택해주세요.');
            return;
        }
        try {
            const result = await this.apiCall(`${this.config.API_URL}/${userId}/link`, { method: 'POST', body: { employee_id: employeeId } });
            this.state.mappingModal.hide();
            this.loadUsers();
            Toast.success(result.message);
        } catch (error) {
            Toast.error('직원 연결 중 오류가 발생했습니다.');
        }
    }

    _sanitizeHTML(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}

new UsersAdminApp();