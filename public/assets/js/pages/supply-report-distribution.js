/**
 * Supply Distribution Report Page
 */

class SupplyReportDistributionPage extends BasePage {
    constructor() {
        super({
            apiBaseUrl: '/supply/reports'
        });
        
        this.distributionTable = null;
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

        // Year selector change
        const yearFilter = document.getElementById('year-filter');
        if (yearFilter) {
            yearFilter.addEventListener('change', () => {
                const form = document.getElementById('filter-form');
                if (form) {
                    form.submit();
                }
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
        const tableElement = document.getElementById('distribution-report-table');
        if (tableElement && typeof DataTable !== 'undefined') {
            this.distributionTable = new DataTable('#distribution-report-table', {
                responsive: true,
                pageLength: 25,
                order: [[0, 'desc']],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ko.json'
                }
            });
        }
    }

    applyFilters() {
        const form = document.getElementById('filter-form');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        
        window.location.href = '/supply/reports/distribution?' + params.toString();
    }

    exportReport() {
        const form = document.getElementById('filter-form');
        const formData = new FormData(form);
        formData.append('report_type', 'distribution');
        
        const params = new URLSearchParams(formData);
        window.location.href = this.config.apiBaseUrl + '/export?' + params.toString();
    }
}

// 전역 인스턴스 생성
new SupplyReportDistributionPage();
