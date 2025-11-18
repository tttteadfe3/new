/**
 * Supply Purchases Index JavaScript
 */

class SupplyPurchasesIndexPage extends BasePage {
    constructor() {
        super({
            API_URL: '/supply/purchases'
        });

        this.currentPurchaseId = null;
        this.receiveModal = null;
        this.deleteModal = null;
        this.purchaseModal = null;
        this.dataTable = null;
        this.items = [];
    }

    setupEventListeners() {
        $(document).on('click', '.receive-purchase-btn', (e) => this.handleReceiveClick(e));
        $(document).on('click', '.delete-purchase-btn', (e) => this.handleDeleteClick(e));
        $(document).on('click', '.edit-purchase-btn', (e) => this.handleEditClick(e));
        $('#add-purchase-btn').on('click', () => this.handleCreateClick());
        $('#confirm-receive-purchase-btn').on('click', () => this.confirmReception());
        $('#confirm-delete-purchase-btn').on('click', () => this.confirmDeletion());
        $('#purchase-form').on('submit', (e) => this.handleFormSubmit(e));

        // 검색 및 필터 이벤트
        $('#search-input, #filter-status').on('keyup change', this.debounce(() => {
            this.loadPurchases();
        }, 300));
        
        // 총액 자동 계산
        $('#quantity, #unit-price').on('input', () => this.calculateTotal());
    }

    loadInitialData() {
        this.loadStats();
        this.initializeDataTable();
        this.loadPurchases();
        this.loadItems();

        this.receiveModal = new bootstrap.Modal(document.getElementById('receivePurchaseModal'));
        this.deleteModal = new bootstrap.Modal(document.getElementById('deletePurchaseModal'));
        this.purchaseModal = new bootstrap.Modal(document.getElementById('purchaseModal'));
    }

    async loadStats() {
        const statsContainer = $('#stats-container');
        const alertContainer = $('#pending-purchases-alert-container');
        try {
            const response = await this.apiCall(`${this.config.API_URL}/statistics`);
            const stats = response.data;
            // ... (render a lot of stats html)
        } catch (error) {
            statsContainer.html('<p class="text-danger">통계 정보를 불러오는데 실패했습니다.</p>');
        }
    }

    async loadPurchases() {
        try {
            const params = {
                search: $('#search-input').val(),
                status: $('#filter-status').val()
            };

            const queryString = new URLSearchParams(params).toString();
            const result = await this.apiCall(`${this.config.API_URL}?${queryString}`);

            this.dataTable.clear().rows.add(result.data || []).draw();
        } catch (error) {
            console.error('Error loading purchases:', error);
            Toast.error('구매 내역을 불러오는 중 오류가 발생했습니다.');
        }
    }

    initializeDataTable() {
        this.dataTable = $('#purchases-table').DataTable({
            processing: true,
            serverSide: false,
            columns: [
                { data: 'purchase_date' },
                { data: 'item_name', render: (d,t,r) => `${this.escapeHtml(d)}<br><small class="text-muted">${this.escapeHtml(r.item_code)}</small>` },
                { data: 'quantity', className: 'text-end', render: (d,t,r) => `${Number(d).toLocaleString()} ${this.escapeHtml(r.unit)}` },
                { data: 'unit_price', className: 'text-end', render: d => `₩${Number(d).toLocaleString()}` },
                { data: 'total_amount', className: 'text-end', render: d => `<strong>₩${Number(d).toLocaleString()}</strong>` },
                { data: 'supplier', render: d => this.escapeHtml(d || '-') },
                { data: 'is_received', render: (d,t,r) => {
                    return d ? `<span class="badge badge-soft-success"><i class="ri-checkbox-circle-line me-1"></i>입고 완료</span><br><small class="text-muted">${new Date(r.received_date).toLocaleDateString()}</small>`
                             : `<span class="badge badge-soft-warning"><i class="ri-time-line me-1"></i>입고 대기</span>`;
                }},
                { data: 'id', orderable: false, render: (d,t,r) => `
                    <div class="dropdown">
                        <button class="btn btn-soft-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="ri-more-fill"></i></button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            ${!r.is_received ? `
                            <li><button class="dropdown-item receive-purchase-btn" data-id="${d}" data-name="${this.escapeHtml(r.item_name)}"><i class="ri-inbox-fill align-bottom me-2 text-muted"></i> 입고 처리</button></li>
                            <li><button class="dropdown-item edit-purchase-btn" data-id="${d}"><i class="ri-pencil-fill align-bottom me-2 text-muted"></i> 수정</button></li>
                            <li><button class="dropdown-item delete-purchase-btn" data-id="${d}" data-name="${this.escapeHtml(r.item_name)}"><i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> 삭제</button></li>
                            ` : `<li><a class="dropdown-item" href="/supply/purchases/show?id=${d}"><i class="ri-eye-fill align-bottom me-2 text-muted"></i> 상세보기</a></li>`}
                        </ul>
                    </div>`
                }
            ],
            responsive: true,
            pageLength: 25,
            order: [[0, 'desc']],
            language: { url: '/assets/libs/datatables.net/i18n/Korean.json' },
            searching: false
        });
    }

    handleReceiveClick(e) {
        const btn = $(e.currentTarget);
        this.currentPurchaseId = btn.data('id');
        $('#receive-purchase-info').html(`<p><strong>품목:</strong> ${this.escapeHtml(btn.data('name'))}</p>`);
        this.receiveModal.show();
    }

    handleDeleteClick(e) {
        const btn = $(e.currentTarget);
        this.currentPurchaseId = btn.data('id');
        $('#delete-purchase-info').html(`<p><strong>품목:</strong> ${this.escapeHtml(btn.data('name'))}</p>`);
        this.deleteModal.show();
    }

    async confirmReception() {
        const receivedDate = $('#received-date').val();
        if (!receivedDate) {
            Toast.error('입고일을 선택해주세요.');
            return;
        }
        this.setButtonLoading('#confirm-receive-purchase-btn', '처리 중...');
        try {
            await this.apiCall(`${this.config.API_URL}/${this.currentPurchaseId}/mark-received`, {
                method: 'POST',
                body: JSON.stringify({ received_date: receivedDate })
            });
            Toast.success('입고 처리되었습니다.');
            this.receiveModal.hide();
            this.loadPurchases();
            this.loadStats();
        } catch (error) {
            this.handleApiError(error);
        } finally {
            this.resetButtonLoading('#confirm-receive-purchase-btn', '입고 처리');
        }
    }

    async confirmDeletion() {
        this.setButtonLoading('#confirm-delete-purchase-btn', '삭제 중...');
        try {
            await this.apiCall(`${this.config.API_URL}/${this.currentPurchaseId}`, { method: 'DELETE' });
            Toast.success('삭제되었습니다.');
            this.deleteModal.hide();
            this.loadPurchases();
            this.loadStats();
        } catch (error) {
            this.handleApiError(error);
        } finally {
            this.resetButtonLoading('#confirm-delete-purchase-btn', '삭제');
        }
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    async loadItems() {
        try {
            const result = await this.apiCall('/supply/items/active');
            this.items = result.data || [];
            this.renderItemOptions();
        } catch (error) {
            console.error('Error loading items:', error);
            Toast.error('품목 정보를 불러오는 중 오류가 발생했습니다.');
        }
    }

    renderItemOptions() {
        const itemSelect = $('#item-id');
        itemSelect.empty().append('<option value="">품목을 선택하세요...</option>');
        this.items.forEach(item => {
            itemSelect.append(`<option value="${item.id}">${this.escapeHtml(item.item_name)} (${this.escapeHtml(item.item_code)})</option>`);
        });
    }

    handleCreateClick() {
        this.currentPurchaseId = null;
        $('#purchase-form')[0].reset();
        $('#purchase-id').val('');
        $('#purchaseModalLabel').text('구매 등록');
        this.purchaseModal.show();
    }

    async handleEditClick(e) {
        this.currentPurchaseId = $(e.currentTarget).data('id');
        try {
            const result = await this.apiCall(`${this.config.API_URL}/${this.currentPurchaseId}`);
            const purchase = result.data;
            
            $('#purchase-id').val(purchase.id);
            $('#item-id').val(purchase.item_id);
            $('#purchase-date').val(purchase.purchase_date);
            $('#supplier').val(purchase.supplier);
            $('#quantity').val(purchase.quantity);
            $('#unit-price').val(purchase.unit_price);
            $('#notes').val(purchase.notes);
            
            this.calculateTotal();
            $('#purchaseModalLabel').text('구매 수정');
            this.purchaseModal.show();
        } catch (error) {
            this.handleApiError(error, '구매 정보를 불러오는 중 오류가 발생했습니다.');
        }
    }

    async handleFormSubmit(e) {
        e.preventDefault();
        const form = e.target;
        if (!form.checkValidity()) {
            e.stopPropagation();
            form.classList.add('was-validated');
            return;
        }

        const formData = {
            item_id: $('#item-id').val(),
            purchase_date: $('#purchase-date').val(),
            supplier: $('#supplier').val(),
            quantity: $('#quantity').val(),
            unit_price: $('#unit-price').val(),
            notes: $('#notes').val()
        };

        const url = this.currentPurchaseId ? `${this.config.API_URL}/${this.currentPurchaseId}` : this.config.API_URL;
        const method = this.currentPurchaseId ? 'PUT' : 'POST';

        this.setButtonLoading('#save-purchase-btn', '저장 중...');
        try {
            await this.apiCall(url, {
                method: method,
                body: JSON.stringify(formData)
            });
            Toast.success(`구매가 성공적으로 ${this.currentPurchaseId ? '수정' : '등록'}되었습니다.`);
            this.purchaseModal.hide();
            this.loadPurchases();
            this.loadStats();
        } catch (error) {
            this.handleApiError(error);
        } finally {
            this.resetButtonLoading('#save-purchase-btn', '저장');
        }
    }

    calculateTotal() {
        const quantity = parseFloat($('#quantity').val()) || 0;
        const unitPrice = parseFloat($('#unit-price').val()) || 0;
        const total = quantity * unitPrice;
        $('#total-amount').val(total > 0 ? `₩${total.toLocaleString()}` : '');
    }

    escapeHtml(text) {
        if (text === null || text === undefined) {
            return '';
        }
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    handleApiError(error, defaultMessage = '작업 중 오류가 발생했습니다.') {
        console.error('API Error:', error);
        if (error && error.message) {
            Toast.error(error.message);
        } else {
            Toast.error(defaultMessage);
        }
    }
}

new SupplyPurchasesIndexPage();