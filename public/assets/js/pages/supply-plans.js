/**
 * Supply Plans Management JavaScript
 */

class SupplyPlansPage extends BasePage {
    constructor() {
        super({
            API_URL: '/supply/plans'
        });
        
        this.dataTable = null;
        this.currentYear = new Date().getFullYear();
        this.currentDeleteId = null;
        this.deletePlanModal = null;
    }

    setupEventListeners() {
        const yearSelector = document.getElementById('year-selector');
        if (yearSelector) {
            yearSelector.addEventListener('change', () => {
                window.location.href = '/supply/plans?year=' + yearSelector.value;
            });
        }

        const exportExcelBtn = document.getElementById('export-excel-btn');
        if (exportExcelBtn) {
            exportExcelBtn.addEventListener('click', () => this.exportToExcel());
        }

        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('delete-plan-btn') || 
                e.target.closest('.delete-plan-btn')) {
                
                const btn = e.target.classList.contains('delete-plan-btn') ? 
                    e.target : e.target.closest('.delete-plan-btn');
                
                const planId = btn.getAttribute('data-id');
                const planName = btn.getAttribute('data-name');
                
                this.showDeleteConfirmation(planId, planName);
            }
        });

        const confirmDeleteBtn = document.getElementById('confirm-delete-plan-btn');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', () => {
                if (this.currentDeleteId) {
                    this.deletePlan(this.currentDeleteId);
                }
            });
        }

        // 검색 및 필터 이벤트
        $('#search-plans, #filter-category').on('keyup change', this.debounce(() => {
            this.loadPlans();
        }, 300));
    }

    loadInitialData() {
        this.initializeDataTable();
        this.loadPlans();
        this.deletePlanModal = new bootstrap.Modal(document.getElementById('deletePlanModal'));
    }

    async loadPlans() {
        try {
            const params = {
                year: $('#year-selector').val() || this.currentYear,
                search: $('#search-plans').val(),
                category_id: $('#filter-category').val()
            };

            const queryString = new URLSearchParams(params).toString();
            const result = await this.apiCall(`${this.config.API_URL}?${queryString}`);

            this.dataTable.clear().rows.add(result.data || []).draw();
        } catch (error) {
            console.error('Error loading plans:', error);
            Toast.error('계획을 불러오는 중 오류가 발생했습니다.');
        }
    }

    initializeDataTable() {
        const plansTable = document.getElementById('plans-table');
        if (plansTable && typeof $.fn.DataTable !== 'undefined') {
            this.dataTable = $(plansTable).DataTable({
                responsive: true,
                pageLength: 25,
                order: [[7, 'desc']], // 등록일 기준 내림차순
                columns: [
                    // ... 컬럼 정의 ...
                ],
                columnDefs: [
                    { targets: [4, 5, 6], className: 'text-end' },
                    { targets: [8], orderable: false }
                ],
                language: {
                    url: '/assets/libs/datatables.net/i18n/Korean.json'
                },
                searching: false
            });
        }
    }

    showDeleteConfirmation(planId, planName) {
        this.currentDeleteId = planId;
        
        const deleteInfo = document.getElementById('delete-plan-info');
        if (deleteInfo) {
            deleteInfo.innerHTML = `
                <div class="alert alert-warning">
                    <strong>삭제할 계획:</strong> ${planName}
                </div>
            `;
        }
        
        this.deletePlanModal.show();
    }

    async deletePlan(planId) {
        this.setButtonLoading('#confirm-delete-plan-btn', '삭제 중...');

        try {
            await this.apiCall(`${this.config.API_URL}/${planId}`, {
                method: 'DELETE'
            });

            Toast.success('계획이 성공적으로 삭제되었습니다.');
            this.loadPlans(); // 데이터 다시 로드
        } catch (error) {
            console.error('Error:', error);
            Toast.error('오류: ' + (error.message || '계획 삭제에 실패했습니다.'));
        } finally {
            this.resetButtonLoading('#confirm-delete-plan-btn');
            this.deletePlanModal.hide();
            this.currentDeleteId = null;
        }
    }

    exportToExcel() {
        const yearSelector = document.getElementById('year-selector');
        const year = yearSelector ? yearSelector.value : this.currentYear;
        window.open(`${this.config.API_URL}/export-excel?year=${year}`, '_blank');
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// 전역 인스턴스 생성
new SupplyPlansPage();
