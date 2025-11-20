/**
 * Supply Budget Execution Report Page
 */

class SupplyReportBudgetPage extends BasePage {
    constructor() {
        super({
            API_URL: '/supply/reports'
        });
        
        this.dataTable = null;
        this.budgetChart = null;
        this.currentYear = new Date().getFullYear();
    }

    setupEventListeners() {
        const yearSelector = document.getElementById('year-selector');
        if (yearSelector) {
            yearSelector.addEventListener('change', () => {
                window.location.href = '/supply/reports/budget?year=' + yearSelector.value;
            });
        }

        const exportBtn = document.getElementById('export-report-btn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportReport());
        }
    }

    loadInitialData() {
        const urlParams = new URLSearchParams(window.location.search);
        this.currentYear = parseInt(urlParams.get('year'), 10) || new Date().getFullYear();

        this.initializeDataTable();
        this.loadReportData();
    }

    async loadReportData() {
        try {
            const params = {
                year: this.currentYear
            };

            const queryString = new URLSearchParams(params).toString();
            const result = await this.apiCall(`${this.config.API_URL}/budget?${queryString}`);

            this.dataTable.clear().rows.add(result.data || []).draw();
            this.updateChart(result.data || []);

        } catch (error) {
            console.error('Error loading report data:', error);
            Toast.error('보고서 데이터를 불러오는 중 오류가 발생했습니다.');
        }
    }

    initializeDataTable() {
        const tableElement = document.getElementById('budget-report-table');
        if (tableElement && typeof DataTable !== 'undefined') {
            this.dataTable = new DataTable('#budget-report-table', {
                responsive: true,
                pageLength: 25,
                order: [[9, 'desc']], // Sort by execution rate
                language: {
                    url: '//cdn.datatables.net/plug-ins/2.3.5/i18n/ko.json'
                },
                // ... columns definition ...
                searching: false
            });
        }
    }

    updateChart(data) {
        if (this.budgetChart) {
            this.budgetChart.destroy();
        }

        const chartElement = document.getElementById('budget-execution-chart');
        if (!chartElement) return;

        const labels = data.map(item => item.item_name);
        const plannedBudgets = data.map(item => item.planned_budget);
        const purchasedAmounts = data.map(item => item.purchased_amount);

        if (labels.length === 0) return;

        const ctx = chartElement.getContext('2d');
        this.budgetChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: '계획 예산',
                        data: plannedBudgets,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: '집행 금액',
                        data: purchasedAmounts,
                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }
                ]
            },
            // ... chart options ...
        });
    }

    exportReport() {
        const params = new URLSearchParams({
            report_type: 'budget',
            year: this.currentYear
        });
        
        window.location.href = `${this.config.API_URL}/export?${params.toString()}`;
    }

    cleanup() {
        super.cleanup();
        if (this.budgetChart) {
            this.budgetChart.destroy();
        }
    }
}

// 전역 인스턴스 생성
new SupplyReportBudgetPage();
