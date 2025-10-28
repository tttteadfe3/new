
class HrOrderPage extends BasePage {
    constructor() {
        super();
        this.state = {
            employees: [],
            departments: [],
            positions: []
        };
    }

    initializeApp() {
        this.cacheDOMElements();
        this.setupEventListeners();
        this.loadInitialData();
    }

    cacheDOMElements() {
        this.elements = {
            form: document.getElementById('hr-order-form'),
            employeeSelect: document.getElementById('employee_id'),
            departmentSelect: document.getElementById('department_id'),
            positionSelect: document.getElementById('position_id'),
            orderDateInput: document.getElementById('order_date'),
            currentInfoPanel: document.getElementById('current-employee-info'),
        };
    }

    setupEventListeners() {
        this.elements.form.addEventListener('submit', (e) => this.handleFormSubmit(e));
        this.elements.employeeSelect.addEventListener('change', (e) => this.handleEmployeeSelect(e));
    }

    async loadInitialData() {
        try {
            const [empRes, initialDataRes] = await Promise.all([
                this.apiCall('/employees?status=재직중'),
                this.apiCall('/employees/initial-data')
            ]);

            this.state.employees = empRes.data;
            this.state.departments = initialDataRes.data.departments;
            this.state.positions = initialDataRes.data.positions;

            this.populateDropdown(this.elements.employeeSelect, this.state.employees, '직원을 선택하세요');
            this.populateDropdown(this.elements.departmentSelect, this.state.departments, '부서를 선택하세요');
            this.populateDropdown(this.elements.positionSelect, this.state.positions, '직급을 선택하세요');

            // URL 쿼리 파라미터에서 employee_id가 있으면 자동으로 선택
            const urlParams = new URLSearchParams(window.location.search);
            const employeeId = urlParams.get('employee_id');
            if (employeeId) {
                this.elements.employeeSelect.value = employeeId;
                this.handleEmployeeSelect({ target: this.elements.employeeSelect });
            }

            // 발령일 기본값 오늘로 설정
            this.elements.orderDateInput.value = new Date().toISOString().slice(0, 10);

        } catch (error) {
            Toast.error('초기 데이터 로딩에 실패했습니다.');
            console.error(error);
        }
    }

    handleEmployeeSelect(event) {
        const employeeId = event.target.value;
        if (!employeeId) {
            this.elements.currentInfoPanel.innerHTML = '<p class="text-muted">직원을 선택하면 현재 정보가 표시됩니다.</p>';
            return;
        }

        const employee = this.state.employees.find(emp => emp.id == employeeId);
        if (employee) {
            this.elements.currentInfoPanel.innerHTML = `
                <dl class="row">
                    <dt class="col-sm-4">이름</dt>
                    <dd class="col-sm-8">${this.sanitizeHTML(employee.name)}</dd>
                    <dt class="col-sm-4">현재 부서</dt>
                    <dd class="col-sm-8">${this.sanitizeHTML(employee.department_name) || '미지정'}</dd>
                    <dt class="col-sm-4">현재 직급</dt>
                    <dd class="col-sm-8">${this.sanitizeHTML(employee.position_name) || '미지정'}</dd>
                </dl>
            `;
        }
    }

    async handleFormSubmit(e) {
        e.preventDefault();
        const formData = new FormData(this.elements.form);
        const data = Object.fromEntries(formData.entries());

        if (!data.department_id && !data.position_id) {
            Toast.error('새 부서 또는 새 직급 중 하나 이상을 선택해야 합니다.');
            return;
        }

        const employee = this.state.employees.find(emp => emp.id == data.employee_id);
        const confirmationText = `
            <strong>[${this.sanitizeHTML(employee.name)}]</strong> 직원에 대해 아래와 같이 인사 발령을 등록하시겠습니까?<br>
            ${data.department_id ? `부서: ${this.elements.departmentSelect.options[this.elements.departmentSelect.selectedIndex].text}<br>` : ''}
            ${data.position_id ? `직급: ${this.elements.positionSelect.options[this.elements.positionSelect.selectedIndex].text}<br>` : ''}
            발령일: ${data.order_date}
        `;

        const result = await Confirm.fire('인사 발령 확인', confirmationText);
        if (!result.isConfirmed) return;

        try {
            const response = await this.apiCall('/hr/orders', { method: 'POST', body: data });
            Toast.success(response.message);
            // 성공 후 폼 리셋 또는 다른 페이지로 이동
            this.elements.form.reset();
            this.elements.currentInfoPanel.innerHTML = '<p class="text-muted">직원을 선택하면 현재 정보가 표시됩니다.</p>';
            this.loadInitialData();

        } catch (error) {
            Toast.error(`인사 발령 등록 실패: ${error.message}`);
        }
    }

    populateDropdown(select, data, defaultOptionText) {
        select.innerHTML = `<option value="">${defaultOptionText}</option>`;
        data.forEach(item => {
            select.insertAdjacentHTML('beforeend', `<option value="${item.id}">${this.sanitizeHTML(item.name)}</option>`);
        });
    }
}

new HrOrderPage();
