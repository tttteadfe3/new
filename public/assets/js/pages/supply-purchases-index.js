/**
 * Supply Purchases Index JavaScript
 */

class SupplyPurchasesIndexPage extends BasePage {
    constructor() {
        super({
            apiBaseUrl: '/api/supply/purchases'
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
    }

    loadInitialData() {
        this.loadStats();
        this.initializeDataTable();

        this.receiveModal = new bootstrap.Modal(document.getElementById('receivePurchaseModal'));
        this.deleteModal = new bootstrap.Modal(document.getElementById('deletePurchaseModal'));
    }

    async loadStats() {
        const statsContainer = $('#stats-container');
        const alertContainer = $('#pending-purchases-alert-container');
        try {
            const response = await this.apiCall(`${this.config.apiBaseUrl}/statistics`);
            const stats = response.data;

            statsContainer.html(`
                <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">총 구매 건수</p></div><div class="flex-shrink-0"><span class="avatar-title bg-success-subtle rounded fs-3"><i class="bx bx-shopping-bag text-success"></i></span></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0"><span class="counter-value">${stats.total_purchases.toLocaleString()}</span>건</h4></div></div></div></div></div>
                <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">총 구매 수량</p></div><div class="flex-shrink-0"><span class="avatar-title bg-info-subtle rounded fs-3"><i class="bx bx-cube text-info"></i></span></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0"><span class="counter-value">${stats.total_quantity.toLocaleString()}</span>개</h4></div></div></div></div></div>
                <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">총 구매 금액</p></div><div class="flex-shrink-0"><span class="avatar-title bg-warning-subtle rounded fs-3"><i class="bx bx-won text-warning"></i></span></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0">₩<span class="counter-value">${stats.total_amount.toLocaleString()}</span></h4></div></div></div></div></div>
                <div class="col-xl-3 col-md-6"><div class="card card-animate"><div class="card-body"><div class="d-flex align-items-center"><div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">미입고 건수</p></div><div class="flex-shrink-0"><span class="avatar-title bg-danger-subtle rounded fs-3"><i class="bx bx-time text-danger"></i></span></div></div><div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0"><span class="counter-value text-danger">${stats.pending_purchases.toLocaleString()}</span>건</h4></div></div></div></div></div>
            `);

            if(stats.pending_purchases > 0) {
                alertContainer.html(`
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                <i class="ri-alert-line me-2"></i>
                                <strong>입고 대기 중인 구매가 ${stats.pending_purchases}건 있습니다.</strong>
                                <a href="/supply/purchases/receive" class="alert-link ms-2">입고 처리하기</a>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        </div>
                    </div>
                `);
            }

        } catch (error) {
            statsContainer.html('<p class="text-danger">통계 정보를 불러오는데 실패했습니다.</p>');
        }
    }

    initializeDataTable() {
        this.dataTable = $('#purchases-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: this.config.apiBaseUrl,
                type: 'GET'
            },
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
            language: { url: '/assets/libs/datatables.net/i18n/Korean.json' }
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
            await this.apiCall(`${this.config.apiBaseUrl}/${this.currentPurchaseId}/mark-received`, {
                method: 'POST',
                body: JSON.stringify({ received_date: receivedDate })
            });
            Toast.success('입고 처리되었습니다.');
            this.receiveModal.hide();
            this.dataTable.ajax.reload();
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
            await this.apiCall(`${this.config.apiBaseUrl}/${this.currentPurchaseId}`, { method: 'DELETE' });
            Toast.success('삭제되었습니다.');
            this.deleteModal.hide();
            this.dataTable.ajax.reload();
            this.loadStats();
        } catch (error) {
            this.handleApiError(error);
        } finally {
            this.resetButtonLoading('#confirm-delete-purchase-btn', '삭제');
        }
    }
}

new SupplyPurchasesIndexPage();
