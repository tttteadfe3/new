/**
 * Supply Plans Edit JavaScript
 */

class SupplyPlansEditPage extends BasePage {
    constructor() {
        super({
            apiBaseUrl: '/supply/plans'
        });
        
        this.planId = window.supplyPlanEditData?.planId || null;
        this.originalData = null;
    }

    setupEventListeners() {
        const form = document.getElementById('planEditForm');
        const quantityInput = document.getElementById('planned-quantity');
        const unitPriceInput = document.getElementById('unit-price');

        // 총 예산 자동 계산
        quantityInput?.addEventListener('input', () => {
            this.calculateTotalBudget();
            this.updateChangesPreview();
        });
        unitPriceInput?.addEventListener('input', () => {
            this.calculateTotalBudget();
            this.updateChangesPreview();
        });

        // 폼 제출
        form?.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    async loadInitialData() {
        await this.loadPlanData();
    }

    async loadPlanData() {
        if (!this.planId) {
            Toast.error('계획 ID가 없습니다.');
            setTimeout(() => {
                window.location.href = '/supply/plans';
            }, 2000);
            return;
        }

        try {
            const data = await this.apiCall(`${this.config.apiBaseUrl}/${this.planId}`);
            this.originalData = data.data;
            this.renderPlanForm();
        } catch (error) {
            console.error('Error loading plan:', error);
            Toast.error('계획 정보를 불러오는 중 오류가 발생했습니다.');
            setTimeout(() => {
                window.location.href = '/supply/plans';
            }, 2000);
        }
    }

    renderPlanForm() {
        const loadingContainer = document.getElementById('loading-container');
        const itemInfoCard = document.getElementById('item-info-card');
        const form = document.getElementById('planEditForm');
        
        if (loadingContainer) loadingContainer.style.display = 'none';
        if (itemInfoCard) itemInfoCard.style.display = 'block';
        if (form) form.style.display = 'block';

        // 품목 정보 표시
        document.getElementById('plan-year').textContent = this.originalData.year;
        document.getElementById('item-code').textContent = this.originalData.item_code;
        document.getElementById('item-name').textContent = this.originalData.item_name;
        document.getElementById('item-unit').textContent = this.originalData.unit || '개';

        // 계획 정보 입력
        document.getElementById('planned-quantity').value = this.originalData.planned_quantity;
        document.getElementById('unit-price').value = this.originalData.unit_price;
        document.getElementById('notes').value = this.originalData.notes || '';
        
        // 단위 표시 업데이트
        const unitDisplay = document.getElementById('unit-display');
        if (unitDisplay) {
            unitDisplay.textContent = this.originalData.unit || '개';
        }

        // 초기 총 예산 계산
        this.calculateTotalBudget();
    }

    calculateTotalBudget() {
        const quantityInput = document.getElementById('planned-quantity');
        const unitPriceInput = document.getElementById('unit-price');
        const totalBudgetInput = document.getElementById('total-budget');
        
        const quantity = parseFloat(quantityInput?.value) || 0;
        const unitPrice = parseFloat(unitPriceInput?.value) || 0;
        const total = quantity * unitPrice;
        
        if (totalBudgetInput) {
            totalBudgetInput.value = total > 0 ? total.toLocaleString('ko-KR') : '';
        }
    }

    updateChangesPreview() {
        const newQuantity = parseFloat(document.getElementById('planned-quantity').value) || 0;
        const newPrice = parseFloat(document.getElementById('unit-price').value) || 0;
        const newBudget = newQuantity * newPrice;

        const originalQuantity = this.originalData.planned_quantity;
        const originalPrice = this.originalData.unit_price;
        const originalBudget = originalQuantity * originalPrice;

        const previewCard = document.getElementById('changes-preview');
        const quantityRow = document.getElementById('quantity-change');
        const priceRow = document.getElementById('price-change');
        const budgetRow = document.getElementById('budget-change');

        let hasChanges = false;

        // 수량 변경
        if (newQuantity !== originalQuantity) {
            hasChanges = true;
            quantityRow.style.display = '';
            document.getElementById('original-quantity').textContent = originalQuantity.toLocaleString();
            document.getElementById('new-quantity').textContent = newQuantity.toLocaleString();
            const diff = newQuantity - originalQuantity;
            const diffClass = diff > 0 ? 'text-success' : 'text-danger';
            document.getElementById('quantity-diff').innerHTML = 
                `<span class="${diffClass}">${diff > 0 ? '+' : ''}${diff.toLocaleString()}</span>`;
        } else {
            quantityRow.style.display = 'none';
        }

        // 단가 변경
        if (newPrice !== originalPrice) {
            hasChanges = true;
            priceRow.style.display = '';
            document.getElementById('original-price').textContent = '₩' + originalPrice.toLocaleString();
            document.getElementById('new-price').textContent = '₩' + newPrice.toLocaleString();
            const diff = newPrice - originalPrice;
            const diffClass = diff > 0 ? 'text-danger' : 'text-success';
            document.getElementById('price-diff').innerHTML = 
                `<span class="${diffClass}">${diff > 0 ? '+' : ''}₩${diff.toLocaleString()}</span>`;
        } else {
            priceRow.style.display = 'none';
        }

        // 예산 변경
        if (newBudget !== originalBudget) {
            hasChanges = true;
            budgetRow.style.display = '';
            document.getElementById('original-budget').textContent = '₩' + originalBudget.toLocaleString();
            document.getElementById('new-budget').textContent = '₩' + newBudget.toLocaleString();
            const diff = newBudget - originalBudget;
            const diffClass = diff > 0 ? 'text-danger' : 'text-success';
            document.getElementById('budget-diff').innerHTML = 
                `<span class="${diffClass}">${diff > 0 ? '+' : ''}₩${diff.toLocaleString()}</span>`;
        } else {
            budgetRow.style.display = 'none';
        }

        previewCard.style.display = hasChanges ? 'block' : 'none';
    }

    async handleSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        if (!form.checkValidity()) {
            e.stopPropagation();
            form.classList.add('was-validated');
            return;
        }

        this.setButtonLoading('#save-plan-btn', '저장 중...');

        const formData = {
            planned_quantity: parseInt(document.getElementById('planned-quantity').value),
            unit_price: parseFloat(document.getElementById('unit-price').value),
            notes: document.getElementById('notes').value
        };

        try {
            const result = await this.apiCall(`${this.config.apiBaseUrl}/${this.planId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            if (result.success) {
                Toast.success('계획이 성공적으로 수정되었습니다.');
                setTimeout(() => {
                    window.location.href = '/supply/plans?year=' + this.originalData.year;
                }, 1000);
            } else {
                Toast.error(result.message || '계획 수정에 실패했습니다.');
                this.resetButtonLoading('#save-plan-btn', '<i class="ri-save-line me-1"></i> 변경사항 저장');
            }
        } catch (error) {
            console.error('Error:', error);
            Toast.error('서버 오류가 발생했습니다.');
            this.resetButtonLoading('#save-plan-btn', '<i class="ri-save-line me-1"></i> 변경사항 저장');
        }
    }
}

// 인스턴스 생성
new SupplyPlansEditPage();
