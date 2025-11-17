/**
 * Supply Budget Summary JavaScript
 */

class SupplyBudgetSummaryPage extends BasePage {
    constructor() {
        super({
            apiBaseUrl: '/api/supply/plans'
        });
        
        this.charts = {};
    }

    setupEventListeners() {
        // 연도 선택 변경
        const yearSelector = document.getElementById('year-selector');
        if (yearSelector) {
            yearSelector.addEventListener('change', () => {
                window.location.href = '/supply/plans/budget-summary?year=' + yearSelector.value;
            });
        }

        // 엑셀 다운로드
        const exportBtn = document.getElementById('export-budget-btn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportBudget());
        }
    }

    loadInitialData() {
        this.initializeCharts();
        this.initializeCounterAnimation();
    }

    initializeCounterAnimation() {
        const counters = document.querySelectorAll('.counter-value');
        counters.forEach(counter => {
            const targetText = counter.getAttribute('data-target');
            const target = parseInt(targetText.replace(/,/g, ''));
            const duration = 1000;
            const step = target / (duration / 16);
            let current = 0;

            const updateCounter = () => {
                current += step;
                if (current < target) {
                    counter.textContent = Math.floor(current).toLocaleString('ko-KR');
                    requestAnimationFrame(updateCounter);
                } else {
                    counter.textContent = target.toLocaleString('ko-KR');
                }
            };

            updateCounter();
        });
    }

    initializeCharts() {
        this.initializeCategoryBudgetChart();
        this.initializeBudgetComparisonChart();
        this.initializeTopItemsChart();
    }

    initializeCategoryBudgetChart() {
        const canvas = document.getElementById('categoryBudgetChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const data = JSON.parse(canvas.dataset.chartData || '[]');

        if (data.length === 0) return;

        this.charts.categoryBudget = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(item => item.category_name),
                datasets: [{
                    data: data.map(item => item.total_budget),
                    backgroundColor: [
                        '#405189',
                        '#0ab39c',
                        '#f06548',
                        '#f7b84b',
                        '#299cdb',
                        '#564ab1'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ₩' + context.parsed.toLocaleString('ko-KR');
                            }
                        }
                    }
                }
            }
        });
    }

    initializeBudgetComparisonChart() {
        const canvas = document.getElementById('budgetComparisonChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const currentData = JSON.parse(canvas.dataset.currentData || '[]');
        const previousData = JSON.parse(canvas.dataset.previousData || '[]');

        if (currentData.length === 0) return;

        this.charts.budgetComparison = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: currentData.map(item => item.category_name),
                datasets: [
                    {
                        label: '전년도',
                        data: previousData.map(item => item.total_budget),
                        backgroundColor: '#e9ecef'
                    },
                    {
                        label: '올해',
                        data: currentData.map(item => item.total_budget),
                        backgroundColor: '#405189'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ₩' + context.parsed.y.toLocaleString('ko-KR');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₩' + value.toLocaleString('ko-KR');
                            }
                        }
                    }
                }
            }
        });
    }

    initializeTopItemsChart() {
        const canvas = document.getElementById('topItemsChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const data = JSON.parse(canvas.dataset.chartData || '[]');

        if (data.length === 0) return;

        this.charts.topItems = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(item => item.item_name),
                datasets: [{
                    label: '예산',
                    data: data.map(item => item.total_budget),
                    backgroundColor: '#0ab39c'
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₩' + context.parsed.x.toLocaleString('ko-KR');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₩' + value.toLocaleString('ko-KR');
                            }
                        }
                    }
                }
            }
        });
    }

    exportBudget() {
        const yearSelector = document.getElementById('year-selector');
        const year = yearSelector ? yearSelector.value : new Date().getFullYear();
        window.open(`${this.config.apiBaseUrl}/export-budget?year=${year}`, '_blank');
    }
}

// 인스턴스 생성
new SupplyBudgetSummaryPage();
