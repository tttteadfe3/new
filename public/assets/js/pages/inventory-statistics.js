class InventoryStatisticsPage extends BasePage {
    constructor() {
        super();
        this.budgetChart = null;
        this.initializeApp();
    }

    async initializeApp() {
        this.cacheDOMElements();
        this.setupEventListeners();
        this.populateYearFilter();
        await this.loadStatistics();
    }

    cacheDOMElements() {
        this.dom = {
            yearFilter: document.getElementById('year-filter'),
            content: document.getElementById('statistics-content'),
            loadingPlaceholder: document.getElementById('loading-placeholder'),

            totalBudget: document.getElementById('total-budget'),
            totalExecuted: document.getElementById('total-executed'),
            budgetChartCanvas: document.getElementById('budget-chart'),

            stockStatusBody: document.getElementById('stock-status-body'),
            itemGiveStatsBody: document.getElementById('item-give-stats-body'),
            deptGiveStatsAccordion: document.getElementById('department-give-stats-accordion'),
        };
    }

    setupEventListeners() {
        this.dom.yearFilter.addEventListener('change', () => this.loadStatistics());
    }

    populateYearFilter() {
        const currentYear = new Date().getFullYear();
        for (let i = currentYear; i >= currentYear - 5; i--) {
            const option = new Option(`${i}년`, i);
            this.dom.yearFilter.add(option);
        }
    }

    async loadStatistics() {
        this.dom.content.style.visibility = 'hidden';
        this.dom.loadingPlaceholder.style.display = 'block';
        const year = this.dom.yearFilter.value;
        try {
            const response = await this.apiCall(`/item-statistics/dashboard?year=${year}`);
            this.renderAllStats(response.data);
            this.dom.content.style.visibility = 'visible';
            this.dom.loadingPlaceholder.style.display = 'none';
        } catch (error) {
            Toast.error('통계 데이터를 불러오는 데 실패했습니다.');
            this.dom.loadingPlaceholder.innerHTML = `<p class="text-danger">오류: ${this.sanitizeHTML(error.message)}</p>`;
        }
    }

    renderAllStats(data) {
        this.renderBudgetStats(data.budget_stats);
        this.renderStockStatus(data.stock_status);
        this.renderItemGiveStats(data.item_give_stats);
        this.renderDepartmentGiveStats(data.department_give_stats);
    }

    renderBudgetStats(stats) {
        this.dom.totalBudget.textContent = `${Number(stats.total_budget).toLocaleString()} 원`;
        this.dom.totalExecuted.textContent = `${Number(stats.total_executed).toLocaleString()} 원`;

        const chartData = {
            labels: ['집행 금액', '잔여 예산'],
            datasets: [{
                data: [stats.total_executed, Math.max(0, stats.total_budget - stats.total_executed)],
                backgroundColor: ['#405189', '#e9ecef'],
            }]
        };

        if (this.budgetChart) {
            this.budgetChart.destroy();
        }

        this.budgetChart = new Chart(this.dom.budgetChartCanvas, {
            type: 'doughnut',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: (context) => `${context.label}: ${Number(context.raw).toLocaleString()} 원`
                        }
                    }
                }
            }
        });
    }

    renderStockStatus(stocks) {
        if (!stocks || stocks.length === 0) {
            this.dom.stockStatusBody.innerHTML = '<tr><td colspan="2" class="text-muted">재고 정보가 없습니다.</td></tr>';
            return;
        }
        this.dom.stockStatusBody.innerHTML = stocks.map(s => `
            <tr>
                <td>
                    <div class="fw-medium">${this.sanitizeHTML(s.item_name)}</div>
                    <small class="text-muted">${this.sanitizeHTML(s.category_name)}</small>
                </td>
                <td class="text-end fw-bold">${Number(s.stock).toLocaleString()} 개</td>
            </tr>
        `).join('');
    }

    renderItemGiveStats(stats) {
        if (!stats || stats.length === 0) {
            this.dom.itemGiveStatsBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">지급 내역이 없습니다.</td></tr>';
            return;
        }
        this.dom.itemGiveStatsBody.innerHTML = stats.map(s => `
            <tr>
                <td>${this.sanitizeHTML(s.category_name)}</td>
                <td>${this.sanitizeHTML(s.item_name)}</td>
                <td class="text-end">${Number(s.total_quantity).toLocaleString()}</td>
            </tr>
        `).join('');
    }

    renderDepartmentGiveStats(stats) {
        if (!stats || Object.keys(stats).length === 0) {
            this.dom.deptGiveStatsAccordion.innerHTML = '<p class="text-muted">부서별 지급 내역이 없습니다.</p>';
            return;
        }

        let accordionHtml = '';
        Object.entries(stats).forEach(([deptName, items], index) => {
            const itemsHtml = items.map(item => `
                <li class="d-flex justify-content-between">
                    <span>${this.sanitizeHTML(item.item_name)}</span>
                    <span class="fw-medium">${Number(item.total_quantity).toLocaleString()} 개</span>
                </li>
            `).join('');

            accordionHtml += `
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading-${index}">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-${index}">
                            ${this.sanitizeHTML(deptName)}
                        </button>
                    </h2>
                    <div id="collapse-${index}" class="accordion-collapse collapse" data-bs-parent="#department-give-stats-accordion">
                        <div class="accordion-body">
                            <ul class="list-unstyled">${itemsHtml}</ul>
                        </div>
                    </div>
                </div>
            `;
        });
        this.dom.deptGiveStatsAccordion.innerHTML = accordionHtml;
    }
}

document.addEventListener('DOMContentLoaded', () => { new InventoryStatisticsPage(); });
