/**
 * Manager 작업 처리 JavaScript
 */

class VehicleManagerWorkPage extends BasePage {
    constructor() {
        super({ API_URL: '/vehicles/works' });
        this.breakdownTable = null;
        this.maintenanceTable = null;
    }

    setupEventListeners() {
        document.querySelector('a[href="#tab-breakdown"]')?.addEventListener('shown.bs.tab', () => this.loadWorks('breakdown'));
        document.querySelector('a[href="#tab-maintenance"]')?.addEventListener('shown.bs.tab', () => this.loadWorks('maintenance'));

        document.getElementById('btn-confirm-accept')?.addEventListener('click', () => this.acceptWork());
    }

    loadInitialData() {
        this.initializeDataTables();
        this.loadWorks('breakdown');
    }

    initializeDataTables() {
        const self = this;

        this.breakdownTable = $('#breakdown-table').DataTable({
            columns: [
                { data: 'vehicle_number' },
                { data: 'work_item' },
                { data: 'reporter_name' },
                {
                    data: 'status',
                    render: (data) => `<span class="badge bg-secondary">${data}</span>`
                },
                { data: 'created_at' },
                {
                    data: null,
                    render: (data, type, row) => `
                        <button class="btn btn-sm btn-info view-btn me-1" data-id="${row.id}">상세</button>
                        <button class="btn btn-sm btn-primary accept-btn" data-id="${row.id}">접수</button>
                    `
                }
            ],
            language: { url: '//cdn.datatables.net/plug-ins/2.3.5/i18n/ko.json' },
            order: [[4, 'desc']]
        });

        this.maintenanceTable = $('#maintenance-table').DataTable({
            columns: [
                { data: 'vehicle_number' },
                { data: 'work_item' },
                { data: 'reporter_name' },
                {
                    data: 'status',
                    render: (data) => `<span class="badge bg-primary">${data}</span>`
                },
                { data: 'created_at' },
                {
                    data: null,
                    render: (data, type, row) => `
                        <button class="btn btn-sm btn-info view-btn me-1" data-id="${row.id}">상세</button>
                        <button class="btn btn-sm btn-success approve-btn" data-id="${row.id}">승인</button>
                    `
                }
            ],
            language: { url: '//cdn.datatables.net/plug-ins/2.3.5/i18n/ko.json' },
            order: [[4, 'desc']]
        });

        $('#breakdown-table').on('click', '.accept-btn', function () {
            self.showAcceptModal($(this).data('id'));
        });

        $('#breakdown-table').on('click', '.view-btn', function () {
            self.showWorkDetail($(this).data('id'));
        });

        $('#maintenance-table').on('click', '.approve-btn', function () {
            self.approveWork($(this).data('id'));
        });

        $('#maintenance-table').on('click', '.view-btn', function () {
            self.showWorkDetail($(this).data('id'));
        });
    }

    async showWorkDetail(id) {
        try {
            const response = await this.apiCall(`${this.config.API_URL}/${id}`);
            const work = response.data;

            let html = `
                <table class="table table-bordered">
                    <tr><th style="width: 120px;">차량</th><td>${work.vehicle_number} (${work.model})</td></tr>
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
            new bootstrap.Modal(document.getElementById('workDetailModal')).show();
        } catch (error) {
            console.error(error);
            Toast.error('상세 정보를 불러오는 중 오류가 발생했습니다.');
        }
    }

    async loadWorks(tab) {
        try {
            let data = [];
            if (tab === 'breakdown') {
                const response = await this.apiCall(`${this.config.API_URL}?status=신고`);
                data = response.data || [];
            } else {
                // 승인 대기는 '완료' 상태인 항목들
                const response = await this.apiCall(`${this.config.API_URL}?status=완료`);
                data = response.data || [];
            }

            const table = tab === 'breakdown' ? this.breakdownTable : this.maintenanceTable;
            table.clear().rows.add(data).draw();
        } catch (error) {
            Toast.error('작업 목록을 불러오는 중 오류가 발생했습니다.');
        }
    }

    showAcceptModal(id) {
        document.getElementById('accept_work_id').value = id;
        document.getElementById('repair_internal').checked = true;
        const modal = new bootstrap.Modal(document.getElementById('acceptModal'));
        modal.show();
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
            this.loadWorks('breakdown');
            bootstrap.Modal.getInstance(document.getElementById('acceptModal')).hide();
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
            this.loadWorks('maintenance');
        } catch (error) {
            Toast.error('승인 중 오류가 발생했습니다.');
        }
    }
}

new VehicleManagerWorkPage();
