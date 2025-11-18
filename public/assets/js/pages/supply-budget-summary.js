/**
 * Supply Budget Summary JavaScript
 */

class SupplyBudgetSummaryPage extends BasePage {
    constructor() {
        super({
            API_URL: '/supply/plans'
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

    initializeApp() {
        this.populateYearSelector();
        super.initializeApp(); // This will call setupEventListeners and loadInitialData
    }

    async loadInitialData() {
        const yearSelector = document.getElementById('year-selector');
        const year = yearSelector ? yearSelector.value : new Date().getFullYear();

        try {
            const response = await this.apiCall(`/supply/plans/budget-summary?year=${year}`);
            const data = response.data;
            this.renderSummaryData(data);
            this.renderCategoryDetailsTable(data.category_budgets);
            this.initializeCharts(data);
            this.initializeCounterAnimation();
        } catch (error) {
            console.error('Error loading budget summary:', error);
            Toast.error('예산 요약 정보를 불러오는 데 실패했습니다.');
            this.hideLoadingState();
        }
    }

    renderSummaryData(data) {
        const safeSet = (id, text) => {
            const el = document.getElementById(id);
            if (el) el.textContent = text;
        };

        // Update summary cards
        safeSet('total-budget', '₩' + (data.total_budget || 0).toLocaleString());
        safeSet('total-items', (data.total_items || 0).toLocaleString());
        safeSet('total-quantity', (data.total_quantity || 0).toLocaleString());
        safeSet('avg-unit-price', '₩' + (data.avg_unit_price || 0).toLocaleString());

        // Update comparison data if available
        if (data.previous_year_summary) {
            const budgetDiff = data.total_budget - data.previous_year_summary.total_budget;
            const diffElem = document.getElementById('budget-comparison');
            if (diffElem) {
                const diffClass = budgetDiff >= 0 ? 'text-success' : 'text-danger';
                const icon = budgetDiff >= 0 ? 'ri-arrow-up-line' : 'ri-arrow-down-line';
                diffElem.innerHTML = `<span class="${diffClass} fs-13"><i class="${icon} align-middle"></i> ₩${Math.abs(budgetDiff).toLocaleString()}</span>`;
            }
        }

        this.hideLoadingState();
    }

    hideLoadingState() {
        const skeletons = document.querySelectorAll('.skeleton-loader');
        skeletons.forEach(el => el.style.display = 'none');
        const contents = document.querySelectorAll('.summary-content');
        contents.forEach(el => el.style.display = 'block');
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

    initializeCharts(summaryData) {
        this.initializeCategoryBudgetChart(summaryData.category_budgets);
        this.initializeBudgetComparisonChart(summaryData.category_budgets, summaryData.previous_year_summary?.category_budgets);
        this.initializeTopItemsChart(summaryData.top_items_by_budget);
    }

    initializeCategoryBudgetChart(data = []) {
        const canvas = document.getElementById('categoryBudgetChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        if (!data || data.length === 0) {
            canvas.parentElement.innerHTML = '<div class="text-center p-3 text-muted">데이터가 없습니다.</div>';
            return;
        }

        if (this.charts.categoryBudget) {
            this.charts.categoryBudget.destroy();
        }

        this.charts.categoryBudget = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(item => item.category_name),
                datasets: [{
                    data: data.map(item => item.total_budget),
                    backgroundColor: [
                        '#405189', '#0ab39c', '#f06548', '#f7b84b', '#299cdb',
                        '#564ab1', '#695e70', '#7269ef', '#3577f1', '#02a8b5'
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
                            label: (context) => `${context.label}: ₩${context.parsed.toLocaleString('ko-KR')}`
                        }
                    }
                }
            }
        });
    }

    initializeBudgetComparisonChart(currentData = [], previousData = []) {
        const canvas = document.getElementById('budgetComparisonChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        if (!currentData || currentData.length === 0) {
            canvas.parentElement.innerHTML = '<div class="text-center p-3 text-muted">데이터가 없습니다.</div>';
            return;
        }

        if (this.charts.budgetComparison) {
            this.charts.budgetComparison.destroy();
        }

        const labels = [...new Set([...currentData.map(i => i.category_name), ...previousData.map(i => i.category_name)])];
        const currentDataset = labels.map(label => currentData.find(d => d.category_name === label)?.total_budget || 0);
        const previousDataset = labels.map(label => previousData.find(d => d.category_name === label)?.total_budget || 0);

        this.charts.budgetComparison = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: '전년도',
                        data: previousDataset,
                        backgroundColor: '#e9ecef'
                    },
                    {
                        label: '올해',
                        data: currentDataset,
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
                            label: (context) => `${context.dataset.label}: ₩${context.parsed.y.toLocaleString('ko-KR')}`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => `₩${value.toLocaleString('ko-KR')}`
                        }
                    }
                }
            }
        });
    }

    initializeTopItemsChart(data = []) {
        const canvas = document.getElementById('topItemsChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        if (!data || data.length === 0) {
            canvas.parentElement.innerHTML = '<div class="text-center p-3 text-muted">데이터가 없습니다.</div>';
            return;
        }

        if (this.charts.topItems) {
            this.charts.topItems.destroy();
        }

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
                            label: (context) => `₩${context.parsed.x.toLocaleString('ko-KR')}`
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => `₩${value.toLocaleString('ko-KR')}`
                        }
                    }
                }
            }
        });
    }

    exportBudget() {
        const yearSelector = document.getElementById('year-selector');
        const year = yearSelector ? yearSelector.value : new Date().getFullYear();
        window.open(`${this.config.API_URL}/export-budget?year=${year}`, '_blank');
    }

    renderCategoryDetailsTable(data = []) {
        const tbody = document.getElementById('category-details-tbody');
        const tfoot = document.getElementById('category-details-tfoot');
        if (!tbody || !tfoot) return;

        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center">데이터가 없습니다.</td></tr>';
            tfoot.innerHTML = '';
            return;
        }

        let totalItems = 0;
        let totalBudget = 0;

        tbody.innerHTML = data.map(item => {
            totalItems += item.item_count;
            totalBudget += item.total_budget;
            return `
                <tr>
                    <td>${this.escapeHtml(item.category_name)}</td>
                    <td class="text-end">${item.item_count.toLocaleString()}</td>
                    <td class="text-end">₩${item.total_budget.toLocaleString()}</td>
                </tr>
            `;
        }).join('');

        tfoot.innerHTML = `
            <tr>
                <th><strong>합계</strong></th>
                <th class="text-end"><strong>${totalItems.toLocaleString()}</strong></th>
                <th class="text-end"><strong>₩${totalBudget.toLocaleString()}</strong></th>
            </tr>
        `;
    }
    
    escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/[&<>"']/g, function(match) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[match];
        });
    }

    populateYearSelector() {
        const selector = document.getElementById('year-selector');
        if (!selector) return;

        const urlParams = new URLSearchParams(window.location.search);
        const currentYear = parseInt(urlParams.get('year'), 10) || new Date().getFullYear();

        const startYear = new Date().getFullYear() + 1;
        for (let y = startYear; y >= 2020; y--) {
            const option = document.createElement('option');
            option.value = y;
            option.textContent = `${y}년`;
            if (y === currentYear) {
                option.selected = true;
            }
            selector.appendChild(option);
        }
    }
}

// 인스턴스 생성
new SupplyBudgetSummaryPage();