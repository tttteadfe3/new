/**
 * Supply Plans Create JavaScript
 */

class SupplyPlansCreatePage extends BasePage {
    constructor() {
        super({
            apiBaseUrl: '/supply/plans'
        });
        
        this.year = window.supplyPlanCreateData?.year || new Date().getFullYear();
    }

    setupEventListeners() {
        const form = document.getElementById('planForm');
        const itemSelect = document.getElementById('item-id');
        const itemUnit = document.getElementById('item-unit');
        const quantityInput = document.getElementById('planned-quantity');
        const priceInput = document.getElementById('unit-price');
        const categoryFilter = document.getElementById('category-filter');
        const itemSearch = document.getElementById('item-search');

        // 품목 선택 시 단위 자동 입력
        itemSelect?.addEventListener('change', () => {
            const selectedOption = itemSelect.options[itemSelect.selectedIndex];
            if (selectedOption.value) {
                itemUnit.value = selectedOption.dataset.unit || '';
                this.updatePreview();
            } else {
                itemUnit.value = '';
                document.getElementById('plan-preview').style.display = 'none';
            }
        });

        // 수량, 단가 변경 시 총 예산 자동 계산
        quantityInput?.addEventListener('input', () => this.calculateTotalBudget());
        priceInput?.addEventListener('input', () => this.calculateTotalBudget());

        // 분류 필터
        categoryFilter?.addEventListener('change', () => this.filterItems());

        // 품목 검색
        itemSearch?.addEventListener('input', () => this.filterItems());

        // 폼 제출
        form?.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    async loadInitialData() {
        await this.loadCategories();
        await this.loadAvailableItems();
    }

    async loadCategories() {
        try {
            const response = await this.apiCall('/supply/categories?active=true');
            const categories = response.data || [];
            const categoryFilter = document.getElementById('category-filter');
            if (categoryFilter) {
                categoryFilter.innerHTML = '<option value="">전체 분류</option>';
                categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.category_name;
                    categoryFilter.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error loading categories:', error);
            Toast.error('카테고리 목록을 불러오는 데 실패했습니다.');
        }
    }

    async loadAvailableItems() {
        try {
            const response = await this.apiCall(`/supply/items/active`);
            const items = response.data || [];
            const itemSelect = document.getElementById('item-id');
            if (itemSelect) {
                itemSelect.innerHTML = '<option value="">품목 선택</option>';
                items.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = `[${item.item_code}] ${item.item_name}`;
                    option.dataset.unit = item.unit;
                    option.dataset.code = item.item_code;
                    option.dataset.category = item.category_id;
                    itemSelect.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error loading items:', error);
            Toast.error('사용 가능한 품목 목록을 불러오는 데 실패했습니다.');
        }
    }

    calculateTotalBudget() {
        const quantityInput = document.getElementById('planned-quantity');
        const priceInput = document.getElementById('unit-price');
        const totalBudgetInput = document.getElementById('total-budget');
        
        const quantity = parseFloat(quantityInput?.value) || 0;
        const price = parseFloat(priceInput?.value) || 0;
        const total = quantity * price;
        
        if (totalBudgetInput) {
            totalBudgetInput.value = total > 0 ? '₩' + total.toLocaleString('ko-KR') : '';
        }
        
        this.updatePreview();
    }

    updatePreview() {
        const itemSelect = document.getElementById('item-id');
        const quantityInput = document.getElementById('planned-quantity');
        const priceInput = document.getElementById('unit-price');
        const previewCard = document.getElementById('plan-preview');
        
        const selectedOption = itemSelect?.options[itemSelect.selectedIndex];
        const quantity = quantityInput?.value;
        const price = priceInput?.value;
        
        if (selectedOption?.value && quantity && price) {
            const itemText = selectedOption.textContent.split('] ');
            document.getElementById('preview-item-name').textContent = itemText[1] || '';
            document.getElementById('preview-item-code').textContent = selectedOption.dataset.code || '';
            document.getElementById('preview-quantity').textContent = 
                parseInt(quantity).toLocaleString() + ' ' + (selectedOption.dataset.unit || '');
            document.getElementById('preview-unit-price').textContent = 
                '₩' + parseFloat(price).toLocaleString();
            document.getElementById('preview-total-budget').textContent = 
                '₩' + (parseInt(quantity) * parseFloat(price)).toLocaleString();
            previewCard.style.display = 'block';
        } else {
            previewCard.style.display = 'none';
        }
    }

    filterItems() {
        const categoryFilter = document.getElementById('category-filter');
        const itemSearch = document.getElementById('item-search');
        const itemSelect = document.getElementById('item-id');
        
        const categoryId = categoryFilter?.value;
        const searchTerm = itemSearch?.value.toLowerCase();
        
        Array.from(itemSelect?.options || []).forEach((option, index) => {
            if (index === 0) return; // 첫 번째 옵션(선택하세요) 제외
            
            const matchesCategory = !categoryId || option.dataset.category === categoryId;
            const matchesSearch = !searchTerm || 
                option.textContent.toLowerCase().includes(searchTerm) ||
                option.dataset.code.toLowerCase().includes(searchTerm);
            
            option.style.display = matchesCategory && matchesSearch ? '' : 'none';
        });
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

        const formData = new FormData(form);
        const data = {
            year: parseInt(formData.get('year')),
            item_id: parseInt(formData.get('item_id')),
            planned_quantity: parseInt(formData.get('planned_quantity')),
            unit_price: parseFloat(formData.get('unit_price')),
            notes: formData.get('notes')
        };

        try {
            const result = await this.apiCall(this.config.apiBaseUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            if (result.success) {
                Toast.success('계획이 성공적으로 등록되었습니다.');
                setTimeout(() => {
                    window.location.href = '/supply/plans?year=' + data.year;
                }, 1000);
            } else {
                Toast.error(result.message || '계획 등록에 실패했습니다.');
                this.resetButtonLoading('#save-plan-btn', '<i class="ri-save-line me-1"></i> 계획 저장');
            }
        } catch (error) {
            console.error('Error:', error);
            Toast.error('서버 오류가 발생했습니다.');
            this.resetButtonLoading('#save-plan-btn', '<i class="ri-save-line me-1"></i> 계획 저장');
        }
    }
}

// 인스턴스 생성
new SupplyPlansCreatePage();
