/**
 * Supply Distribution Report Page
 */

class SupplyReportDistributionPage extends BasePage {
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

        $('#year-filter').on('change', () => {
            this.loadReportData();
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
                year: $('#year-filter').val(),
                month: $('#month-filter').val(),
                department_id: $('#department-filter').val(),
                item_id: $('#item-filter').val()
            };

            const queryString = new URLSearchParams(params).toString();
            const result = await this.apiCall(`${this.config.API_URL}/distribution?${queryString}`);

            this.dataTable.clear().rows.add(result.data || []).draw();
        } catch (error) {
            console.error('Error loading report data:', error);
            Toast.error('보고서 데이터를 불러오는 중 오류가 발생했습니다.');
        }
    }

    initializeDataTable() {
        this.dataTable = new DataTable('#distribution-report-table', {
            responsive: true,
            pageLength: 25,
            order: [[0, 'desc']],
            language: {
                url: '//cdn.datatables.net/plug-ins/2.3.5/i18n/ko.json'
            },
            searching: false
        });
    }

    exportReport() {
        const params = new URLSearchParams({
            report_type: 'distribution',
            year: $('#year-filter').val(),
            month: $('#month-filter').val(),
            department_id: $('#department-filter').val(),
            item_id: $('#item-filter').val()
        });
        
        window.location.href = `${this.config.API_URL}/export?${params.toString()}`;
    }
}

new SupplyReportDistributionPage();
