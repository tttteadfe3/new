/**
 * 지급품 지급 관리 JavaScript
 */

class SupplyDistributionsPage extends BasePage {
    constructor() {
        super({
            API_URL: '/supply/distributions'
        });
        
        this.dataTable = null;
        this.currentDistributionId = null;
    }

    setupEventListeners() {
        this.initializeFormHandlers();
        this.initializeModalHandlers();
        this.initializeSearchAndFilter();
    }

    loadInitialData() {
        if (document.getElementById('distributions-table')) {
            this.initializeDataTable();
            this.loadDistributionsData();
        }
        if (document.getElementById('distribution-form')) {
            this.loadCreateFormData();
        }
    }

    async loadDistributionsData() {
        try {
            const currentYear = new Date().getFullYear();
            const statsData = await this.apiCall(`${this.config.API_URL}/statistics?start_date=${currentYear}-01-01&end_date=${currentYear}-12-31`);
            this.renderStats(statsData.data.statistics);

            const params = {
                search: document.getElementById('search-distributions')?.value || ''
            };
            const queryString = new URLSearchParams(params).toString();
            const distributionsData = await this.apiCall(`${this.config.API_URL}?${queryString}`);

            this.dataTable.clear().rows.add(distributionsData.data.distributions || []).draw();
            this.rebindEventListeners();
        } catch (error) {
            console.error('Error loading distributions data:', error);
            Toast.error('데이터를 불러오는 중 오류가 발생했습니다.');
        }
    }

    renderStats(stats) {
        const statsContainer = document.getElementById('stats-container');
        if (!statsContainer) return;

        statsContainer.innerHTML = `
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1 overflow-hidden">
                                <p class="text-uppercase fw-medium text-muted text-truncate mb-0">총 지급 건수</p>
                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                    <span class="counter-value">${stats.total_distributions || 0}</span>건
                                </h4>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-success-subtle rounded fs-3">
                                        <i class="bx bx-package text-success"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1 overflow-hidden">
                                <p class="text-uppercase fw-medium text-muted text-truncate mb-0">총 지급 수량</p>
                                <h4 class="fs-22 fw-semibold ff-secondary mb-4">
                                    <span class="counter-value">${stats.total_quantity || 0}</span>개
                                </h4>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-info-subtle rounded fs-3">
                                        <i class="bx bx-cube text-info"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    initializeDataTable() {
        const self = this;
        this.dataTable = $('#distributions-table').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/2.3.5/i18n/ko.json'
            },
            order: [[0, 'desc']],
            searching: false,
            columns: [
                { data: 'distribution_date', render: data => new Date(data).toLocaleDateString('ko-KR') },
                {
                    data: null,
                    render: (data, type, row) => `
                        <h6 class="fs-14 mb-0">${self.escapeHtml(row.item_name)}</h6>
                        <p class="text-muted mb-0 fs-12">${self.escapeHtml(row.item_code)}</p>
                    `
                },
                { data: 'quantity', className: 'text-end', render: data => parseInt(data).toLocaleString() },
                { data: 'employee_name', render: data => self.escapeHtml(data) },
                { data: 'department_name', render: data => self.escapeHtml(data) },
                {
                    data: 'is_cancelled',
                    render: data => data
                        ? `<span class="badge badge-soft-danger"><i class="ri-close-circle-line me-1"></i>취소됨</span>`
                        : `<span class="badge badge-soft-success"><i class="ri-checkbox-circle-line me-1"></i>지급 완료</span>`
                },
                {
                    data: null,
                    orderable: false,
                    render: (data, type, row) => `
                        <div class="dropdown">
                            <button class="btn btn-soft-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="ri-more-fill align-middle"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                ${!row.is_cancelled ? `
                                    <li><button class="dropdown-item cancel-distribution-btn" data-id="${row.id}" data-name="${self.escapeHtml(row.item_name)}">
                                        <i class="ri-close-circle-fill align-bottom me-2"></i> 취소
                                    </button></li>
                                ` : ''}
                            </ul>
                        </div>
                    `
                }
            ],
            drawCallback: () => {
                this.rebindEventListeners();
            }
        });
    }

    rebindEventListeners() {
        // 기존 이벤트 리스너 제거 후 다시 바인딩 (중복 방지)
        $('.cancel-distribution-btn').off('click').on('click', (e) => {
            const btn = e.currentTarget;
            const id = btn.dataset.id;
            const itemName = btn.dataset.name;
            this.showCancelModal(id, itemName);
        });
    }

    initializeFormHandlers() {
        const createForm = document.getElementById('distribution-form');
        if (createForm) {
            createForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleCreateSubmit(createForm);
            });
        }
    }

    async handleCreateSubmit(form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        this.setButtonLoading('#submit-btn', '처리 중...');

        try {
            await this.apiCall(this.config.API_URL, {
                method: 'POST',
                body: data
            });

            Toast.success('지급이 성공적으로 등록되었습니다.');
            setTimeout(() => {
                window.location.href = '/supply/distributions';
            }, 1500);
        } catch (error) {
            console.error('Error creating distribution:', error);
            Toast.error(error.message || '지급 등록에 실패했습니다.');
            this.resetButtonLoading('#submit-btn', '<i class="ri-save-line me-1"></i> 지급 등록');
        }
    }

    initializeModalHandlers() {
        const confirmCancelBtn = document.getElementById('confirm-cancel-distribution-btn');
        if (confirmCancelBtn) {
            confirmCancelBtn.addEventListener('click', () => this.handleCancelDistribution());
        }
    }

    showCancelModal(id, itemName) {
        this.currentDistributionId = id;
        const modal = new bootstrap.Modal(document.getElementById('cancelDistributionModal'));
        const infoDiv = document.getElementById('cancel-distribution-info');
        
        infoDiv.innerHTML = `
            <div class="alert alert-info">
                <p class="mb-0"><strong>품목:</strong> ${itemName}</p>
            </div>
        `;
        
        document.getElementById('cancel-reason').value = '';
        modal.show();
    }

    async handleCancelDistribution() {
        const cancelReason = document.getElementById('cancel-reason').value.trim();
        
        if (!cancelReason) {
            Toast.warning('취소 사유를 입력해주세요.');
            return;
        }

        this.setButtonLoading('#confirm-cancel-distribution-btn', '처리 중...');

        try {
            await this.apiCall(`${this.config.API_URL}/${this.currentDistributionId}/cancel`, {
                method: 'POST',
                body: { cancel_reason: cancelReason }
            });

            Toast.success('지급이 성공적으로 취소되었습니다.');
            this.loadDistributionsData();

            const modal = bootstrap.Modal.getInstance(document.getElementById('cancelDistributionModal'));
            modal.hide();

        } catch (error) {
            console.error('Error canceling distribution:', error);
            Toast.error(error.message || '지급 취소에 실패했습니다.');
        } finally {
            this.resetButtonLoading('#confirm-cancel-distribution-btn', '취소 처리');
        }
    }

    initializeSearchAndFilter() {
        const searchInput = document.getElementById('search-distributions');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce(() => this.loadDistributionsData(), 300));
        }
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

    escapeHtml(text) {
        if (text === null || typeof text === 'undefined') return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// 전역 인스턴스 생성
new SupplyDistributionsPage();
