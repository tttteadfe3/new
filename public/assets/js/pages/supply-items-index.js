/**
 * 지급품 품목 관리 JavaScript
 */

class SupplyItemsPage extends BasePage {
    constructor() {
        super({
            API_URL: '/supply/items'
        });

        this.dataTable = null;
        this.currentItemId = null;
        this.categories = [];
    }

    setupEventListeners() {
        document.getElementById('filter-category')?.addEventListener('change', () => this.loadItems());
        document.getElementById('filter-status')?.addEventListener('change', () => this.loadItems());
        document.getElementById('search-input')?.addEventListener('keyup', () => this.loadItems());
        document.getElementById('create-item-btn')?.addEventListener('click', () => this.openCreateModal());
        document.getElementById('confirm-create-btn')?.addEventListener('click', () => this.handleCreateSubmit());
        document.getElementById('confirm-edit-btn')?.addEventListener('click', () => this.handleEditSubmit());
        document.getElementById('confirm-status-btn')?.addEventListener('click', () => this.confirmStatusChange());
        document.getElementById('confirm-delete-btn')?.addEventListener('click', () => this.confirmDelete());
    }

    loadInitialData() {
        this.loadCategories();
        this.initializeDataTable();
        this.loadItems();
    }

    async loadCategories() {
        try {
            const data = await this.apiCall('/supply/categories');
            this.categories = data.data || [];

            ['filter-category', 'create-category-id', 'edit-category-id'].forEach(id => {
                const select = document.getElementById(id);
                if (select && id !== 'filter-category') {
                    this.categories.forEach(cat => {
                        const option = document.createElement('option');
                        option.value = cat.id;
                        option.textContent = cat.category_name;
                        select.appendChild(option);
                    });
                } else if (select) {
                    this.categories.forEach(cat => {
                        const option = document.createElement('option');
                        option.value = cat.id;
                        option.textContent = cat.category_name;
                        select.appendChild(option);
                    });
                }
            });
        } catch (error) {
            console.error('Error loading categories:', error);
        }
    }

    async loadItems() {
        try {
            const params = new URLSearchParams({
                category_id: document.getElementById('filter-category')?.value || '',
                is_active: document.getElementById('filter-status')?.value || '',
                search: document.getElementById('search-input')?.value || ''
            });
            const result = await this.apiCall(`${this.config.API_URL}?${params}`);
            // API 응답 구조에 따라 데이터 추출
            const items = result.data?.data || result.data || [];
            this.dataTable.clear().rows.add(items).draw();
        } catch (error) {
            console.error('Error loading items:', error);
            Toast.error('품목을 불러오는 중 오류가 발생했습니다.');
        }
    }

    initializeDataTable() {
        const self = this;
        this.dataTable = $('#items-table').DataTable({
            processing: true,
            serverSide: false,
            columns: [

                { data: 'item_name' },
                { data: 'category_name' },
                { data: 'unit' },
                { data: 'is_active', render: data => data == 1 ? '<span class="badge bg-success">활성</span>' : '<span class="badge bg-secondary">비활성</span>' },
                { data: 'created_at', render: data => data ? new Date(data).toLocaleDateString('ko-KR') : '-' },
                {
                    data: null,
                    orderable: false,
                    render: (data, type, row) => `
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-info show-btn" data-id="${row.id}" title="상세"><i class="ri-eye-line"></i></button>
                            <button type="button" class="btn btn-primary edit-btn" data-id="${row.id}" title="수정"><i class="ri-edit-line"></i></button>
                            <button type="button" class="btn btn-warning toggle-status-btn" data-id="${row.id}" data-status="${row.is_active}" title="상태 변경"><i class="ri-refresh-line"></i></button>
                            <button type="button" class="btn btn-danger delete-btn" data-id="${row.id}" data-name="${row.item_name}" title="삭제"><i class="ri-delete-bin-line"></i></button>
                        </div>
                    `
                }
            ],
            language: { url: '//cdn.datatables.net/plug-ins/2.3.5/i18n/ko.json' },
            order: [[0, 'asc']],
            pageLength: 25,
            searching: false
        });

        $('#items-table').on('click', '.show-btn', function () { self.openShowModal($(this).data('id')); });
        $('#items-table').on('click', '.edit-btn', function () { self.openEditModal($(this).data('id')); });
        $('#items-table').on('click', '.toggle-status-btn', function () { self.showStatusModal($(this).data('id'), $(this).data('status')); });
        $('#items-table').on('click', '.delete-btn', function () { self.showDeleteModal($(this).data('id'), $(this).data('name')); });
    }

    openCreateModal() {
        document.getElementById('create-form').reset();
        document.getElementById('create-is-active').checked = true;
        new bootstrap.Modal(document.getElementById('createModal')).show();
    }

    async handleCreateSubmit() {
        const form = document.getElementById('create-form');
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        try {
            await this.apiCall(this.config.API_URL, {
                method: 'POST',
                body: JSON.stringify({
                    category_id: document.getElementById('create-category-id').value,

                    item_name: document.getElementById('create-item-name').value,
                    unit: document.getElementById('create-unit').value,
                    min_stock_level: document.getElementById('create-min-stock-level').value || 0,
                    description: document.getElementById('create-description').value,
                    is_active: document.getElementById('create-is-active').checked ? 1 : 0
                })
            });

            Toast.success('품목이 등록되었습니다.');
            this.loadItems();
            bootstrap.Modal.getInstance(document.getElementById('createModal')).hide();
            form.classList.remove('was-validated');
        } catch (error) {
            Toast.error(error.message || '품목 등록 중 오류가 발생했습니다.');
        }
    }

    async openEditModal(id) {
        try {
            const result = await this.apiCall(`${this.config.API_URL}/${id}`);
            const item = result.data;

            document.getElementById('edit-item-id').value = item.id;
            document.getElementById('edit-category-id').value = item.category_id;

            document.getElementById('edit-item-name').value = item.item_name;
            document.getElementById('edit-unit').value = item.unit;
            document.getElementById('edit-min-stock-level').value = item.min_stock_level || 0;
            document.getElementById('edit-description').value = item.description || '';
            document.getElementById('edit-is-active').checked = item.is_active == 1;

            new bootstrap.Modal(document.getElementById('editModal')).show();
        } catch (error) {
            Toast.error('품목 정보를 불러오는 중 오류가 발생했습니다.');
        }
    }

    async handleEditSubmit() {
        const form = document.getElementById('edit-form');
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        try {
            const itemId = document.getElementById('edit-item-id').value;
            await this.apiCall(`${this.config.API_URL}/${itemId}`, {
                method: 'PUT',
                body: JSON.stringify({
                    category_id: document.getElementById('edit-category-id').value,

                    item_name: document.getElementById('edit-item-name').value,
                    unit: document.getElementById('edit-unit').value,
                    min_stock_level: document.getElementById('edit-min-stock-level').value || 0,
                    description: document.getElementById('edit-description').value,
                    is_active: document.getElementById('edit-is-active').checked ? 1 : 0
                })
            });

            Toast.success('품목이 수정되었습니다.');
            this.loadItems();
            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
            form.classList.remove('was-validated');
        } catch (error) {
            Toast.error(error.message || '품목 수정 중 오류가 발생했습니다.');
        }
    }

    async openShowModal(id) {
        try {
            const result = await this.apiCall(`${this.config.API_URL}/${id}`);
            const item = result.data;


            document.getElementById('show-category-name').textContent = item.category_name || '-';
            document.getElementById('show-item-name').textContent = item.item_name || '-';
            document.getElementById('show-unit').textContent = item.unit || '-';
            document.getElementById('show-min-stock-level').textContent = item.min_stock_level || '0';
            document.getElementById('show-description').textContent = item.description || '-';

            const statusBadge = document.getElementById('show-status-badge');
            statusBadge.innerHTML = item.is_active == 1 ? '<span class="badge bg-success">활성</span>' : '<span class="badge bg-secondary">비활성</span>';

            document.getElementById('show-created-at').textContent = item.created_at ? new Date(item.created_at).toLocaleString('ko-KR') : '-';
            document.getElementById('show-updated-at').textContent = item.updated_at ? new Date(item.updated_at).toLocaleString('ko-KR') : '-';

            new bootstrap.Modal(document.getElementById('showModal')).show();

            this.loadStockInfo(id);
            this.loadPurchaseHistory(id);
            this.loadDistributionHistory(id);
        } catch (error) {
            Toast.error('품목 정보를 불러오는 중 오류가 발생했습니다.');
        }
    }

    async loadStockInfo(itemId) {
        const container = document.getElementById('stock-info-container');
        container.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>';

        try {
            const result = await this.apiCall(`/supply/stocks?item_id=${itemId}`);
            const stocks = result.data?.data || result.data || [];

            if (!Array.isArray(stocks) || stocks.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-3"><i class="ri-inbox-line fs-1"></i><p class="mt-2">재고 정보가 없습니다.</p></div>';
                return;
            }

            let html = '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>창고/부서</th><th>현재 재고</th><th>최소 재고</th><th>상태</th></tr></thead><tbody>';
            stocks.forEach(stock => {
                const quantity = parseInt(stock.quantity) || 0;
                const minLevel = parseInt(stock.min_stock_level) || 0;
                const statusBadge = quantity === 0 ? '<span class="badge bg-danger">재고 없음</span>' : quantity <= minLevel ? '<span class="badge bg-warning">부족</span>' : '<span class="badge bg-success">정상</span>';
                html += `<tr><td>${stock.location || '-'}</td><td>${quantity}</td><td>${minLevel}</td><td>${statusBadge}</td></tr>`;
            });
            html += '</tbody></table></div>';
            container.innerHTML = html;
        } catch (error) {
            console.error('Error loading stock info:', error);
            container.innerHTML = '<div class="text-center text-danger py-3"><p>재고 정보를 불러오는 중 오류가 발생했습니다.</p></div>';
        }
    }

    async loadPurchaseHistory(itemId) {
        const container = document.getElementById('purchases-container');
        container.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>';

        try {
            const result = await this.apiCall(`/supply/purchases?item_id=${itemId}&limit=10`);
            const purchases = result.data?.data || result.data || [];

            if (!Array.isArray(purchases) || purchases.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-3"><i class="ri-inbox-line fs-1"></i><p class="mt-2">구매 내역이 없습니다.</p></div>';
                return;
            }

            let html = '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>구매일</th><th>수량</th><th>단가</th><th>합계</th><th>공급업체</th></tr></thead><tbody>';
            purchases.forEach(purchase => {
                const total = (parseFloat(purchase.quantity) || 0) * (parseFloat(purchase.unit_price) || 0);
                html += `<tr><td>${purchase.purchase_date ? new Date(purchase.purchase_date).toLocaleDateString('ko-KR') : '-'}</td><td>${purchase.quantity || 0}</td><td>${(purchase.unit_price || 0).toLocaleString()}원</td><td>${total.toLocaleString()}원</td><td>${purchase.supplier || purchase.supplier_name || '-'}</td></tr>`;
            });
            html += '</tbody></table></div>';
            container.innerHTML = html;
        } catch (error) {
            console.error('Error loading purchase history:', error);
            container.innerHTML = '<div class="text-center text-danger py-3"><p>구매 내역을 불러오는 중 오류가 발생했습니다.</p></div>';
        }
    }

    async loadDistributionHistory(itemId) {
        const container = document.getElementById('distributions-container');
        container.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>';

        try {
            console.log(`Loading distribution history for item ${itemId}...`);
            const result = await this.apiCall(`/supply/distributions?item_id=${itemId}&limit=10`);
            console.log('Distribution API Response:', result);

            // API 응답 구조에 따라 데이터 추출 (result.data.data 또는 result.data)
            const distributions = result.data?.data || result.data || [];
            console.log('Parsed distributions:', distributions);

            if (!Array.isArray(distributions) || distributions.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-3"><i class="ri-inbox-line fs-1"></i><p class="mt-2">지급 내역이 없습니다.</p></div>';
                return;
            }

            let html = '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>지급일</th><th>수량</th><th>문서명</th><th>생성자</th></tr></thead><tbody>';
            distributions.forEach(dist => {
                html += `<tr><td>${dist.distribution_date ? new Date(dist.distribution_date).toLocaleDateString('ko-KR') : '-'}</td><td>${dist.quantity || 0}</td><td>${dist.title || '-'}</td><td>${dist.created_by_name || '-'}</td></tr>`;
            });
            html += '</tbody></table></div>';
            container.innerHTML = html;
        } catch (error) {
            console.error('Error loading distribution history:', error);
            container.innerHTML = '<div class="text-center text-danger py-3"><p>지급 내역을 불러오는 중 오류가 발생했습니다.</p></div>';
        }
    }

    showStatusModal(id, currentStatus) {
        this.currentItemId = id;
        const newStatus = currentStatus == 1 ? '비활성' : '활성';
        document.getElementById('status-change-message').textContent = `이 품목을 ${newStatus} 상태로 변경하시겠습니까?`;
        new bootstrap.Modal(document.getElementById('statusModal')).show();
    }

    async confirmStatusChange() {
        try {
            await this.apiCall(`${this.config.API_URL}/${this.currentItemId}/toggle-status`, { method: 'PUT' });
            Toast.success('품목 상태가 변경되었습니다.');
            this.loadItems();
            bootstrap.Modal.getInstance(document.getElementById('statusModal')).hide();
        } catch (error) {
            Toast.error(error.message || '상태 변경 중 오류가 발생했습니다.');
        }
    }

    showDeleteModal(id, name) {
        this.currentItemId = id;
        document.getElementById('delete-item-info').innerHTML = `<strong>${name}</strong>`;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }

    async confirmDelete() {
        try {
            await this.apiCall(`${this.config.API_URL}/${this.currentItemId}`, { method: 'DELETE' });
            Toast.success('품목이 삭제되었습니다.');
            this.loadItems();
            bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
        } catch (error) {
            Toast.error(error.message || '품목 삭제 중 오류가 발생했습니다.');
        }
    }
}

new SupplyItemsPage();
