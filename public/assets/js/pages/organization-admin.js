class DepartmentAdminPage extends BasePage {
    constructor() {
        super({
            API_URL: '/organization'
        });
        this.elements = {};
        this.choicesInstances = {};
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
            const deptResponse = await this.apiCall(this.config.API_URL);
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
            const response = await this.apiCall(this.config.API_URL);
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
                    this.apiCall(this.config.API_URL),
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
                    this.apiCall(this.config.API_URL)
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
            viewer_department_ids: this.choicesInstances.viewerDepartments.getValue(true)
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
            const result = await this.apiCall(`${this.config.API_URL}/${id}`, { method: 'DELETE' });
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
        new DepartmentAdminPage();
    }
});
