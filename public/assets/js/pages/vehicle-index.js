/**
 * 차량 관리 JavaScript
 */

class VehicleIndexPage extends BasePage {
    constructor() {
        super({ API_URL: '/vehicles' });
        this.dataTable = null;
        this.currentId = null;
    }

    setupEventListeners() {
        document.getElementById('btn-create-vehicle')?.addEventListener('click', () => this.showCreateModal());
        document.getElementById('btn-save-vehicle')?.addEventListener('click', () => this.saveVehicle());
        document.getElementById('btn-confirm-delete')?.addEventListener('click', () => this.confirmDelete());

        document.getElementById('filter-department')?.addEventListener('change', () => this.loadVehicles());
        document.getElementById('filter-status')?.addEventListener('change', () => this.loadVehicles());
        document.getElementById('search-input')?.addEventListener('keyup', () => this.loadVehicles());

        document.getElementById('department_id')?.addEventListener('change', (e) => this.loadDrivers(e.target.value));
    }

    loadInitialData() {
        this.loadDepartments();
        this.initializeDataTable();
        this.loadVehicles();
    }

    async loadDepartments() {
        try {
            const data = await this.apiCall('/organization/managable-departments');
            const select = document.getElementById('filter-department');
            const formSelect = document.getElementById('department_id');

            [select, formSelect].forEach(el => {
                if (el && data.data) {
                    data.data.forEach(dept => {
                        const option = document.createElement('option');
                        option.value = dept.id;
                        option.textContent = dept.name;
                        el.appendChild(option.cloneNode(true));
                    });
                }
            });
        } catch (error) {
            console.error('Error loading departments:', error);
        }
    }

    getStatusBadgeColor(statusCode) {
        const badges = {
            '정상': 'success',
            '수리중': 'warning',
            '폐차': 'secondary'
        };
        return badges[statusCode] || 'secondary';
    }

    initializeDataTable() {
        const self = this;
        this.dataTable = $('#vehicles-table').DataTable({
            processing: true,
            serverSide: false,
            columns: [
                { data: 'vehicle_number' },
                { data: 'model' },
                { data: 'vehicle_type', defaultContent: '-' },
                { data: 'year' },
                { data: 'release_date', defaultContent: '-' },
                { data: 'department_name', defaultContent: '-' },
                { data: 'driver_name', defaultContent: '-' },
                {
                    data: 'status_code',
                    render: function (data) {
                        return `<span class="badge bg-${self.getStatusBadgeColor(data)}">${data}</span>`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function (data, type, row) {
                        return `
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-info view-btn" data-id="${row.id}">
                                    <i class="ri-eye-line"></i>
                                </button>
                                <button type="button" class="btn btn-primary edit-btn" data-id="${row.id}">
                                    <i class="ri-edit-line"></i>
                                </button>
                                <button type="button" class="btn btn-danger delete-btn" data-id="${row.id}">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        `;
                    }
                }
            ],
            language: { url: '//cdn.datatables.net/plug-ins/2.3.5/i18n/ko.json' },
            order: [[0, 'asc']]
        });

        $('#vehicles-table').on('click', '.view-btn', function () {
            self.showDetailModal($(this).data('id'));
        });
        $('#vehicles-table').on('click', '.edit-btn', function () {
            self.showEditModal($(this).data('id'));
        });
        $('#vehicles-table').on('click', '.delete-btn', function () {
            self.showDeleteModal($(this).data('id'));
        });
    }

    async loadDrivers(departmentId, selectedDriverId = null) {
        const select = document.getElementById('driver_employee_id');
        select.innerHTML = '<option value="">미배정</option>';

        if (!departmentId) return;

        try {
            const data = await this.apiCall(`/employees?department_id=${departmentId}`);
            if (data.data) {
                data.data.forEach(emp => {
                    const option = document.createElement('option');
                    option.value = emp.id;
                    option.textContent = `${emp.name} (${emp.position_name || '-'})`;
                    select.appendChild(option);
                });
            }

            if (selectedDriverId) {
                select.value = selectedDriverId;
            }
        } catch (error) {
            console.error('Error loading drivers:', error);
            Toast.error('운전원 목록을 불러오는 중 오류가 발생했습니다.');
        }
    }

    async loadVehicles() {
        try {
            const params = {
                department_id: document.getElementById('filter-department')?.value || '',
                status_code: document.getElementById('filter-status')?.value || '',
                search: document.getElementById('search-input')?.value || ''
            };

            const queryString = new URLSearchParams(params).toString();
            const result = await this.apiCall(`${this.config.API_URL}?${queryString}`);
            this.dataTable.clear().rows.add(result.data || []).draw();
        } catch (error) {
            console.error('Error loading vehicles:', error);
            Toast.error('차량 목록을 불러오는 중 오류가 발생했습니다.');
        }
    }

    showCreateModal() {
        document.getElementById('vehicleForm').reset();
        document.getElementById('vehicle_id').value = '';
        document.querySelector('#vehicleModal .modal-title').textContent = '차량 등록';
        const modal = new bootstrap.Modal(document.getElementById('vehicleModal'));
        modal.show();
    }

    async showEditModal(id) {
        try {
            const data = await this.apiCall(`${this.config.API_URL}/${id}`);
            document.getElementById('vehicle_id').value = data.data.id;
            document.getElementById('vehicle_number').value = data.data.vehicle_number;
            document.getElementById('model').value = data.data.model;
            document.getElementById('vehicle_type').value = data.data.vehicle_type || '';
            document.getElementById('payload_capacity').value = data.data.payload_capacity || '';
            document.getElementById('year').value = data.data.year || '';
            document.getElementById('release_date').value = data.data.release_date || '';
            document.getElementById('department_id').value = data.data.department_id || '';

            if (data.data.department_id) {
                await this.loadDrivers(data.data.department_id, data.data.driver_employee_id);
            } else {
                document.getElementById('driver_employee_id').innerHTML = '<option value="">미배정</option>';
            }

            document.getElementById('status_code').value = data.data.status_code;

            document.querySelector('#vehicleModal .modal-title').textContent = '차량 수정';
            const modal = new bootstrap.Modal(document.getElementById('vehicleModal'));
            modal.show();
        } catch (error) {
            console.error('Error loading vehicle details:', error);
            Toast.error('차량 정보를 불러오는 중 오류가 발생했습니다.');
        }
    }

    async saveVehicle() {
        const form = document.getElementById('vehicleForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const id = document.getElementById('vehicle_id').value;
        const data = {
            vehicle_number: document.getElementById('vehicle_number').value,
            model: document.getElementById('model').value,
            vehicle_type: document.getElementById('vehicle_type').value,
            payload_capacity: document.getElementById('payload_capacity').value,
            year: document.getElementById('year').value,
            release_date: document.getElementById('release_date').value,
            department_id: document.getElementById('department_id').value,
            driver_employee_id: document.getElementById('driver_employee_id').value,
            status_code: document.getElementById('status_code').value
        };

        try {
            if (id) {
                await this.apiCall(`${this.config.API_URL}/${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                Toast.success('차량 정보가 수정되었습니다.');
            } else {
                await this.apiCall(this.config.API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                Toast.success('차량이 등록되었습니다.');
            }

            this.loadVehicles();
            bootstrap.Modal.getInstance(document.getElementById('vehicleModal')).hide();
        } catch (error) {
            console.error('Error saving vehicle:', error);
            Toast.error(error.message || '차량 저장 중 오류가 발생했습니다.');
        }
    }

    showDeleteModal(id) {
        this.currentId = id;
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }

    async confirmDelete() {
        try {
            await this.apiCall(`${this.config.API_URL}/${this.currentId}`, { method: 'DELETE' });
            Toast.success('차량이 삭제되었습니다.');
            this.loadVehicles();
            bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
        } catch (error) {
            Toast.error('삭제 중 오류가 발생했습니다.');
        }
    }

    async showDetailModal(id) {
        try {
            const data = await this.apiCall(`${this.config.API_URL}/${id}`);
            const vehicle = data.data;

            const content = `
                <div class="row g-3">
                    <div class="col-md-6"><strong>차량번호:</strong> ${vehicle.vehicle_number}</div>
                    <div class="col-md-6"><strong>차종/모델:</strong> ${vehicle.model}</div>
                    <div class="col-md-6"><strong>차종:</strong> ${vehicle.vehicle_type || '-'}</div>
                    <div class="col-md-6"><strong>적재량:</strong> ${vehicle.payload_capacity || '-'}</div>
                    <div class="col-md-6"><strong>연식:</strong> ${vehicle.year || '-'}</div>
                    <div class="col-md-6"><strong>출고일자:</strong> ${vehicle.release_date || '-'}</div>
                    <div class="col-md-6"><strong>배정부서:</strong> ${vehicle.department_name || '-'}</div>
                    <div class="col-md-6"><strong>담당운전원:</strong> ${vehicle.driver_name || '-'}</div>
                    <div class="col-md-6"><strong>상태:</strong> <span class="badge bg-${this.getStatusBadgeColor(vehicle.status_code)}">${vehicle.status_code}</span></div>
                </div>
            `;

            document.getElementById('vehicle-detail-content').innerHTML = content;
            const modal = new bootstrap.Modal(document.getElementById('detailModal'));
            modal.show();
        } catch (error) {
            console.error('Error loading vehicle details:', error);
            Toast.error('차량 정보를 불러오는 중 오류가 발생했습니다.');
        }
    }
}

new VehicleIndexPage();
