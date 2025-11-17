/**
 * Supply Distributions Edit JavaScript
 */

class SupplyDistributionsEditPage extends BasePage {
    constructor() {
        super({
            apiBaseUrl: '/api/supply/distributions'
        });
        
        this.distributionId = document.getElementById('distribution-id')?.value || null;
        this.distribution = null;
        this.originalQuantity = 0;
    }

    setupEventListeners() {
        const form = document.getElementById('distribution-edit-form');
        const quantityInput = document.getElementById('quantity');

        // 수량 변경 시 재고 정보 업데이트
        quantityInput?.addEventListener('input', () => this.updateStockInfo());

        // 폼 제출
        form?.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    async loadInitialData() {
        await this.loadDistributionData();
    }

    async loadDistributionData() {
        if (!this.distributionId) {
            Toast.error('지급 ID가 없습니다.');
            setTimeout(() => {
                window.location.href = '/supply/distributions';
            }, 2000);
            return;
        }

        try {
            const data = await this.apiCall(`${this.config.apiBaseUrl}/${this.distributionId}`);
            this.distribution = data.data;
            this.originalQuantity = this.distribution.quantity;
            this.renderDistributionForm();
        } catch (error) {
            console.error('Error loading distribution:', error);
            Toast.error('지급 정보를 불러오는 중 오류가 발생했습니다.');
            setTimeout(() => {
                window.location.href = '/supply/distributions';
            }, 2000);
        }
    }

    renderDistributionForm() {
        const loadingContainer = document.getElementById('loading-container');
        const form = document.getElementById('distribution-edit-form');
        
        if (loadingContainer) loadingContainer.style.display = 'none';
        if (form) form.style.display = 'block';

        // 품목 정보 표시
        document.getElementById('item-name').value = 
            `${this.distribution.item_name} (${this.distribution.item_code})`;
        
        // 단위 표시
        const unitDisplay = document.getElementById('unit-display');
        if (unitDisplay) {
            unitDisplay.textContent = this.distribution.unit || '개';
        }

        // 부서 및 직원 정보
        document.getElementById('department-name').value = this.distribution.department_name;
        document.getElementById('employee-name').value = this.distribution.employee_name;

        // 지급 정보 입력
        document.getElementById('quantity').value = this.distribution.quantity;
        document.getElementById('distribution-date').value = this.distribution.distribution_date;
        document.getElementById('notes').value = this.distribution.notes || '';

        // 재고 정보 업데이트
        this.updateStockInfo();
    }

    updateStockInfo() {
        const quantityInput = document.getElementById('quantity');
        const stockInfo = document.getElementById('stock-info');
        
        if (!this.distribution || !quantityInput || !stockInfo) return;

        const newQuantity = parseInt(quantityInput.value) || 0;
        const quantityDiff = newQuantity - this.originalQuantity;
        const currentStock = this.distribution.current_stock;
        const availableStock = currentStock + this.originalQuantity; // 원래 지급량을 재고에 더함
        
        if (newQuantity > availableStock) {
            stockInfo.innerHTML = `
                <span class="text-danger">
                    <i class="ri-error-warning-line me-1"></i>
                    재고 부족 (사용 가능: ${availableStock.toLocaleString()}${this.distribution.unit || '개'})
                </span>
            `;
            quantityInput.classList.add('is-invalid');
        } else {
            const afterStock = availableStock - newQuantity;
            let stockClass = 'text-success';
            if (afterStock < 10) stockClass = 'text-danger';
            else if (afterStock < 50) stockClass = 'text-warning';
            
            stockInfo.innerHTML = `
                <span class="${stockClass}">
                    <i class="ri-information-line me-1"></i>
                    현재 재고: ${currentStock.toLocaleString()}${this.distribution.unit || '개'} 
                    → 수정 후: ${afterStock.toLocaleString()}${this.distribution.unit || '개'}
                    ${quantityDiff !== 0 ? `(${quantityDiff > 0 ? '+' : ''}${quantityDiff})` : ''}
                </span>
            `;
            quantityInput.classList.remove('is-invalid');
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

        const quantityInput = document.getElementById('quantity');
        const newQuantity = parseInt(quantityInput.value);
        const availableStock = this.distribution.current_stock + this.originalQuantity;

        if (newQuantity > availableStock) {
            Toast.error('재고가 부족합니다.');
            return;
        }

        this.setButtonLoading('#submit-btn', '저장 중...');

        const formData = {
            quantity: newQuantity,
            distribution_date: document.getElementById('distribution-date').value,
            notes: document.getElementById('notes').value
        };

        try {
            const result = await this.apiCall(`${this.config.apiBaseUrl}/${this.distributionId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            if (result.success) {
                Toast.success('지급 정보가 성공적으로 수정되었습니다.');
                setTimeout(() => {
                    window.location.href = '/supply/distributions';
                }, 1000);
            } else {
                Toast.error(result.message || '지급 수정에 실패했습니다.');
                this.resetButtonLoading('#submit-btn', '<i class="ri-save-line me-1"></i> 수정 저장');
            }
        } catch (error) {
            console.error('Error:', error);
            Toast.error('서버 오류가 발생했습니다.');
            this.resetButtonLoading('#submit-btn', '<i class="ri-save-line me-1"></i> 수정 저장');
        }
    }
}

// 인스턴스 생성
new SupplyDistributionsEditPage();
