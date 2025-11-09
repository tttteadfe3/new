class InventoryGivesPage extends BasePage {
    constructor() {
        super();
        this.state = {
            gives: [],
            departments: [],
            employees: [],
            availableItems: []
        };
        this.initializeApp();
    }

    async initializeApp() {
        this.cacheDOMElements();
        this.setupEventListeners();
        this.setInitialDates();
        await this.loadInitialData();
    }

    cacheDOMElements() {
        this.dom = {
            startDateFilter: document.getElementById('start-date-filter'),
            endDateFilter: document.getElementById('end-date-filter'),
            departmentFilter: document.getElementById('department-filter'),
            tableBody: document.getElementById('gives-table-body'),
            tablePlaceholder: document.getElementById('table-placeholder'),
            modal: new bootstrap.Modal(document.getElementById('give-modal')),
            form: document.getElementById('give-form'),
            giveDateInput: document.getElementById('give-date'),
            itemSelect: document.getElementById('give-item'),
            targetTypeRadios: document.querySelectorAll('input[name="give-target-type"]'),
            deptSelectGroup: document.getElementById('department-select-group'),
            empSelectGroup: document.getElementById('employee-select-group'),
            departmentSelect: document.getElementById('give-department'),
            employeeSelect: document.getElementById('give-employee'),
            quantityInput: document.getElementById('give-quantity'),
            noteTextarea: document.getElementById('give-note'),
        };
    }

    setupEventListeners() {
        this.dom.startDateFilter.addEventListener('change', () => this.loadGives());
        this.dom.endDateFilter.addEventListener('change', () => this.loadGives());
        this.dom.departmentFilter.addEventListener('change', () => this.loadGives());

        this.dom.form.addEventListener('submit', (e) => { e.preventDefault(); this.handleSave(); });
        document.getElementById('give-modal').addEventListener('show.bs.modal', () => this.handleModalOpen());

        this.dom.targetTypeRadios.forEach(radio => {
            radio.addEventListener('change', (e) => this.toggleTargetSelect(e.target.value));
        });

        this.dom.tableBody.addEventListener('click', (e) => {
            if (e.target.matches('.delete-btn')) this.handleDelete(e.target.dataset.id);
        });
    }

    setInitialDates() {
        const today = new Date();
        const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        this.dom.startDateFilter.value = firstDayOfMonth.toISOString().slice(0, 10);
        this.dom.endDateFilter.value = today.toISOString().slice(0, 10);
    }

    async loadInitialData() {
        await this.loadDepartments();
        await this.loadGives();
    }

    async loadGives() {
        this.showTablePlaceholder(true, '목록을 불러오는 중입니다...');
        const params = new URLSearchParams({
            start_date: this.dom.startDateFilter.value,
            end_date: this.dom.endDateFilter.value,
            department_id: this.dom.departmentFilter.value,
        });
        try {
            const response = await this.apiCall(`/item-gives?${params}`);
            this.state.gives = response.data;
            this.renderTable();
        } catch (error) {
            Toast.error('지급 내역을 불러오는 데 실패했습니다.');
            this.showTablePlaceholder(true, `오류: ${this.sanitizeHTML(error.message)}`);
        }
    }

    async loadDepartments() {
        try {
            const response = await this.apiCall('/organization'); // Assuming this endpoint returns all departments
            this.state.departments = response.data.departments;
            let optionsHtml = '<option value="">전체 부서</option>';
            this.state.departments.forEach(dept => optionsHtml += `<option value="${dept.id}">${this.sanitizeHTML(dept.name)}</option>`);
            this.dom.departmentFilter.innerHTML = optionsHtml;
            this.dom.departmentSelect.innerHTML = optionsHtml.replace('전체 부서', '부서 선택');
        } catch (error) { Toast.error('부서 목록 로딩 실패'); }
    }

    async loadEmployees() {
        try {
            const response = await this.apiCall('/employees'); // Assuming this endpoint returns all employees
            this.state.employees = response.data;
            let optionsHtml = '<option value="">직원 선택</option>';
            this.state.employees.forEach(emp => optionsHtml += `<option value="${emp.id}">${this.sanitizeHTML(emp.name)} (${this.sanitizeHTML(emp.department_name)})</option>`);
            this.dom.employeeSelect.innerHTML = optionsHtml;
        } catch (error) { Toast.error('직원 목록 로딩 실패'); }
    }

    async loadAvailableItems() {
        try {
            const response = await this.apiCall('/item-gives/available-items');
            this.state.availableItems = response.data;
            let optionsHtml = '<option value="">품목 선택 (재고)</option>';
            this.state.availableItems.forEach(item => optionsHtml += `<option value="${item.id}">${this.sanitizeHTML(item.category_name)} - ${this.sanitizeHTML(item.name)} (${item.stock})</option>`);
            this.dom.itemSelect.innerHTML = optionsHtml;
        } catch (error) { Toast.error('품목 목록 로딩 실패'); }
    }

    renderTable() {
        if (!this.state.gives || this.state.gives.length === 0) {
            this.showTablePlaceholder(true, '해당 조건에 맞는 지급 내역이 없습니다.');
            this.dom.tableBody.innerHTML = '';
            return;
        }

        this.showTablePlaceholder(false);
        this.dom.tableBody.innerHTML = this.state.gives.map(g => `
            <tr>
                <td>${g.give_date}</td>
                <td>${this.sanitizeHTML(g.item_name)}</td>
                <td>${g.department_name ? `(부서) ${this.sanitizeHTML(g.department_name)}` : ''} ${g.employee_name ? `(개인) ${this.sanitizeHTML(g.employee_name)}` : ''}</td>
                <td>${Number(g.quantity).toLocaleString()}</td>
                <td>${this.sanitizeHTML(g.note || '')}</td>
                <td>${this.sanitizeHTML(g.creator_name || 'N/A')}</td>
                <td>
                    <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${g.id}">지급취소</button>
                </td>
            </tr>
        `).join('');
    }

    toggleTargetSelect(targetType) {
        if (targetType === 'department') {
            this.dom.deptSelectGroup.style.display = 'block';
            this.dom.empSelectGroup.style.display = 'none';
            this.dom.departmentSelect.required = true;
            this.dom.employeeSelect.required = false;
        } else {
            this.dom.deptSelectGroup.style.display = 'none';
            this.dom.empSelectGroup.style.display = 'block';
            this.dom.departmentSelect.required = false;
            this.dom.employeeSelect.required = true;
        }
    }

    async handleModalOpen() {
        this.dom.form.reset();
        this.dom.giveDateInput.value = new Date().toISOString().slice(0, 10);
        this.toggleTargetSelect('department');

        // Asynchronously load data for the modal selects
        this.loadAvailableItems();
        if (this.state.employees.length === 0) { // Load employees only once
            this.loadEmployees();
        }
    }

    async handleSave() {
        const targetType = document.querySelector('input[name="give-target-type"]:checked').value;
        const data = {
            give_date: this.dom.giveDateInput.value,
            item_id: this.dom.itemSelect.value,
            department_id: targetType === 'department' ? this.dom.departmentSelect.value : null,
            employee_id: targetType === 'employee' ? this.dom.employeeSelect.value : null,
            quantity: this.dom.quantityInput.value,
            note: this.dom.noteTextarea.value.trim(),
        };

        try {
            const response = await this.apiCall('/item-gives', { method: 'POST', body: JSON.stringify(data) });
            Toast.success(response.message);
            this.dom.modal.hide();
            await this.loadGives();
        } catch (error) { Toast.error(`저장 실패: ${this.sanitizeHTML(error.message)}`); }
    }

    async handleDelete(id) {
        const confirmed = await Swal.fire({ title: '지급을 취소하시겠습니까?', text: "취소 시 품목의 재고가 다시 복원됩니다.", icon: 'warning', showCancelButton: true, confirmButtonText: '확인', cancelButtonText: '취소' });
        if (confirmed.isConfirmed) {
            try {
                const response = await this.apiCall(`/item-gives/${id}`, { method: 'DELETE' });
                Toast.success(response.message);
                await this.loadGives();
            } catch (error) { Toast.error(`취소 실패: ${this.sanitizeHTML(error.message)}`); }
        }
    }

    showTablePlaceholder(show, message = '') {
        this.dom.tablePlaceholder.style.display = show ? 'block' : 'none';
        if (show) this.dom.tablePlaceholder.innerHTML = `<p class="text-muted">${this.sanitizeHTML(message)}</p>`;
    }
}

document.addEventListener('DOMContentLoaded', () => { new InventoryGivesPage(); });
