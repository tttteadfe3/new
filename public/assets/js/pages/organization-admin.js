class DepartmentAdminPage extends BasePage {
    constructor() {
        super({
            API_URL: '/organization'
        });
        this.elements = {};
        this.choicesInstances = {};
        this.initializeApp(); // Auto-initialize
    }

    initializeApp() {
        this.cacheDOMElements();
        if (!this.elements.deptModalEl) return;
        this.state.deptModal = new bootstrap.Modal(this.elements.deptModalEl);
        this.setupEventListeners();
        this.loadInitialData();
    }

    cacheDOMElements() {
        this.elements = {
            deptModalEl: document.getElementById('department-modal'),
            deptForm: document.getElementById('department-form'),
            modalTitle: document.getElementById('department-modal-title'),
            deptIdInput: document.getElementById('department-id'),
            deptNameInput: document.getElementById('department-name'),
            departmentsListContainer: document.getElementById('departments-list-container'),
            addDepartmentBtn: document.getElementById('add-department-btn'),
            parentIdSelect: document.getElementById('parent-id'),
            viewerEmployeeIdsSelect: document.getElementById('viewer-employee-ids'),
            viewerDepartmentIdsSelect: document.getElementById('viewer-department-ids')
        };
    }

    setupEventListeners() {
        this.elements.addDepartmentBtn.addEventListener('click', () => this.openModal());
        this.elements.deptForm.addEventListener('submit', (e) => this.handleFormSubmit(e));
        this.elements.departmentsListContainer.addEventListener('click', (e) => this.handleActionClick(e));
    }

    loadInitialData() {
        this.loadDepartments();
        this.loadSelectOptions();
    }

    async loadSelectOptions() {
        try {
            const deptResponse = await this.apiCall(`${this.config.API_URL}?type=department`);
            this.populateSelect(this.elements.parentIdSelect, deptResponse.data, 'id', 'name', '(없음)');
        } catch (error) {
            console.error('Failed to load select options:', error);
            Toast.error('상위 부서 목록을 불러오는데 실패했습니다.');
        }
    }

    populateSelect(selectElement, items, valueKey, textKey, defaultOptionText) {
        selectElement.innerHTML = `<option value="">${defaultOptionText}</option>`;
        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item[valueKey];
            option.textContent = item[textKey];
            selectElement.appendChild(option);
        });
    }

    async loadDepartments() {
        try {
            const response = await this.apiCall(`${this.config.API_URL}?type=department`);
            if (response.data.length === 0) {
                this.elements.departmentsListContainer.innerHTML = `<div class="list-group-item">부서가 없습니다.</div>`;
                return;
            }
            this.elements.departmentsListContainer.innerHTML = response.data.map(item => `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        ${this._sanitizeHTML(item.name)}
                        ${item.viewer_employee_names ? `<br><small class="text-muted">조회 권한 직원: ${item.viewer_employee_names}</small>` : ''}
                    </div>
                    <div>
                        <button class="btn btn-success btn-sm edit-btn" data-id="${item.id}" data-name="${this._sanitizeHTML(item.name)}" data-parent-id="${item.parent_id || ''}">수정</button>
                        <button class="btn btn-danger btn-sm delete-btn" data-id="${item.id}" data-name="${this._sanitizeHTML(item.name)}">삭제</button>
                    </div>
                </div>`).join('');
        } catch (error) {
            console.error('Error loading departments:', error);
            this.elements.departmentsListContainer.innerHTML = `<div class="list-group-item text-danger">부서 목록 로딩 실패</div>`;
        }
    }

    async openModal(data = null) {
        this.elements.deptForm.reset();

        if (!this.choicesInstances.viewerEmployees) {
            this.choicesInstances.viewerEmployees = new Choices(this.elements.viewerEmployeeIdsSelect, { removeItemButton: true, placeholder: true, placeholderValue: '직원을 선택하세요' });
        }
        if (!this.choicesInstances.viewerDepartments) {
            this.choicesInstances.viewerDepartments = new Choices(this.elements.viewerDepartmentIdsSelect, { removeItemButton: true, placeholder: true, placeholderValue: '부서를 선택하세요' });
        }

        if (data) { // Editing
            this.elements.modalTitle.textContent = '부서 정보 수정';
            this.elements.deptIdInput.value = data.id;
            this.elements.deptNameInput.value = data.name;
            this.elements.parentIdSelect.value = data.parentId || '';

            try {
                const [empResponse, deptResponse, permResponse] = await Promise.all([
                    this.apiCall(`${this.config.API_URL}/${data.id}/eligible-viewer-employees`),
                    this.apiCall(`${this.config.API_URL}?type=department`),
                    this.apiCall(`${this.config.API_URL}/${data.id}/view-permissions`)
                ]);

                const empChoices = empResponse.data.map(emp => ({ value: emp.id.toString(), label: emp.name }));
                this.choicesInstances.viewerEmployees.setChoices(empChoices, 'value', 'label', true);

                const currentPerms = await this.apiCall(`/api/organization/${data.id}`);
                const viewerEmployeeIds = currentPerms.data.viewer_employee_ids ? currentPerms.data.viewer_employee_ids.split(',').map(id => id.trim().toString()) : [];
                this.choicesInstances.viewerEmployees.setValue(viewerEmployeeIds);

                const deptChoices = deptResponse.data.map(dept => ({ value: dept.id.toString(), label: dept.name }));
                this.choicesInstances.viewerDepartments.setChoices(deptChoices, 'value', 'label', true);

                const viewerDepartmentIds = permResponse.data.map(id => id.toString());
                this.choicesInstances.viewerDepartments.setValue(viewerDepartmentIds);

            } catch (error) {
                console.error('Failed to load select options for editing:', error);
                Toast.error('권한 목록을 불러오는데 실패했습니다.');
            }
        } else { // Adding
            this.elements.modalTitle.textContent = '새 부서 추가';
            this.elements.deptIdInput.value = '';
            try {
                 const [empResponse, deptResponse] = await Promise.all([
                    this.apiCall('/employees?status=active'),
                    this.apiCall(`${this.config.API_URL}?type=department`)
                ]);

                const empChoices = empResponse.data.map(emp => ({ value: emp.id.toString(), label: emp.name }));
                this.choicesInstances.viewerEmployees.setChoices(empChoices, 'value', 'label', true);
                this.choicesInstances.viewerEmployees.setValue([]);

                const deptChoices = deptResponse.data.map(dept => ({ value: dept.id.toString(), label: dept.name }));
                this.choicesInstances.viewerDepartments.setChoices(deptChoices, 'value', 'label', true);
                this.choicesInstances.viewerDepartments.setValue([]);
            } catch (error) {
                console.error('Failed to load select options for new department:', error);
                Toast.error('권한 목록을 불러오는데 실패했습니다.');
            }
        }
        this.state.deptModal.show();
    }

    async handleFormSubmit(e) {
        e.preventDefault();
        const id = this.elements.deptIdInput.value;
        const payload = {
            name: this.elements.deptNameInput.value,
            parent_id: this.elements.parentIdSelect.value,
            viewer_employee_ids: this.choicesInstances.viewerEmployees.getValue(true),
            viewer_department_ids: this.choicesInstances.viewerDepartments.getValue(true),
            type: 'department'
        };

        const url = id ? `${this.config.API_URL}/${id}` : this.config.API_URL;
        const method = id ? 'PUT' : 'POST';

        try {
            const result = await this.apiCall(url, { method, body: payload });
            Toast.success(result.message);
            this.state.deptModal.hide();
            this.loadDepartments();
            this.loadSelectOptions();
        } catch (error) {
            console.error('Error saving department:', error);
            Toast.error(`저장 중 오류 발생: ${error.message}`);
        }
    }

    handleActionClick(e) {
        const target = e.target.closest('.edit-btn, .delete-btn');
        if (!target) return;

        const { id, name, parentId } = target.dataset;

        if (target.classList.contains('edit-btn')) {
            this.openModal({ id, name, parentId });
        } else if (target.classList.contains('delete-btn')) {
            Confirm.fire('삭제 확인', `'${name}' 부서를 정말 삭제하시겠습니까?`).then(result => {
                if (result.isConfirmed) this.deleteItem(id);
            });
        }
    }

    async deleteItem(id) {
        try {
            const result = await this.apiCall(`${this.config.API_URL}/${id}`, { method: 'DELETE', body: { type: 'department' } });
            Toast.success(result.message);
            this.loadDepartments();
            this.loadSelectOptions();
        } catch(error) {
            console.error('Error deleting department:', error);
            Toast.error(`부서 삭제 중 오류가 발생했습니다: ${error.message}`);
        }
    }

    _sanitizeHTML(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}

class PositionAdminPage extends BasePage {
    constructor() {
        super({
            API_URL: '/api/organization' // Use the organization API endpoint
        });
        this.elements = {};
        this.state = {};
        this.initializeApp(); // Auto-initialize
    }

    initializeApp() {
        this.cacheDOMElements();
        if (!this.elements.modalEl) return;

        this.state.modal = new bootstrap.Modal(this.elements.modalEl);
        this.setupEventListeners();
        this.loadPositions(); // Load initial data
    }

    cacheDOMElements() {
        this.elements = {
            modalEl: document.getElementById('position-modal'),
            form: document.getElementById('position-form'),
            modalTitle: document.getElementById('position-modal-title'),
            idInput: document.getElementById('position-id'),
            nameInput: document.getElementById('position-name'),
            levelInput: document.getElementById('position-level'),
            tableBody: document.querySelector('#positions-table tbody'),
            addBtn: document.getElementById('add-position-btn')
        };
    }

    setupEventListeners() {
        this.elements.addBtn.addEventListener('click', () => this.openModal());
        this.elements.tableBody.addEventListener('click', e => this.handleActionClick(e));
        this.elements.form.addEventListener('submit', e => this.handleFormSubmit(e));
    }

    openModal(id = null, name = '', level = '') {
        this.elements.form.reset();
        this.elements.idInput.value = id || '';
        this.elements.nameInput.value = name;
        this.elements.levelInput.value = level || 10;
        this.elements.modalTitle.textContent = id ? '직급 수정' : '새 직급 추가';
        this.state.modal.show();
    }

    handleActionClick(e) {
        const target = e.target;
        const row = target.closest('tr');
        if (!row) return;

        const id = row.dataset.id;
        const name = row.dataset.name;
        const level = row.dataset.level;

        if (target.classList.contains('edit-position-btn')) {
            this.openModal(id, name, level);
        }

        if (target.classList.contains('delete-position-btn')) {
            Confirm.fire('삭제 확인', `'${name}' 직급을 정말 삭제하시겠습니까?`).then(result => {
                if (result.isConfirmed) this.deletePosition(id);
            });
        }
    }

    async loadPositions() {
        try {
            const response = await this.apiCall(`${this.config.API_URL}?type=position`);
            const positions = response.data;

            if (positions.length === 0) {
                this.elements.tableBody.innerHTML = '<tr><td colspan="3">직급이 없습니다.</td></tr>';
                return;
            }

            this.elements.tableBody.innerHTML = positions.map(pos => `
                <tr data-id="${pos.id}" data-name="${this._sanitizeHTML(pos.name)}" data-level="${pos.level}">
                    <td>${this._sanitizeHTML(pos.name)}</td>
                    <td>${pos.level}</td>
                    <td>
                        <button class="btn btn-success btn-sm edit-position-btn">수정</button>
                        <button class="btn btn-danger btn-sm delete-position-btn">삭제</button>
                    </td>
                </tr>
            `).join('');
        } catch (error) {
            console.error('Error loading positions:', error);
            this.elements.tableBody.innerHTML = '<tr><td colspan="3" class="text-danger">직급 목록 로딩 실패</td></tr>';
        }
    }

    async handleFormSubmit(e) {
        e.preventDefault();
        const id = this.elements.idInput.value;
        const method = id ? 'PUT' : 'POST';
        const url = id ? `${this.config.API_URL}/${id}` : this.config.API_URL;

        const payload = {
            name: this.elements.nameInput.value,
            level: this.elements.levelInput.value,
            type: 'position' // Specify the type
        };

        try {
            const result = await this.apiCall(url, { method, body: payload });
            this.state.modal.hide();
            Toast.success(result.message);
            await this.loadPositions(); // Reload the list
        } catch (error) {
            console.error('Error saving position:', error);
            Toast.error(`저장 중 오류 발생: ${error.message}`);
        }
    }

    async deletePosition(id) {
        try {
            const result = await this.apiCall(`${this.config.API_URL}/${id}`, {
                method: 'DELETE',
                body: { type: 'position' }
            });
            Toast.success(result.message);
            await this.loadPositions(); // Reload the list
        } catch (error) {
            console.error('Error deleting position:', error);
            Toast.error(`삭제 중 오류 발생: ${error.message}`);
        }
    }

    _sanitizeHTML(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}


// Initialize pages
if (document.getElementById('departments-list-container')) {
    new DepartmentAdminPage();
}
if (document.getElementById('positions-table')) {
    new PositionAdminPage();
}
