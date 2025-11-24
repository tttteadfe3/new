/**
 * 운전원 작업 관리 JavaScript
 */

class VehicleDriverWorkPage extends BasePage {
    constructor() {
        super({ API_URL: '/vehicles/works' });
        this.breakdownTable = null;
        this.maintenanceTable = null;
        this.currentType = null;
    }

    setupEventListeners() {
        document.getElementById('btn-report-breakdown')?.addEventListener('click', () => this.showReportModal('고장'));
        document.getElementById('btn-report-maintenance')?.addEventListener('click', () => this.showReportModal('정비'));
        document.getElementById('btn-save-work')?.addEventListener('click', () => this.saveWork());
        document.getElementById('btn-save-repair')?.addEventListener('click', () => this.saveRepair());

        document.querySelector('a[href="#tab-breakdown"]')?.addEventListener('shown.bs.tab', () => this.loadWorks('고장'));
        document.querySelector('a[href="#tab-maintenance"]')?.addEventListener('shown.bs.tab', () => this.loadWorks('정비'));

        // 사진 압축 처리
        document.querySelectorAll('#photo, #photo2, #photo3, #repair_photo, #repair_photo2').forEach(input => {
            input.addEventListener('change', (e) => this.handlePhotoUpload(e));
        });
    }

    loadInitialData() {
        this.loadMyVehicles();
        this.initializeDataTables();
        this.loadWorks('고장');
    }

    async loadMyVehicles() {
        try {
            const data = await this.apiCall('/vehicles');
            const select = document.getElementById('vehicle_id');
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

    getStatusBadgeColor(status) {
        const colors = {
            '신고': 'secondary',
            '처리결정': 'info',
            '완료': 'primary',
            '승인': 'success',
            '반려': 'danger'
        };
        return colors[status] || 'secondary';
    }
    initializeDataTables() {
        this.breakdownTable = $('#breakdown-table').DataTable({
            columns: [
                { data: 'vehicle_number' },
                { data: 'work_item' },
                {
                    data: 'repair_type',
                    defaultContent: '-',
                    render: (data) => data || '-'
                },
                {
                    data: 'status',
                    render: (data) => `<span class="badge bg-${this.getStatusBadgeColor(data)}">${data}</span>`
                },
                { data: 'created_at' },
                {
                    data: null,
                    render: (data, type, row) => {
                        if (row.status === '처리결정') {
                            return `<button class="btn btn-sm btn-success repair-btn" data-id="${row.id}" data-repair-type="${row.repair_type}">수리 등록</button>`;
                        } else if (row.status === '신고') {
                            return `
                                <button class="btn btn-sm btn-primary edit-btn" data-id="${row.id}">수정</button>
                                <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}">삭제</button>
                            `;
                        }
                        return `<button class="btn btn-sm btn-info view-btn" data-id="${row.id}">상세</button>`;
                    }
                }
            ],
            language: { url: '//cdn.datatables.net/plug-ins/2.3.5/i18n/ko.json' },
            order: [[3, 'desc']]
        });

        $('#breakdown-table').on('click', '.repair-btn', (e) => {
            const btn = $(e.currentTarget);
            this.showRepairModal(btn.data('id'), btn.data('repair-type'));
        });

        $('#breakdown-table').on('click', '.edit-btn', (e) => {
            this.showEditModal($(e.target).data('id'));
        });

        $('#breakdown-table').on('click', '.delete-btn', (e) => {
            if (confirm('정말 삭제하시겠습니까?')) {
                this.deleteWork($(e.target).data('id'));
            }
        });

        $('#breakdown-table').on('click', '.view-btn', (e) => {
            this.showDetailModal($(e.target).data('id'));
        });

        this.maintenanceTable = $('#maintenance-table').DataTable({
            columns: [
                { data: 'vehicle_number' },
                { data: 'work_item' },
                {
                    data: 'status',
                    render: (data) => `<span class="badge bg-${this.getStatusBadgeColor(data)}">${data}</span>`
                },
                { data: 'created_at' },
                {
                    data: null,
                    render: (data, type, row) => `
                        <button class="btn btn-sm btn-info view-btn" data-id="${row.id}">상세</button>
                    `
                }
            ],
            language: { url: '//cdn.datatables.net/plug-ins/2.3.5/i18n/ko.json' },
            order: [[3, 'desc']]
        });

        $('#maintenance-table').on('click', '.view-btn', (e) => {
            this.showDetailModal($(e.target).data('id'));
        });
    }

    async loadWorks(type) {
        try {
            const data = await this.apiCall(`${this.config.API_URL}?type=${type}`);
            const table = type === '고장' ? this.breakdownTable : this.maintenanceTable;
            table.clear().rows.add(data.data || []).draw();
        } catch (error) {
            Toast.error('작업 목록을 불러오는 중 오류가 발생했습니다.');
        }
    }

    async showDetailModal(id) {
        try {
            const data = await this.apiCall(`${this.config.API_URL}/${id}`);
            const work = data.data;

            let html = `
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th class="bg-light" style="width: 150px;">차량번호</th>
                                <td>${work.vehicle_number} (${work.model})</td>
                                <th class="bg-light" style="width: 150px;">작업유형</th>
                                <td>${work.type}</td>
                            </tr>
                            <tr>
                                <th class="bg-light">작업항목</th>
                                <td>${work.work_item}</td>
                                <th class="bg-light">상태</th>
                                <td><span class="badge bg-${this.getStatusBadgeColor(work.status)}">${work.status}</span></td>
                            </tr>
                            <tr>
                                <th class="bg-light">주행거리</th>
                                <td>${work.mileage ? Number(work.mileage).toLocaleString() + ' km' : '-'}</td>
                                <th class="bg-light">신고일</th>
                                <td>${work.created_at}</td>
                            </tr>
                            <tr>
                                <th class="bg-light">상세내용</th>
                                <td colspan="3">${work.description || '-'}</td>
                            </tr>
            `;

            if (work.repair_type) {
                html += `
                    <tr>
                        <th class="bg-light">수리방법</th>
                        <td colspan="3">${work.repair_type}</td>
                    </tr>
                `;
            }

            if (work.photo_path || work.photo2_path || work.photo3_path) {
                html += `
                            <tr>
                                <th class="bg-light">사진</th>
                                <td colspan="3">
                                    <div class="d-flex gap-2 overflow-auto">
                `;
                if (work.photo_path) html += `<a href="${work.photo_path}" target="_blank"><img src="${work.photo_path}" class="img-thumbnail" style="height: 150px;"></a>`;
                if (work.photo2_path) html += `<a href="${work.photo2_path}" target="_blank"><img src="${work.photo2_path}" class="img-thumbnail" style="height: 150px;"></a>`;
                if (work.photo3_path) html += `<a href="${work.photo3_path}" target="_blank"><img src="${work.photo3_path}" class="img-thumbnail" style="height: 150px;"></a>`;
                html += `
                                    </div>
                                </td>
                            </tr>
                `;
            }

            if (work.status === '완료' || work.status === '승인') {
                if (work.type === '정비') {
                    html += `
                        <tr>
                            <th class="bg-light">정비일자</th>
                            <td colspan="3">${work.completed_at || work.created_at.split(' ')[0]}</td>
                        </tr>
                    `;
                } else {
                    html += `
                        <tr>
                            <th class="bg-light">정비소</th>
                            <td>${work.repair_shop || '-'}</td>
                            <th class="bg-light">수리비용</th>
                            <td>${work.cost ? Number(work.cost).toLocaleString() + ' 원' : '-'}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">수리완료일</th>
                            <td colspan="3">${work.completed_at || '-'}</td>
                        </tr>
                    `;
                }
            }

            html += `
                        </tbody>
                    </table>
                </div>
            `;

            document.getElementById('detail-content').innerHTML = html;
            const modal = new bootstrap.Modal(document.getElementById('detailModal'));
            modal.show();
        } catch (error) {
            console.error(error);
            Toast.error('상세 정보를 불러오는 중 오류가 발생했습니다.');
        }
    }

    showReportModal(type) {
        this.currentType = type;
        document.getElementById('workForm').reset();
        document.getElementById('work_id').value = '';
        document.getElementById('work_type').value = type;
        document.querySelector('#workModal .modal-title').textContent = `${type} 신고`;

        const photoExtras = document.querySelectorAll('#workModal .photo-extra');
        if (type === '정비') {
            photoExtras.forEach(el => el.classList.remove('d-none'));
        } else {
            photoExtras.forEach(el => el.classList.add('d-none'));
        }

        const modal = new bootstrap.Modal(document.getElementById('workModal'));
        modal.show();
    }

    async showEditModal(id) {
        try {
            const data = await this.apiCall(`${this.config.API_URL}/${id}`);
            const work = data.data;

            document.getElementById('workForm').reset();
            document.getElementById('work_id').value = work.id;
            document.getElementById('work_type').value = work.type;
            document.getElementById('vehicle_id').value = work.vehicle_id;
            document.getElementById('work_item').value = work.work_item;
            document.getElementById('description').value = work.description || '';
            document.getElementById('mileage').value = work.mileage || '';

            this.currentType = work.type;
            document.querySelector('#workModal .modal-title').textContent = `${work.type} 수정`;

            const photoExtras = document.querySelectorAll('#workModal .photo-extra');
            if (work.type === '정비') {
                photoExtras.forEach(el => el.classList.remove('d-none'));
            } else {
                photoExtras.forEach(el => el.classList.add('d-none'));
            }

            const modal = new bootstrap.Modal(document.getElementById('workModal'));
            modal.show();
        } catch (error) {
            console.error(error);
            Toast.error('정보를 불러오는 중 오류가 발생했습니다.');
        }
    }

    async deleteWork(id) {
        try {
            await this.apiCall(`${this.config.API_URL}/${id}`, {
                method: 'DELETE'
            });
            Toast.success('삭제되었습니다.');
            this.loadWorks('고장');
        } catch (error) {
            Toast.error('삭제 중 오류가 발생했습니다.');
        }
    }

    showRepairModal(id, repairType) {
        document.getElementById('repairForm').reset();
        document.getElementById('repair_work_id').value = id;

        const shopInput = document.getElementById('repair_shop');
        const shopContainer = shopInput.closest('.mb-3');

        if (repairType === '자체수리') {
            shopContainer.classList.add('d-none');
            shopInput.required = false;
            shopInput.value = '자체정비';
        } else {
            shopContainer.classList.remove('d-none');
            shopInput.required = true;
            shopInput.value = '';
        }

        const modal = new bootstrap.Modal(document.getElementById('repairModal'));
        modal.show();
    }

    async saveWork() {
        const form = document.getElementById('workForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const type = document.getElementById('work_type').value;
        const vehicleId = document.getElementById('vehicle_id').value;
        const id = document.getElementById('work_id').value;

        if (!vehicleId) {
            Toast.error('차량을 선택해주세요.');
            return;
        }

        const formData = new FormData();

        formData.append('type', type);
        formData.append('vehicle_id', vehicleId);
        formData.append('work_item', document.getElementById('work_item').value);
        formData.append('description', document.getElementById('description').value);
        formData.append('mileage', document.getElementById('mileage').value);
        formData.append('status', type === '정비' ? '완료' : '신고');

        const photo = document.getElementById('photo').files[0];
        if (photo) formData.append('photo', photo);

        if (type === '정비') {
            const photo2 = document.getElementById('photo2').files[0];
            const photo3 = document.getElementById('photo3').files[0];
            if (photo2) formData.append('photo2', photo2);
            if (photo3) formData.append('photo3', photo3);
        }

        try {
            if (id) {
                formData.append('_method', 'PUT');
                await this.apiCall(`${this.config.API_URL}/${id}`, {
                    method: 'POST',
                    body: formData
                });
                Toast.success('수정되었습니다.');
            } else {
                await this.apiCall(this.config.API_URL, {
                    method: 'POST',
                    body: formData
                });
                Toast.success(`${type}이 신고되었습니다.`);
            }

            this.loadWorks(type);
            bootstrap.Modal.getInstance(document.getElementById('workModal')).hide();
        } catch (error) {
            Toast.error('저장 중 오류가 발생했습니다.');
        }
    }

    async saveRepair() {
        const form = document.getElementById('repairForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const id = document.getElementById('repair_work_id').value;
        const formData = new FormData();

        formData.append('_method', 'PUT');
        formData.append('type', '고장');
        formData.append('repair_shop', document.getElementById('repair_shop').value);

        const cost = document.getElementById('repair_cost').value;
        if (cost) formData.append('cost', cost);

        formData.append('description', document.getElementById('repair_description').value);

        const date = document.getElementById('repair_date').value;
        if (date) formData.append('completed_at', date + ' 00:00:00');

        formData.append('status', '완료');

        const photo = document.getElementById('repair_photo').files[0];
        const photo2 = document.getElementById('repair_photo2').files[0];

        if (photo) formData.append('photo2', photo);
        if (photo2) formData.append('photo3', photo2);

        try {
            await this.apiCall(`${this.config.API_URL}/${id}`, {
                method: 'POST',
                body: formData
            });
            Toast.success('수리 내역이 등록되었습니다.');
            this.loadWorks('고장');
            bootstrap.Modal.getInstance(document.getElementById('repairModal')).hide();
        } catch (error) {
            Toast.error('수리 등록 중 오류가 발생했습니다.');
        }
    }

    /**
     * 이미지 파일 압축
     */
    async compressImageFile(file) {
        const MAX_WIDTH = 1200;
        const MAX_HEIGHT = 1200;
        const QUALITY = 0.8;

        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => {
                let { width, height } = img;
                const ratio = Math.min(MAX_WIDTH / width, MAX_HEIGHT / height, 1);
                width = Math.round(width * ratio);
                height = Math.round(height * ratio);

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob((blob) => {
                    if (blob) {
                        resolve(new File([blob], file.name, {
                            type: 'image/jpeg',
                            lastModified: Date.now()
                        }));
                    } else {
                        reject(new Error('Canvas to Blob conversion failed'));
                    }
                }, 'image/jpeg', QUALITY);
            };
            img.onerror = () => reject(new Error('Image loading failed'));
            img.src = URL.createObjectURL(file);
        });
    }

    /**
     * 사진 업로드 시 자동 압축 처리
     */
    async handlePhotoUpload(e) {
        const file = e.target.files[0];
        if (!file || !file.type.startsWith('image/')) return;

        try {
            const compressed = await this.compressImageFile(file);
            const dt = new DataTransfer();
            dt.items.add(compressed);
            e.target.files = dt.files;

            const reduction = ((1 - compressed.size / file.size) * 100).toFixed(0);
            console.log(`Image compressed: ${(file.size / 1024 / 1024).toFixed(2)}MB → ${(compressed.size / 1024 / 1024).toFixed(2)}MB (${reduction}% reduction)`);
        } catch (error) {
            console.error('Image compression failed:', error);
            // 압축 실패 시 원본 사용
        }
    }
}

new VehicleDriverWorkPage();
