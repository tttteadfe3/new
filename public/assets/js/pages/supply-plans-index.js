66/**
 * Supply Plans Index JavaScript
 */

class SupplyPlansIndexPage extends BasePage {
    constructor() {
        super({
            apiBaseUrl: '/supply/plans'
        });
        
        this.currentYear = new Date().getFullYear();
        this.currentDeleteId = null;
        this.deletePlanModal = null;
        this.dataTable = null;
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
        this.initializeDeleteHandlers();

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
        this.initializeCounterAnimation();
        
        const deleteModalElement = document.getElementById('deletePlanModal');
        if (deleteModalElement) {
            this.deletePlanModal = new bootstrap.Modal(deleteModalElement);
        }
    }

    initializeDataTable() {
        const plansTable = document.getElementById('plans-table');
        if (plansTable && typeof $.fn.DataTable !== 'undefined') {
            this.dataTable = $(plansTable).DataTable({
                responsive: true,
                pageLength: 25,
                order: [[7, 'desc']], // 등록일 기준 내림차순
                columnDefs: [
                    { targets: [4, 5, 6], className: 'text-end' },
                    { targets: [8], orderable: false }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ko.json'
                }
            });

            // 검색 기능
            const searchInput = document.getElementById('search-plans');
            if (searchInput) {
                searchInput.addEventListener('keyup', () => {
                    this.dataTable.search(searchInput.value).draw();
                });
            }

            // 분류 필터
            const categoryFilter = document.getElementById('filter-category');
            if (categoryFilter) {
                categoryFilter.addEventListener('change', () => {
                    const filterValue = categoryFilter.value;
                    this.dataTable.column(2).search(filterValue).draw();
                });
            }
        }
    }

    initializeCounterAnimation() {
        const counters = document.querySelectorAll('.counter-value');
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'));
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

    initializeDeleteHandlers() {
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
    }

    showDeleteConfirmation(planId, planName) {
        this.currentDeleteId = planId;
        
        const deleteInfo = document.getElementById('delete-plan-info');
        if (deleteInfo) {
            deleteInfo.innerHTML = `
                <div class="alert alert-warning">
                    <strong>삭제할 계획:</strong> ${this.escapeHtml(planName)}
                </div>
            `;
        }
        
        this.deletePlanModal.show();
    }

    async deletePlan(planId) {
        this.setButtonLoading('#confirm-delete-plan-btn', '삭제 중...');

        try {
            const result = await this.apiCall(`${this.config.apiBaseUrl}/${planId}`, {
                method: 'DELETE'
            });

            if (result.success) {
                Toast.success('계획이 성공적으로 삭제되었습니다.');
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                Toast.error(result.message || '계획 삭제에 실패했습니다.');
                this.resetButtonLoading('#confirm-delete-plan-btn', '삭제');
            }
        } catch (error) {
            console.error('Error:', error);
            Toast.error('오류: ' + (error.message || '계획 삭제에 실패했습니다.'));
            this.resetButtonLoading('#confirm-delete-plan-btn', '삭제');
        } finally {
            this.deletePlanModal.hide();
            this.currentDeleteId = null;
        }
    }

    exportToExcel() {
        const yearSelector = document.getElementById('year-selector');
        const year = yearSelector ? yearSelector.value : this.currentYear;
        window.open(this.config.apiBaseUrl + '/export-excel?year=' + year, '_blank');
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// 인스턴스 생성
new SupplyPlansIndexPage();
