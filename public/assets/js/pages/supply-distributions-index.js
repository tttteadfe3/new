/**
 * Supply Distributions Index JavaScript
 */

class SupplyDistributionsIndexPage extends BasePage {
    constructor() {
        super({
            apiBaseUrl: '/supply/distributions'
        });
        
        this.currentDistributionId = null;
        this.cancelModal = null;
        this.dataTable = null;
    }

    setupEventListeners() {
        this.initializeSearchAndFilter();
        this.initializeCancelHandlers();
    }

    loadInitialData() {
        this.initializeDataTable();
        this.initializeCounterAnimation();
        
        const cancelModalElement = document.getElementById('cancelDistributionModal');
        if (cancelModalElement) {
            this.cancelModal = new bootstrap.Modal(cancelModalElement);
        }
    }

    initializeDataTable() {
        const table = document.getElementById('distributions-table');
        if (table && typeof $.fn.DataTable !== 'undefined') {
            this.dataTable = $(table).DataTable({
                responsive: true,
                pageLength: 25,
                order: [[0, 'desc']], // 지급일 기준 내림차순
                columnDefs: [
                    { targets: [2], className: 'text-end' },
                    { targets: [6], orderable: false }
                ],
                language: {
                    url: '/assets/libs/datatables.net/i18n/Korean.json'
                }
            });
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

    initializeSearchAndFilter() {
        const searchInput = document.getElementById('search-distributions');
        if (searchInput && this.dataTable) {
            searchInput.addEventListener('keyup', () => {
                this.dataTable.search(searchInput.value).draw();
            });
        }

        const startDateInput = document.getElementById('filter-start-date');
        const endDateInput = document.getElementById('filter-end-date');

        if (startDateInput && endDateInput) {
            startDateInput.addEventListener('change', () => this.filterByDateRange());
            endDateInput.addEventListener('change', () => this.filterByDateRange());
        }
    }

    filterByDateRange() {
        const startDate = document.getElementById('filter-start-date').value;
        const endDate = document.getElementById('filter-end-date').value;

        if (this.dataTable) {
            $.fn.dataTable.ext.search.push((settings, data, dataIndex) => {
                const dateStr = data[0]; // 첫 번째 컬럼이 날짜
                
                if (!startDate && !endDate) return true;
                
                const rowDate = new Date(dateStr);
                const start = startDate ? new Date(startDate) : null;
                const end = endDate ? new Date(endDate) : null;

                if (start && rowDate < start) return false;
                if (end && rowDate > end) return false;
                
                return true;
            });

            this.dataTable.draw();
            $.fn.dataTable.ext.search.pop();
        }
    }

    initializeCancelHandlers() {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('cancel-distribution-btn') || 
                e.target.closest('.cancel-distribution-btn')) {
                
                const btn = e.target.classList.contains('cancel-distribution-btn') ? 
                    e.target : e.target.closest('.cancel-distribution-btn');
                
                const id = btn.getAttribute('data-id');
                const itemName = btn.getAttribute('data-name');
                const employeeName = btn.getAttribute('data-employee');
                
                this.showCancelModal(id, itemName, employeeName);
            }
        });

        const confirmCancelBtn = document.getElementById('confirm-cancel-distribution-btn');
        if (confirmCancelBtn) {
            confirmCancelBtn.addEventListener('click', () => this.handleCancelDistribution());
        }
    }

    showCancelModal(id, itemName, employeeName) {
        this.currentDistributionId = id;
        
        const infoDiv = document.getElementById('cancel-distribution-info');
        if (infoDiv) {
            infoDiv.innerHTML = `
                <div class="alert alert-info">
                    <p class="mb-1"><strong>품목:</strong> ${this.escapeHtml(itemName)}</p>
                    <p class="mb-0"><strong>직원:</strong> ${this.escapeHtml(employeeName)}</p>
                </div>
            `;
        }
        
        document.getElementById('cancel-reason').value = '';
        this.cancelModal.show();
    }

    async handleCancelDistribution() {
        const cancelReason = document.getElementById('cancel-reason').value.trim();
        
        if (!cancelReason) {
            Toast.warning('취소 사유를 입력해주세요.');
            return;
        }

        this.setButtonLoading('#confirm-cancel-distribution-btn', '처리 중...');

        try {
            const result = await this.apiCall(`${this.config.apiBaseUrl}/${this.currentDistributionId}/cancel`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ cancel_reason: cancelReason })
            });

            if (result.success) {
                Toast.success('지급이 성공적으로 취소되었습니다.');
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                Toast.error(result.message || '지급 취소에 실패했습니다.');
                this.resetButtonLoading('#confirm-cancel-distribution-btn', '취소 처리');
            }
        } catch (error) {
            console.error('Error:', error);
            Toast.error('서버 오류가 발생했습니다.');
            this.resetButtonLoading('#confirm-cancel-distribution-btn', '취소 처리');
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// 인스턴스 생성
new SupplyDistributionsIndexPage();
