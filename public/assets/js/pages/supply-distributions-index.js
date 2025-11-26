class SupplyDistributionsIndexPage extends BasePage {
    constructor() {
        super({
            API_URL: '/api/supply/distributions'
        });

        this.dataTable = null;
        this.distributionModal = null;
        this.deleteModal = null;
        this.cancelModal = null;
        this.currentDistributionId = null;
    }

    loadInitialData() {
        this.initializeDataTable();
        this.initializeModals();
        this.setupEventListeners();
        this.loadStatistics();
    }

    async initializeDataTable() {
        this.dataTable = $('#distributions-table').DataTable({
            columns: [
                { data: 'distribution_date' },
                { data: 'item_name' },
                { data: 'quantity' },
                { data: 'employee_name' },
                { data: 'department_name' },
                {
                    data: 'is_cancelled',
                    render: (data) => data ? '<span class="badge bg-danger">취소됨</span>' : '<span class="badge bg-success">완료</span>'
                },
                {
                    data: 'id',
                    render: (data, type, row) => this.createActionButtons(data, row)
                }
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/2.3.5/i18n/ko.json'
            }
        });
        await this.reloadTable();
    }

    async reloadTable() {
        try {
            const response = await this.apiCall(this.config.API_URL);
            // API 응답 구조 처리: response.data.distributions 또는 response.data 또는 빈 배열
            const distributions = response.data?.distributions || response.data?.data || response.data || [];
            console.log('Loaded distributions:', distributions); // 디버깅용
            this.dataTable.clear().rows.add(distributions).draw();
        } catch (error) {
            console.error('Failed to load distributions:', error);
            Toast.error('데이터를 불러오는 데 실패했습니다.');
        }
    }

    initializeModals() {
        this.distributionModal = new bootstrap.Modal(document.getElementById('distributionModal'));
        this.cancelModal = new bootstrap.Modal(document.getElementById('cancelDistributionModal'));
    }

    setupEventListeners() {
        $('#add-distribution-btn').on('click', () => this.openDistributionModal());
        $('#save-distribution-btn').on('click', () => this.saveDistribution());
        $('#confirm-cancel-distribution-btn').on('click', () => this.handleCancelDistribution());

        $('#distributions-table tbody').on('click', '.edit-btn', (e) => {
            const id = $(e.currentTarget).data('id');
            this.openDistributionModal(id);
        });

        $('#distributions-table tbody').on('click', '.cancel-btn', (e) => {
            const id = $(e.currentTarget).data('id');
            this.openCancelModal(id);
        });
    }

    async loadStatistics() {
        try {
            const response = await this.apiCall(`${this.config.API_URL}/statistics`);
            const stats = response.data.statistics;
            $('#stats-container .counter-value').each(function () {
                const key = $(this).closest('.card').find('p').text().trim();
                let value = 0;
                switch (key) {
                    case '총 지급 건수':
                        value = stats.total_distributions;
                        break;
                    case '총 지급 수량':
                        value = stats.total_quantity_distributed;
                        break;
                    case '지급 직원 수':
                        value = stats.unique_employees;
                        break;
                    case '지급 부서 수':
                        value = stats.unique_departments;
                        break;
                }
                $(this).text(value);
            });
        } catch (error) {
            console.error('Failed to load statistics:', error);
        }
    }

    createActionButtons(id, row) {
        let buttons = `
            <a href="/supply/distributions/show?id=${id}" class="btn btn-sm btn-info">상세</a>
            <button class="btn btn-sm btn-primary edit-btn" data-id="${id}">수정</button>
        `;
        if (!row.is_cancelled) {
            buttons += `<button class="btn btn-sm btn-warning cancel-btn" data-id="${id}">취소</button>`;
        }
        return buttons;
    }

    async openDistributionModal(id = null) {
        this.currentDistributionId = id;
        $('#distribution-form')[0].reset();
        $('#distributionModalLabel').text(id ? '지급 수정' : '지급 등록');

        await this.populateDropdowns();

        if (id) {
            try {
                const response = await this.apiCall(`${this.config.API_URL}/${id}`);
                const data = response.data;
                $('#distribution-id').val(data.id);
                $('#modal-item-id').val(data.item_id);
                $('#modal-quantity').val(data.quantity);
                $('#modal-department-id').val(data.department_id);

                await this.populateEmployees(data.department_id);
                $('#modal-employee-id').val(data.employee_id);

                $('#modal-distribution-date').val(data.distribution_date);
                $('#modal-notes').val(data.notes);
            } catch (error) {
                Toast.error('데이터를 불러오는 데 실패했습니다.');
                return;
            }
        }
        this.distributionModal.show();
    }

    async populateDropdowns() {
        await this.populateItems();
        await this.populateDepartments();

        $('#modal-department-id').on('change', async (e) => {
            const departmentId = $(e.currentTarget).val();
            await this.populateEmployees(departmentId);
        });
    }

    async populateItems() {
        try {
            const response = await this.apiCall(`${this.config.API_URL}/available-items`);
            const items = response.data.items;
            const $select = $('#modal-item-id');
            $select.empty().append('<option value="">선택하세요</option>');
            items.forEach(item => {
                $select.append(`<option value="${item.id}">${item.name}</option>`);
            });
        } catch (error) {
            Toast.error('품목을 불러오는 데 실패했습니다.');
        }
    }

    async populateDepartments() {
        try {
            const response = await this.apiCall('/api/organization');
            const departments = response.data;
            const $select = $('#modal-department-id');
            $select.empty().append('<option value="">선택하세요</option>');
            departments.forEach(dept => {
                $select.append(`<option value="${dept.id}">${dept.name}</option>`);
            });
        } catch (error) {
            Toast.error('부서를 불러오는 데 실패했습니다.');
        }
    }

    async populateEmployees(departmentId) {
        const $select = $('#modal-employee-id');
        $select.empty().append('<option value="">선택하세요</option>');
        if (!departmentId) {
            return;
        }

        try {
            const response = await this.apiCall(`/api/employees?department_id=${departmentId}`);
            const employees = response.data;
            employees.forEach(emp => {
                $select.append(`<option value="${emp.id}">${emp.name}</option>`);
            });
        } catch (error) {
            Toast.error('직원을 불러오는 데 실패했습니다.');
        }
    }

    async saveDistribution() {
        const id = $('#distribution-id').val();
        const data = {
            item_id: $('#modal-item-id').val(),
            quantity: $('#modal-quantity').val(),
            department_id: $('#modal-department-id').val(),
            employee_id: $('#modal-employee-id').val(),
            distribution_date: $('#modal-distribution-date').val(),
            notes: $('#modal-notes').val()
        };

        const url = id ? `${this.config.API_URL}/${id}` : this.config.API_URL;
        const method = id ? 'PUT' : 'POST';

        try {
            await this.apiCall(url, { method, body: JSON.stringify(data) });
            Toast.success(`지급이 성공적으로 ${id ? '수정' : '등록'}되었습니다.`);
            this.distributionModal.hide();
            await this.reloadTable();
        } catch (error) {
            Toast.error(error.message || '저장에 실패했습니다.');
        }
    }

    openCancelModal(id) {
        this.currentDistributionId = id;
        this.cancelModal.show();
    }

    async handleCancelDistribution() {
        const reason = $('#cancel-reason').val();
        if (!reason) {
            Toast.warning('취소 사유를 입력해주세요.');
            return;
        }

        try {
            await this.apiCall(`${this.config.API_URL}/${this.currentDistributionId}/cancel`, {
                method: 'POST',
                body: JSON.stringify({ cancel_reason: reason })
            });
            Toast.success('지급이 성공적으로 취소되었습니다.');
            this.cancelModal.hide();
            await this.reloadTable();
        } catch (error) {
            Toast.error(error.message || '취소에 실패했습니다.');
        }
    }
}

new SupplyDistributionsIndexPage();
