/**
 * 지급품 분류 관리 JavaScript
 */

class SupplyCategoryPage extends BasePage {
    constructor() {
        super({
            API_URL: '/api/supply/categories'
        });
        
        this.categories = [];
        this.filteredCategories = [];
        this.selectedCategoryId = null;
        this.isEditMode = false;
    }

    setupEventListeners() {
        // 신규 등록 버튼
        document.getElementById('add-category-btn')?.addEventListener('click', () => {
            this.showCategoryModal();
        });

        // 분류 폼 제출
        document.getElementById('categoryForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveCategoryForm();
        });

        // 코드 자동 생성 버튼
        document.getElementById('generate-code-btn')?.addEventListener('click', () => {
            this.generateCategoryCode();
        });

        // 레벨 변경 시 상위 분류 표시/숨김
        document.getElementById('category-level')?.addEventListener('change', (e) => {
            this.toggleParentCategoryField(e.target.value);
        });

        // 필터 이벤트
        document.getElementById('filter-level')?.addEventListener('change', () => {
            this.applyFilters();
        });

        document.getElementById('filter-status')?.addEventListener('change', () => {
            this.applyFilters();
        });

        document.getElementById('search-categories')?.addEventListener('input', (e) => {
            this.searchCategories(e.target.value);
        });

        // 삭제 확인 버튼
        document.getElementById('confirm-delete-btn')?.addEventListener('click', () => {
            this.deleteCategory();
        });
    }

    loadInitialData() {
        this.loadCategories();
        this.setupFilters();
    }

    async loadCategories() {
        try {
            const data = await this.apiCall(`${this.config.API_URL}?hierarchical=true`);
            this.categories = data.data || [];
            this.filteredCategories = [...this.categories];
            this.renderCategoryList();
        } catch (error) {
            console.error('Error loading categories:', error);
            Toast.error('분류 목록을 불러오는 중 오류가 발생했습니다.');
        }
    }

    renderCategoryList() {
        const container = document.getElementById('category-list-container');
        const noResultDiv = document.getElementById('no-category-result');
        
        if (!container) return;

        if (this.filteredCategories.length === 0) {
            container.innerHTML = '';
            noResultDiv.style.display = 'block';
            return;
        }

        noResultDiv.style.display = 'none';
        
        const html = this.filteredCategories.map(category => {
            const isActive = category.is_active;
            const levelBadge = category.level === 1 ? 
                '<span class="badge bg-primary">대분류</span>' : 
                '<span class="badge bg-info">소분류</span>';
            const statusBadge = isActive ? 
                '<span class="badge bg-success">활성</span>' : 
                '<span class="badge bg-secondary">비활성</span>';
            
            return `
                <div class="list-group-item list-group-item-action category-item" 
                     data-category-id="${category.id}" 
                     style="cursor: pointer;">
                    <div class="d-flex w-100 justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${this.escapeHtml(category.category_name)}</h6>
                            <p class="mb-1 small text-muted">
                                <code>${this.escapeHtml(category.category_code)}</code>
                            </p>
                            <div class="d-flex gap-1">
                                ${levelBadge}
                                ${statusBadge}
                            </div>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                    type="button" data-bs-toggle="dropdown">
                                <i class="ri-more-fill"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item edit-category" href="#" data-category-id="${category.id}">
                                    <i class="ri-edit-line me-2"></i>수정
                                </a></li>
                                <li><a class="dropdown-item toggle-status" href="#" data-category-id="${category.id}">
                                    <i class="ri-toggle-line me-2"></i>${isActive ? '비활성화' : '활성화'}
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger delete-category" href="#" data-category-id="${category.id}">
                                    <i class="ri-delete-bin-line me-2"></i>삭제
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        container.innerHTML = html;
        this.bindCategoryItemEvents();
    }

    bindCategoryItemEvents() {
        // 분류 항목 클릭 이벤트
        document.querySelectorAll('.category-item').forEach(item => {
            item.addEventListener('click', (e) => {
                if (e.target.closest('.dropdown')) return;
                
                const categoryId = parseInt(item.dataset.categoryId);
                this.selectCategory(categoryId);
            });
        });

        // 수정 버튼 이벤트
        document.querySelectorAll('.edit-category').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const categoryId = parseInt(btn.dataset.categoryId);
                this.editCategory(categoryId);
            });
        });

        // 상태 토글 버튼 이벤트
        document.querySelectorAll('.toggle-status').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const categoryId = parseInt(btn.dataset.categoryId);
                this.toggleCategoryStatus(categoryId);
            });
        });

        // 삭제 버튼 이벤트
        document.querySelectorAll('.delete-category').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const categoryId = parseInt(btn.dataset.categoryId);
                this.showDeleteModal(categoryId);
            });
        });
    }

    selectCategory(categoryId) {
        // 선택된 항목 하이라이트
        document.querySelectorAll('.category-item').forEach(item => {
            item.classList.remove('active');
        });
        
        const selectedItem = document.querySelector(`[data-category-id="${categoryId}"]`);
        if (selectedItem) {
            selectedItem.classList.add('active');
        }

        this.selectedCategoryId = categoryId;
        this.showCategoryDetails(categoryId);
    }

    async showCategoryDetails(categoryId) {
        try {
            const data = await this.apiCall(`${this.config.API_URL}/${categoryId}`);
            const category = data.data;
            this.renderCategoryDetails(category);
        } catch (error) {
            console.error('Error loading category details:', error);
            Toast.error('분류 상세 정보를 불러오는 중 오류가 발생했습니다.');
        }
    }

    renderCategoryDetails(category) {
        const container = document.getElementById('category-details-container');
        if (!container) return;

        const levelText = category.level === 1 ? '대분류' : '소분류';
        const statusText = category.is_active ? '활성' : '비활성';
        const statusClass = category.is_active ? 'success' : 'secondary';
        const levelClass = category.level === 1 ? 'primary' : 'info';

        let parentInfo = '';
        if (category.parent_category) {
            parentInfo = `
                <div class="col-12">
                    <label class="form-label fw-medium">상위 분류</label>
                    <div class="form-control-plaintext">
                        ${this.escapeHtml(category.parent_category.category_name)} 
                        <small class="text-muted">(${this.escapeHtml(category.parent_category.category_code)})</small>
                    </div>
                </div>
            `;
        }

        let subCategoriesInfo = '';
        if (category.sub_categories && category.sub_categories.length > 0) {
            const subCategoriesList = category.sub_categories.map(sub => 
                `<li>${this.escapeHtml(sub.category_name)} <small class="text-muted">(${this.escapeHtml(sub.category_code)})</small></li>`
            ).join('');
            
            subCategoriesInfo = `
                <div class="col-12">
                    <label class="form-label fw-medium">하위 분류</label>
                    <div class="form-control-plaintext">
                        <ul class="mb-0">
                            ${subCategoriesList}
                        </ul>
                    </div>
                </div>
            `;
        }

        const html = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">분류 상세 정보</h5>
                <div>
                    <button type="button" class="btn btn-primary btn-sm me-2" onclick="window.supplyCategoryPage.editCategory(${category.id})">
                        <i class="ri-edit-line me-1"></i> 수정
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.supplyCategoryPage.toggleCategoryStatus(${category.id})">
                        <i class="ri-toggle-line me-1"></i> 상태 변경
                    </button>
                </div>
            </div>
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-medium">분류 코드</label>
                    <div class="form-control-plaintext">
                        <code class="fs-6">${this.escapeHtml(category.category_code)}</code>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium">분류 레벨</label>
                    <div class="form-control-plaintext">
                        <span class="badge bg-${levelClass} fs-6">${levelText}</span>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-medium">분류명</label>
                    <div class="form-control-plaintext fs-5 fw-medium">
                        ${this.escapeHtml(category.category_name)}
                    </div>
                </div>
                ${parentInfo}
                <div class="col-md-6">
                    <label class="form-label fw-medium">표시 순서</label>
                    <div class="form-control-plaintext">
                        ${category.display_order}
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium">상태</label>
                    <div class="form-control-plaintext">
                        <span class="badge bg-${statusClass} fs-6">${statusText}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium">생성일</label>
                    <div class="form-control-plaintext">
                        ${this.formatDate(category.created_at)}
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-medium">최종 수정일</label>
                    <div class="form-control-plaintext">
                        ${this.formatDate(category.updated_at)}
                    </div>
                </div>
                ${subCategoriesInfo}
            </div>
        `;

        container.innerHTML = html;
    }

    showCategoryModal(category = null) {
        this.isEditMode = !!category;
        const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
        const modalTitle = document.getElementById('categoryModalLabel');
        const form = document.getElementById('categoryForm');
        const helpContainer = document.getElementById('category-form-help');

        modalTitle.textContent = this.isEditMode ? '분류 수정' : '분류 등록';
        form.reset();
        this.clearFormErrors();

        if (this.isEditMode && category) {
            // Edit mode
            document.getElementById('category-level').value = category.level;
            document.getElementById('category-code').value = category.category_code;
            document.getElementById('category-name').value = category.category_name;
            document.getElementById('display-order').value = category.display_order;
            document.getElementById('is-active').value = category.is_active ? '1' : '0';

            if (category.level === 2) {
                document.getElementById('parent-category').value = category.parent_id;
                this.toggleParentCategoryField('2');
            }

            document.getElementById('category-level').disabled = true;
            document.getElementById('category-code').readOnly = true;
            document.getElementById('generate-code-btn').disabled = true;

            helpContainer.innerHTML = `
                <div class="alert alert-info">
                    <h6 class="alert-heading">수정 가능한 항목</h6>
                    <ul class="mb-0 small">
                        <li>분류명</li>
                        <li>표시 순서</li>
                        <li>상태 (활성/비활성)</li>
                    </ul>
                </div>
                <div class="alert alert-warning">
                    <h6 class="alert-heading">수정 불가능한 항목</h6>
                    <ul class="mb-0 small">
                        <li>분류 코드</li>
                        <li>분류 레벨</li>
                        <li>상위 분류</li>
                    </ul>
                </div>
            `;
        } else {
            // Create mode
            document.getElementById('category-level').disabled = false;
            document.getElementById('category-code').readOnly = false;
            document.getElementById('generate-code-btn').disabled = false;
            this.toggleParentCategoryField('');

            helpContainer.innerHTML = `
                <div class="alert alert-info">
                    <h6 class="alert-heading">분류 생성 가이드</h6>
                    <ul class="mb-0 small">
                        <li><strong>대분류:</strong> 최상위 분류입니다.</li>
                        <li><strong>소분류:</strong> 대분류 하위에 속합니다.</li>
                        <li><strong>분류 코드:</strong> 자동 생성을 권장합니다.</li>
                        <li><strong>표시 순서:</strong> 숫자가 작을수록 먼저 표시됩니다.</li>
                    </ul>
                </div>
                <div class="alert alert-warning">
                    <h6 class="alert-heading">주의사항</h6>
                    <ul class="mb-0 small">
                        <li>분류 코드는 생성 후 수정할 수 없습니다.</li>
                        <li>소분류는 반드시 상위 분류를 선택해야 합니다.</li>
                    </ul>
                </div>
            `;
        }

        this.loadParentCategoryOptions();
        modal.show();
    }

    async loadParentCategoryOptions() {
        try {
            const data = await this.apiCall(`${this.config.API_URL}/level/1`);
            const mainCategories = data.data || [];
            
            const select = document.getElementById('parent-category');
            select.innerHTML = '<option value="">선택하세요</option>';
            
            mainCategories.forEach(category => {
                if (category.is_active) {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = `${category.category_name} (${category.category_code})`;
                    select.appendChild(option);
                }
            });
        } catch (error) {
            console.error('Error loading parent categories:', error);
        }
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
        const level = document.getElementById('category-level').value;
        const parentId = document.getElementById('parent-category').value;
        
        if (!level) {
            Toast.error('먼저 분류 레벨을 선택해주세요.');
            return;
        }
        
        if (level === '2' && !parentId) {
            Toast.error('소분류는 상위 분류를 먼저 선택해주세요.');
            return;
        }
        
        try {
            const url = `${this.config.API_URL}/generate-code?level=${level}${parentId ? `&parent_id=${parentId}` : ''}`;
            const data = await this.apiCall(url);
            document.getElementById('category-code').value = data.data.code;
        } catch (error) {
            console.error('Error generating category code:', error);
            Toast.error('분류 코드 생성 중 오류가 발생했습니다.');
        }
    }

    async saveCategoryForm() {
        const form = document.getElementById('categoryForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        // Ensure parent_id is null if not provided or invalid
        if (data.level !== '2' || !data.parent_id || data.parent_id === 'undefined') {
            data.parent_id = null;
        }

        if (!this.validateCategoryForm(data)) {
            return;
        }
        
        try {
            const url = this.isEditMode ? 
                `${this.config.API_URL}/${this.selectedCategoryId}` :
                this.config.API_URL;
            
            const method = this.isEditMode ? 'PUT' : 'POST';
            
            await this.apiCall(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            Toast.success(this.isEditMode ? '분류가 수정되었습니다.' : '분류가 생성되었습니다.');
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('categoryModal'));
            modal.hide();
            
            await this.loadCategories();
            
            if (this.isEditMode) {
                this.selectCategory(this.selectedCategoryId);
            }
            
        } catch (error) {
            console.error('Error saving category:', error);
            Toast.error(error.message);
        }
    }

    validateCategoryForm(data) {
        this.clearFormErrors();
        let isValid = true;
        
        if (!data.level) {
            this.showFieldError('category-level', '분류 레벨을 선택해주세요.');
            isValid = false;
        }
        
        if (!data.category_code) {
            this.showFieldError('category-code', '분류 코드를 입력해주세요.');
            isValid = false;
        }
        
        if (!data.category_name) {
            this.showFieldError('category-name', '분류명을 입력해주세요.');
            isValid = false;
        }
        
        if (data.level === '2' && !data.parent_id) {
            this.showFieldError('parent-category', '소분류는 상위 분류를 선택해야 합니다.');
            isValid = false;
        }
        
        return isValid;
    }

    editCategory(categoryId) {
        const category = this.categories.find(c => c.id === categoryId);
        if (category) {
            this.selectedCategoryId = categoryId;
            this.showCategoryModal(category);
        }
    }

    async toggleCategoryStatus(categoryId) {
        const category = this.categories.find(c => c.id === categoryId);
        if (!category) return;
        
        const newStatus = category.is_active ? '비활성' : '활성';
        
        const result = await Confirm.fire({
            title: '상태 변경 확인',
            text: `정말로 이 분류를 ${newStatus} 상태로 변경하시겠습니까?`
        });
        
        if (!result.isConfirmed) return;
        
        try {
            const response = await this.apiCall(`${this.config.API_URL}/${categoryId}/toggle-status`, {
                method: 'PUT'
            });
            
            Toast.success(response.data.message);
            await this.loadCategories();
            
            if (this.selectedCategoryId === categoryId) {
                this.selectCategory(categoryId);
            }
            
        } catch (error) {
            console.error('Error toggling category status:', error);
            Toast.error(error.message);
        }
    }

    showDeleteModal(categoryId) {
        const category = this.categories.find(c => c.id === categoryId);
        if (!category) return;
        
        this.selectedCategoryId = categoryId;
        
        const modal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
        const infoDiv = document.getElementById('delete-category-info');
        
        infoDiv.innerHTML = `
            <div class="alert alert-light">
                <strong>${this.escapeHtml(category.category_name)}</strong><br>
                <small class="text-muted">코드: ${this.escapeHtml(category.category_code)}</small>
            </div>
        `;
        
        modal.show();
    }

    async deleteCategory() {
        if (!this.selectedCategoryId) return;
        
        try {
            await this.apiCall(`${this.config.API_URL}/${this.selectedCategoryId}`, {
                method: 'DELETE'
            });

            Toast.success('분류가 삭제되었습니다.');
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteCategoryModal'));
            modal.hide();
            
            await this.loadCategories();
            
            this.clearCategoryDetails();
            this.selectedCategoryId = null;
            
        } catch (error) {
            console.error('Error deleting category:', error);
            Toast.error(error.message);
        }
    }

    setupFilters() {
        this.applyFilters();
    }

    applyFilters() {
        const levelFilter = document.getElementById('filter-level')?.value;
        const statusFilter = document.getElementById('filter-status')?.value;
        
        this.filteredCategories = this.categories.filter(category => {
            if (levelFilter && category.level.toString() !== levelFilter) {
                return false;
            }
            
            if (statusFilter !== '' && category.is_active.toString() !== statusFilter) {
                return false;
            }
            
            return true;
        });
        
        this.renderCategoryList();
    }

    searchCategories(query) {
        if (!query.trim()) {
            this.applyFilters();
            return;
        }
        
        const searchTerm = query.toLowerCase();
        this.filteredCategories = this.categories.filter(category => {
            return category.category_name.toLowerCase().includes(searchTerm) ||
                   category.category_code.toLowerCase().includes(searchTerm);
        });
        
        this.renderCategoryList();
    }

    clearCategoryDetails() {
        const container = document.getElementById('category-details-container');
        if (container) {
            container.innerHTML = `
                <div class="text-center p-5">
                    <i class="ri-folder-line fs-1 text-muted"></i>
                    <p class="mt-3 text-muted">왼쪽 목록에서 분류를 선택하거나 '신규 등록' 버튼을 클릭하여 시작하세요.</p>
                </div>
            `;
        }
    }

    showFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        if (field) {
            field.classList.add('is-invalid');
            const feedback = field.parentNode.querySelector('.invalid-feedback');
            if (feedback) {
                feedback.textContent = message;
            }
        }
    }

    clearFormErrors() {
        document.querySelectorAll('.is-invalid').forEach(field => {
            field.classList.remove('is-invalid');
        });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('ko-KR', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
}

// 인스턴스 생성
new SupplyCategoryPage();
