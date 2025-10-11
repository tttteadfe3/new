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
            addPositionBtn: document.getElementById('add-position-btn')
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
    }

    async loadOrganizationData(type, container) {
        try {
            const response = await this.apiCall(`${this.config.API_URL}?type=${type}`);
            const entityName = type === 'department' ? '부서' : '직급';

            if (response.data.length === 0) {
                container.innerHTML = `<div class="list-group-item">${entityName}(이)가 없습니다.</div>`;
                return;
            }

            container.innerHTML = response.data.map(item => `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    ${this._sanitizeHTML(item.name)}
                    <div>
                        <button class="btn btn-secondary btn-sm edit-btn" data-id="${item.id}" data-name="${this._sanitizeHTML(item.name)}" data-type="${type}">수정</button>
                        <button class="btn btn-danger btn-sm delete-btn" data-id="${item.id}" data-name="${this._sanitizeHTML(item.name)}" data-type="${type}">삭제</button>
                    </div>
                </div>`
            ).join('');
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

        if (data) { // Editing
            this.elements.modalTitle.textContent = `${entityName} 정보 수정`;
            this.elements.orgIdInput.value = data.id;
            this.elements.orgNameInput.value = data.name;
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

        const url = id ? `${this.config.API_URL}/${id}` : this.config.API_URL;
        const method = id ? 'PUT' : 'POST';

        try {
            const result = await this.apiCall(url, { method, body: payload });
            Toast.success(result.message);
            this.state.orgModal.hide();
            const container = type === 'department' ? this.elements.departmentsListContainer : this.elements.positionsListContainer;
            this.loadOrganizationData(type, container);
        } catch (error) {
            console.error(`Error saving ${type}:`, error);
            Toast.error(`저장 중 오류 발생: ${error.message}`);
        }
    }

    async handleActionClick(e) {
        const target = e.target;
        const id = target.dataset.id;
        const name = target.dataset.name;
        const type = target.dataset.type;

        if (!type || !id) return;

        if (target.classList.contains('edit-btn')) {
            this.openModal(type, { id, name });
        } else if (target.classList.contains('delete-btn')) {
            const entityName = type === 'department' ? '부서' : '직급';
            const result = await Confirm.fire('삭제 확인', `'${name}' ${entityName}을(를) 정말 삭제하시겠습니까?`);
            if (result.isConfirmed) {
                this.deleteItem(type, id);
            }
        }
    }

    async deleteItem(type, id) {
        try {
            const result = await this.apiCall(`${this.config.API_URL}/${id}`, { method: 'DELETE', body: { type } });
            Toast.success(result.message);
            const container = type === 'department' ? this.elements.departmentsListContainer : this.elements.positionsListContainer;
            this.loadOrganizationData(type, container);
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

new OrganizationAdminPage();