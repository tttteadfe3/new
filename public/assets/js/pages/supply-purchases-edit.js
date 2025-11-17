/**
 * 구매 수정 페이지
 */

class SupplyPurchasesEditPage extends BasePage {
    constructor() {
        super({
            apiBaseUrl: '/supply/purchases'
        });
        
        this.purchaseId = window.purchaseEditData?.purchaseId || null;
        this.purchase = null;
        this.item = null;
    }

    setupEventListeners() {
        const form = document.getElementById('purchase-form');
        const quantityInput = document.getElementById('quantity');
        const unitPriceInput = document.getElementById('unit-price');

        // 총액 자동 계산
        quantityInput?.addEventListener('input', () => this.calculateTotal());
        unitPriceInput?.addEventListener('input', () => this.calculateTotal());

        // 폼 제출
        form?.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    async loadInitialData() {
        await this.loadPurchaseData();
    }

    async loadPurchaseData() {
        if (!this.purchaseId) {
            Toast.error('구매 ID가 없습니다.');
            setTimeout(() => {
                window.location.href = '/supply/purchases';
            }, 2000);
            return;
        }

        try {
            const data = await this.apiCall(`${this.config.apiBaseUrl}/${this.purchaseId}`);
            this.purchase = data.data;
            this.item = this.purchase.item;
            this.renderPurchaseForm();
        } catch (error) {
            console.error('Error loading purchase:', error);
            Toast.error('구매 정보를 불러오는 중 오류가 발생했습니다.');
            setTimeout(() => {
                window.location.href = '/supply/purchases';
            }, 2000);
        }
    }

    renderPurchaseForm() {
        const loadingContainer = document.getElementById('loading-container');
        const form = document.getElementById('purchase-form');
        
        if (loadingContainer) loadingContainer.style.display = 'none';
        if (form) form.style.display = 'block';

        // 품목 정보 표시
        const itemNameInput = document.getElementById('item-name');
        if (itemNameInput && this.item) {
            itemNameInput.value = `${this.item.item_name} (${this.item.item_code})`;
        }

        // 구매 정보 입력
        document.getElementById('purchase-date').value = this.purchase.purchase_date;
        document.getElementById('supplier').value = this.purchase.supplier || '';
        document.getElementById('quantity').value = this.purchase.quantity;
        document.getElementById('unit-price').value = this.purchase.unit_price;
        document.getElementById('notes').value = this.purchase.notes || '';

        // 초기 총액 계산
        this.calculateTotal();
    }

    calculateTotal() {
        const quantityInput = document.getElementById('quantity');
        const unitPriceInput = document.getElementById('unit-price');
        const totalAmountInput = document.getElementById('total-amount');
        
        const quantity = parseFloat(quantityInput?.value) || 0;
        const unitPrice = parseFloat(unitPriceInput?.value) || 0;
        const total = quantity * unitPrice;
        
        if (totalAmountInput) {
            totalAmountInput.value = total > 0 ? '₩' + total.toLocaleString('ko-KR') : '';
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

        this.setButtonLoading('#submit-btn', '수정 중...');

        const formData = {
            purchase_date: document.getElementById('purchase-date').value,
            quantity: parseInt(document.getElementById('quantity').value),
            unit_price: parseFloat(document.getElementById('unit-price').value),
            supplier: document.getElementById('supplier').value,
            notes: document.getElementById('notes').value
        };

        try {
            const result = await this.apiCall(`${this.config.apiBaseUrl}/${this.purchaseId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            if (result.success) {
                Toast.success('구매가 성공적으로 수정되었습니다.');
                setTimeout(() => {
                    window.location.href = '/supply/purchases';
                }, 1000);
            } else {
                Toast.error(result.message || '구매 수정에 실패했습니다.');
                this.resetButtonLoading('#submit-btn', '<i class="ri-save-line me-1"></i> 수정');
            }
        } catch (error) {
            console.error('Error:', error);
            Toast.error('서버 오류가 발생했습니다.');
            this.resetButtonLoading('#submit-btn', '<i class="ri-save-line me-1"></i> 수정');
        }
    }
}

// 인스턴스 생성
new SupplyPurchasesEditPage();
