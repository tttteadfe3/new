/**
 * Supply Plans Management JavaScript
 */

class SupplyPlansPage extends BasePage {
    constructor() {
        super({
            apiBaseUrl: '/supply/plans'
        });
        
        this.currentYear = new Date().getFullYear();
        this.currentDeleteId = null;
        this.deletePlanModal = null;
    }

    setupEventListeners() {
        // 연도 선택 변경
        const yearSelector = document.getElementById('year-selector');
        if (yearSelector) {
            yearSelector.addEventListener('change', () => {
                window.location.href = '/supply/plans?year=' + yearSelector.value;
            });
        }

        // 엑셀 다운로드
        const exportExcelBtn = document.getElementById('export-excel-btn');
        if (exportExcelBtn) {
            exportExcelBtn.addEventListener('click', () => this.exportToExcel());
        }

        // 삭제 버튼 이벤트
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

        // 삭제 확인 버튼
        const confirmDeleteBtn = document.getElementById('confirm-delete-plan-btn');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', () => {
                if (this.currentDeleteId) {
                    this.deletePlan(this.currentDeleteId);
                }
            });
        }
    }

    loadInitialData() {
        this.initializeDataTable();
        this.deletePlanModal = new bootstrap.Modal(document.getElementById('deletePlanModal'));
    }

    initializeDataTable() {
        const plansTable = document.getElementById('plans-table');
        if (plansTable && typeof $.fn.DataTable !== 'undefined') {
            $(plansTable).DataTable({
                responsive: true,
                pageLength: 25,
                order: [[7, 'desc']], // 등록일 기준 내림차순
                columnDefs: [
                    { targets: [4, 5, 6], className: 'text-end' },
                    { targets: [8], orderable: false }
                ],
                language: {
                    url: '/assets/libs/datatables.net/i18n/Korean.json'
                }
            });

            // 검색 기능
            const searchInput = document.getElementById('search-plans');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    $(plansTable).DataTable().search(this.value).draw();
                });
            }

            // 분류 필터
            const categoryFilter = document.getElementById('filter-category');
            if (categoryFilter) {
                categoryFilter.addEventListener('change', function() {
                    const filterValue = this.value;
                    $(plansTable).DataTable().column(2).search(filterValue).draw();
                });
            }
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
            await this.apiCall(`${this.config.apiBaseUrl}/${planId}`, {
                method: 'DELETE'
            });

            Toast.success('계획이 성공적으로 삭제되었습니다.');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
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
        window.open(this.config.apiBaseUrl + '/export-excel?year=' + year, '_blank');
    }
}

// 전역 인스턴스 생성
new SupplyPlansPage();
