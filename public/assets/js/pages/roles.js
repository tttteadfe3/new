class RolesAdminPage extends BasePage {
    constructor() {
        super({
            API_URL: '/roles'
        });

        this.state = {
            ...this.state,
            currentRoleId: null,
            roleModal: null
        };
        this.elements = {};
    }

    initializeApp() {
        this.cacheDOMElements();
        this.state.roleModal = new bootstrap.Modal(this.elements.roleModalEl);
        this.setupEventListeners();
        this.loadInitialData();
    }

    cacheDOMElements() {
        this.elements = {
            rolesListContainer: document.getElementById('roles-list-container'),
            roleDetailsContainer: document.getElementById('role-details-container'),
            roleModalEl: document.getElementById('role-modal'),
            roleForm: document.getElementById('role-form'),
            addRoleBtn: document.getElementById('add-role-btn')
        };
    }

    setupEventListeners() {
        this.elements.rolesListContainer.addEventListener('click', (e) => this.handleRoleSelection(e));
        this.elements.addRoleBtn.addEventListener('click', () => this.openRoleModal());
        this.elements.roleForm.addEventListener('submit', (e) => this.handleRoleFormSubmit(e));

        // Use event delegation for dynamically created buttons
        this.elements.roleDetailsContainer.addEventListener('click', (e) => {
            if (e.target.closest('.edit-role-btn')) this.handleRoleEditClick();
            if (e.target.closest('.delete-role-btn')) this.handleRoleDeleteClick();
        });
    }

    async loadInitialData(selectRoleId = null) {
        try {
            const result = await this.apiCall(this.config.API_URL);
            this.elements.rolesListContainer.innerHTML = '';
            const roles = result.data;

            if (roles.length === 0) {
                this.elements.rolesListContainer.innerHTML = `<div class="list-group-item">표시할 역할이 없습니다.</div>`;
                this.elements.roleDetailsContainer.innerHTML = '<div class="alert alert-info">새 역할을 추가해주세요.</div>';
                return;
            }

            const listHtml = roles.map(role => `
                <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-id="${role.id}">
                    ${this._sanitizeHTML(role.name)}
                    <span class="badge bg-secondary rounded-pill ms-auto">${role.user_count}</span>
                </a>`
            ).join('');
            this.elements.rolesListContainer.innerHTML = listHtml;

            const roleIdToLoad = selectRoleId || roles[0]?.id;
            if (roleIdToLoad) {
                this.setActiveRole(roleIdToLoad);
                await this.loadRoleDetails(roleIdToLoad);
            }
        } catch (error) {
            this.elements.rolesListContainer.innerHTML = `<div class="list-group-item text-danger">역할 목록 로딩 실패: ${error.message}</div>`;
        }
    }

    async loadRoleDetails(roleId) {
        this.state.currentRoleId = roleId;
        try {
            const result = await this.apiCall(`${this.config.API_URL}/${roleId}`);
            const { role, all_permissions, assigned_permission_ids, assigned_users } = result.data;
            this.renderRoleDetails(role, all_permissions, assigned_permission_ids, assigned_users);
        } catch (error) {
            this.elements.roleDetailsContainer.innerHTML = `<div class="alert alert-danger">상세 정보 로딩 실패: ${error.message}</div>`;
        }
    }

    renderRoleDetails(role, allPermissions, assignedIds, assignedUsers) {
        const permissionsHtml = allPermissions.map(p => `
            <div class="col-md-6">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="permissions[]" value="${p.id}" id="perm_${p.id}" ${assignedIds.includes(p.id) ? 'checked' : ''}>
                    <label class="form-check-label" for="perm_${p.id}">
                        <strong>${this._sanitizeHTML(p.key)}</strong><small class="text-muted d-block">${this._sanitizeHTML(p.description)}</small>
                    </label>
                </div>
            </div>`
        ).join('');

        const usersHtml = assignedUsers.length > 0
            ? assignedUsers.map(user => `<li class="list-group-item">${this._sanitizeHTML(user.nickname)} (ID: ${user.id})</li>`).join('')
            : '<li class="list-group-item text-muted">이 역할을 가진 사용자가 없습니다.</li>';

        this.elements.roleDetailsContainer.innerHTML = `
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="m-0">"${this._sanitizeHTML(role.name)}" 설정</h5>
                    <div>
                        <button class="btn btn-secondary btn-sm edit-role-btn">정보 수정</button>
                        <button class="btn btn-danger btn-sm delete-role-btn">역할 삭제</button>
                    </div>
                </div>
                <div class="card-body">
                    <p><strong>설명:</strong> ${this._sanitizeHTML(role.description) || '<i>없음</i>'}</p><hr>
                    <h6><strong>할당된 퍼미션</strong></h6>
                    <form id="permissions-form"><div class="row">${permissionsHtml}</div><hr><button type="submit" class="btn btn-primary">퍼미션 저장</button></form>
                </div>
            </div>
            <div class="card shadow">
                <div class="card-header"><h6 class="m-0">할당된 사용자 목록 (${assignedUsers.length}명)</h6></div>
                <ul class="list-group list-group-flush">${usersHtml}</ul>
            </div>`;

        document.getElementById('permissions-form').addEventListener('submit', (e) => this.handlePermissionSave(e));
    }

    handleRoleSelection(e) {
        e.preventDefault();
        const target = e.target.closest('.list-group-item');
        if (target && !target.classList.contains('active')) {
            this.setActiveRole(target.dataset.id);
            this.loadRoleDetails(target.dataset.id);
        }
    }

    setActiveRole(roleId) {
        this.elements.rolesListContainer.querySelectorAll('.list-group-item').forEach(el => el.classList.remove('active'));
        const roleElement = this.elements.rolesListContainer.querySelector(`[data-id='${roleId}']`);
        if(roleElement) roleElement.classList.add('active');
    }

    openRoleModal(role = null) {
        this.elements.roleForm.reset();
        const roleIdInput = document.getElementById('role-id');
        const roleNameInput = document.getElementById('role-name');
        const roleDescriptionInput = document.getElementById('role-description');
        const modalTitle = document.getElementById('role-modal-title');

        if (role) {
            modalTitle.textContent = '역할 정보 수정';
            roleIdInput.value = role.id;
            roleNameInput.value = role.name;
            roleDescriptionInput.value = role.description;
        } else {
            modalTitle.textContent = '새 역할 추가';
            roleIdInput.value = '';
        }
        this.state.roleModal.show();
    }

    async handleRoleFormSubmit(e) {
        e.preventDefault();
        const id = document.getElementById('role-id').value;
        const data = {
            name: document.getElementById('role-name').value,
            description: document.getElementById('role-description').value
        };

        const url = id ? `${this.config.API_URL}/${id}` : this.config.API_URL;
        const method = id ? 'PUT' : 'POST';

        try {
            const result = await this.apiCall(url, { method, body: data });
            Toast.success(result.message);
            this.state.roleModal.hide();
            await this.loadInitialData(result.data?.id || id);
        } catch (error) {
            Toast.error(`저장 중 오류 발생: ${error.message}`);
        }
    }

    async handlePermissionSave(e) {
        e.preventDefault();
        const permissions = Array.from(new FormData(e.target).getAll('permissions[]')).map(Number);
        try {
            const result = await this.apiCall(`${this.config.API_URL}/${this.state.currentRoleId}/permissions`, { method: 'PUT', body: { permissions } });
            Toast.success(result.message);
        } catch (error) {
            Toast.error(`권한 저장 중 오류 발생: ${error.message}`);
        }
    }

    async handleRoleEditClick() {
        try {
            const result = await this.apiCall(`${this.config.API_URL}/${this.state.currentRoleId}`);
            this.openRoleModal(result.data.role);
        } catch (error) {
            Toast.error('역할 정보를 불러오는 데 실패했습니다.');
        }
    }

    async handleRoleDeleteClick() {
        const roleNameElement = document.querySelector(`#roles-list-container .list-group-item[data-id='${this.state.currentRoleId}']`);
        if (!roleNameElement) return;
        const roleName = roleNameElement.textContent.trim().split('\n')[0];

        const confirmResult = await Confirm.fire('역할 삭제', `'${roleName}' 역할을 정말 삭제하시겠습니까?`);
        if (!confirmResult.isConfirmed) return;

        try {
            const result = await this.apiCall(`${this.config.API_URL}/${this.state.currentRoleId}`, { method: 'DELETE' });
            Toast.success(result.message);
            this.elements.roleDetailsContainer.innerHTML = '<div class="alert alert-info">역할을 선택해주세요.</div>';
            await this.loadInitialData();
        } catch(error) {
            Toast.error(`역할 삭제 중 오류가 발생했습니다: ${error.message}`);
        }
    }

    _sanitizeHTML(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}

new RolesAdminPage();