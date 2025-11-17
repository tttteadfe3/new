/**
 * Supply Plans Copy JavaScript
 */

class SupplyPlansCopyPage extends BasePage {
    constructor() {
        super({
            apiBaseUrl: '/api/supply/plans'
        });

        this.sourceYear = new Date().getFullYear() - 1;
        this.targetYear = new Date().getFullYear();
        this.sourcePlans = [];
        this.targetPlans = [];
        this.copyablePlans = [];
    }

    setupEventListeners() {
        const sourceYearSelect = document.getElementById('source-year');
        const targetYearSelect = document.getElementById('target-year');
        const copyForm = document.getElementById('copy-plan-form');

        sourceYearSelect?.addEventListener('change', () => {
            this.sourceYear = parseInt(sourceYearSelect.value);
            this.loadInitialData();
        });

        targetYearSelect?.addEventListener('change', () => {
            this.targetYear = parseInt(targetYearSelect.value);
            this.loadInitialData();
        });

        copyForm?.addEventListener('submit', (e) => this.handleCopySubmit(e));

        document.getElementById('select-all-checkbox')?.addEventListener('change', (e) => this.toggleSelectAll(e.target.checked));
    }

    async loadInitialData() {
        this.showLoading();
        try {
            const [sourceResponse, targetResponse] = await Promise.all([
                this.apiCall(`${this.config.apiBaseUrl}?year=${this.sourceYear}`),
                this.apiCall(`${this.config.apiBaseUrl}?year=${this.targetYear}`)
            ]);

            this.sourcePlans = sourceResponse.data || [];
            this.targetPlans = targetResponse.data || [];

            this.filterCopyablePlans();
            this.renderPlanList();

        } catch (error) {
            console.error('Error loading plans:', error);
            Toast.error('계획 정보를 불러오는 중 오류가 발생했습니다.');
        } finally {
            this.hideLoading();
        }
    }

    filterCopyablePlans() {
        const targetItemIds = new Set(this.targetPlans.map(p => p.item_id));
        this.copyablePlans = this.sourcePlans.filter(p => !targetItemIds.has(p.item_id));
    }

    renderPlanList() {
        const listContainer = document.getElementById('copyable-plans-list');
        const noDataContainer = document.getElementById('no-plans-to-copy');

        if (!listContainer || !noDataContainer) return;

        if (this.copyablePlans.length === 0) {
            listContainer.innerHTML = '';
            noDataContainer.style.display = 'block';
            document.getElementById('copy-actions').style.display = 'none';
        } else {
            noDataContainer.style.display = 'none';
            document.getElementById('copy-actions').style.display = 'block';

            const html = this.copyablePlans.map(plan => `
                <li class="list-group-item">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <input class="form-check-input plan-checkbox" type="checkbox" name="plan_ids[]" value="${plan.id}">
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <strong>${this.sanitizeHTML(plan.item_name)}</strong>
                            <small class="text-muted d-block">${this.sanitizeHTML(plan.item_code)}</small>
                        </div>
                        <div class="text-end">
                            <span>${plan.planned_quantity.toLocaleString()} ${this.sanitizeHTML(plan.unit)}</span>
                            <small class="text-muted d-block">₩${plan.unit_price.toLocaleString()}</small>
                        </div>
                    </div>
                </li>
            `).join('');
            listContainer.innerHTML = html;
        }
    }

    toggleSelectAll(checked) {
        document.querySelectorAll('.plan-checkbox').forEach(checkbox => {
            checkbox.checked = checked;
        });
    }

    async handleCopySubmit(e) {
        e.preventDefault();

        const selectedIds = Array.from(document.querySelectorAll('.plan-checkbox:checked')).map(cb => parseInt(cb.value));

        if (selectedIds.length === 0) {
            Toast.error('복사할 계획을 하나 이상 선택해주세요.');
            return;
        }

        const result = await Confirm.fire({
            title: '계획 복사 확인',
            text: `선택된 ${selectedIds.length}개의 계획을 ${this.targetYear}년으로 복사하시겠습니까?`,
            icon: 'info'
        });

        if (!result.isConfirmed) return;

        this.setButtonLoading('#copy-plans-btn', '복사 중...');

        try {
            const response = await this.apiCall(`${this.config.apiBaseUrl}/copy`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    source_year: this.sourceYear,
                    target_year: this.targetYear,
                    plan_ids: selectedIds
                })
            });

            Toast.success(`${response.data.copied_count}개의 계획이 복사되었습니다.`);
            setTimeout(() => {
                window.location.href = `/supply/plans?year=${this.targetYear}`;
            }, 1500);

        } catch (error) {
            console.error('Error copying plans:', error);
            Toast.error(error.message || '계획 복사 중 오류가 발생했습니다.');
            this.resetButtonLoading('#copy-plans-btn', '선택한 계획 복사');
        }
    }

    showLoading() {
        document.getElementById('loader').style.display = 'block';
        document.getElementById('plan-list-container').style.display = 'none';
    }

    hideLoading() {
        document.getElementById('loader').style.display = 'none';
        document.getElementById('plan-list-container').style.display = 'block';
    }
}

// 인스턴스 생성
new SupplyPlansCopyPage();
