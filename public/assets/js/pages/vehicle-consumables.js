/**
 * 차량 소모품 관리 JavaScript
 */

class VehicleConsumablesPage extends BasePage {
    constructor() {
        super({ API_URL: '/vehicles/consumables' });
        this.consumablesTable = null;
        this.categories = [];
        this.vehicles = [];
    }

    setupEventListeners() {
        document.getElementById('btn-add-consumable')?.addEventListener('click', () => this.showConsumableModal());
        document.getElementById('btn-save-consumable')?.addEventListener('click', () => this.saveConsumable());
        document.getElementById('btn-save-stock-in')?.addEventListener('click', () => this.saveStockIn());
        document.getElementById('btn-save-use')?.addEventListener('click', () => this.saveUse());
        document.getElementById('btn-reset-filter')?.addEventListener('click', () => this.resetFilters());

        // Filters
        document.getElementById('filter-category')?.addEventListener('change', () => this.loadConsumables());
        document.getElementById('filter-search')?.addEventListener('input', this.debounce(() => this.loadConsumables(), 300));
        document.getElementById('filter-low-stock')?.addEventListener('change', () => this.loadConsumables());

        // Set today as default purchase date
        document.getElementById('stock_in_purchase_date').value = new Date().toISOString().split('T')[0];
    }

    loadInitialData() {
        this.initializeDataTable();
        this.loadCategories();
        this.loadVehicles();
        this.loadConsumables();
    }

    initializeDataTable() {
        this.consumablesTable = $('#consumables-table').DataTable({
            columns: [
                { data: 'category', defaultContent: '-' },
                { data: 'name' },
                { data: 'part_number', defaultContent: '-' },
                { data: 'unit' },
                {
                    data: 'unit_price',
                    render: (data) => data ? Number(data).toLocaleString() + ' 원' : '-'
                },
                {
                    data: 'current_stock',
                    render: (data, type, row) => {
                        const isLow = row.is_low_stock == 1;
                        const className = isLow ? 'text-danger fw-bold' : '';
                        return `<span class="${className}">${Number(data).toLocaleString()}</span>`;
                    }
                },
                { data: 'minimum_stock', render: (data) => Number(data).toLocaleString() },
                { data: 'location', defaultContent: '-' },
                {
                    data: null,
                    render: (data, type, row) => `
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-success stock-in-btn" data-id="${row.id}" data-name="${row.name}" title="입고">
                                <i class="ri-add-box-line"></i>
                            </button>
                            <button class="btn btn-warning use-btn" data-id="${row.id}" data-name="${row.name}" data-stock="${row.current_stock}" title="출고">
                                <i class="ri-subtract-line"></i>
                            </button>
                            <button class="btn btn-info history-btn" data-id="${row.id}" title="이력">
                                <i class="ri-history-line"></i>
                            </button>
                            <button class="btn btn-primary edit-btn" data-id="${row.id}" title="수정">
                                <i class="ri-edit-line"></i>
                            </button>
                            <button class="btn btn-danger delete-btn" data-id="${row.id}" title="삭제">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                    `
                }
            ],
            language: { url: '//cdn.datatables.net/plug-ins/2.3.5/i18n/ko.json' },
            order: [[1, 'asc']],
            pageLength: 25
        });

        $('#consumables-table').on('click', '.stock-in-btn', (e) => {
            const btn = $(e.currentTarget);
            this.showStockInModal(btn.data('id'), btn.data('name'));
        });

        $('#consumables-table').on('click', '.use-btn', (e) => {
            const btn = $(e.currentTarget);
            this.showUseModal(btn.data('id'), btn.data('name'), btn.data('stock'));
        });

        $('#consumables-table').on('click', '.history-btn', (e) => {
            this.showHistoryModal($(e.currentTarget).data('id'));
        });

        $('#consumables-table').on('click', '.edit-btn', (e) => {
            this.showEditModal($(e.currentTarget).data('id'));
        });

        $('#consumables-table').on('click', '.delete-btn', (e) => {
            if (confirm('정말 삭제하시겠습니까?')) {
                this.deleteConsumable($(e.currentTarget).data('id'));
            }
        });
    }

    async loadConsumables() {
        try {
            const params = new URLSearchParams();

            const category = document.getElementById('filter-category').value;
            if (category) params.append('category', category);

            const search = document.getElementById('filter-search').value;
            if (search) params.append('search', search);

            const lowStock = document.getElementById('filter-low-stock').checked;
            if (lowStock) params.append('low_stock', '1');

            const url = params.toString() ? `${this.config.API_URL}?${params}` : this.config.API_URL;
            const data = await this.apiCall(url);

            this.consumablesTable.clear().rows.add(data.data || []).draw();
        } catch (error) {
            Toast.error('소모품 목록을 불러오는 중 오류가 발생했습니다.');
        }
    }

    async loadCategories() {
        try {
            const data = await this.apiCall(`${this.config.API_URL}/categories`);
            this.categories = data.data || [];

            const filterSelect = document.getElementById('filter-category');
            const datalist = document.getElementById('category-list');

            filterSelect.innerHTML = '<option value="">전체 카테고리</option>';
            datalist.innerHTML = '';

            this.categories.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat;
                option.textContent = cat;
                filterSelect.appendChild(option);

                const datalistOption = document.createElement('option');
                datalistOption.value = cat;
                datalist.appendChild(datalistOption);
            });
        } catch (error) {
            console.error('Failed to load categories:', error);
        }
    }

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

    showConsumableModal() {
        document.getElementById('consumableForm').reset();
        document.getElementById('consumable_id').value = '';
        document.querySelector('#consumableModal .modal-title').textContent = '소모품 등록';
        const modal = new bootstrap.Modal(document.getElementById('consumableModal'));
        modal.show();
    }

    async showEditModal(id) {
        try {
            const data = await this.apiCall(`${this.config.API_URL}/${id}`);
            const consumable = data.data;

            document.getElementById('consumable_id').value = consumable.id;
            document.getElementById('name').value = consumable.name;
            document.getElementById('category').value = consumable.category || '';
            document.getElementById('part_number').value = consumable.part_number || '';
            document.getElementById('location').value = consumable.location || '';
            document.getElementById('unit').value = consumable.unit;
            document.getElementById('unit_price').value = consumable.unit_price || '';
            document.getElementById('minimum_stock').value = consumable.minimum_stock || 0;
            document.getElementById('note').value = consumable.note || '';

            document.querySelector('#consumableModal .modal-title').textContent = '소모품 수정';
            const modal = new bootstrap.Modal(document.getElementById('consumableModal'));
            modal.show();
        } catch (error) {
            Toast.error('소모품 정보를 불러오는 중 오류가 발생했습니다.');
        }
    }

    async saveConsumable() {
        const form = document.getElementById('consumableForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const id = document.getElementById('consumable_id').value;
        const data = {
            name: document.getElementById('name').value,
            category: document.getElementById('category').value,
            part_number: document.getElementById('part_number').value,
            location: document.getElementById('location').value,
            unit: document.getElementById('unit').value,
            unit_price: document.getElementById('unit_price').value || 0,
            minimum_stock: document.getElementById('minimum_stock').value || 0,
            note: document.getElementById('note').value
        };

        try {
            if (id) {
                await this.apiCall(`${this.config.API_URL}/${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                Toast.success('수정되었습니다.');
            } else {
                await this.apiCall(this.config.API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                Toast.success('등록되었습니다.');
            }

            this.loadConsumables();
            this.loadCategories(); // Refresh categories
            bootstrap.Modal.getInstance(document.getElementById('consumableModal')).hide();
        } catch (error) {
            Toast.error('저장 중 오류가 발생했습니다.');
        }
    }

    async deleteConsumable(id) {
        try {
            await this.apiCall(`${this.config.API_URL}/${id}`, {
                method: 'DELETE'
            });
            Toast.success('삭제되었습니다.');
            this.loadConsumables();
        } catch (error) {
            Toast.error('삭제 중 오류가 발생했습니다.');
        }
    }

    showStockInModal(id, name) {
        document.getElementById('stockInForm').reset();
        document.getElementById('stock_in_consumable_id').value = id;
        document.getElementById('stock_in_name').value = name;
        document.getElementById('stock_in_purchase_date').value = new Date().toISOString().split('T')[0];
        const modal = new bootstrap.Modal(document.getElementById('stockInModal'));
        modal.show();
    }

    async saveStockIn() {
        const form = document.getElementById('stockInForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const id = document.getElementById('stock_in_consumable_id').value;
        const data = {
            quantity: parseInt(document.getElementById('stock_in_quantity').value),
            unit_price: document.getElementById('stock_in_unit_price').value || null,
            supplier: document.getElementById('stock_in_supplier').value,
            purchase_date: document.getElementById('stock_in_purchase_date').value,
            note: document.getElementById('stock_in_note').value
        };

        try {
            await this.apiCall(`${this.config.API_URL}/${id}/stock-in`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            Toast.success('입고 처리되었습니다.');
            this.loadConsumables();
            bootstrap.Modal.getInstance(document.getElementById('stockInModal')).hide();
        } catch (error) {
            Toast.error('입고 처리 중 오류가 발생했습니다.');
        }
    }

    showUseModal(id, name, currentStock) {
        document.getElementById('useForm').reset();
        document.getElementById('use_consumable_id').value = id;
        document.getElementById('use_name').value = name;
        document.getElementById('use_current_stock').value = Number(currentStock).toLocaleString();
        const modal = new bootstrap.Modal(document.getElementById('useModal'));
        modal.show();
    }

    async saveUse() {
        const form = document.getElementById('useForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const id = document.getElementById('use_consumable_id').value;
        const data = {
            quantity: parseInt(document.getElementById('use_quantity').value),
            vehicle_id: document.getElementById('use_vehicle_id').value || null,
            note: document.getElementById('use_note').value
        };

        try {
            await this.apiCall(`${this.config.API_URL}/${id}/use`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            Toast.success('출고 처리되었습니다.');
            this.loadConsumables();
            bootstrap.Modal.getInstance(document.getElementById('useModal')).hide();
        } catch (error) {
            Toast.error('출고 처리 중 오류가 발생했습니다.');
        }
    }

    async showHistoryModal(id) {
        try {
            // Load usage history
            const usageData = await this.apiCall(`${this.config.API_URL}/${id}/usage-history`);
            const usageHistory = usageData.data || [];

            let usageHtml = '<table class="table table-sm">';
            usageHtml += '<thead><tr><th>사용일시</th><th>수량</th><th>차량</th><th>사용자</th><th>작업</th><th>비고</th></tr></thead><tbody>';

            if (usageHistory.length === 0) {
                usageHtml += '<tr><td colspan="6" class="text-center">이력이 없습니다.</td></tr>';
            } else {
                usageHistory.forEach(item => {
                    usageHtml += `<tr>
                        <td>${item.used_at}</td>
                        <td>${item.quantity}</td>
                        <td>${item.vehicle_number || '-'}</td>
                        <td>${item.used_by_name || '-'}</td>
                        <td>${item.work_item || '-'}</td>
                        <td>${item.note || '-'}</td>
                    </tr>`;
                });
            }
            usageHtml += '</tbody></table>';

            document.getElementById('usage-history-content').innerHTML = usageHtml;

            // Load stock-in history
            const stockInData = await this.apiCall(`${this.config.API_URL}/${id}/stock-in-history`);
            const stockInHistory = stockInData.data || [];

            let stockInHtml = '<table class="table table-sm">';
            stockInHtml += '<thead><tr><th>구매일</th><th>수량</th><th>단가</th><th>공급업체</th><th>등록자</th><th>비고</th></tr></thead><tbody>';

            if (stockInHistory.length === 0) {
                stockInHtml += '<tr><td colspan="6" class="text-center">이력이 없습니다.</td></tr>';
            } else {
                stockInHistory.forEach(item => {
                    stockInHtml += `<tr>
                        <td>${item.purchase_date || item.created_at}</td>
                        <td>${item.quantity}</td>
                        <td>${item.unit_price ? Number(item.unit_price).toLocaleString() + ' 원' : '-'}</td>
                        <td>${item.supplier || '-'}</td>
                        <td>${item.registered_by_name || '-'}</td>
                        <td>${item.note || '-'}</td>
                    </tr>`;
                });
            }
            stockInHtml += '</tbody></table>';

            document.getElementById('stock-in-history-content').innerHTML = stockInHtml;

            const modal = new bootstrap.Modal(document.getElementById('historyModal'));
            modal.show();
        } catch (error) {
            Toast.error('이력을 불러오는 중 오류가 발생했습니다.');
        }
    }

    resetFilters() {
        document.getElementById('filter-category').value = '';
        document.getElementById('filter-search').value = '';
        document.getElementById('filter-low-stock').checked = false;
        this.loadConsumables();
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
