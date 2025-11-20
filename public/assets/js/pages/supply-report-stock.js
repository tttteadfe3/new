/**
 * Supply Stock Report Page
 */

class SupplyReportStockPage extends BasePage {
    constructor() {
        super({
            API_URL: '/supply/reports'
        });
        
        this.dataTable = null;
    }

    setupEventListeners() {
        $('#filter-form').on('submit', (e) => {
            e.preventDefault();
            this.loadReportData();
        });

        $('#reset-filter-btn').on('click', () => {
            window.location.href = '/supply/reports/stock';
        });

        $('#export-report-btn').on('click', () => this.exportReport());
    }

    loadInitialData() {
        this.initializeDataTable();
        this.loadReportData();
    }

    async loadReportData() {
        try {
            const params = {
                category_id: $('#category-filter').val(),
                stock_status: $('#stock-status-filter').val()
            };

            const queryString = new URLSearchParams(params).toString();
            const result = await this.apiCall(`${this.config.API_URL}/stock?${queryString}`);

            this.dataTable.clear().rows.add(result.data || []).draw();
        } catch (error) {
            console.error('Error loading report data:', error);
            Toast.error('보고서 데이터를 불러오는 중 오류가 발생했습니다.');
        }
    }

    initializeDataTable() {
        this.dataTable = new DataTable('#stock-report-table', {
            responsive: true,
            pageLength: 25,
            order: [[6, 'asc']], // Sort by current stock
            language: {
                url: '//cdn.datatables.net/plug-ins/2.3.5/i18n/ko.json'
            },
            searching: false
        });
    }

    exportReport() {
        const params = new URLSearchParams({
            report_type: 'stock',
            category_id: $('#category-filter').val(),
            stock_status: $('#stock-status-filter').val()
        });
        
        window.location.href = `${this.config.API_URL}/export?${params.toString()}`;
    }
}

new SupplyReportStockPage();
