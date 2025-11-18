/**
 * Supply Department Usage Report Page
 */

class SupplyReportDepartmentPage extends BasePage {
    constructor() {
        super({
            API_URL: '/supply/reports'
        });
        
        this.summaryTable = null;
        this.detailTable = null;
        this.usageChart = null;
        this.detailChart = null;
    }

    setupEventListeners() {
        $('#filter-form').on('submit', (e) => {
            e.preventDefault();
            this.loadReportData();
        });

        $('#reset-filter-btn').on('click', () => {
            const year = $('#year-filter').val() || new Date().getFullYear();
            window.location.href = `/supply/reports/department?year=${year}`;
        });

        $('#export-report-btn').on('click', () => this.exportReport());
    }

    loadInitialData() {
        this.initDataTables();
        this.loadReportData();
    }

    async loadReportData() {
        try {
            const params = {
                year: $('#year-filter').val(),
                department_id: $('#department-filter').val()
            };

            const queryString = new URLSearchParams(params).toString();
            const result = await this.apiCall(`${this.config.API_URL}/department?${queryString}`);

            if (params.department_id) {
                // 상세 데이터 로드
                this.summaryTable.clear().draw();
                this.detailTable.clear().rows.add(result.data.details || []).draw();
                this.updateDetailChart(result.data.details || []);
            } else {
                // 요약 데이터 로드
                this.detailTable.clear().draw();
                this.summaryTable.clear().rows.add(result.data.summary || []).draw();
                this.updateSummaryChart(result.data.summary || []);
            }
        } catch (error) {
            console.error('Error loading report data:', error);
            Toast.error('보고서 데이터를 불러오는 중 오류가 발생했습니다.');
        }
    }

    initDataTables() {
        this.summaryTable = new DataTable('#department-summary-table', {
            responsive: true,
            pageLength: 25,
            order: [[3, 'desc']],
            language: { url: '//cdn.datatables.net/plug-ins/2.3.5/i18n/ko.json' },
            searching: false
        });

        this.detailTable = new DataTable('#department-detail-table', {
            responsive: true,
            pageLength: 25,
            order: [[5, 'desc']],
            language: { url: '//cdn.datatables.net/plug-ins/2.3.5/i18n/ko.json' },
            searching: false
        });
    }

    updateSummaryChart(data) {
        if (this.usageChart) this.usageChart.destroy();
        const chartElement = document.getElementById('department-usage-chart');
        if (!chartElement) return;

        const labels = data.map(item => item.department_name);
        const quantities = data.map(item => item.total_quantity);

        const ctx = chartElement.getContext('2d');
        this.usageChart = new Chart(ctx, {
            type: 'bar',
            data: { labels, datasets: [{ label: '총 지급 수량', data: quantities, /* ... */ }] },
            options: { /* ... */ }
        });
    }

    updateDetailChart(data) {
        if (this.detailChart) this.detailChart.destroy();
        const chartElement = document.getElementById('department-detail-chart');
        if (!chartElement) return;

        const labels = data.map(item => item.item_name);
        const quantities = data.map(item => item.total_quantity);
        
        const ctx = chartElement.getContext('2d');
        this.detailChart = new Chart(ctx, {
            type: 'bar',
            data: { labels, datasets: [{ label: '지급 수량', data: quantities, /* ... */ }] },
            options: { indexAxis: 'y', /* ... */ }
        });
    }

    exportReport() {
        const departmentId = $('#department-filter').val();
        if (!departmentId) {
            Toast.warning('부서를 선택해주세요.');
            return;
        }
        const year = $('#year-filter').val() || new Date().getFullYear();
        const params = new URLSearchParams({
            report_type: 'department',
            department_id: departmentId,
            year: year
        });
        
        window.location.href = `${this.config.API_URL}/export?${params.toString()}`;
    }

    cleanup() {
        super.cleanup();
        if (this.usageChart) this.usageChart.destroy();
        if (this.detailChart) this.detailChart.destroy();
    }
}

new SupplyReportDepartmentPage();
