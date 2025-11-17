/**
 * Supply Purchases Management JavaScript
 */

class SupplyPurchasesPage extends BasePage {
    constructor() {
        super({
            apiBaseUrl: '/supply/purchases'
        });
        
        this.currentReceivePurchaseId = null;
        this.currentDeletePurchaseId = null;
        this.receivePurchaseModal = null;
        this.deletePurchaseModal = null;
    }

    setupEventListeners() {
        // 입고 처리 버튼
        document.querySelectorAll('.receive-purchase-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                this.currentReceivePurchaseId = btn.dataset.id;
                const itemName = btn.dataset.name;
                
                document.getElementById('receive-purchase-info').innerHTML = `
                    <p><strong>품목:</strong> ${itemName}</p>
                    <p class="text-muted">이 구매를 입고 처리하시겠습니까?</p>
                `;
                
                this.receivePurchaseModal.show();
            });
        });

        // 입고 처리 확인 버튼
        const confirmReceiveBtn = document.getElementById('confirm-receive-purchase-btn');
        if (confirmReceiveBtn) {
            confirmReceiveBtn.addEventListener('click', () => this.handleReceivePurchase());
        }

        // 삭제 버튼
        document.querySelectorAll('.delete-purchase-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                this.currentDeletePurchaseId = btn.dataset.id;
                const itemName = btn.dataset.name;
                
                document.getElementById('delete-purchase-info').innerHTML = `
                    <p><strong>품목:</strong> ${itemName}</p>
                `;
                
                this.deletePurchaseModal.show();
            });
        });

        // 삭제 확인 버튼
        const confirmDeleteBtn = document.getElementById('confirm-delete-purchase-btn');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', () => this.handleDeletePurchase());
        }
    }

    loadInitialData() {
        this.initializeDataTable();
        this.initializeModals();
        this.animateCounters();
    }

    initializeDataTable() {
        const table = document.getElementById('purchases-table');
        if (table && typeof $.fn.DataTable !== 'undefined') {
            $(table).DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ko.json'
                },
                order: [[0, 'desc']], // 구매일 기준 내림차순
                columnDefs: [
                    { orderable: false, targets: [7] } // 작업 열은 정렬 불가
                ]
            });

            // 검색 기능
            const searchInput = document.getElementById('search-purchases');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    $(table).DataTable().search(this.value).draw();
                });
            }

            // 상태 필터
            const filterStatus = document.getElementById('filter-status');
            if (filterStatus) {
                filterStatus.addEventListener('change', function() {
                    const status = this.value;
                    if (status === 'received') {
                        $(table).DataTable().column(6).search('입고 완료').draw();
                    } else if (status === 'pending') {
                        $(table).DataTable().column(6).search('입고 대기').draw();
                    } else {
                        $(table).DataTable().column(6).search('').draw();
                    }
                });
            }
        }
    }

    initializeModals() {
        this.receivePurchaseModal = new bootstrap.Modal(document.getElementById('receivePurchaseModal'));
        this.deletePurchaseModal = new bootstrap.Modal(document.getElementById('deletePurchaseModal'));
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
                `${this.config.apiBaseUrl}/${this.currentReceivePurchaseId}/mark-received`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        received_date: receivedDate
                    })
                }
            );

            Toast.success('입고 처리가 완료되었습니다.');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } catch (error) {
            console.error('Error:', error);
            Toast.error('오류: ' + (error.message || '입고 처리에 실패했습니다.'));
            this.resetButtonLoading('#confirm-receive-purchase-btn', '입고 처리');
        }
    }

    async handleDeletePurchase() {
        this.setButtonLoading('#confirm-delete-purchase-btn', '삭제 중...');

        try {
            await this.apiCall(
                `${this.config.apiBaseUrl}/${this.currentDeletePurchaseId}`,
                {
                    method: 'DELETE'
                }
            );

            Toast.success('구매가 성공적으로 삭제되었습니다.');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } catch (error) {
            console.error('Error:', error);
            Toast.error('오류: ' + (error.message || '구매 삭제에 실패했습니다.'));
            this.resetButtonLoading('#confirm-delete-purchase-btn', '삭제');
        }
    }

    animateCounters() {
        const counters = document.querySelectorAll('.counter-value');
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target').replace(/,/g, ''));
            const duration = 1000;
            const step = target / (duration / 16);
            let current = 0;

            const updateCounter = () => {
                current += step;
                if (current < target) {
                    counter.textContent = Math.floor(current).toLocaleString('ko-KR');
                    requestAnimationFrame(updateCounter);
                } else {
                    counter.textContent = target.toLocaleString('ko-KR');
                }
            };

            updateCounter();
        });
    }
}

// 전역 인스턴스 생성
new SupplyPurchasesPage();
