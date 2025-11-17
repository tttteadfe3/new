/**
 * Supply Stock Report Page
 */

class SupplyReportStockPage extends BasePage {
    constructor() {
        super({
            apiBaseUrl: '/supply/reports'
        });
        
        this.stockTable = null;
    }

    setupEventListeners() {
        // Filter form submission
        const filterForm = document.getElementById('filter-form');
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.applyFilters();
            });
        }

        // Reset filter button
        const resetBtn = document.getElementById('reset-filter-btn');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                window.location.href = '/supply/reports/stock';
            });
        }

        // Export button
        const exportBtn = document.getElementById('export-report-btn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportReport());
        }
    }

    loadInitialData() {
        this.initDataTable();
    }

    initDataTable() {
        const tableElement = document.getElementById('stock-report-table');
        if (tableElement && typeof DataTable !== 'undefined') {
            this.stockTable = new DataTable('#stock-report-table', {
                responsive: true,
                pageLength: 25,
                order: [[6, 'asc']], // Sort by current stock
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ko.json'
                }
            });
        }
    }

    applyFilters() {
        const form = document.getElementById('filter-form');
        const formData = new FormData(form);
        
        // Handle stock status filter
        const stockStatus = formData.get('stock_status');
        if (stockStatus) {
            formData.delete('stock_status');
            if (stockStatus === 'low_stock') {
                formData.set('low_stock', '1');
            } else if (stockStatus === 'out_of_stock') {
                formData.set('out_of_stock', '1');
            }
        }
        
        const params = new URLSearchParams(formData);
        window.location.href = '/supply/reports/stock?' + params.toString();
    }

    exportReport() {
        const form = document.getElementById('filter-form');
        const formData = new FormData(form);
        formData.append('report_type', 'stock');
        
        // Handle stock status filter
        const stockStatus = formData.get('stock_status');
        if (stockStatus) {
            formData.delete('stock_status');
            if (stockStatus === 'low_stock') {
                formData.set('low_stock', '1');
            } else if (stockStatus === 'out_of_stock') {
                formData.set('out_of_stock', '1');
            }
        }
        
        const params = new URLSearchParams(formData);
        window.location.href = this.config.apiBaseUrl + '/export?' + params.toString();
    }
}

// 전역 인스턴스 생성
new SupplyReportStockPage();
