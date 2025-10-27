class DepartmentAdminPage extends BasePage {
    constructor() {
        super({
            API_URL: '/organization'
        });
        this.elements = {};
        this.choicesInstances = {};
        this.state.departments = [];
        this.state.employees = [];
        // BasePage's constructor will call initializeApp on DOMContentLoaded
    }

    initializeApp() {
        this.cacheDOMElements();
        if (!this.elements.deptModalEl) return;
        
        this.state.deptModal = new bootstrap.Modal(this.elements.deptModalEl);
        this.initializeChoices();
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

    initializeChoices() {
        // Choices 인스턴스는 모달이 열릴 때마다 재생성
        this.choicesInstances = {};
    }

    createChoicesInstances() {
        // 기존 인스턴스 제거
        if (this.choicesInstances.viewerEmployees) {
            this.choicesInstances.viewerEmployees.destroy();
        }
        if (this.choicesInstances.viewerDepartments) {
            this.choicesInstances.viewerDepartments.destroy();
        }

        // 새로운 인스턴스 생성
        this.choicesInstances.viewerEmployees = new Choices(
            this.elements.viewerEmployeeIdsSelect, 
            { 
                removeItemButton: true, 
                placeholder: true, 
                placeholderValue: '직원을 선택하세요',
                searchEnabled: true
            }
        );
        
        this.choicesInstances.viewerDepartments = new Choices(
            this.elements.viewerDepartmentIdsSelect, 
            { 
                removeItemButton: true, 
                placeholder: true, 
                placeholderValue: '부서를 선택하세요',
                searchEnabled: true
            }
        );
    }

    setupEventListeners() {
        if (this._listenersAttached) return;
        this._listenersAttached = true;

        this.elements.addDepartmentBtn?.addEventListener('click', () => this.openModal());
        this.elements.deptForm?.addEventListener('submit', (e) => this.handleFormSubmit(e));
        this.elements.departmentsListContainer?.addEventListener('click', (e) => this.handleActionClick(e));
    }

    async loadInitialData() {
        try {
            const [deptResponse, empResponse] = await Promise.all([
                this.apiCall(`${this.config.API_URL}?type=department`),
                this.apiCall('/employees?status=active')
            ]);

            this.state.departments = deptResponse.data || [];
            this.state.employees = empResponse.data || [];

            this.renderDepartments();
            this.updateParentDepartmentOptions();
        } catch (error) {
            console.error('Error loading initial data:', error);
            Toast.error('초기 데이터를 불러오는데 실패했습니다.');
            this.renderError('목록 로딩 실패');
        }
    }

    updateParentDepartmentOptions() {
        this.populateSelect(
            this.elements.parentIdSelect, 
            this.state.departments, 
            'id', 
            'name', 
            '(없음)'
        );
    }

    populateSelect(selectElement, items, valueKey, textKey, defaultOptionText) {
        if (!selectElement) return;
        
        selectElement.innerHTML = `<option value="">${defaultOptionText}</option>`;
        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item[valueKey];
            option.textContent = item[textKey];
            selectElement.appendChild(option);
        });
    }

    renderDepartments() {
        if (!this.state.departments || this.state.departments.length === 0) {
            this.elements.departmentsListContainer.innerHTML = 
                '<div class="list-group-item">부서가 없습니다.</div>';
            return;
        }

        this.elements.departmentsListContainer.innerHTML = this.state.departments
            .map(dept => this.createDepartmentListItem(dept))
            .join('');
    }

    createDepartmentListItem(dept) {
        const viewerInfo = dept.viewer_employee_names 
            ? `<br><small class="text-muted">조회 권한 직원: ${this._sanitizeHTML(dept.viewer_employee_names)}</small>` 
            : '';

        return `
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    ${this._sanitizeHTML(dept.name)}
                    ${viewerInfo}
                </div>
                <div>
                    <button class="btn btn-success btn-sm edit-btn"
                            data-id="${dept.id}"
                            data-name="${this._sanitizeHTML(dept.name)}"
                            data-parent-id="${dept.parent_id || ''}"
                            data-viewer-employee-ids="${dept.viewer_employee_ids || ''}">
                        수정
                    </button>
                    <button class="btn btn-danger btn-sm delete-btn" 
                            data-id="${dept.id}" 
                            data-name="${this._sanitizeHTML(dept.name)}">
                        삭제
                    </button>
                </div>
            </div>`;
    }

    renderError(message) {
        this.elements.departmentsListContainer.innerHTML = 
            `<div class="list-group-item text-danger">${message}</div>`;
    }

    async openModal(data = null) {
        this.elements.deptForm.reset();
        
        // Choices 인스턴스 재생성
        this.createChoicesInstances();

        if (data) {
            await this.openEditModal(data);
        } else {
            await this.openAddModal();
        }

        this.state.deptModal.show();
    }

    resetChoicesSelections() {
        // 더 이상 필요없음 - 인스턴스를 재생성하므로
    }

    async openEditModal(data) {
        this.elements.modalTitle.textContent = '부서 정보 수정';
        this.elements.deptIdInput.value = data.id;
        this.elements.deptNameInput.value = data.name;
        this.elements.parentIdSelect.value = data.parentId || '';

        try {
            const [empResponse, permResponse] = await Promise.all([
                this.apiCall(`${this.config.API_URL}/${data.id}/eligible-viewer-employees`),
                this.apiCall(`${this.config.API_URL}/${data.id}/view-permissions`)
            ]);

            this.setEmployeeChoices(empResponse.data);
            this.setDepartmentChoices(this.state.departments);

            const viewerEmployeeIds = data.viewerEmployeeIds 
                ? data.viewerEmployeeIds.split(',').map(id => id.trim())
                : [];
            
            const viewerDepartmentIds = permResponse.data.map(id => id.toString());

            setTimeout(() => {
                if (viewerEmployeeIds.length > 0) {
                    this.choicesInstances.viewerEmployees.setChoiceByValue(viewerEmployeeIds);
                }
                if (viewerDepartmentIds.length > 0) {
                    this.choicesInstances.viewerDepartments.setChoiceByValue(viewerDepartmentIds);
                }
            }, 50);

        } catch (error) {
            console.error('Failed to load permissions for editing:', error);
            Toast.error('권한 목록을 불러오는데 실패했습니다.');
        }
    }

    async openAddModal() {
        this.elements.modalTitle.textContent = '새 부서 추가';
        this.elements.deptIdInput.value = '';

        this.setEmployeeChoices(this.state.employees);
        this.setDepartmentChoices(this.state.departments);
    }

    setEmployeeChoices(employees) {
        const choices = employees.map(emp => ({ 
            value: emp.id.toString(), 
            label: emp.name,
            selected: false
        }));
        
        this.choicesInstances.viewerEmployees.setChoices(choices, 'value', 'label', true);
    }

    setDepartmentChoices(departments) {
        const choices = departments.map(dept => ({ 
            value: dept.id.toString(), 
            label: dept.name,
            selected: false
        }));
        
        this.choicesInstances.viewerDepartments.setChoices(choices, 'value', 'label', true);
    }

    async handleFormSubmit(e) {
        e.preventDefault();

        const id = this.elements.deptIdInput.value;
        const payload = this.buildPayload();
        
        const url = id ? `${this.config.API_URL}/${id}` : this.config.API_URL;
        const method = id ? 'PUT' : 'POST';

        try {
            const result = await this.apiCall(url, { method, body: payload });
            Toast.success(result.message);
            this.state.deptModal.hide();
            await this.loadInitialData();
        } catch (error) {
            console.error('Error saving department:', error);
            Toast.error(`저장 중 오류 발생: ${error.message}`);
        }
    }

    buildPayload() {
        return {
            name: this.elements.deptNameInput.value.trim(),
            parent_id: this.elements.parentIdSelect.value || null,
            viewer_employee_ids: this.choicesInstances.viewerEmployees.getValue(true),
            viewer_department_ids: this.choicesInstances.viewerDepartments.getValue(true),
            type: 'department'
        };
    }

    handleActionClick(e) {
        const editBtn = e.target.closest('.edit-btn');
        const deleteBtn = e.target.closest('.delete-btn');

        if (editBtn) {
            e.preventDefault();
            e.stopPropagation();
            this.handleEdit(editBtn.dataset);
        } else if (deleteBtn) {
            e.preventDefault();
            e.stopPropagation();
            this.handleDelete(deleteBtn.dataset);
        }
    }

    handleEdit(data) {
        this.openModal(data);
    }

    handleDelete(data) {
        Confirm.fire(
            '삭제 확인', 
            `'${data.name}' 부서를 정말 삭제하시겠습니까?`
        ).then(result => {
            if (result.isConfirmed) {
                this.deleteItem(data.id);
            }
        });
    }

    async deleteItem(id) {
        try {
            const result = await this.apiCall(
                `${this.config.API_URL}/${id}`, 
                { 
                    method: 'DELETE', 
                    body: { type: 'department' } 
                }
            );
            Toast.success(result.message);
            await this.loadInitialData();
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
            API_URL: '/organization'
        });
        this.elements = {};
        this.state.positions = [];
        // BasePage's constructor will call initializeApp on DOMContentLoaded
    }

    initializeApp() {
        this.cacheDOMElements();
        if (!this.elements.modalEl) return;

        this.state.modal = new bootstrap.Modal(this.elements.modalEl);
        this.setupEventListeners();
        this.loadPositions();
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
        this.elements.addBtn?.addEventListener('click', () => this.openModal());
        this.elements.tableBody?.addEventListener('click', e => this.handleActionClick(e));
        this.elements.form?.addEventListener('submit', e => this.handleFormSubmit(e));
    }

    openModal(position = null) {
        this.elements.form.reset();

        if (position) {
            this.elements.modalTitle.textContent = '직급 수정';
            this.elements.idInput.value = position.id;
            this.elements.nameInput.value = position.name;
            this.elements.levelInput.value = position.level;
        } else {
            this.elements.modalTitle.textContent = '새 직급 추가';
            this.elements.idInput.value = '';
            this.elements.levelInput.value = 10;
        }

        this.state.modal.show();
    }

    handleActionClick(e) {
        const row = e.target.closest('tr');
        if (!row) return;

        const position = {
            id: row.dataset.id,
            name: row.dataset.name,
            level: row.dataset.level
        };

        if (e.target.classList.contains('edit-position-btn')) {
            this.openModal(position);
        } else if (e.target.classList.contains('delete-position-btn')) {
            this.handleDelete(position);
        }
    }

    handleDelete(position) {
        Confirm.fire(
            '삭제 확인', 
            `'${position.name}' 직급을 정말 삭제하시겠습니까?`
        ).then(result => {
            if (result.isConfirmed) {
                this.deletePosition(position.id);
            }
        });
    }

    async loadPositions() {
        try {
            const response = await this.apiCall(`${this.config.API_URL}?type=position`);
            this.state.positions = response.data || [];
            this.renderPositions();
        } catch (error) {
            console.error('Error loading positions:', error);
            this.renderError('직급 목록 로딩 실패');
            Toast.error('직급 목록을 불러오는데 실패했습니다.');
        }
    }

    renderPositions() {
        if (this.state.positions.length === 0) {
            this.elements.tableBody.innerHTML = 
                '<tr><td colspan="3">직급이 없습니다.</td></tr>';
            return;
        }

        this.elements.tableBody.innerHTML = this.state.positions
            .map(pos => this.createPositionRow(pos))
            .join('');
    }

    createPositionRow(position) {
        return `
            <tr data-id="${position.id}" 
                data-name="${this._sanitizeHTML(position.name)}" 
                data-level="${position.level}">
                <td>${this._sanitizeHTML(position.name)}</td>
                <td>${position.level}</td>
                <td>
                    <button class="btn btn-success btn-sm edit-position-btn">수정</button>
                    <button class="btn btn-danger btn-sm delete-position-btn">삭제</button>
                </td>
            </tr>`;
    }

    renderError(message) {
        this.elements.tableBody.innerHTML = 
            `<tr><td colspan="3" class="text-danger">${message}</td></tr>`;
    }

    async handleFormSubmit(e) {
        e.preventDefault();

        const id = this.elements.idInput.value;
        const payload = this.buildPayload();
        
        const url = id ? `${this.config.API_URL}/${id}` : this.config.API_URL;
        const method = id ? 'PUT' : 'POST';

        try {
            const result = await this.apiCall(url, { method, body: payload });
            this.state.modal.hide();
            Toast.success(result.message);
            await this.loadPositions();
        } catch (error) {
            console.error('Error saving position:', error);
            Toast.error(`저장 중 오류 발생: ${error.message}`);
        }
    }

    buildPayload() {
        return {
            name: this.elements.nameInput.value.trim(),
            level: parseInt(this.elements.levelInput.value, 10),
            type: 'position'
        };
    }

    async deletePosition(id) {
        try {
            const result = await this.apiCall(
                `${this.config.API_URL}/${id}`, 
                {
                    method: 'DELETE',
                    body: { type: 'position' }
                }
            );
            Toast.success(result.message);
            await this.loadPositions();
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

if (document.getElementById('departments-list-container')) {
    new DepartmentAdminPage();
}
if (document.getElementById('positions-table')) {
    new PositionAdminPage();
}
