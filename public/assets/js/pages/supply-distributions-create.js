/**
 * 지급 등록 페이지
 */

class SupplyDistributionsCreatePage extends BasePage {
    constructor() {
        super({
            apiBaseUrl: '/supply/distributions'
        });
        
        this.availableItems = [];
        this.departments = [];
        this.employees = [];
    }

    setupEventListeners() {
        const form = document.getElementById('distribution-form');
        const itemSelect = document.getElementById('item-id');
        const quantityInput = document.getElementById('quantity');
        const departmentSelect = document.getElementById('department-id');
        const employeeSelect = document.getElementById('employee-id');

        // 품목 선택 시 재고 정보 표시
        itemSelect?.addEventListener('change', (e) => {
            this.updateStockInfo(e.target);
        });

        // 수량 입력 시 재고 검증
        quantityInput?.addEventListener('input', () => {
            this.validateQuantity();
        });

        // 부서 선택 시 직원 목록 로드
        departmentSelect?.addEventListener('change', (e) => {
            this.loadEmployees(e.target.value);
        });

        // 폼 제출
        form?.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    async loadInitialData() {
        await Promise.all([
            this.loadAvailableItems(),
            this.loadDepartments()
        ]);
    }

    async loadAvailableItems() {
        try {
            const data = await this.apiCall('/supply/items?has_stock=1');
            this.availableItems = data.data || [];
            this.renderItemOptions();
        } catch (error) {
            console.error('Error loading items:', error);
            Toast.error('품목 목록을 불러오는 중 오류가 발생했습니다.');
        }
    }

    async loadDepartments() {
        try {
            const data = await this.apiCall('/organization/departments');
            this.departments = data.data || [];
            this.renderDepartmentOptions();
        } catch (error) {
            console.error('Error loading departments:', error);
            Toast.error('부서 목록을 불러오는 중 오류가 발생했습니다.');
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

        try {
            const data = await this.apiCall(`/organization/departments/${departmentId}/employees`);
            this.employees = data.data || [];
            
            employeeSelect.innerHTML = '<option value="">직원을 선택하세요</option>';
            this.employees.forEach(emp => {
                const option = document.createElement('option');
                option.value = emp.id;
                option.textContent = `${emp.name} (${emp.employee_number})`;
                employeeSelect.appendChild(option);
            });
            
            employeeSelect.disabled = false;
        } catch (error) {
            console.error('Error loading employees:', error);
            Toast.error('직원 목록을 불러오는 중 오류가 발생했습니다.');
        }
    }

    renderItemOptions() {
        const itemSelect = document.getElementById('item-id');
        if (!itemSelect) return;

        itemSelect.innerHTML = '<option value="">품목을 선택하세요</option>';
        
        this.availableItems.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.dataset.stock = item.current_stock;
            option.dataset.unit = item.unit;
            option.textContent = `${item.item_name} (재고: ${this.formatNumber(item.current_stock)} ${item.unit})`;
            itemSelect.appendChild(option);
        });
    }

    renderDepartmentOptions() {
        const deptSelect = document.getElementById('department-id');
        if (!deptSelect) return;

        deptSelect.innerHTML = '<option value="">부서를 선택하세요</option>';
        
        this.departments.forEach(dept => {
            const option = document.createElement('option');
            option.value = dept.id;
            option.textContent = dept.name;
            deptSelect.appendChild(option);
        });
    }

    updateStockInfo(selectElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const stockInfo = document.getElementById('stock-info');
        const unitDisplay = document.getElementById('unit-display');
        
        if (selectedOption.value) {
            const stock = selectedOption.dataset.stock;
            const unit = selectedOption.dataset.unit;
            
            if (stockInfo) {
                stockInfo.textContent = `현재 재고: ${this.formatNumber(stock)} ${unit}`;
            }
            if (unitDisplay) {
                unitDisplay.textContent = unit;
            }
        } else {
            if (stockInfo) {
                stockInfo.textContent = '품목을 선택하면 재고 정보가 표시됩니다.';
            }
            if (unitDisplay) {
                unitDisplay.textContent = '개';
            }
        }
    }

    validateQuantity() {
        const itemSelect = document.getElementById('item-id');
        const quantityInput = document.getElementById('quantity');
        
        if (!itemSelect || !quantityInput) return;

        const selectedOption = itemSelect.options[itemSelect.selectedIndex];
        if (!selectedOption.value) return;

        const stock = parseInt(selectedOption.dataset.stock);
        const quantity = parseInt(quantityInput.value);

        if (quantity > stock) {
            quantityInput.setCustomValidity('재고가 부족합니다.');
            Toast.warning('입력한 수량이 재고보다 많습니다.');
        } else {
            quantityInput.setCustomValidity('');
        }
    }

    async handleSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        if (!form.checkValidity()) {
            e.stopPropagation();
            form.classList.add('was-validated');
            return;
        }

        this.setButtonLoading('#submit-btn', '등록 중...');

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        // 데이터 타입 변환
        data.item_id = parseInt(data.item_id);
        data.quantity = parseInt(data.quantity);
        data.department_id = parseInt(data.department_id);
        data.employee_id = parseInt(data.employee_id);

        try {
            const result = await this.apiCall(this.config.apiBaseUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            if (result.success) {
                Toast.success('지급이 성공적으로 등록되었습니다.');
                setTimeout(() => {
                    window.location.href = '/supply/distributions';
                }, 1000);
            } else {
                Toast.error(result.message || '지급 등록에 실패했습니다.');
                this.resetButtonLoading('#submit-btn', '<i class="ri-save-line me-1"></i> 지급 등록');
            }
        } catch (error) {
            console.error('Error:', error);
            Toast.error('서버 오류가 발생했습니다.');
            this.resetButtonLoading('#submit-btn', '<i class="ri-save-line me-1"></i> 지급 등록');
        }
    }

    formatNumber(num) {
        return new Intl.NumberFormat('ko-KR').format(num);
    }
}

// 인스턴스 생성
new SupplyDistributionsCreatePage();
