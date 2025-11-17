/**
 * 지급품 분류 상세 페이지
 */

class SupplyCategoryShowPage extends BasePage {
    constructor() {
        super({
            apiBaseUrl: '/api/supply/categories'
        });
        
        this.categoryId = window.viewData?.categoryId || null;
        this.categoryData = null;
    }

    setupEventListeners() {
        // Event listeners can be attached after elements are rendered
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

            document.getElementById('loading-container').style.display = 'none';
            document.getElementById('main-container').style.display = 'flex';

            this.renderDetails();
            this.renderSubCategories();
            this.renderSidebar();

        } catch (error) {
            console.error('Error loading category data:', error);
            Toast.error('분류 정보를 불러오는 데 실패했습니다.');
            document.getElementById('loading-container').innerHTML = '<p class="text-danger">데이터 로딩 실패</p>';
        }
    }

    renderDetails() {
        const container = document.getElementById('details-col');
        if (!container) return;

        let parentCategoryInfo = '';
        if (this.categoryData.parent_category) {
            parentCategoryInfo = `
                <div class="col-12">
                    <label class="form-label fw-medium">상위 분류</label>
                    <div class="form-control-plaintext">
                        <a href="/supply/categories/show?id=${this.categoryData.parent_category.id}" class="text-decoration-none">
                            ${this.sanitizeHTML(this.categoryData.parent_category.category_name)}
                            <small class="text-muted">(${this.sanitizeHTML(this.categoryData.parent_category.category_code)})</small>
                        </a>
                    </div>
                </div>
            `;
        }

        container.innerHTML = `
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <h5 class="card-title mb-0 flex-grow-1">분류 정보</h5>
                        <div class="flex-shrink-0">
                            <a href="/supply/categories/edit?id=${this.categoryData.id}" class="btn btn-primary btn-sm">
                                <i class="ri-edit-line me-1"></i> 수정
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label fw-medium">분류 코드</label><div class="form-control-plaintext"><code class="fs-6">${this.sanitizeHTML(this.categoryData.category_code)}</code></div></div>
                        <div class="col-md-6"><label class="form-label fw-medium">분류 레벨</label><div class="form-control-plaintext"><span class="badge bg-${this.categoryData.level == 1 ? 'primary' : 'info'} fs-6">${this.categoryData.level == 1 ? '대분류' : '소분류'}</span></div></div>
                        <div class="col-12"><label class="form-label fw-medium">분류명</label><div class="form-control-plaintext fs-5 fw-medium">${this.sanitizeHTML(this.categoryData.category_name)}</div></div>
                        ${parentCategoryInfo}
                        <div class="col-md-6"><label class="form-label fw-medium">표시 순서</label><div class="form-control-plaintext">${this.categoryData.display_order}</div></div>
                        <div class="col-md-6"><label class="form-label fw-medium">상태</label><div class="form-control-plaintext"><span class="badge bg-${this.categoryData.is_active ? 'success' : 'secondary'} fs-6">${this.categoryData.is_active ? '활성' : '비활성'}</span></div></div>
                        <div class="col-md-6"><label class="form-label fw-medium">생성일</label><div class="form-control-plaintext">${new Date(this.categoryData.created_at).toLocaleString()}</div></div>
                        <div class="col-md-6"><label class="form-label fw-medium">최종 수정일</label><div class="form-control-plaintext">${new Date(this.categoryData.updated_at).toLocaleString()}</div></div>
                    </div>
                </div>
            </div>
        `;
    }

    renderSubCategories() {
        if (!this.categoryData.sub_categories || this.categoryData.sub_categories.length === 0) {
            return;
        }

        const container = document.getElementById('details-col');
        if (!container) return;

        const subCategoriesHtml = this.categoryData.sub_categories.map(sub => `
            <tr>
                <td><code>${this.sanitizeHTML(sub.category_code)}</code></td>
                <td>${this.sanitizeHTML(sub.category_name)}</td>
                <td>${sub.display_order}</td>
                <td><span class="badge bg-${sub.is_active ? 'success' : 'secondary'}">${sub.is_active ? '활성' : '비활성'}</span></td>
                <td>${new Date(sub.created_at).toLocaleDateString()}</td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-soft-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="ri-more-fill"></i></button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/supply/categories/show?id=${sub.id}"><i class="ri-eye-line align-bottom me-2 text-muted"></i> 상세보기</a></li>
                            <li><a class="dropdown-item" href="/supply/categories/edit?id=${sub.id}"><i class="ri-pencil-fill align-bottom me-2 text-muted"></i> 수정</a></li>
                        </ul>
                    </div>
                </td>
            </tr>
        `).join('');

        container.innerHTML += `
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">하위 분류 목록</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>분류 코드</th><th>분류명</th><th>표시 순서</th><th>상태</th><th>생성일</th><th>작업</th>
                                </tr>
                            </thead>
                            <tbody>${subCategoriesHtml}</tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    renderSidebar() {
        const container = document.getElementById('sidebar-col');
        if (!container) return;

        const addSubCategoryBtn = this.categoryData.level == 1 ? `
            <a href="/supply/categories/create?parent_id=${this.categoryData.id}" class="btn btn-success">
                <i class="ri-add-line me-1"></i> 하위 분류 추가
            </a>` : '';

        container.innerHTML = `
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">빠른 작업</h5></div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="/supply/categories/edit?id=${this.categoryData.id}" class="btn btn-primary"><i class="ri-edit-line me-1"></i> 분류 수정</a>
                        ${addSubCategoryBtn}
                        <button type="button" class="btn btn-outline-secondary" id="toggle-status-btn">
                            <i class="ri-toggle-line me-1"></i> 상태 변경 (${this.categoryData.is_active ? '비활성화' : '활성화'})
                        </button>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">분류 경로</h5></div>
                <div class="card-body">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0" id="breadcrumb-container"></ol>
                    </nav>
                </div>
            </div>
        `;
        
        this.renderBreadcrumb();
        document.getElementById('toggle-status-btn').addEventListener('click', () => this.toggleStatus());
    }

    renderBreadcrumb() {
        const container = document.getElementById('breadcrumb-container');
        if (!container) return;

        if (this.categoryData.parent_category) {
            container.innerHTML += `
                <li class="breadcrumb-item">
                    <a href="/supply/categories/show?id=${this.categoryData.parent_category.id}">
                        ${this.sanitizeHTML(this.categoryData.parent_category.category_name)}
                    </a>
                </li>`;
        }
        container.innerHTML += `<li class="breadcrumb-item active" aria-current="page">${this.sanitizeHTML(this.categoryData.category_name)}</li>`;
    }

    async toggleStatus() {
        const newStatusText = this.categoryData.is_active ? '비활성' : '활성';
        const result = await Confirm.fire({
            title: '상태 변경 확인',
            text: `정말로 이 분류를 ${newStatusText}으로 변경하시겠습니까?`,
            icon: 'warning'
        });

        if (result.isConfirmed) {
            try {
                await this.apiCall(`${this.config.apiBaseUrl}/${this.categoryId}/toggle-status`, { method: 'PUT' });
                Toast.success('상태가 변경되었습니다.');
                this.loadInitialData(); // Reload data to show updated status
            } catch (error) {
                console.error('Error toggling status:', error);
                Toast.error(error.message || '상태 변경 중 오류가 발생했습니다.');
            }
        }
    }
}

new SupplyCategoryShowPage();
