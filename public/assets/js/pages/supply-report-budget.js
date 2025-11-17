/**
 * Supply Budget Execution Report Page
 */

class SupplyReportBudgetPage extends BasePage {
    constructor() {
        super({
            apiBaseUrl: '/supply/reports'
        });
        
        this.budgetTable = null;
        this.budgetChart = null;
    }

    setupEventListeners() {
        // Year selector change
        const yearSelector = document.getElementById('year-selector');
        if (yearSelector) {
            yearSelector.addEventListener('change', () => {
                window.location.href = '/supply/reports/budget?year=' + yearSelector.value;
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
        this.initChart();
    }

    initDataTable() {
        const tableElement = document.getElementById('budget-report-table');
        if (tableElement && typeof DataTable !== 'undefined') {
            this.budgetTable = new DataTable('#budget-report-table', {
                responsive: true,
                pageLength: 25,
                order: [[9, 'desc']], // Sort by execution rate
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ko.json'
                }
            });
        }
    }

    initChart() {
        const chartElement = document.getElementById('budget-execution-chart');
        if (!chartElement) {
            return;
        }

        // Chart will be initialized with data from the table
        const table = document.getElementById('budget-report-table');
        if (!table) return;

        const rows = table.querySelectorAll('tbody tr');
        const labels = [];
        const plannedBudgets = [];
        const purchasedAmounts = [];

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length > 0) {
                labels.push(cells[1].textContent.trim()); // 품목명
                plannedBudgets.push(parseFloat(cells[5].textContent.replace(/[^\d]/g, '')) || 0); // 계획예산
                purchasedAmounts.push(parseFloat(cells[7].textContent.replace(/[^\d]/g, '')) || 0); // 구매금액
            }
        });

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
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₩' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += '₩' + context.parsed.y.toLocaleString();
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }

    exportReport() {
        const yearSelector = document.getElementById('year-selector');
        const year = yearSelector ? yearSelector.value : new Date().getFullYear();
        const params = new URLSearchParams({
            report_type: 'budget',
            year: year
        });
        
        window.location.href = this.config.apiBaseUrl + '/export?' + params.toString();
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
