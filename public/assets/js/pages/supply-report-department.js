/**
 * Supply Department Usage Report Page
 */

class SupplyReportDepartmentPage extends BasePage {
    constructor() {
        super({
            apiBaseUrl: '/supply/reports'
        });
        
        this.summaryTable = null;
        this.detailTable = null;
        this.usageChart = null;
        this.detailChart = null;
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
                const yearFilter = document.getElementById('year-filter');
                const year = yearFilter ? yearFilter.value : new Date().getFullYear();
                window.location.href = '/supply/reports/department?year=' + year;
            });
        }

        // Export button
        const exportBtn = document.getElementById('export-report-btn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportReport());
        }
    }

    loadInitialData() {
        this.initDataTables();
        this.initCharts();
    }

    initDataTables() {
        // Department summary table
        const summaryTableElement = document.getElementById('department-summary-table');
        if (summaryTableElement && typeof DataTable !== 'undefined') {
            this.summaryTable = new DataTable('#department-summary-table', {
                responsive: true,
                pageLength: 25,
                order: [[3, 'desc']], // Sort by total quantity
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ko.json'
                }
            });
        }

        // Department detail table
        const detailTableElement = document.getElementById('department-detail-table');
        if (detailTableElement && typeof DataTable !== 'undefined') {
            this.detailTable = new DataTable('#department-detail-table', {
                responsive: true,
                pageLength: 25,
                order: [[5, 'desc']], // Sort by total quantity
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ko.json'
                }
            });
        }
    }

    initCharts() {
        // Department usage chart (summary view)
        const usageChartElement = document.getElementById('department-usage-chart');
        if (usageChartElement) {
            const table = document.getElementById('department-summary-table');
            if (!table) return;

            const rows = table.querySelectorAll('tbody tr');
            const labels = [];
            const quantities = [];

            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length > 0) {
                    labels.push(cells[0].textContent.trim()); // 부서명
                    quantities.push(parseInt(cells[3].textContent.replace(/[^\d]/g, '')) || 0); // 총 지급 수량
                }
            });

            if (labels.length === 0) return;

            const ctx = usageChartElement.getContext('2d');
            this.usageChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '총 지급 수량',
                        data: quantities,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Department detail chart (detail view)
        const detailChartElement = document.getElementById('department-detail-chart');
        if (detailChartElement) {
            const table = document.getElementById('department-detail-table');
            if (!table) return;

            const rows = table.querySelectorAll('tbody tr');
            const labels = [];
            const quantities = [];

            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length > 0) {
                    labels.push(cells[1].textContent.trim()); // 품목명
                    quantities.push(parseInt(cells[5].textContent.replace(/[^\d]/g, '')) || 0); // 총 수량
                }
            });

            if (labels.length === 0) return;

            const ctx = detailChartElement.getContext('2d');
            this.detailChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '지급 수량',
                        data: quantities,
                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        x: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    }

    applyFilters() {
        const form = document.getElementById('filter-form');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);
        
        window.location.href = '/supply/reports/department?' + params.toString();
    }

    exportReport() {
        const form = document.getElementById('filter-form');
        const formData = new FormData(form);
        const departmentId = formData.get('department_id');
        
        if (!departmentId) {
            Toast.warning('부서를 선택해주세요.');
            return;
        }

        const year = formData.get('year') || new Date().getFullYear();
        const params = new URLSearchParams({
            report_type: 'department',
            department_id: departmentId,
            year: year
        });
        
        window.location.href = this.config.apiBaseUrl + '/export?' + params.toString();
    }

    cleanup() {
        super.cleanup();
        if (this.usageChart) {
            this.usageChart.destroy();
        }
        if (this.detailChart) {
            this.detailChart.destroy();
        }
    }
}

// 전역 인스턴스 생성
new SupplyReportDepartmentPage();
