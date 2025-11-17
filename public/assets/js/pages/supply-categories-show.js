/**
 * 지급품 분류 상세 페이지
 */

class SupplyCategoryShowPage extends BasePage {
    constructor() {
        super({
            apiBaseUrl: '/api/supply/categories'
        });
        
        this.categoryId = window.supplyCategoryShowData?.category?.id || null;
        this.category = null;
    }

    setupEventListeners() {
        const toggleStatusBtn = document.getElementById('toggle-status-btn');

        // 상태 토글
        toggleStatusBtn?.addEventListener('click', () => {
            this.toggleStatus();
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

    async toggleStatus() {
        const currentStatus = this.category?.is_active;
        const newStatus = currentStatus ? '비활성' : '활성';
        
        const result = await Confirm.fire({
            title: '상태 변경 확인',
            text: `정말로 이 분류를 ${newStatus} 상태로 변경하시겠습니까?`
        });
        
        if (!result.isConfirmed) return;

        try {
            const response = await this.apiCall(`${this.config.apiBaseUrl}/${this.categoryId}/toggle-status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (response.success) {
                Toast.success('상태가 변경되었습니다.');
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                Toast.error(response.message || '상태 변경에 실패했습니다.');
            }
        } catch (error) {
            console.error('Error:', error);
            Toast.error('상태 변경 중 오류가 발생했습니다.');
        }
    }
}

// 인스턴스 생성
new SupplyCategoryShowPage();
