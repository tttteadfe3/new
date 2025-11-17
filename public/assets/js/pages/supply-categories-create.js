/**
 * 지급품 분류 생성 페이지
 */

class SupplyCategoryCreatePage extends BasePage {
    constructor() {
        super({
            apiBaseUrl: '/api/supply/categories'
        });
        
        this.mainCategories = [];
    }

    setupEventListeners() {
        const form = document.getElementById('createCategoryForm');
        const levelSelect = document.getElementById('category-level');
        const generateCodeBtn = document.getElementById('generate-code-btn');

        // 레벨 변경 시 상위 분류 표시/숨김
        levelSelect?.addEventListener('change', (e) => {
            this.toggleParentCategoryField(e.target.value);
        });

        // 코드 자동 생성
        generateCodeBtn?.addEventListener('click', () => {
            this.generateCategoryCode();
        });

        // 폼 제출
        form?.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    async loadInitialData() {
        await this.loadMainCategories();
    }

    async loadMainCategories() {
        try {
            const data = await this.apiCall(`${this.config.apiBaseUrl}/level/1`);
            this.mainCategories = data.data || [];
            this.renderParentOptions();
        } catch (error) {
            console.error('Error loading main categories:', error);
            Toast.error('대분류 목록을 불러오는 중 오류가 발생했습니다.');
        }
    }

    renderParentOptions() {
        const select = document.getElementById('parent-category');
        if (!select) return;

        select.innerHTML = '<option value="">선택하세요</option>';
        
        this.mainCategories.forEach(category => {
            if (category.is_active) {
                const option = document.createElement('option');
                option.value = category.id;
                option.textContent = `${category.category_name} (${category.category_code})`;
                select.appendChild(option);
            }
        });
    }

    toggleParentCategoryField(level) {
        const container = document.getElementById('parent-category-container');
        const parentSelect = document.getElementById('parent-category');
        
        if (level === '2') {
            container.style.display = 'block';
            parentSelect.required = true;
        } else {
            container.style.display = 'none';
            parentSelect.required = false;
            parentSelect.value = '';
        }
    }

    async generateCategoryCode() {
        const level = document.getElementById('category-level')?.value;
        const parentId = document.getElementById('parent-category')?.value;
        
        if (!level) {
            Toast.error('먼저 분류 레벨을 선택해주세요.');
            return;
        }
        
        if (level === '2' && !parentId) {
            Toast.error('소분류는 상위 분류를 먼저 선택해주세요.');
            return;
        }
        
        try {
            const url = `${this.config.apiBaseUrl}/generate-code?level=${level}${parentId ? `&parent_id=${parentId}` : ''}`;
            const data = await this.apiCall(url);
            document.getElementById('category-code').value = data.data.code;
        } catch (error) {
            console.error('Error generating category code:', error);
            Toast.error('분류 코드 생성 중 오류가 발생했습니다.');
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

        this.setButtonLoading('#save-btn', '저장 중...');

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        try {
            const result = await this.apiCall(this.config.apiBaseUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            if (result.success) {
                Toast.success('분류가 생성되었습니다.');
                setTimeout(() => {
                    window.location.href = '/supply/categories';
                }, 1000);
            } else {
                Toast.error(result.message || '분류 생성에 실패했습니다.');
                this.resetButtonLoading('#save-btn', '<i class="ri-save-line me-1"></i> 저장');
            }
        } catch (error) {
            console.error('Error:', error);
            Toast.error('서버 오류가 발생했습니다.');
            this.resetButtonLoading('#save-btn', '<i class="ri-save-line me-1"></i> 저장');
        }
    }
}

// 인스턴스 생성
new SupplyCategoryCreatePage();
