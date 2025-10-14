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
            // New elements for department fields
            departmentFields: document.getElementById('department-fields'),
            parentIdSelect: document.getElementById('parent-id'),
            managerIdSelect: document.getElementById('manager-id')
        };
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
        // Pre-load select options for the modal
        this.loadSelectOptions();
    }

    async loadSelectOptions() {
        try {
            // Load all departments for parent selection
            const deptResponse = await this.apiCall('/organization?type=department');
            this.populateSelect(this.elements.parentIdSelect, deptResponse.data, 'id', 'name', '(없음)');

            // Load all active employees for manager selection
            const empResponse = await this.apiCall('/employees?status=active');
            this.populateSelect(this.elements.managerIdSelect, empResponse.data, 'id', 'name', '(없음)');
        } catch (error) {
            console.error('Failed to load select options:', error);
            Toast.error('부서장 및 상위 부서 목록을 불러오는데 실패했습니다.');
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
            const response = await this.apiCall(`${this.config.API_URL}?type=${type}`);
            const entityName = type === 'department' ? '부서' : '직급';

            if (response.data.length === 0) {
                container.innerHTML = `<div class="list-group-item">${entityName}(이)가 없습니다.</div>`;
                return;
            }

            container.innerHTML = response.data.map(item => {
                const dataAttrs = `data-id="${item.id}" data-name="${this._sanitizeHTML(item.name)}" data-type="${type}"` +
                                  (type === 'department' ? ` data-parent-id="${item.parent_id || ''}" data-manager-id="${item.manager_id || ''}"` : '');
                return `
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        ${this._sanitizeHTML(item.name)}
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

    openModal(type, data = null) {
        this.elements.orgForm.reset();
        const entityName = type === 'department' ? '부서' : '직급';

        this.elements.orgTypeInput.value = type;
        this.elements.orgNameLabel.textContent = `${entityName} 이름`;
        this.elements.departmentFields.style.display = type === 'department' ? 'block' : 'none';

        if (data) { // Editing
            this.elements.modalTitle.textContent = `${entityName} 정보 수정`;
            this.elements.orgIdInput.value = data.id;
            this.elements.orgNameInput.value = data.name;
            if (type === 'department') {
                this.elements.parentIdSelect.value = data.parentId || '';
                this.elements.managerIdSelect.value = data.managerId || '';
            }
        } else { // Adding
            this.elements.modalTitle.textContent = `새 ${entityName} 추가`;
            this.elements.orgIdInput.value = '';
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
            payload.manager_id = this.elements.managerIdSelect.value;
        }

        const url = id ? `${this.config.API_URL}/${id}` : this.config.API_URL;
        const method = id ? 'PUT' : 'POST';

        try {
            const result = await this.apiCall(url, { method, body: payload });
            Toast.success(result.message);
            this.state.orgModal.hide();
            const container = type === 'department' ? this.elements.departmentsListContainer : this.elements.positionsListContainer;
            this.loadOrganizationData(type, container);

            // If a department was updated, the parent department list might need refreshing
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
        const { id, name, type, parentId, managerId } = target.dataset;

        if (!type || !id) return;

        if (target.classList.contains('edit-btn')) {
            const data = { id, name, parentId, managerId };
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
    new OrganizationAdminPage();
});
