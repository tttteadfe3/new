/**
 * 차량 검사 관리 JavaScript
 */

class VehicleInspectionPage extends BasePage {
    constructor() {
        super({ API_URL: '/vehicles/inspections' });
        this.dataTable = null;
        this.isEditing = false;
    }

    setupEventListeners() {
        // 모달이 열릴 때 차량 목록 로드 (신규 등록일 때만 초기화)
        document.getElementById('addInspectionModal')?.addEventListener('show.bs.modal', (e) => {
            if (!this.isEditing) {
                this.resetForm();
                this.loadVehicles();
            }
        });

        // 저장 버튼 클릭
        document.getElementById('saveInspectionBtn')?.addEventListener('click', () => this.saveInspection());

        // 테이블 내 버튼 이벤트 (이벤트 위임)
        $('#inspectionTable').on('click', '.edit-btn', (e) => {
            const id = $(e.currentTarget).data('id');
            this.showEditModal(id);
        });

        $('#inspectionTable').on('click', '.delete-btn', (e) => {
            const id = $(e.currentTarget).data('id');
            Confirm.fire({
                title: '삭제 확인',
                text: '정말 삭제하시겠습니까?',
                icon: 'warning'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.deleteInspection(id);
                }
            });
        });
    }

    loadInitialData() {
        this.initializeDataTable();
        this.loadInspections();
    }

    initializeDataTable() {
        this.dataTable = $('#inspectionTable').DataTable({
            processing: true,
            serverSide: false,
            columns: [
                { data: 'vehicle_number' },
                { data: 'model' },
                { data: 'inspection_date' },
                { data: 'expiry_date' },
                { data: 'inspector_name', defaultContent: '-' },
                {
                    data: 'result',
                    render: function (data) {
                        const badges = {
                            '합격': 'success',
                            '불합격': 'danger',
                            '재검사': 'warning'
                        };
                        const color = badges[data] || 'secondary';
                        return `<span class="badge bg-${color}">${data}</span>`;
                    }
                },
                {
                    data: 'cost',
                    render: function (data) {
                        return data ? new Intl.NumberFormat('ko-KR').format(data) + '원' : '-';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function (data, type, row) {
                        return `<button class="btn btn-sm btn-info me-1 edit-btn" data-id="${row.id}">수정</button>` +
                            `<button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}">삭제</button>`;
                    }
                }
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/ko.json'
            },
            order: [[3, 'asc']] // 만료일자 오름차순
        });
    }

    async loadInspections() {
        try {
            const response = await this.apiCall(this.config.API_URL);
            this.dataTable.clear().rows.add(response.data || []).draw();
        } catch (error) {
            console.error('Error loading inspections:', error);
            Toast.error('데이터를 불러오는 중 오류가 발생했습니다.');
        }
    }

    async loadVehicles(selectedId = null) {
        try {
            const response = await this.apiCall('/vehicles');
            const select = document.getElementById('vehicle_id');
            select.innerHTML = '<option value="">차량을 선택하세요</option>';

            if (response.data) {
                response.data.forEach(vehicle => {
                    const option = document.createElement('option');
                    option.value = vehicle.id;
                    option.textContent = `${vehicle.vehicle_number} (${vehicle.model})`;
                    select.appendChild(option);
                });
            }

            if (selectedId) {
                select.value = selectedId;
            }
        } catch (error) {
            console.error('Error loading vehicles:', error);
        }
    }

    resetForm() {
        this.isEditing = false;
        document.getElementById('addInspectionForm').reset();
        document.getElementById('inspection_id').value = '';
        document.getElementById('addInspectionModalLabel').textContent = '차량 검사 등록';
    }

    async showEditModal(id) {
        try {
            const response = await this.apiCall(`${this.config.API_URL}/${id}`);
            const data = response.data;

            this.isEditing = true;
            document.getElementById('inspection_id').value = data.id;
            document.getElementById('inspection_date').value = data.inspection_date;
            document.getElementById('expiry_date').value = data.expiry_date;
            document.getElementById('inspector_name').value = data.inspector_name;
            document.getElementById('result').value = data.result;
            document.getElementById('cost').value = data.cost;

            await this.loadVehicles(data.vehicle_id);

            document.getElementById('addInspectionModalLabel').textContent = '차량 검사 수정';
            new bootstrap.Modal(document.getElementById('addInspectionModal')).show();
        } catch (error) {
            console.error('Error loading inspection details:', error);
            Toast.error('데이터를 불러오는 중 오류가 발생했습니다.');
        }
    }

    async saveInspection() {
        const id = document.getElementById('inspection_id').value;
        const formData = {
            vehicle_id: document.getElementById('vehicle_id').value,
            inspection_date: document.getElementById('inspection_date').value,
            expiry_date: document.getElementById('expiry_date').value,
            inspector_name: document.getElementById('inspector_name').value,
            result: document.getElementById('result').value,
            cost: document.getElementById('cost').value
        };

        if (!formData.vehicle_id || !formData.inspection_date || !formData.expiry_date || !formData.result) {
            Toast.warning('필수 항목을 모두 입력해주세요.');
            return;
        }

        try {
            const url = id ? `${this.config.API_URL}/${id}` : this.config.API_URL;
            const method = id ? 'PUT' : 'POST';

            await this.apiCall(url, {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });

            Toast.success(id ? '수정되었습니다.' : '검사 내역이 등록되었습니다.');
            bootstrap.Modal.getInstance(document.getElementById('addInspectionModal')).hide();
            document.getElementById('addInspectionForm').reset();
            this.loadInspections();
        } catch (error) {
            console.error('Error saving inspection:', error);
            Toast.error(error.message || '등록 중 오류가 발생했습니다.');
        }
    }

    async deleteInspection(id) {
        try {
            await this.apiCall(`${this.config.API_URL}/${id}`, {
                method: 'DELETE'
            });
            Toast.success('삭제되었습니다.');
            this.loadInspections();
        } catch (error) {
            console.error('Error deleting inspection:', error);
            Toast.error('삭제 중 오류가 발생했습니다.');
        }
    }
}

new VehicleInspectionPage();
