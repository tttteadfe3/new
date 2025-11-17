/**
 * 지급 등록 페이지
 */

class SupplyDistributionsCreatePage extends BasePage {
    constructor() {
        super({
            API_URL: '/supply/distributions'
        });
        
        this.availableItems = [];
        this.departments = [];
        this.employees = [];

        // 오늘 날짜로 지급일 기본 설정
        const today = new Date().toISOString().split('T')[0];
        const distributionDate = document.getElementById('distribution_date');
        if (distributionDate) {
            distributionDate.value = today;
        }
    }

    setupEventListeners() {
        const form = document.getElementById('distribution-form');
        const itemSelect = document.getElementById('item-id');
        const quantityInput = document.getElementById('quantity');
        const departmentSelect = document.getElementById('department-id');

        itemSelect?.addEventListener('change', (e) => this.updateStockInfo(e.target));
        quantityInput?.addEventListener('input', () => this.validateQuantity());
        departmentSelect?.addEventListener('change', (e) => this.loadEmployees(e.target.value));
        form?.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    async loadInitialData() {
        await Promise.all([
            this.loadAvailableItems(),
            this.loadDepartments()
        ]);
    }

    async loadAvailableItems() {
        const itemSelect = document.getElementById('item-id');
        try {
            const response = await this.apiCall(`${this.config.API_URL}/available-items`);
            this.availableItems = response.data || [];
            this.renderOptions(itemSelect, this.availableItems, {
                value: 'id',
                text: item => `${item.item_name} (재고: ${this.formatNumber(item.current_stock)} ${item.unit})`,
                placeholder: '품목을 선택하세요',
                attributes: {
                    'data-stock': 'current_stock',
                    'data-unit': 'unit'
                }
            });
        } catch (error) {
            this.handleApiError(error, itemSelect, '품목 목록을 불러오는 중 오류가 발생했습니다.');
        }
    }

    async loadDepartments() {
        const deptSelect = document.getElementById('department-id');
        try {
            const response = await this.apiCall(`${this.config.API_URL}/departments`);
            this.departments = response.data || [];
            this.renderOptions(deptSelect, this.departments, {
                value: 'id',
                text: 'name',
                placeholder: '부서를 선택하세요'
            });
        } catch (error) {
            this.handleApiError(error, deptSelect, '부서 목록을 불러오는 중 오류가 발생했습니다.');
        }
    }

    async loadEmployees(departmentId) {
        const employeeSelect = document.getElementById('employee-id');
        if (!employeeSelect) return;

        if (!departmentId) {
            employeeSelect.innerHTML = '<option value="">먼저 부서를 선택하세요</option>';
            employeeSelect.disabled = true;
            return;
        }

        employeeSelect.innerHTML = '<option value="">불러오는 중...</option>';
        employeeSelect.disabled = true;

        try {
            const response = await this.apiCall(`${this.config.API_URL}/employees-by-department/${departmentId}`);
            this.employees = response.data || [];
            this.renderOptions(employeeSelect, this.employees, {
                value: 'id',
                text: item => `${item.name} (${item.employee_number || '번호 없음'})`,
                placeholder: '직원을 선택하세요'
            });
            employeeSelect.disabled = false;
        } catch (error) {
            this.handleApiError(error, employeeSelect, '직원 목록을 불러오는 중 오류가 발생했습니다.');
        }
    }

    updateStockInfo(selectElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const stockInfo = document.getElementById('stock-info');
        const unitDisplay = document.getElementById('unit-display');
        
        if (selectedOption && selectedOption.value) {
            const stock = selectedOption.dataset.stock;
            const unit = selectedOption.dataset.unit;
            
            stockInfo.textContent = `현재 재고: ${this.formatNumber(stock || 0)} ${unit || '개'}`;
            unitDisplay.textContent = unit || '개';
        } else {
            stockInfo.textContent = '품목을 선택하면 재고 정보가 표시됩니다.';
            unitDisplay.textContent = '개';
        }
    }

    validateQuantity() {
        const itemSelect = document.getElementById('item-id');
        const quantityInput = document.getElementById('quantity');
        
        if (!itemSelect || !quantityInput) return;

        const selectedOption = itemSelect.options[itemSelect.selectedIndex];
        if (!selectedOption || !selectedOption.value) return;

        const stock = parseInt(selectedOption.dataset.stock, 10);
        const quantity = parseInt(quantityInput.value, 10);

        if (!isNaN(quantity) && quantity > stock) {
            quantityInput.classList.add('is-invalid');
            Toast.warning('입력한 수량이 재고보다 많습니다.');
        } else {
            quantityInput.classList.remove('is-invalid');
        }
    }

    async handleSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        this.validateQuantity();
        if (!form.checkValidity() || form.querySelector('.is-invalid')) {
            Toast.error('입력 내용을 다시 확인해주세요.');
            return;
        }

        this.setButtonLoading('#submit-btn', '등록 중...');

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        try {
            await this.apiCall(this.config.API_URL, {
                method: 'POST',
                body: JSON.stringify(data)
            });

            Toast.success('지급이 성공적으로 등록되었습니다.');
            setTimeout(() => window.location.href = '/supply/distributions', 1000);
        } catch (error) {
            this.handleApiError(error);
            this.resetButtonLoading('#submit-btn', '<i class="ri-save-line me-1"></i> 지급 등록');
        }
    }
}

// 인스턴스 생성
new SupplyDistributionsCreatePage();
