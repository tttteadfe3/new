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
        this.dataTable = null;
    }

    setupEventListeners() {
        $(document).on('click', '.receive-purchase-btn', (e) => this.handleReceiveClick(e));
        $(document).on('click', '.delete-purchase-btn', (e) => this.handleDeleteClick(e));
        $('#confirm-receive-purchase-btn').on('click', () => this.confirmReception());
        $('#confirm-delete-purchase-btn').on('click', () => this.confirmDeletion());

        // 검색 및 필터 이벤트
        $('#search-input, #filter-status').on('keyup change', this.debounce(() => {
            this.loadPurchases();
        }, 300));
    }

    loadInitialData() {
        this.loadStats();
        this.initializeDataTable();
        this.loadPurchases();

        this.receiveModal = new bootstrap.Modal(document.getElementById('receivePurchaseModal'));
        this.deleteModal = new bootstrap.Modal(document.getElementById('deletePurchaseModal'));
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
                            <li><a class="dropdown-item" href="/supply/purchases/edit?id=${d}"><i class="ri-pencil-fill align-bottom me-2 text-muted"></i> 수정</a></li>
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
}

new SupplyPurchasesIndexPage();
