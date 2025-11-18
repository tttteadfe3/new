/**
 * 구매 입고 처리 페이지
 */

class SupplyPurchasesReceivePage extends BasePage {
    constructor() {
        super({
            API_URL: '/supply/purchases'
        });
        
        this.pendingPurchases = [];
        this.currentPurchaseIds = [];
        this.isBulkReceive = false;
        this.receiveModal = null;
    }

    setupEventListeners() {
        // 모달 초기화
        const modalElement = document.getElementById('receiveModal');
        if (modalElement) {
            this.receiveModal = new bootstrap.Modal(modalElement);
        }

        // 입고 처리 확인 버튼
        document.getElementById('confirm-receive-btn')?.addEventListener('click', () => {
            this.handleReceiveConfirm();
        });
    }

    async loadInitialData() {
        await this.loadPendingPurchases();
    }

    async loadPendingPurchases() {
        try {
            const data = await this.apiCall(`${this.config.API_URL}?is_received=0`);
            this.pendingPurchases = data.data.purchases || [];
            this.renderPendingPurchases();
        } catch (error) {
            console.error('Error loading pending purchases:', error);
            Toast.error('입고 대기 목록을 불러오는 중 오류가 발생했습니다.');
        }
    }

    renderPendingPurchases() {
        const container = document.getElementById('pending-purchases-container');
        if (!container) return;

        if (this.pendingPurchases.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="ri-checkbox-circle-line fs-1 text-success"></i>
                    <p class="mt-3 text-muted">입고 대기 중인 구매가 없습니다.</p>
                    <a href="/supply/purchases" class="btn btn-primary">
                        <i class="ri-arrow-left-line me-1"></i> 구매 목록으로 돌아가기
                    </a>
                </div>
            `;
            return;
        }

        const html = `
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="select-all">
                    <label class="form-check-label" for="select-all">
                        전체 선택
                    </label>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-nowrap table-striped-columns mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" style="width: 50px;">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="select-all-header">
                                </div>
                            </th>
                            <th scope="col">구매일</th>
                            <th scope="col">품목</th>
                            <th scope="col">수량</th>
                            <th scope="col">단가</th>
                            <th scope="col">총액</th>
                            <th scope="col">공급업체</th>
                            <th scope="col">작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${this.pendingPurchases.map(purchase => `
                            <tr>
                                <td>
                                    <div class="form-check">
                                        <input class="form-check-input purchase-checkbox" type="checkbox" 
                                               value="${purchase.id}" 
                                               data-name="${this.escapeHtml(purchase.item_name)}">
                                    </div>
                                </td>
                                <td>${this.formatDate(purchase.purchase_date, 'date')}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="fs-14 mb-0">${this.escapeHtml(purchase.item_name)}</h6>
                                            <p class="text-muted mb-0 fs-12">${this.escapeHtml(purchase.item_code)}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end">${this.formatNumber(purchase.quantity)}</td>
                                <td class="text-end">₩${this.formatNumber(purchase.unit_price)}</td>
                                <td class="text-end">
                                    <strong>₩${this.formatNumber(purchase.quantity * purchase.unit_price)}</strong>
                                </td>
                                <td>${this.escapeHtml(purchase.supplier || '-')}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-success receive-single-btn" 
                                            data-id="${purchase.id}" 
                                            data-name="${this.escapeHtml(purchase.item_name)}">
                                        <i class="ri-inbox-line me-1"></i> 입고
                                    </button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;

        container.innerHTML = html;
        this.bindReceiveEvents();
    }

    bindReceiveEvents() {
        const selectAllCheckbox = document.getElementById('select-all');
        const selectAllHeaderCheckbox = document.getElementById('select-all-header');
        const purchaseCheckboxes = document.querySelectorAll('.purchase-checkbox');
        const bulkReceiveBtn = document.getElementById('bulk-receive-btn');

        // 전체 선택
        const updateSelectAll = () => {
            const checkedCount = document.querySelectorAll('.purchase-checkbox:checked').length;
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = checkedCount === purchaseCheckboxes.length && checkedCount > 0;
            }
            if (selectAllHeaderCheckbox) {
                selectAllHeaderCheckbox.checked = selectAllCheckbox?.checked || false;
            }
            if (bulkReceiveBtn) {
                bulkReceiveBtn.disabled = checkedCount === 0;
            }
        };

        selectAllCheckbox?.addEventListener('change', function() {
            purchaseCheckboxes.forEach(cb => cb.checked = this.checked);
            updateSelectAll();
        });

        selectAllHeaderCheckbox?.addEventListener('change', function() {
            purchaseCheckboxes.forEach(cb => cb.checked = this.checked);
            if (selectAllCheckbox) selectAllCheckbox.checked = this.checked;
            updateSelectAll();
        });

        purchaseCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateSelectAll);
        });

        // 개별 입고 처리
        document.querySelectorAll('.receive-single-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const purchaseId = btn.dataset.id;
                const itemName = btn.dataset.name;
                this.showReceiveModal([purchaseId], false, [itemName]);
            });
        });

        // 일괄 입고 처리
        bulkReceiveBtn?.addEventListener('click', () => {
            const checkedBoxes = document.querySelectorAll('.purchase-checkbox:checked');
            const purchaseIds = Array.from(checkedBoxes).map(cb => cb.value);
            const itemNames = Array.from(checkedBoxes).map(cb => cb.dataset.name);
            this.showReceiveModal(purchaseIds, true, itemNames);
        });
    }

    showReceiveModal(purchaseIds, isBulk, itemNames) {
        this.currentPurchaseIds = purchaseIds;
        this.isBulkReceive = isBulk;

        const infoDiv = document.getElementById('receive-info');
        if (infoDiv) {
            if (isBulk) {
                infoDiv.innerHTML = `
                    <p><strong>선택된 구매:</strong> ${purchaseIds.length}건</p>
                    <ul class="mb-0">
                        ${itemNames.slice(0, 5).map(name => `<li>${this.escapeHtml(name)}</li>`).join('')}
                        ${itemNames.length > 5 ? `<li class="text-muted">외 ${itemNames.length - 5}건...</li>` : ''}
                    </ul>
                    <p class="text-muted mt-2">선택한 모든 구매를 입고 처리하시겠습니까?</p>
                `;
            } else {
                infoDiv.innerHTML = `
                    <p><strong>품목:</strong> ${this.escapeHtml(itemNames[0])}</p>
                    <p class="text-muted">이 구매를 입고 처리하시겠습니까?</p>
                `;
            }
        }

        this.receiveModal?.show();
    }

    async handleReceiveConfirm() {
        const receivedDate = document.getElementById('received-date')?.value;
        
        if (!receivedDate) {
            Toast.error('입고일을 선택해주세요.');
            return;
        }

        this.setButtonLoading('#confirm-receive-btn', '처리 중...');

        try {
            if (this.isBulkReceive) {
                // 일괄 입고 처리
                const result = await this.apiCall(`${this.config.API_URL}/bulk-receive`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        purchase_ids: this.currentPurchaseIds,
                        received_date: receivedDate
                    })
                });

                if (result.success) {
                    Toast.success(`입고 처리가 완료되었습니다.\n성공: ${result.data.success_count}건, 실패: ${result.data.failed_count}건`);
                    if (result.data.errors.length > 0) {
                        console.error('Errors:', result.data.errors);
                    }
                    this.receiveModal?.hide();
                    await this.loadPendingPurchases();
                } else {
                    Toast.error(result.message || '입고 처리에 실패했습니다.');
                }
            } else {
                // 개별 입고 처리
                const result = await this.apiCall(`${this.config.API_URL}/${this.currentPurchaseIds[0]}/mark-received`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        received_date: receivedDate
                    })
                });

                if (result.success) {
                    Toast.success('입고 처리가 완료되었습니다.');
                    this.receiveModal?.hide();
                    await this.loadPendingPurchases();
                } else {
                    Toast.error(result.message || '입고 처리에 실패했습니다.');
                }
            }
        } catch (error) {
            console.error('Error:', error);
            Toast.error('서버 오류가 발생했습니다.');
        } finally {
            this.resetButtonLoading('#confirm-receive-btn', '입고 처리');
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatNumber(num) {
        return new Intl.NumberFormat('ko-KR').format(num);
    }

    formatDate(dateString, format = 'datetime') {
        const date = new Date(dateString);
        if (format === 'date') {
            return date.toLocaleDateString('ko-KR');
        }
        return date.toLocaleString('ko-KR');
    }
}

// 인스턴스 생성
new SupplyPurchasesReceivePage();
