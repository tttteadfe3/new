class OrganizationAdminPage extends BasePage {
    constructor() {
        super({
            API_URL: '/organization'
        });
        this.elements = {};
    }

    initializeApp() {
        this.cacheDOMElements();
        this.state.orgModal = new bootstrap.Modal(this.elements.orgModalEl);
        this.setupEventListeners();
        this.loadInitialData();
    }

    cacheDOMElements() {
        this.elements = {
            orgModalEl: document.getElementById('org-modal'),
            orgForm: document.getElementById('org-form'),
            modalTitle: document.getElementById('org-modal-title'),
            orgIdInput: document.getElementById('org-id'),
            orgTypeInput: document.getElementById('org-type'),
            orgNameInput: document.getElementById('org-name'),
            orgNameLabel: document.getElementById('org-name-label'),
            departmentsListContainer: document.getElementById('departments-list-container'),
            addDepartmentBtn: document.getElementById('add-department-btn'),
            positionsListContainer: document.getElementById('positions-list-container'),
            addPositionBtn: document.getElementById('add-position-btn'),
            departmentFields: document.getElementById('department-fields'),
            parentIdSelect: document.getElementById('parent-id'),
            viewerEmployeeIdsSelect: document.getElementById('viewer-employee-ids'),
            viewerDepartmentIdsSelect: document.getElementById('viewer-department-ids')
        };
        this.choicesInstances = {};
    }

    setupEventListeners() {
        this.elements.addDepartmentBtn.addEventListener('click', () => this.openModal('department'));
        this.elements.addPositionBtn.addEventListener('click', () => this.openModal('position'));
        this.elements.orgForm.addEventListener('submit', (e) => this.handleFormSubmit(e));
        this.elements.departmentsListContainer.addEventListener('click', (e) => this.handleActionClick(e));
        this.elements.positionsListContainer.addEventListener('click', (e) => this.handleActionClick(e));
    }

    loadInitialData() {
        this.loadOrganizationData('department', this.elements.departmentsListContainer);
        this.loadOrganizationData('position', this.elements.positionsListContainer);
        this.loadSelectOptions();
    }

    async loadSelectOptions() {
        try {
            const deptResponse = await this.apiCall('/organization?type=department&context=management');
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

    async loadOrganizationData(type, container) {
        try {
            const response = await this.apiCall(`${this.config.API_URL}?type=${type}&context=management`);
            const entityName = type === 'department' ? '부서' : '직급';

            if (response.data.length === 0) {
                container.innerHTML = `<div class="list-group-item">${entityName}(이)가 없습니다.</div>`;
                return;
            }

            container.innerHTML = response.data.map(item => {
                let dataAttrs = `data-id="${item.id}" data-name="${this._sanitizeHTML(item.name)}" data-type="${type}"`;
                if (type === 'department') {
                    dataAttrs += ` data-simple-name="${this._sanitizeHTML(item.simple_name)}"`;
                    dataAttrs += ` data-parent-id="${item.parent_id || ''}"`;
                    dataAttrs += ` data-viewer-employee-ids="${item.viewer_employee_ids || ''}"`;
                    dataAttrs += ` data-viewer-department-ids="${item.viewer_department_ids || ''}"`;
                }
                return `
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            ${this._sanitizeHTML(item.name)}
                            ${item.viewer_employee_names ? `<br><small class="text-muted">조회 권한 직원: ${item.viewer_employee_names}</small>` : ''}
                        </div>
                        <div>
                            <button class="btn btn-secondary btn-sm edit-btn" ${dataAttrs}>수정</button>
                            <button class="btn btn-danger btn-sm delete-btn" data-id="${item.id}" data-name="${this._sanitizeHTML(item.name)}" data-type="${type}">삭제</button>
                        </div>
                    </div>`;
            }).join('');
        } catch (error) {
            console.error(`Error loading ${type}s:`, error);
            const entityName = type === 'department' ? '부서' : '직급';
            container.innerHTML = `<div class="list-group-item text-danger">${entityName} 목록 로딩 실패</div>`;
        }
    }

    async openModal(type, data = null) {
        this.elements.orgForm.reset();
        const entityName = type === 'department' ? '부서' : '직급';

        this.elements.orgTypeInput.value = type;
        this.elements.orgNameLabel.textContent = `${entityName} 이름`;
        this.elements.departmentFields.style.display = type === 'department' ? 'block' : 'none';

        if (type === 'department') {
            if (!this.choicesInstances.viewerEmployees) {
                this.choicesInstances.viewerEmployees = new Choices(this.elements.viewerEmployeeIdsSelect, {
                    removeItemButton: true,
                    placeholder: true,
                    placeholderValue: '직원을 선택하세요',
                });
            }
            if (!this.choicesInstances.viewerDepartments) {
                this.choicesInstances.viewerDepartments = new Choices(this.elements.viewerDepartmentIdsSelect, {
                    removeItemButton: true,
                    placeholder: true,
                    placeholderValue: '부서를 선택하세요',
                });
            }
        }

        if (data) { // Editing
            this.elements.modalTitle.textContent = `${entityName} 정보 수정`;
            this.elements.orgIdInput.value = data.id;
            this.elements.orgNameInput.value = data.simpleName || data.name;

            if (type === 'department') {
                this.elements.parentIdSelect.value = data.parentId || '';

                try {
                    const empResponse = await this.apiCall(`${this.config.API_URL}/${data.id}/eligible-viewer-employees`);
                    const empChoices = empResponse.data.map(emp => ({ value: emp.id.toString(), label: emp.name }));
                    this.choicesInstances.viewerEmployees.setChoices(empChoices, 'value', 'label', true);
                    const viewerEmployeeIds = data.viewerEmployeeIds ? data.viewerEmployeeIds.split(',').map(id => id.trim().toString()) : [];
                    this.choicesInstances.viewerEmployees.setValue(viewerEmployeeIds);

                    const deptResponse = await this.apiCall('/organization?type=department&context=management');
                    const deptChoices = deptResponse.data.map(dept => ({ value: dept.id.toString(), label: dept.name }));
                    this.choicesInstances.viewerDepartments.setChoices(deptChoices, 'value', 'label', true);

                    const permResponse = await this.apiCall(`${this.config.API_URL}/${data.id}/view-permissions`);
                    const viewerDepartmentIds = permResponse.data.map(id => id.toString());
                    this.choicesInstances.viewerDepartments.setValue(viewerDepartmentIds);

                } catch (error) {
                    console.error('Failed to load select options for editing:', error);
                    Toast.error('권한 목록을 불러오는데 실패했습니다.');
                }
            }
        } else { // Adding
            this.elements.modalTitle.textContent = `새 ${entityName} 추가`;
            this.elements.orgIdInput.value = '';
            if (type === 'department') {
                try {
                    const empResponse = await this.apiCall('/employees?status=active');
                    const empChoices = empResponse.data.map(emp => ({ value: emp.id.toString(), label: emp.name }));
                    this.choicesInstances.viewerEmployees.setChoices(empChoices, 'value', 'label', true);
                    this.choicesInstances.viewerEmployees.setValue([]);

                    const deptResponse = await this.apiCall('/organization?type=department&context=management');
                    const deptChoices = deptResponse.data.map(dept => ({ value: dept.id.toString(), label: dept.name }));
                    this.choicesInstances.viewerDepartments.setChoices(deptChoices, 'value', 'label', true);
                    this.choicesInstances.viewerDepartments.setValue([]);
                } catch (error) {
                    console.error('Failed to load select options for new department:', error);
                    Toast.error('권한 목록을 불러오는데 실패했습니다.');
                }
            }
        }
        this.state.orgModal.show();
    }

    async handleFormSubmit(e) {
        e.preventDefault();
        const id = this.elements.orgIdInput.value;
        const type = this.elements.orgTypeInput.value;
        const name = this.elements.orgNameInput.value;

        const payload = { name, type };
        if (type === 'department') {
            payload.parent_id = this.elements.parentIdSelect.value;
            payload.viewer_employee_ids = this.choicesInstances.viewerEmployees ? this.choicesInstances.viewerEmployees.getValue(true) : [];
            payload.viewer_department_ids = this.choicesInstances.viewerDepartments ? this.choicesInstances.viewerDepartments.getValue(true) : [];
        }

        const url = id ? `${this.config.API_URL}/${id}` : this.config.API_URL;
        const method = id ? 'PUT' : 'POST';

        try {
            const result = await this.apiCall(url, { method, body: payload });
            Toast.success(result.message);
            this.state.orgModal.hide();
            const container = type === 'department' ? this.elements.departmentsListContainer : this.elements.positionsListContainer;
            this.loadOrganizationData(type, container);

            if (type === 'department') {
                this.loadSelectOptions();
            }
        } catch (error) {
            console.error(`Error saving ${type}:`, error);
            Toast.error(`저장 중 오류 발생: ${error.message}`);
        }
    }

    handleActionClick(e) {
        const target = e.target;
        const { id, name, type, parentId, viewerEmployeeIds, viewerDepartmentIds, simpleName } = target.dataset;

        if (!type || !id) return;

        if (target.classList.contains('edit-btn')) {
            const data = { id, name, type, parentId, viewerEmployeeIds, viewerDepartmentIds, simpleName };
            this.openModal(type, data);
        } else if (target.classList.contains('delete-btn')) {
            const entityName = type === 'department' ? '부서' : '직급';
            Confirm.fire('삭제 확인', `'${name}' ${entityName}을(를) 정말 삭제하시겠습니까?`).then(result => {
                if (result.isConfirmed) {
                    this.deleteItem(type, id);
                }
            });
        }
    }

    async deleteItem(type, id) {
        try {
            const result = await this.apiCall(`${this.config.API_URL}/${id}`, { method: 'DELETE', body: { type } });
            Toast.success(result.message);
            const container = type === 'department' ? this.elements.departmentsListContainer : this.elements.positionsListContainer;
            this.loadOrganizationData(type, container);

            if (type === 'department') {
                this.loadSelectOptions();
            }
        } catch(error) {
            console.error(`Error deleting ${type}:`, error);
            const entityName = type === 'department' ? '부서' : '직급';
            Toast.error(`${entityName} 삭제 중 오류가 발생했습니다: ${error.message}`);
        }
    }

    _sanitizeHTML(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // --- Position Management Logic ---
    const positionModalEl = document.getElementById('position-modal');
    if (!positionModalEl) return;

    const positionModal = new bootstrap.Modal(positionModalEl);
    const positionForm = document.getElementById('position-form');
    const positionModalTitle = document.getElementById('position-modal-title');
    const positionIdInput = document.getElementById('position-id');
    const positionNameInput = document.getElementById('position-name');
    const positionLevelInput = document.getElementById('position-level');
    const positionsTableBody = document.querySelector('#positions-table tbody');

    const openPositionModal = (id = null, name = '', level = '') => {
        positionForm.reset();
        positionIdInput.value = id || '';
        positionNameInput.value = name;
        positionLevelInput.value = level || 10;
        positionModalTitle.textContent = id ? '직급 수정' : '새 직급 추가';
        positionModal.show();
    };

    document.getElementById('add-position-btn').addEventListener('click', () => {
        openPositionModal();
    });

    positionsTableBody.addEventListener('click', (e) => {
        const target = e.target;
        const row = target.closest('tr');
        if (!row) return;

        const id = row.dataset.id;
        const name = row.dataset.name;
        const level = row.dataset.level;

        if (target.classList.contains('edit-position-btn')) {
            openPositionModal(id, name, level);
        }

        if (target.classList.contains('delete-position-btn')) {
            if (confirm(`'${name}' 직급을 정말 삭제하시겠습니까?`)) {
                deletePosition(id);
            }
        }
    });

    positionForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = positionIdInput.value;
        const url = id ? `/api/positions/${id}` : '/api/positions';
        const method = id ? 'PUT' : 'POST';

        const payload = {
            name: positionNameInput.value,
            level: positionLevelInput.value
        };

        try {
            const response = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            });
            const result = await response.json();
            if (response.ok) {
                positionModal.hide();
                location.reload(); // Simple reload for now
            } else {
                alert(result.message || '저장 중 오류가 발생했습니다.');
            }
        } catch (error) {
            console.error('Error saving position:', error);
            alert('저장 중 오류가 발생했습니다.');
        }
    });

    const deletePosition = async (id) => {
        try {
            const response = await fetch(`/api/positions/${id}`, {
                method: 'DELETE',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await response.json();
            if (response.ok) {
                location.reload(); // Simple reload
            } else {
                alert(result.message || '삭제 중 오류가 발생했습니다.');
            }
        } catch (error) {
            console.error('Error deleting position:', error);
            alert('삭제 중 오류가 발생했습니다.');
        }
    };

    // Initialize department logic if it exists on the page
    if (document.getElementById('departments-list-container')) {
        new OrganizationAdminPage();
    }
});
