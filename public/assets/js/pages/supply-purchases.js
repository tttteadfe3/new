/**
 * Supply Purchases Management JavaScript
 */

class SupplyPurchasesPage extends BasePage {
    constructor() {
        super({
            API_URL: '/supply/purchases'
        });
        
        this.dataTable = null;
        this.currentReceivePurchaseId = null;
        this.currentDeletePurchaseId = null;
        this.receivePurchaseModal = null;
        this.deletePurchaseModal = null;
    }

    setupEventListeners() {
        $(document).on('click', '.receive-purchase-btn', (e) => this.showReceiveModal(e));
        $(document).on('click', '.delete-purchase-btn', (e) => this.showDeleteModal(e));

        $('#confirm-receive-purchase-btn').on('click', () => this.handleReceivePurchase());
        $('#confirm-delete-purchase-btn').on('click', () => this.handleDeletePurchase());

        $('#search-purchases, #filter-status').on('keyup change', this.debounce(() => {
            this.loadPurchases();
        }, 300));
    }

    loadInitialData() {
        this.initializeDataTable();
        this.loadPurchases();
        this.initializeModals();
        this.animateCounters();
    }

    async loadPurchases() {
        try {
            const params = {
                search: $('#search-purchases').val(),
                status: $('#filter-status').val(),
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
        const table = document.getElementById('purchases-table');
        if (table && typeof $.fn.DataTable !== 'undefined') {
            this.dataTable = $(table).DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/2.3.5/i18n/ko.json'
                },
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [7] }
                ],
                searching: false,
                // ... columns definition ...
            });
        }
    }

    initializeModals() {
        this.receivePurchaseModal = new bootstrap.Modal(document.getElementById('receivePurchaseModal'));
        this.deletePurchaseModal = new bootstrap.Modal(document.getElementById('deletePurchaseModal'));
    }

    showReceiveModal(e) {
        const btn = e.currentTarget;
        this.currentReceivePurchaseId = btn.dataset.id;
        const itemName = btn.dataset.name;

        document.getElementById('receive-purchase-info').innerHTML = `
            <p><strong>품목:</strong> ${itemName}</p>
            <p class="text-muted">이 구매를 입고 처리하시겠습니까?</p>
        `;

        this.receivePurchaseModal.show();
    }

    showDeleteModal(e) {
        const btn = e.currentTarget;
        this.currentDeletePurchaseId = btn.dataset.id;
        const itemName = btn.dataset.name;

        document.getElementById('delete-purchase-info').innerHTML = `
            <p><strong>품목:</strong> ${itemName}</p>
        `;

        this.deletePurchaseModal.show();
    }

    async handleReceivePurchase() {
        const receivedDate = document.getElementById('received-date').value;
        
        if (!receivedDate) {
            Toast.warning('입고일을 선택해주세요.');
            return;
        }

        this.setButtonLoading('#confirm-receive-purchase-btn', '처리 중...');

        try {
            await this.apiCall(
                `${this.config.API_URL}/${this.currentReceivePurchaseId}/mark-received`,
                {
                    method: 'POST',
                    body: { received_date: receivedDate }
                }
            );

            Toast.success('입고 처리가 완료되었습니다.');
            this.receivePurchaseModal.hide();
            this.loadPurchases();
        } catch (error) {
            console.error('Error:', error);
            Toast.error('오류: ' + (error.message || '입고 처리에 실패했습니다.'));
        } finally {
            this.resetButtonLoading('#confirm-receive-purchase-btn', '입고 처리');
        }
    }

    async handleDeletePurchase() {
        this.setButtonLoading('#confirm-delete-purchase-btn', '삭제 중...');

        try {
            await this.apiCall(
                `${this.config.API_URL}/${this.currentDeletePurchaseId}`,
                {
                    method: 'DELETE'
                }
            );

            Toast.success('구매가 성공적으로 삭제되었습니다.');
            this.deletePurchaseModal.hide();
            this.loadPurchases();
        } catch (error) {
            console.error('Error:', error);
            Toast.error('오류: ' + (error.message || '구매 삭제에 실패했습니다.'));
        } finally {
            this.resetButtonLoading('#confirm-delete-purchase-btn', '삭제');
        }
    }

    animateCounters() {
        // ... (counter animation logic)
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

// 전역 인스턴스 생성
new SupplyPurchasesPage();
