/**
 * 지급품 분류 수정 페이지
 */

class SupplyCategoryEditPage extends BasePage {
    constructor() {
        super({
            apiBaseUrl: '/api/supply/categories'
        });
        
        this.categoryId = window.supplyCategoryEditData?.category?.id || null;
        this.category = null;
    }

    setupEventListeners() {
        const form = document.getElementById('editCategoryForm');
        const deleteBtn = document.getElementById('delete-btn');
        const confirmDeleteBtn = document.getElementById('confirm-delete-btn');

        // 폼 제출
        form?.addEventListener('submit', (e) => this.handleSubmit(e));

        // 삭제 버튼
        deleteBtn?.addEventListener('click', () => {
            this.showDeleteModal();
        });

        // 삭제 확인
        confirmDeleteBtn?.addEventListener('click', () => {
            this.handleDelete();
        });
    }

    async loadInitialData() {
        if (this.categoryId) {
            await this.loadCategoryData();
        }
    }

    async loadCategoryData() {
        try {
            const data = await this.apiCall(`${this.config.apiBaseUrl}/${this.categoryId}`);
            this.category = data.data;
        } catch (error) {
            console.error('Error loading category:', error);
            Toast.error('분류 정보를 불러오는 중 오류가 발생했습니다.');
        }
    }

    showDeleteModal() {
        const modal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
        modal.show();
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
            const result = await this.apiCall(`${this.config.apiBaseUrl}/${this.categoryId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            if (result.success) {
                Toast.success('분류가 수정되었습니다.');
                setTimeout(() => {
                    window.location.href = '/supply/categories';
                }, 1000);
            } else {
                Toast.error(result.message || '분류 수정에 실패했습니다.');
                this.resetButtonLoading('#save-btn', '<i class="ri-save-line me-1"></i> 저장');
            }
        } catch (error) {
            console.error('Error:', error);
            Toast.error('서버 오류가 발생했습니다.');
            this.resetButtonLoading('#save-btn', '<i class="ri-save-line me-1"></i> 저장');
        }
    }

    async handleDelete() {
        try {
            const result = await this.apiCall(`${this.config.apiBaseUrl}/${this.categoryId}`, {
                method: 'DELETE'
            });

            if (result.success) {
                Toast.success('분류가 삭제되었습니다.');
                setTimeout(() => {
                    window.location.href = '/supply/categories';
                }, 1000);
            } else {
                Toast.error(result.message || '분류 삭제에 실패했습니다.');
            }
        } catch (error) {
            console.error('Error:', error);
            Toast.error('서버 오류가 발생했습니다.');
        }
    }
}

// 인스턴스 생성
new SupplyCategoryEditPage();
