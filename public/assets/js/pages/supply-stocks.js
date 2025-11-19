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

        $(document).on('click', '.view-detail-btn', (e) => {
            const itemId = $(e.currentTarget).data('id');
            // 상세 정보 로드 및 모달 표시
            $('#stockDetailModal').modal('show');
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
                { data: 'last_received_at' },
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
}

new SupplyStocksPage();
