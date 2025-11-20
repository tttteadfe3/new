/**
 * 재고 현황 페이지 스크립트
 */

class SupplyStocksPage extends BasePage {
    constructor() {
        super({
            API_URL: '/supply/stocks'
        });

        this.dataTable = null;
    }

    setupEventListeners() {
        $('#filter-category, #filter-stock-status').on('change', () => {
            this.loadStocks();
        });

        $('#search-input').on('keyup', this.debounce(() => {
            this.loadStocks();
        }, 300));

        $('#refresh-btn').on('click', () => {
            this.loadStocks();
        });

        $(document).on('click', '.view-detail-btn', async (e) => {
            const stockId = $(e.currentTarget).data('id');
            const modal = $('#stockDetailModal');
            const content = modal.find('#stock-detail-content');

            content.html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
            modal.modal('show');

            try {
                const result = await this.apiCall(`${this.config.API_URL}/${stockId}`);
                if (result.success) {
                    this.renderStockDetails(result.data);
                } else {
                    content.html(`<div class="alert alert-danger">${result.message}</div>`);
                }
            } catch (error) {
                console.error('Error loading stock details:', error);
                content.html('<div class="alert alert-danger">상세 정보를 불러오는 중 오류가 발생했습니다.</div>');
            }
        });
    }

    loadInitialData() {
        this.loadCategories();
        this.initializeDataTable();
        this.loadStocks();
    }

    async loadCategories() {
        try {
            const result = await this.apiCall('/supply/categories');
            const select = $('#filter-category');
            result.data.forEach((category) => {
                select.append(`<option value="${category.id}">${category.name}</option>`);
            });
        } catch (error) {
            console.error('Error loading categories:', error);
        }
    }

    async loadStocks() {
        try {
            const params = {
                category_id: $('#filter-category').val(),
                stock_status: $('#filter-stock-status').val(),
                search: $('#search-input').val()
            };

            const queryString = new URLSearchParams(params).toString();
            const result = await this.apiCall(`${this.config.API_URL}?${queryString}`);

            this.dataTable.clear().rows.add(result.data || []).draw();
        } catch (error) {
            console.error('Error loading stocks:', error);
            Toast.error('재고 정보를 불러오는 중 오류가 발생했습니다.');
        }
    }

    initializeDataTable() {
        this.dataTable = $('#stocks-table').DataTable({
            processing: true,
            serverSide: false,
            columns: [
                { data: 'item_code' },
                { data: 'item_name' },
                { data: 'category_name' },
                { data: 'unit' },
                { 
                    data: 'current_stock',
                    render: (data) => data ? parseInt(data).toLocaleString() : '0'
                },
                {
                    data: null,
                    orderable: false,
                    render: (data, type, row) => `
                        <button class="btn btn-sm btn-info view-detail-btn" data-id="${row.id}">
                            <i class="ri-eye-line"></i> 상세
                        </button>
                    `
                }
            ],
            order: [[0, 'asc']],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/ko.json'
            },
            searching: false
        });
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

    renderStockDetails(data) {
        const { stock, history } = data;
        const content = $('#stock-detail-content');

        let detailsHtml = `
            <h5>기본 정보</h5>
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <th>품목명</th>
                        <td>${stock.item_name} (${stock.item_code})</td>
                        <th>분류</th>
                        <td>${stock.category_name || '미지정'}</td>
                    </tr>
                    <tr>
                        <th>현재 재고</th>
                        <td>${parseInt(stock.current_stock).toLocaleString()} ${stock.unit}</td>
                        <th>단위</th>
                        <td>${stock.unit}</td>
                    </tr>
                </tbody>
            </table>
            <h5 class="mt-4">재고 변동 이력</h5>
        `;

        if (history && history.length > 0) {
            detailsHtml += `
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>일자</th>
                            <th>유형</th>
                            <th>수량</th>
                            <th>상세 내용</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            history.forEach(item => {
                const badgeClass = item.type === 'purchase' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
                const typeText = item.type === 'purchase' ? '입고' : '출고';
                detailsHtml += `
                    <tr>
                        <td>${item.date}</td>
                        <td><span class="badge ${badgeClass}">${typeText}</span></td>
                        <td>${parseInt(item.quantity).toLocaleString()}</td>
                        <td>${item.description}</td>
                    </tr>
                `;
            });
            detailsHtml += `
                    </tbody>
                </table>
            `;
        } else {
            detailsHtml += '<p>재고 변동 이력이 없습니다.</p>';
        }

        content.html(detailsHtml);
    }
}

new SupplyStocksPage();
