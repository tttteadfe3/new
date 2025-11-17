/**
 * 지급품 분류 수정 페이지
 */

class SupplyCategoryEditPage extends BasePage {
    constructor() {
        super({
            apiBaseUrl: '/api/supply/categories'
        });
        
        // From the global scope, injected by the controller
        this.categoryId = window.viewData?.categoryId || null;
        this.categoryData = null;
    }

    setupEventListeners() {
        // Event listeners will be attached after the form is rendered
    }

    async loadInitialData() {
        if (!this.categoryId) {
            Toast.error('분류 ID가 없습니다.');
            setTimeout(() => window.location.href = '/supply/categories', 1500);
            return;
        }

        try {
            const response = await this.apiCall(`${this.config.apiBaseUrl}/${this.categoryId}`);
            this.categoryData = response.data;
            this.renderForm();
            this.renderInfo();
            this.attachFormEventListeners();
        } catch (error) {
            console.error('Error loading category data:', error);
            Toast.error('분류 정보를 불러오는 데 실패했습니다.');
        }
    }

    renderForm() {
        const container = document.getElementById('form-container');
        if (!container) return;

        const parentCategoryField = this.categoryData.level == 2 ? `
            <div class="col-md-6">
                <label for="parent-category" class="form-label">상위 분류</label>
                <input type="text" class="form-control" value="${this.sanitizeHTML(this.categoryData.parent_category?.category_name || '없음')}" readonly>
                <div class="form-text">상위 분류는 수정할 수 없습니다.</div>
            </div>
        ` : '';

        container.innerHTML = `
            <form id="editCategoryForm">
                <input type="hidden" id="category-id" name="id" value="${this.categoryData.id}">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="category-level" class="form-label">분류 레벨</label>
                        <input type="text" class="form-control" value="${this.categoryData.level == 1 ? '대분류' : '소분류'}" readonly>
                        <div class="form-text">분류 레벨은 수정할 수 없습니다.</div>
                    </div>
                    ${parentCategoryField}
                    <div class="col-12">
                        <label for="category-code" class="form-label">분류 코드</label>
                        <input type="text" class="form-control" id="category-code" value="${this.sanitizeHTML(this.categoryData.category_code)}" readonly>
                        <div class="form-text">분류 코드는 수정할 수 없습니다.</div>
                    </div>
                    <div class="col-12">
                        <label for="category-name" class="form-label">분류명 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="category-name" name="category_name" required maxlength="100" value="${this.sanitizeHTML(this.categoryData.category_name)}">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6">
                        <label for="display-order" class="form-label">표시 순서</label>
                        <input type="number" class="form-control" id="display-order" name="display_order" value="${this.categoryData.display_order}" min="0">
                        <div class="form-text">숫자가 작을수록 먼저 표시됩니다.</div>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6">
                        <label for="is-active" class="form-label">상태</label>
                        <select class="form-select" id="is-active" name="is_active">
                            <option value="1" ${this.categoryData.is_active ? 'selected' : ''}>활성</option>
                            <option value="0" ${!this.categoryData.is_active ? 'selected' : ''}>비활성</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary me-2" id="save-btn">
                        <i class="ri-save-line me-1"></i> 저장
                    </button>
                    <a href="/supply/categories" class="btn btn-secondary">
                        <i class="ri-arrow-left-line me-1"></i> 목록으로
                    </a>
                </div>
            </form>
        `;
    }

    renderInfo() {
        const container = document.getElementById('info-container');
        if (!container) return;

        container.innerHTML = `
            <table class="table table-borderless table-sm">
                <tbody>
                    <tr>
                        <td class="fw-medium">분류 ID:</td>
                        <td>${this.categoryData.id}</td>
                    </tr>
                    <tr>
                        <td class="fw-medium">분류 코드:</td>
                        <td><code>${this.sanitizeHTML(this.categoryData.category_code)}</code></td>
                    </tr>
                    <tr>
                        <td class="fw-medium">분류 레벨:</td>
                        <td>
                            <span class="badge bg-${this.categoryData.level == 1 ? 'primary' : 'info'}">
                                ${this.categoryData.level == 1 ? '대분류' : '소분류'}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-medium">상태:</td>
                        <td>
                            <span class="badge bg-${this.categoryData.is_active ? 'success' : 'secondary'}">
                                ${this.categoryData.is_active ? '활성' : '비활성'}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-medium">생성일:</td>
                        <td>${new Date(this.categoryData.created_at).toLocaleString()}</td>
                    </tr>
                    <tr>
                        <td class="fw-medium">수정일:</td>
                        <td>${new Date(this.categoryData.updated_at).toLocaleString()}</td>
                    </tr>
                </tbody>
            </table>
        `;
    }

    attachFormEventListeners() {
        const form = document.getElementById('editCategoryForm');
        form?.addEventListener('submit', (e) => this.handleSubmit(e));
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
        const data = {
            category_name: formData.get('category_name'),
            display_order: parseInt(formData.get('display_order')),
            is_active: parseInt(formData.get('is_active')),
        };

        try {
            await this.apiCall(`${this.config.apiBaseUrl}/${this.categoryId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            Toast.success('분류가 수정되었습니다.');
            setTimeout(() => window.location.href = '/supply/categories', 1000);

        } catch (error) {
            console.error('Error updating category:', error);
            Toast.error(error.message || '분류 수정 중 오류가 발생했습니다.');
            this.resetButtonLoading('#save-btn', '<i class="ri-save-line me-1"></i> 저장');
        }
    }
}

// Assume categoryId is available in the global scope, e.g., from a script tag in the view
// window.viewData = { categoryId: <?= $categoryId ?> };
new SupplyCategoryEditPage();
