/**
 * Application for the Vehicle Manager Work page.
 * Manages vehicle maintenance and repair work assignments for managers.
 */
class VehicleManagerWorkPage extends BasePage {
    constructor() {
        super({ API_URL: '/vehicles/works' });

        this.state = {
            ...this.state,
            workTable: null,
            modals: {}
        };
    }

    /**
     * @override
     */
    async initializeApp() {
        this.setupModals();
        this.setupEventListeners();
        this.initializeDataTable();
        await this.loadInitialData();
    }

    /**
     * @override
     */
    async loadInitialData() {
        await this.loadVehicles();
        this.loadWorks();
    }

    /**
     * @override
     */
    setupEventListeners() {
        document.getElementById('btn-confirm-accept')?.addEventListener('click', () => this.acceptWork());
        document.getElementById('filter-type')?.addEventListener('change', () => this.loadWorks());
        document.getElementById('filter-vehicle')?.addEventListener('change', () => this.loadWorks());
    }

    setupModals() {
        // Initialize modals if needed
        const acceptModalEl = document.getElementById('acceptModal');
        if (acceptModalEl) {
            this.state.modals.accept = new bootstrap.Modal(acceptModalEl);
        }

        const detailModalEl = document.getElementById('workDetailModal');
        if (detailModalEl) {
            this.state.modals.detail = new bootstrap.Modal(detailModalEl);
        }
    }

    // --- DataTable Initialization ---

    initializeDataTable() {
        const self = this;

        this.state.workTable = $('#work-table').DataTable({
            columns: [
                { data: 'vehicle_number' },
                {
                    data: 'type',
                    render: (data) => {
                        const badge = data === '고장' ? 'bg-danger' : 'bg-success';
                        return `<span class="badge ${badge}">${data}</span>`;
                    }
                },
                { data: 'work_item' },
                { data: 'reporter_name' },
                {
                    data: 'status',
                    render: (data) => `<span class="badge bg-secondary">${data}</span>`
                },
                { data: 'created_at' },
                {
                    data: null,
                    render: (data, type, row) => {
                        let buttons = `<button class="btn btn-sm btn-info view-btn me-1" data-id="${row.id}">상세</button>`;

                        if (row.status === '신고' && row.type === '고장') {
                            buttons += `<button class="btn btn-sm btn-primary accept-btn" data-id="${row.id}">접수</button>`;
                        } else if (row.status === '완료') {
                            buttons += `<button class="btn btn-sm btn-success approve-btn" data-id="${row.id}">승인</button>`;
                        }

                        return buttons;
                    }
                }
            ],
            language: { url: '//cdn.datatables.net/plug-ins/2.3.5/i18n/ko.json' },
            order: [[5, 'desc']]
        });

        $('#work-table').on('click', '.accept-btn', function () {
            self.showAcceptModal($(this).data('id'));
        });

        $('#work-table').on('click', '.view-btn', function () {
            self.showWorkDetail($(this).data('id'));
        });

        $('#work-table').on('click', '.approve-btn', function () {
            self.approveWork($(this).data('id'));
        });
    }

    async showWorkDetail(id) {
        try {
            const response = await this.apiCall(`${this.config.API_URL}/${id}`);
            const work = response.data;

            let html = `
                <table class="table table-bordered">
                    <tr><th style="width: 120px;">차량</th><td>${work.vehicle_number} (${work.model})</td></tr>
                    <tr><th>작업유형</th><td>${work.type}</td></tr>
                    <tr><th>작업항목</th><td>${work.work_item}</td></tr>
                    <tr><th>신고자</th><td>${work.reporter_name}</td></tr>
                    <tr><th>상태</th><td>${work.status}</td></tr>
                    <tr><th>내용</th><td>${work.description || '-'}</td></tr>
                    <tr><th>주행거리</th><td>${work.mileage ? work.mileage + ' km' : '-'}</td></tr>
                </table>
                <div class="mt-3">
                    <h6>사진</h6>
                    <div class="d-flex flex-wrap gap-2">
            `;

            const photos = [work.photo_path, work.photo2_path, work.photo3_path].filter(p => p);
            if (photos.length > 0) {
                photos.forEach(path => {
                    html += `<a href="${path}" target="_blank"><img src="${path}" class="img-thumbnail" style="max-height: 200px;"></a>`;
                });
            } else {
                html += '<p class="text-muted">등록된 사진이 없습니다.</p>';
            }

            html += `
                    </div>
                </div>
            `;

            document.getElementById('work-detail-content').innerHTML = html;
            if (this.state.modals.detail) {
                this.state.modals.detail.show();
            }
        } catch (error) {
            console.error(error);
            Toast.error('상세 정보를 불러오는 중 오류가 발생했습니다.');
        }
    }

    async loadVehicles() {
        try {
            const data = await this.apiCall('/vehicles');
            const select = document.getElementById('filter-vehicle');
            if (select && data.data) {
                data.data.forEach(vehicle => {
                    const option = document.createElement('option');
                    option.value = vehicle.id;
                    option.textContent = `${vehicle.vehicle_number} (${vehicle.model})`;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error loading vehicles:', error);
        }
    }

    async loadWorks() {
        try {
            const typeFilter = document.getElementById('filter-type')?.value || '';
            const vehicleFilter = document.getElementById('filter-vehicle')?.value || '';

            let url = this.config.API_URL;
            const params = [];
            if (typeFilter) params.push(`type=${typeFilter}`);
            if (vehicleFilter) params.push(`vehicle_id=${vehicleFilter}`);
            if (params.length > 0) url += '?' + params.join('&');

            const response = await this.apiCall(url);
            this.state.workTable.clear().rows.add(response.data || []).draw();
        } catch (error) {
            Toast.error('작업 목록을 불러오는 중 오류가 발생했습니다.');
        }
    }

    showAcceptModal(id) {
        document.getElementById('accept_work_id').value = id;
        document.getElementById('repair_internal').checked = true;
        if (this.state.modals.accept) {
            this.state.modals.accept.show();
        }
    }

    async acceptWork() {
        const id = document.getElementById('accept_work_id').value;
        const repairType = document.querySelector('input[name="repair_type"]:checked').value;

        try {
            await this.apiCall(`${this.config.API_URL}/${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: '처리결정', repair_type: repairType })
            });
            Toast.success('접수되었습니다.');
            this.loadWorks();
            if (this.state.modals.accept) {
                this.state.modals.accept.hide();
            }
        } catch (error) {
            Toast.error('접수 중 오류가 발생했습니다.');
        }
    }

    async approveWork(id) {
        if (!confirm('정말 승인하시겠습니까?')) return;

        try {
            await this.apiCall(`${this.config.API_URL}/${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: '승인' })
            });
            Toast.success('승인되었습니다.');
            this.loadWorks();
        } catch (error) {
            Toast.error('승인 중 오류가 발생했습니다.');
        }
    }

    /**
     * @override
     */
    cleanup() {
        super.cleanup();
        if (this.state.workTable) {
            this.state.workTable.destroy();
            this.state.workTable = null;
        }
    }
}

new VehicleManagerWorkPage();
