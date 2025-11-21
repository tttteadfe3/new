class VehiclePage extends BasePage {
    constructor() {
        super();
        this.state = {
            vehicles: [],
            departments: [],
            employees: [],
            currentVehicle: null
        };
        this.initializeApp();
    }

    async initializeApp() {
        this.cacheDOMElements();
        this.setupEventListeners();
        await this.loadInitialData();
    }

    cacheDOMElements() {
        this.dom = {
            addVehicleBtn: document.getElementById('add-vehicle-btn'),
            vehicleListContainer: document.getElementById('vehicle-list-container'),
            vehicleTableBody: document.getElementById('vehicle-table-body'),
            vehicleModal: new bootstrap.Modal(document.getElementById('vehicle-modal')),
            vehicleModalTitle: document.getElementById('vehicle-modal-title'),
            vehicleForm: document.getElementById('vehicle-form'),
            vehicleId: document.getElementById('vehicle-id'),
            vin: document.getElementById('vin'),
            licensePlate: document.getElementById('license_plate'),
            make: document.getElementById('make'),
            model: document.getElementById('model'),
            year: document.getElementById('year'),
            departmentId: document.getElementById('department_id'),
            driverId: document.getElementById('driver_id'),
            status: document.getElementById('status')
        };
    }

    setupEventListeners() {
        this.dom.addVehicleBtn.addEventListener('click', () => this.handleAddVehicle());
        this.dom.vehicleForm.addEventListener('submit', (e) => this.handleFormSubmit(e));
    }

    async loadInitialData() {
        try {
            const [vehicles, departments, employees] = await Promise.all([
                this.apiCall('/vehicles'),
                this.apiCall('/organization/departments'),
                this.apiCall('/employees')
            ]);
            this.state.vehicles = vehicles.data;
            this.state.departments = departments.data;
            this.state.employees = employees.data;
            this.renderVehicleList();
            this.populateSelectOptions();
        } catch (error) {
            console.error('Error loading initial data:', error);
            Toast.error('데이터 로딩에 실패했습니다.');
        }
    }

    renderVehicleList() {
        this.dom.vehicleTableBody.innerHTML = '';
        if (this.state.vehicles.length === 0) {
            this.dom.vehicleTableBody.innerHTML = '<tr><td colspan="7" class="text-center">차량이 없습니다.</td></tr>';
            return;
        }

        this.state.vehicles.forEach(vehicle => {
            const row = `
                <tr>
                    <td>${this.sanitizeHTML(vehicle.license_plate)}</td>
                    <td>${this.sanitizeHTML(vehicle.model)}</td>
                    <td>${this.sanitizeHTML(vehicle.year)}</td>
                    <td>${this.sanitizeHTML(vehicle.department_name)}</td>
                    <td>${this.sanitizeHTML(vehicle.driver_name)}</td>
                    <td>${this.sanitizeHTML(vehicle.status)}</td>
                    <td>
                        <button class="btn btn-sm btn-primary edit-btn" data-id="${vehicle.id}">수정</button>
                        <button class="btn btn-sm btn-danger delete-btn" data-id="${vehicle.id}">삭제</button>
                    </td>
                </tr>
            `;
            this.dom.vehicleTableBody.insertAdjacentHTML('beforeend', row);
        });

        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleEditVehicle(e.currentTarget.dataset.id));
        });
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.handleDeleteVehicle(e.currentTarget.dataset.id));
        });
    }

    populateSelectOptions() {
        this.populateDepartments();
        this.populateEmployees();
    }

    populateDepartments() {
        this.dom.departmentId.innerHTML = '<option value="">부서 선택</option>';
        this.state.departments.forEach(dept => {
            const option = `<option value="${dept.id}">${this.sanitizeHTML(dept.name)}</option>`;
            this.dom.departmentId.insertAdjacentHTML('beforeend', option);
        });
    }

    populateEmployees() {
        this.dom.driverId.innerHTML = '<option value="">운전자 선택</option>';
        this.state.employees.forEach(emp => {
            const option = `<option value="${emp.id}">${this.sanitizeHTML(emp.name)}</option>`;
            this.dom.driverId.insertAdjacentHTML('beforeend', option);
        });
    }

    handleAddVehicle() {
        this.state.currentVehicle = null;
        this.dom.vehicleForm.reset();
        this.dom.vehicleModalTitle.textContent = '신규 차량 등록';
        this.dom.vehicleModal.show();
    }

    handleEditVehicle(id) {
        this.state.currentVehicle = this.state.vehicles.find(v => v.id == id);
        if (!this.state.currentVehicle) return;

        this.dom.vehicleId.value = this.state.currentVehicle.id;
        this.dom.vin.value = this.state.currentVehicle.vin;
        this.dom.licensePlate.value = this.state.currentVehicle.license_plate;
        this.dom.make.value = this.state.currentVehicle.make;
        this.dom.model.value = this.state.currentVehicle.model;
        this.dom.year.value = this.state.currentVehicle.year;
        this.dom.departmentId.value = this.state.currentVehicle.department_id;
        this.dom.driverId.value = this.state.currentVehicle.driver_id;
        this.dom.status.value = this.state.currentVehicle.status;

        this.dom.vehicleModalTitle.textContent = '차량 정보 수정';
        this.dom.vehicleModal.show();
    }

    async handleDeleteVehicle(id) {
        if (!confirm('정말로 이 차량을 삭제하시겠습니까?')) return;

        try {
            await this.apiCall(`/vehicles/${id}`, { method: 'DELETE' });
            Toast.success('차량이 삭제되었습니다.');
            await this.loadInitialData();
        } catch (error) {
            console.error('Error deleting vehicle:', error);
            Toast.error('차량 삭제에 실패했습니다.');
        }
    }

    async handleFormSubmit(e) {
        e.preventDefault();
        const formData = new FormData(this.dom.vehicleForm);
        const data = Object.fromEntries(formData.entries());

        const url = this.state.currentVehicle ? `/vehicles/${this.state.currentVehicle.id}` : '/vehicles';
        const method = this.state.currentVehicle ? 'PUT' : 'POST';

        try {
            await this.apiCall(url, {
                method: method,
                body: JSON.stringify(data),
                headers: { 'Content-Type': 'application/json' }
            });
            Toast.success('차량 정보가 저장되었습니다.');
            this.dom.vehicleModal.hide();
            await this.loadInitialData();
        } catch (error) {
            console.error('Error saving vehicle:', error);
            Toast.error('차량 정보 저장에 실패했습니다.');
        }
    }
}

new VehiclePage();
