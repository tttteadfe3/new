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
        this.initializeCancelHandlers();
    }

    loadInitialData() {
        this.loadStatistics();
        this.initializeDataTable();
        
        const cancelModalElement = document.getElementById('cancelDistributionModal');
        if (cancelModalElement) {
            this.cancelModal = new bootstrap.Modal(cancelModalElement);
        }
    }

    async loadStatistics() {
        const statsContainer = document.getElementById('stats-container');
        try {
            const response = await this.apiCall(`${this.config.apiBaseUrl}/statistics`);
            const stats = response.data;

            statsContainer.innerHTML = `
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">총 지급 건수</p></div>
                                <div class="flex-shrink-0"><span class="avatar-title bg-success-subtle rounded fs-3"><i class="bx bx-package text-success"></i></span></div>
                            </div>
                            <div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0"><span class="counter-value">${stats.total_distributions.toLocaleString()}</span>건</h4></div></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate">
                        <div class="card-body">
                           <div class="d-flex align-items-center">
                                <div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">총 지급 수량</p></div>
                                <div class="flex-shrink-0"><span class="avatar-title bg-info-subtle rounded fs-3"><i class="bx bx-cube text-info"></i></span></div>
                            </div>
                            <div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0"><span class="counter-value">${stats.total_quantity.toLocaleString()}</span>개</h4></div></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">지급 직원 수</p></div>
                                <div class="flex-shrink-0"><span class="avatar-title bg-warning-subtle rounded fs-3"><i class="bx bx-user text-warning"></i></span></div>
                            </div>
                            <div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0"><span class="counter-value">${stats.unique_employees.toLocaleString()}</span>명</h4></div></div>
                        </div>
                    </div>
                </div>
               <div class="col-xl-3 col-md-6">
                    <div class="card card-animate">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1"><p class="text-uppercase fw-medium text-muted mb-0">지급 부서 수</p></div>
                                <div class="flex-shrink-0"><span class="avatar-title bg-primary-subtle rounded fs-3"><i class="bx bx-buildings text-primary"></i></span></div>
                            </div>
                            <div class="d-flex align-items-end justify-content-between mt-4"><div><h4 class="fs-22 fw-semibold ff-secondary mb-0"><span class="counter-value">${stats.unique_departments.toLocaleString()}</span>개</h4></div></div>
                        </div>
                    </div>
                </div>
            `;
        } catch (error) {
            console.error('Failed to load statistics:', error);
            statsContainer.innerHTML = '<p class="text-danger">통계 정보를 불러오는데 실패했습니다.</p>';
        }
    }

    initializeDataTable() {
        const table = document.getElementById('distributions-table');
        if (table && typeof $.fn.DataTable !== 'undefined') {
            this.dataTable = $(table).DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: this.config.apiBaseUrl,
                    type: 'GET',
                    error: (xhr, error, thrown) => {
                        Toast.error(`데이터를 불러오는 중 오류가 발생했습니다: ${thrown}`);
                    }
                },
                columns: [
                    { data: 'distribution_date' },
                    { data: 'item_name', render: (data, type, row) => `
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="fs-14 mb-0">${this.escapeHtml(data)}</h6>
                                <p class="text-muted mb-0 fs-12">${this.escapeHtml(row.item_code)}</p>
                            </div>
                        </div>`
                    },
                    { data: 'quantity', className: 'text-end', render: data => Number(data).toLocaleString() },
                    { data: 'employee_name' },
                    { data: 'department_name' },
                    { data: 'is_cancelled', render: (data, type, row) => {
                        if (data) {
                            return `<span class="badge badge-soft-danger"><i class="ri-close-circle-line me-1"></i>취소됨</span>
                                    <br><small class="text-muted">${new Date(row.cancelled_at).toLocaleDateString()}</small>`;
                        }
                        return `<span class="badge badge-soft-success"><i class="ri-checkbox-circle-line me-1"></i>지급 완료</span>`;
                    }},
                    { data: 'id', orderable: false, render: (data, type, row) => `
                        <div class="dropdown">
                            <button class="btn btn-soft-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ri-more-fill align-middle"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/supply/distributions/show?id=${data}"><i class="ri-eye-fill align-bottom me-2 text-muted"></i> 상세보기</a></li>
                                ${!row.is_cancelled ? `
                                <li><a class="dropdown-item" href="/supply/distributions/edit?id=${data}"><i class="ri-pencil-fill align-bottom me-2 text-muted"></i> 수정</a></li>
                                <li><button class="dropdown-item cancel-distribution-btn" data-id="${data}" data-name="${this.escapeHtml(row.item_name)}" data-employee="${this.escapeHtml(row.employee_name)}"><i class="ri-close-circle-fill align-bottom me-2 text-muted"></i> 취소</button></li>
                                ` : ''}
                            </ul>
                        </div>`
                    }
                ],
                responsive: true,
                pageLength: 25,
                order: [[0, 'desc']],
                language: {
                    url: '/assets/libs/datatables.net/i18n/Korean.json'
                },
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'print']
            });
        }
    }

    initializeCancelHandlers() {
        $(document).on('click', '.cancel-distribution-btn', (e) => {
            const btn = e.currentTarget;
            const id = btn.getAttribute('data-id');
            const itemName = btn.getAttribute('data-name');
            const employeeName = btn.getAttribute('data-employee');
            this.showCancelModal(id, itemName, employeeName);
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
                    <p class="mb-1"><strong>품목:</strong> ${itemName}</p>
                    <p class="mb-0"><strong>직원:</strong> ${employeeName}</p>
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
            await this.apiCall(`${this.config.apiBaseUrl}/${this.currentDistributionId}/cancel`, {
                method: 'POST',
                body: JSON.stringify({ cancel_reason: cancelReason })
            });
            Toast.success('지급이 성공적으로 취소되었습니다.');
            this.cancelModal.hide();
            this.dataTable.ajax.reload(); // Reload table data
            this.loadStatistics(); // Reload stats
        } catch (error) {
            Toast.error(error.message || '지급 취소에 실패했습니다.');
        } finally {
            this.resetButtonLoading('#confirm-cancel-distribution-btn', '취소 처리');
        }
    }
}

// 인스턴스 생성
new SupplyDistributionsIndexPage();
