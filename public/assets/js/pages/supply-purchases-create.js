/**
 * 구매 등록 페이지
 */

class SupplyPurchasesCreatePage extends BasePage {
    constructor() {
        super({
            apiBaseUrl: '/supply/purchases'
        });
        
        this.items = [];
    }

    setupEventListeners() {
        const form = document.getElementById('purchase-form');
        const quantityInput = document.getElementById('quantity');
        const unitPriceInput = document.getElementById('unit-price');
        const isReceivedCheckbox = document.getElementById('is-received');
        const receivedDateGroup = document.getElementById('received-date-group');
        const receivedDateInput = document.getElementById('received-date');
        const purchaseDateInput = document.getElementById('purchase-date');

        // 총액 자동 계산
        quantityInput?.addEventListener('input', () => this.calculateTotal());
        unitPriceInput?.addEventListener('input', () => this.calculateTotal());

        // 즉시 입고 처리 체크박스
        isReceivedCheckbox?.addEventListener('change', function() {
            if (this.checked) {
                receivedDateGroup.style.display = 'block';
                receivedDateInput.required = true;
            } else {
                receivedDateGroup.style.display = 'none';
                receivedDateInput.required = false;
            }
        });

        // 입고일 검증
        receivedDateInput?.addEventListener('change', function() {
            const purchaseDate = new Date(purchaseDateInput.value);
            const receivedDate = new Date(this.value);
            
            if (receivedDate < purchaseDate) {
                Toast.error('입고일은 구매일보다 이전일 수 없습니다.');
                this.value = purchaseDateInput.value;
            }
        });

        // 폼 제출
        form?.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    async loadInitialData() {
        await this.loadItems();
    }

    async loadItems() {
        try {
            const data = await this.apiCall('/supply/items?active=1');
            this.items = data.data || [];
            this.renderItemOptions();
        } catch (error) {
            console.error('Error loading items:', error);
            Toast.error('품목 목록을 불러오는 중 오류가 발생했습니다.');
        }
    }

    renderItemOptions() {
        const itemSelect = document.getElementById('item-id');
        if (!itemSelect) return;

        itemSelect.innerHTML = '<option value="">품목을 선택하세요</option>';
        
        this.items.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.dataset.code = item.item_code;
            option.dataset.unit = item.unit;
            option.textContent = `${item.item_name} (${item.item_code})`;
            itemSelect.appendChild(option);
        });
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

        this.setButtonLoading('#submit-btn', '등록 중...');

        const formData = {
            item_id: parseInt(document.getElementById('item-id').value),
            purchase_date: document.getElementById('purchase-date').value,
            quantity: parseInt(document.getElementById('quantity').value),
            unit_price: parseFloat(document.getElementById('unit-price').value),
            supplier: document.getElementById('supplier').value,
            is_received: document.getElementById('is-received').checked,
            received_date: document.getElementById('is-received').checked ? 
                document.getElementById('received-date').value : null,
            notes: document.getElementById('notes').value
        };

        try {
            const result = await this.apiCall(this.config.apiBaseUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            if (result.success) {
                Toast.success('구매가 성공적으로 등록되었습니다.');
                setTimeout(() => {
                    window.location.href = '/supply/purchases';
                }, 1000);
            } else {
                Toast.error(result.message || '구매 등록에 실패했습니다.');
                this.resetButtonLoading('#submit-btn', '<i class="ri-save-line me-1"></i> 등록');
            }
        } catch (error) {
            console.error('Error:', error);
            Toast.error('서버 오류가 발생했습니다.');
            this.resetButtonLoading('#submit-btn', '<i class="ri-save-line me-1"></i> 등록');
        }
    }
}

// 인스턴스 생성
new SupplyPurchasesCreatePage();
