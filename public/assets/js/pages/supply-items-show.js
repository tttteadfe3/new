/**
 * 지급품 품목 상세 JavaScript
 */

class SupplyItemShowPage extends BasePage {
    constructor() {
        super({
            API_URL: '/supply/items'
        });
        
        this.itemId = document.getElementById('item-id')?.value;
    }

    setupEventListeners() {
        // 필요시 추가
    }

    loadInitialData() {
        this.loadItemDetails();
        this.loadStockInfo();
        this.loadRecentPurchases();
        this.loadRecentDistributions();
    }

    async loadItemDetails() {
        try {
            const data = await this.apiCall(`${this.config.API_URL}/${this.itemId}`);
            const item = data.data;
            
            const container = document.getElementById('item-details-container');
            if (container && item) {
                const statusBadge = item.is_active == 1 ? 
                    '<span class="badge bg-success">활성</span>' : 
                    '<span class="badge bg-secondary">비활성</span>';
                
                container.innerHTML = `
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <th style="width: 200px;">품목 코드</th>
                                <td>${this.escapeHtml(item.item_code)}</td>
                            </tr>
                            <tr>
                                <th>품목명</th>
                                <td>${this.escapeHtml(item.item_name)}</td>
                            </tr>
                            <tr>
                                <th>분류</th>
                                <td>${this.escapeHtml(item.category_name || '-')}</td>
                            </tr>
                            <tr>
                                <th>단위</th>
                                <td>${this.escapeHtml(item.unit)}</td>
                            </tr>
                            <tr>
                                <th>설명</th>
                                <td>${this.escapeHtml(item.description || '-')}</td>
                            </tr>
                            <tr>
                                <th>상태</th>
                                <td>${statusBadge}</td>
                            </tr>
                            <tr>
                                <th>등록일</th>
                                <td>${item.created_at ? new Date(item.created_at).toLocaleString('ko-KR') : '-'}</td>
                            </tr>
                            <tr>
                                <th>수정일</th>
                                <td>${item.updated_at ? new Date(item.updated_at).toLocaleString('ko-KR') : '-'}</td>
                            </tr>
                        </tbody>
                    </table>
                `;
            }
        } catch (error) {
            console.error('Error loading item details:', error);
            const container = document.getElementById('item-details-container');
            if (container) {
                container.innerHTML = '<div class="alert alert-danger">품목 정보를 불러오는 중 오류가 발생했습니다.</div>';
            }
        }
    }

    async loadStockInfo() {
        const container = document.getElementById('stock-info-container');
        if (!container) return;

        try {
            // 재고 정보 API 호출 (추후 구현)
            container.innerHTML = `
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="p-3">
                            <h5 class="text-muted mb-2">총 구매량</h5>
                            <h3 class="mb-0">-</h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3">
                            <h5 class="text-muted mb-2">총 지급량</h5>
                            <h3 class="mb-0">-</h3>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3">
                            <h5 class="text-muted mb-2">현재 재고</h5>
                            <h3 class="mb-0 text-primary">-</h3>
                        </div>
                    </div>
                </div>
            `;
        } catch (error) {
            console.error('Error loading stock info:', error);
            container.innerHTML = '<div class="alert alert-warning">재고 정보를 불러올 수 없습니다.</div>';
        }
    }

    async loadRecentPurchases() {
        const container = document.getElementById('purchases-container');
        if (!container) return;

        try {
            // 구매 내역 API 호출 (추후 구현)
            container.innerHTML = '<p class="text-muted text-center py-3">구매 내역이 없습니다.</p>';
        } catch (error) {
            console.error('Error loading purchases:', error);
            container.innerHTML = '<div class="alert alert-warning">구매 내역을 불러올 수 없습니다.</div>';
        }
    }

    async loadRecentDistributions() {
        const container = document.getElementById('distributions-container');
        if (!container) return;

        try {
            // 지급 내역 API 호출 (추후 구현)
            container.innerHTML = '<p class="text-muted text-center py-3">지급 내역이 없습니다.</p>';
        } catch (error) {
            console.error('Error loading distributions:', error);
            container.innerHTML = '<div class="alert alert-warning">지급 내역을 불러올 수 없습니다.</div>';
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

    new SupplyItemShowPage();
