/**
 * 지급품 품목 수정 JavaScript
 */

class SupplyItemEditPage extends BasePage {
    constructor() {
        super({
            apiBaseUrl: '/supply/items'
        });
        
        this.itemId = document.getElementById('item-id')?.value;
    }

    setupEventListeners() {
        document.getElementById('item-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitForm();
        });
    }

    loadInitialData() {
        this.loadCategories();
        this.loadItemData();
    }

    async loadCategories() {
        try {
            const data = await this.apiCall('/supply/categories');
            const categories = data.data || [];
            
            const select = document.getElementById('category-id');
            if (select) {
                categories.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat.id;
                    option.textContent = `[${cat.category_code}] ${cat.category_name}`;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error loading categories:', error);
            Toast.error('분류 목록을 불러오는 중 오류가 발생했습니다.');
        }
    }

    async loadItemData() {
        try {
            const data = await this.apiCall(`${this.config.apiBaseUrl}/${this.itemId}`);
            const item = data.data;
            
            if (item) {
                document.getElementById('item-code').value = item.item_code || '';
                document.getElementById('item-name').value = item.item_name || '';
                document.getElementById('category-id').value = item.category_id || '';
                document.getElementById('unit').value = item.unit || '';
                document.getElementById('description').value = item.description || '';
                document.getElementById('is-active').checked = item.is_active == 1;
            }
        } catch (error) {
            console.error('Error loading item:', error);
            Toast.error('품목 정보를 불러오는 중 오류가 발생했습니다.');
        }
    }

    async submitForm() {
        const form = document.getElementById('item-form');
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        const submitBtn = document.getElementById('submit-btn');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ri-loader-4-line"></i> 처리 중...';

        try {
            const formData = {
                item_code: document.getElementById('item-code').value,
                item_name: document.getElementById('item-name').value,
                category_id: document.getElementById('category-id').value,
                unit: document.getElementById('unit').value,
                description: document.getElementById('description').value,
                is_active: document.getElementById('is-active').checked ? 1 : 0
            };

            await this.apiCall(`${this.config.apiBaseUrl}/${this.itemId}`, {
                method: 'PUT',
                body: JSON.stringify(formData)
            });

            Toast.success('품목이 수정되었습니다.');
            setTimeout(() => {
                window.location.href = '/supply/items';
            }, 1000);
        } catch (error) {
            Toast.error(error.message || '품목 수정 중 오류가 발생했습니다.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }
}

    new SupplyItemEditPage();

