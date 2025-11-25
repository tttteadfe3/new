/**
 * 차량 소모품 관리 JavaScript (Category Tree Structure)
 */

class VehicleConsumablesPage extends BasePage {
    constructor() {
        super({ API_URL: '/vehicles/consumables' });
        this.consumablesTable = null;
        this.categories = [];
        this.vehicles = [];
        this.currentEditingCategoryId = null;
    }

    setupEventListeners() {
        // 버튼 이벤트
        document.getElementById('btn-manage-category')?.addEventListener('click', () => this.showCategoryModal());
        document.getElementById('btn-stock-in')?.addEventListener('click', () => this.showStockInModal());
        document.getElementById('btn-add-category')?.addEventListener('click', () => this.addCategory());
        document.getElementById('btn-save-stock-in')?.addEventListener('click', () => this.saveStockIn());
        document.getElementById('btn-save-use')?.addEventListener('click', () => this.saveUse());
        document.getElementById('btn-reset-filter')?.addEventListener('click', () => this.resetFilters());

        // 필터 이벤트
        document.getElementById('filter-category')?.addEventListener('change', () => this.loadCategories());
        document.getElementById('filter-search')?.addEventListener('input', this.debounce(() => this.loadCategories(), 300));

        // 기본값 설정
        document.getElementById('stock_in_purchase_date').value = new Date().toISOString().split('T')[0];
    }

    loadInitialData() {
        this.initializeDataTable();
        this.loadCategories();
        this.loadVehicles();
    }

    // ============ DataTable ============

    initializeDataTable() {
        this.consumablesTable = $('#consumables-table').DataTable({
            columns: [
                {
                    data: 'name',
                    render: (data, type, row) => {
                        const indent = '&nbsp;&nbsp;'.repeat((row.level - 1) * 2);
                        return `${indent}${data}`;
                    }
                },
                { data: 'unit', defaultContent: '-' },
                {
                    data: 'current_stock',
                    render: (data) => `<span>${Number(data || 0).toLocaleString()}</span>`
                },
                {
                    data: null,
                    render: (data, type, row) => `
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-success stock-in-btn" data-id="${row.id}" title="입고">
                                <i class="ri-add-box-line"></i>
                            </button>
                            <button class="btn btn-warning use-btn" data-id="${row.id}" data-name="${row.name}" data-stock="${row.current_stock}" title="사용">
                                <i class="ri-subtract-line"></i>
                            </button>
                            <button class="btn btn-info history-btn" data-id="${row.id}" title="이력">
                                <i class="ri-history-line"></i>
                            </button>
                        </div>
                    `
                }
            ],
            language: { url: '//cdn.datatables.net/plug-ins/2.3.5/i18n/ko.json' },
            order: [[0, 'asc']],
            pageLength: 50,
            paging: false
        });

        // 테이블 이벤트
        $('#consumables-table').on('click', '.stock-in-btn', (e) => {
            const id = $(e.currentTarget).data('id');
            this.showStockInModal(id);
        });

        $('#consumables-table').on('click', '.use-btn', (e) => {
            const btn = $(e.currentTarget);
            this.showUseModal(btn.data('id'), btn.data('name'), btn.data('stock'));
        });

        $('#consumables-table').on('click', '.history-btn', (e) => {
            this.showHistoryModal($(e.currentTarget).data('id'));
        });
    }

    // ============ 카테고리 관리 ============

    async loadCategories() {
        try {
            const params = new URLSearchParams();

            const search = document.getElementById('filter-search').value;
            if (search) params.append('search', search);

            const url = params.toString()
                ? `${this.config.API_URL}/categories?${params}`
                : `${this.config.API_URL}/categories`;

            const data = await this.apiCall(url);
            this.categories = data.data || [];

            // 테이블 업데이트
            this.consumablesTable.clear().rows.add(this.categories).draw();

            // 카테고리 선택 옵션 업데이트
            this.updateCategorySelects();
        } catch (error) {
            Toast.error('카테고리 목록을 불러오는 중 오류가 발생했습니다.');
        }
    }

    updateCategorySelects() {
        // 필터 select
        const filterSelect = document.getElementById('filter-category');
        filterSelect.innerHTML = '<option value="">전체 카테고리</option>';

        // 부모 카테고리 select (카테고리 추가용)
        const parentSelect = document.getElementById('parent_category_id');
        parentSelect.innerHTML = '<option value="">최상위 카테고리</option>';

        // 입고 카테고리 select
        const stockInSelect = document.getElementById('stock_in_category_id');
        stockInSelect.innerHTML = '<option value="">카테고리 선택</option>';

        this.categories.forEach(cat => {
            const indent = '\u00A0\u00A0'.repeat((cat.level - 1) * 2);
            const text = `${indent}${cat.name}`;

            // 필터
            const filterOption = new Option(text, cat.id);
            filterSelect.add(filterOption);

            // 부모 (자기 자신은 제외)
            if (!this.currentEditingCategoryId || cat.id !== this.currentEditingCategoryId) {
                const parentOption = new Option(text, cat.id);
                parentSelect.add(parentOption);
            }

            // 입고 (말단 카테고리만 - 자식이 없는 것)
            if (cat.children_count === 0) {
                const stockOption = new Option(text + ` (${cat.unit})`, cat.id);
                stockInSelect.add(stockOption);
            }
        });
    }

    showCategoryModal() {
        this.loadCategoryTree();
        document.getElementById('categoryForm').reset();
        const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
        modal.show();
    }

    async loadCategoryTree() {
        try {
            const data = await this.apiCall(`${this.config.API_URL}/categories/tree`);
            const tree = data.data || [];
            this.renderCategoryTree(tree);
        } catch (error) {
            document.getElementById('category-tree-container').innerHTML =
                '<div class="text-danger">카테고리를 불러오는 중 오류가 발생했습니다.</div>';
        }
    }

    renderCategoryTree(tree, container = null, level = 0) {
        if (!container) {
            container = document.getElementById('category-tree-container');
            container.innerHTML = '';
        }

        if (tree.length === 0 && level === 0) {
            container.innerHTML = '<div class="text-muted">등록된 카테고리가 없습니다.</div>';
            return;
        }

        const ul = document.createElement('ul');
        ul.className = level === 0 ? 'list-unstyled' : 'list-unstyled ms-3';

        tree.forEach(cat => {
            const li = document.createElement('li');
            li.className = 'mb-2';

            const hasChildren = cat.children && cat.children.length > 0;
            const icon = hasChildren ? '<i class="ri-folder-line"></i>' : '<i class="ri-file-line"></i>';

            li.innerHTML = `
                <div class="d-flex justify-content-between align-items-center p-2 border rounded">
                    <span>${icon} <strong>${cat.name}</strong> (${cat.unit})</span>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-sm btn-outline-primary edit-category-btn" data-id="${cat.id}">
                            <i class="ri-edit-line"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger delete-category-btn" data-id="${cat.id}">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </div>
                </div>
            `;

            if (hasChildren) {
                this.renderCategoryTree(cat.children, li, level + 1);
            }

            ul.appendChild(li);
        });

        container.appendChild(ul);

        // 이벤트 리스너 추가
        container.querySelectorAll('.edit-category-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.editCategory(parseInt(btn.dataset.id));
            });
        });

        container.querySelectorAll('.delete-category-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (confirm('정말 삭제하시겠습니까?')) {
                    this.deleteCategory(parseInt(btn.dataset.id));
                }
            });
        });
    }

    async addCategory() {
        const form = document.getElementById('categoryForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const data = {
            name: document.getElementById('category_name').value,
            parent_id: document.getElementById('parent_category_id').value || null,
            unit: document.getElementById('category_unit').value,
            sort_order: parseInt(document.getElementById('category_sort_order').value) || 0,
            note: document.getElementById('category_note').value
        };

        try {
            await this.apiCall(`${this.config.API_URL}/categories`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            Toast.success('카테고리가 추가되었습니다.');
            form.reset();
            this.loadCategories();
            this.loadCategoryTree();
        } catch (error) {
            Toast.error('카테고리 추가 중 오류가 발생했습니다.');
        }
    }

    async editCategory(id) {
        try {
            const data = await this.apiCall(`${this.config.API_URL}/categories/${id}`);
            const category = data.data;

            this.currentEditingCategoryId = id;
            document.getElementById('category_name').value = category.name;
            document.getElementById('parent_category_id').value = category.parent_id || '';
            document.getElementById('category_unit').value = category.unit;
            document.getElementById('category_sort_order').value = category.sort_order || 0;
            document.getElementById('category_note').value = category.note || '';

            this.updateCategorySelects(); // 부모 선택에서 자기 자신 제외

            // 버튼 변경
            const addBtn = document.getElementById('btn-add-category');
            addBtn.innerHTML = '<i class="ri-save-line"></i> 수정';
            addBtn.onclick = () => this.updateCategory(id);

        } catch (error) {
            Toast.error('카테고리 정보를 불러오는 중 오류가 발생했습니다.');
        }
    }

    async updateCategory(id) {
        const form = document.getElementById('categoryForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const data = {
            name: document.getElementById('category_name').value,
            parent_id: document.getElementById('parent_category_id').value || null,
            unit: document.getElementById('category_unit').value,
            sort_order: parseInt(document.getElementById('category_sort_order').value) || 0,
            note: document.getElementById('category_note').value
        };

        try {
            await this.apiCall(`${this.config.API_URL}/categories/${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            Toast.success('카테고리가 수정되었습니다.');
            form.reset();
            this.currentEditingCategoryId = null;

            // 버튼 복원
            const addBtn = document.getElementById('btn-add-category');
            addBtn.innerHTML = '<i class="ri-add-line"></i> 카테고리 추가';
            addBtn.onclick = () => this.addCategory();

            this.loadCategories();
            this.loadCategoryTree();
        } catch (error) {
            Toast.error('카테고리 수정 중 오류가 발생했습니다.');
        }
    }

    async deleteCategory(id) {
        try {
            await this.apiCall(`${this.config.API_URL}/categories/${id}`, {
                method: 'DELETE'
            });
            Toast.success('카테고리가 삭제되었습니다.');
            this.loadCategories();
            this.loadCategoryTree();
        } catch (error) {
            Toast.error(error.message || '카테고리 삭제 중 오류가 발생했습니다.');
        }
    }

    // ============ 입고 관리 ============

    showStockInModal(categoryId = null) {
        document.getElementById('stockInForm').reset();
        document.getElementById('stock_in_purchase_date').value = new Date().toISOString().split('T')[0];

        if (categoryId) {
            document.getElementById('stock_in_category_id').value = categoryId;
        }

        const modal = new bootstrap.Modal(document.getElementById('stockInModal'));
        modal.show();
    }

    async saveStockIn() {
        const form = document.getElementById('stockInForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const data = {
            category_id: parseInt(document.getElementById('stock_in_category_id').value),
            item_name: document.getElementById('stock_in_item_name').value,
            quantity: parseInt(document.getElementById('stock_in_quantity').value),
            unit_price: document.getElementById('stock_in_unit_price').value || null,
            purchase_date: document.getElementById('stock_in_purchase_date').value,
            note: document.getElementById('stock_in_note').value
        };

        try {
            await this.apiCall(`${this.config.API_URL}/stock-in`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            Toast.success('입고 처리되었습니다.');
            this.loadCategories();
            bootstrap.Modal.getInstance(document.getElementById('stockInModal')).hide();
        } catch (error) {
            Toast.error('입고 처리 중 오류가 발생했습니다.');
        }
    }

    // ============ 사용 관리 ============

    async showUseModal(categoryId, categoryName, currentStock) {
        document.getElementById('useForm').reset();
        document.getElementById('use_category_id').value = categoryId;
        document.getElementById('use_category_name').value = categoryName;
        document.getElementById('use_current_stock').value = Number(currentStock).toLocaleString();

        // 해당 카테고리의 품명 목록 불러오기
        try {
            const data = await this.apiCall(`${this.config.API_URL}/categories/${categoryId}/stock-by-item`);
            const items = data.data || [];

            const select = document.getElementById('use_item_name');
            select.innerHTML = '<option value="">전체</option>';
            items.forEach(item => {
                const option = new Option(`${item.item_name} (재고: ${item.current_stock})`, item.item_name);
                select.add(option);
            });
        } catch (error) {
            console.error('품명 목록 로드 실패:', error);
        }

        const modal = new bootstrap.Modal(document.getElementById('useModal'));
        modal.show();
    }

    async saveUse() {
        const form = document.getElementById('useForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const data = {
            category_id: parseInt(document.getElementById('use_category_id').value),
            item_name: document.getElementById('use_item_name').value || null,
            quantity: parseInt(document.getElementById('use_quantity').value),
            vehicle_id: document.getElementById('use_vehicle_id').value || null,
            note: document.getElementById('use_note').value
        };

        try {
            await this.apiCall(`${this.config.API_URL}/use`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            Toast.success('사용 처리되었습니다.');
            this.loadCategories();
            bootstrap.Modal.getInstance(document.getElementById('useModal')).hide();
        } catch (error) {
            Toast.error('사용 처리 중 오류가 발생했습니다.');
        }
    }

    // ============ 이력 조회 ============

    async showHistoryModal(categoryId) {
        try {
            // 품명별 재고
            const stockData = await this.apiCall(`${this.config.API_URL}/categories/${categoryId}/stock-by-item`);
            const items = stockData.data || [];

            let stockHtml = '<table class="table table-sm">';
            stockHtml += '<thead><tr><th>품명</th><th>단위</th><th>입고</th><th>사용</th><th>현재재고</th></tr></thead><tbody>';

            if (items.length === 0) {
                stockHtml += '<tr><td colspan="5" class="text-center">품명이 없습니다.</td></tr>';
            } else {
                items.forEach(item => {
                    stockHtml += `<tr>
                        <td>${item.item_name}</td>
                        <td>${item.unit}</td>
                        <td>${Number(item.stock_in).toLocaleString()}</td>
                        <td>${Number(item.used).toLocaleString()}</td>
                        <td><strong>${Number(item.current_stock).toLocaleString()}</strong></td>
                    </tr>`;
                });
            }
            stockHtml += '</tbody></table>';
            document.getElementById('stock-by-item-content').innerHTML = stockHtml;

            // 입고 이력
            const stockInData = await this.apiCall(`${this.config.API_URL}/categories/${categoryId}/stock-in-history`);
            const stockInHistory = stockInData.data || [];

            let stockInHtml = '<table class="table table-sm">';
            stockInHtml += '<thead><tr><th>품명</th><th>수량</th><th>단가</th><th>구매일</th><th>등록자</th><th>비고</th></tr></thead><tbody>';

            if (stockInHistory.length === 0) {
                stockInHtml += '<tr><td colspan="6" class="text-center">이력이 없습니다.</td></tr>';
            } else {
                stockInHistory.forEach(item => {
                    stockInHtml += `<tr>
                        <td>${item.item_name}</td>
                        <td>${item.quantity}</td>
                        <td>${item.unit_price ? Number(item.unit_price).toLocaleString() + ' 원' : '-'}</td>
                        <td>${item.purchase_date || item.created_at}</td>
                        <td>${item.registered_by_name || '-'}</td>
                        <td>${item.note || '-'}</td>
                    </tr>`;
                });
            }
            stockInHtml += '</tbody></table>';
            document.getElementById('stock-in-history-content').innerHTML = stockInHtml;

            // 사용 이력
            const usageData = await this.apiCall(`${this.config.API_URL}/categories/${categoryId}/usage-history`);
            const usageHistory = usageData.data || [];

            let usageHtml = '<table class="table table-sm">';
            usageHtml += '<thead><tr><th>품명</th><th>수량</th><th>차량</th><th>사용자</th><th>작업</th><th>사용일시</th><th>비고</th></tr></thead><tbody>';

            if (usageHistory.length === 0) {
                usageHtml += '<tr><td colspan="7" class="text-center">이력이 없습니다.</td></tr>';
            } else {
                usageHistory.forEach(item => {
                    usageHtml += `<tr>
                        <td>${item.item_name || '-'}</td>
                        <td>${item.quantity}</td>
                        <td>${item.vehicle_number || '-'}</td>
                        <td>${item.used_by_name || '-'}</td>
                        <td>${item.work_item || '-'}</td>
                        <td>${item.used_at}</td>
                        <td>${item.note || '-'}</td>
                    </tr>`;
                });
            }
            usageHtml += '</tbody></table>';
            document.getElementById('usage-history-content').innerHTML = usageHtml;

            const modal = new bootstrap.Modal(document.getElementById('historyModal'));
            modal.show();
        } catch (error) {
            Toast.error('이력을 불러오는 중 오류가 발생했습니다.');
        }
    }

    // ============ 기타 ============

    async loadVehicles() {
        try {
            const data = await this.apiCall('/vehicles');
            this.vehicles = data.data || [];

            const select = document.getElementById('use_vehicle_id');
            select.innerHTML = '<option value="">선택</option>';

            this.vehicles.forEach(vehicle => {
                const option = document.createElement('option');
                option.value = vehicle.id;
                option.textContent = `${vehicle.vehicle_number} (${vehicle.model})`;
                select.appendChild(option);
            });
        } catch (error) {
            console.error('Failed to load vehicles:', error);
        }
    }

    resetFilters() {
        document.getElementById('filter-category').value = '';
        document.getElementById('filter-search').value = '';
        this.loadCategories();
    }

    debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }
}

new VehicleConsumablesPage();
