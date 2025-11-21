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

        // For document creation modal
        this.documentItems = [];
        this.documentEmployees = [];
        this.availableItems = [];
        this.departments = [];
        this.employeesByDept = [];
    }

    setupEventListeners() {
        this.initializeModalHandlers();
        this.initializeSearchAndFilter();
    }

    loadInitialData() {
        if (document.getElementById('documents-table')) {
            this.initializeDataTable();
            this.loadDocumentsData();
        }
        this.loadDocumentModalData();
    }

    async loadDocumentsData() {
        try {
            const params = {
                search: document.getElementById('search-documents')?.value || ''
            };
            const queryString = new URLSearchParams(params).toString();
            const documentsData = await this.apiCall(`/api/supply-distributions/documents?${queryString}`);

            this.dataTable.clear().rows.add(documentsData.data || []).draw();
        } catch (error) {
            console.error('Error loading documents data:', error);
            Toast.error('문서 데이터를 불러오는 중 오류가 발생했습니다.');
        }
    }

    initializeDataTable() {
        const self = this;
        const table = document.getElementById('documents-table');
        if (!table) return;

        this.dataTable = new DataTable(table, {
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/2.3.5/i18n/ko.json'
            },
            order: [[2, 'desc']],
            searching: false,
            columns: [
                { data: 'title', render: data => self.escapeHtml(data) },
                { data: 'author_name', render: data => self.escapeHtml(data) },
                { data: 'created_at', render: data => new Date(data).toLocaleDateString('ko-KR') },
                {
                    data: 'status',
                    render: status => {
                        switch (status) {
                            case 'draft': return `<span class="badge badge-soft-secondary">초안</span>`;
                            case 'completed': return `<span class="badge badge-soft-success">완료</span>`;
                            case 'cancelled': return `<span class="badge badge-soft-danger">취소</span>`;
                            default: return `<span class="badge badge-soft-info">${self.escapeHtml(status)}</span>`;
                        }
                    }
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
                                <li><a class="dropdown-item" href="/supply/distributions/show?id=${row.id}"><i class="ri-eye-fill align-bottom me-2"></i>상세보기</a></li>
                                ${row.status === 'draft' ? `
                                    <li><a class="dropdown-item" href="/supply/distributions/edit?id=${row.id}"><i class="ri-pencil-fill align-bottom me-2"></i>편집</a></li>
                                    <li><button class="dropdown-item delete-document-btn" data-id="${row.id}"><i class="ri-delete-bin-fill align-bottom me-2"></i>삭제</button></li>
                                ` : ''}
                            </ul>
                        </div>
                    `
                }
            ],
            }
        });

        table.addEventListener('click', (e) => {
            const target = e.target;
            const cancelButton = target.closest('.cancel-distribution-btn');

            if (cancelButton) {
                const id = cancelButton.dataset.id;
                const itemName = cancelButton.dataset.name;
                this.showCancelModal(id, itemName);
            }
        });
    }

    initializeModalHandlers() {
        // Cancel Modal
        const confirmCancelBtn = document.getElementById('confirm-cancel-distribution-btn');
        if (confirmCancelBtn) {
            confirmCancelBtn.addEventListener('click', () => this.handleCancelDistribution());
        }

    }

    async loadDocumentModalData() {
        await Promise.all([
            this.loadAvailableItems(),
            this.loadDepartments()
        ]);
    }

    async loadAvailableItems() {
        const itemSelect = document.getElementById('item-select');
        try {
            const response = await this.apiCall(`/api/supply-distributions/available-items`);
            this.availableItems = response.data || [];
            this.renderOptions(itemSelect, this.availableItems, {
                value: 'id',
                text: item => `${item.item_name} (재고: ${this.formatNumber(item.current_stock)} ${item.unit})`,
                placeholder: '품목을 선택하세요'
            });
        } catch (error) {
            this.handleApiError(error, itemSelect, '품목 목록을 불러오는 중 오류가 발생했습니다.');
        }
    }

    async loadDepartments() {
        const deptSelect = document.getElementById('department-select');
        try {
            const response = await this.apiCall('/api/organization/managable-departments');
            this.departments = response.data || [];
            this.renderOptions(deptSelect, this.departments, {
                value: 'id',
                text: 'name',
                placeholder: '부서를 선택하세요'
            });
        } catch (error) {
            this.handleApiError(error, deptSelect, '부서 목록을 불러오는 중 오류가 발생했습니다.');
        }

        // Document Create Modal
        const addItemBtn = document.getElementById('add-item-btn');
        const addEmployeeBtn = document.getElementById('add-employee-btn');
        const saveDocumentBtn = document.getElementById('save-document-btn');
        const itemList = document.getElementById('item-list');
        const employeeList = document.getElementById('employee-list');
        const departmentSelect = document.getElementById('department-select');

        departmentSelect?.addEventListener('change', (e) => this.loadEmployeesByDepartment(e.target.value));
        addItemBtn?.addEventListener('click', () => this.addItem());
        addEmployeeBtn?.addEventListener('click', () => this.addEmployee());
        saveDocumentBtn?.addEventListener('click', () => this.handleSaveDocument());

        itemList?.addEventListener('click', (e) => {
            const removeBtn = e.target.closest('.remove-item-btn');
            if (removeBtn) {
                const itemId = removeBtn.dataset.id;
                this.removeItem(itemId);
            }
        });

        employeeList?.addEventListener('click', (e) => {
            const removeBtn = e.target.closest('.remove-employee-btn');
            if (removeBtn) {
                const employeeId = removeBtn.dataset.id;
                this.removeEmployee(employeeId);
            }
        });
    }

    async loadEmployeesByDepartment(departmentId) {
        const employeeSelect = document.getElementById('employee-select');
        if (!employeeSelect) return;

        if (!departmentId) {
            employeeSelect.innerHTML = '<option value="">부서를 먼저 선택하세요</option>';
            employeeSelect.disabled = true;
            this.employeesByDept = [];
            return;
        }

        employeeSelect.innerHTML = '<option value="">불러오는 중...</option>';
        employeeSelect.disabled = false;

        try {
            const response = await this.apiCall(`/api/supply-distributions/employees-by-department/${departmentId}`);
            this.employeesByDept = response.data || [];
            this.renderOptions(employeeSelect, this.employeesByDept, {
                value: 'id',
                text: item => `${item.name} (${item.employee_number || '번호 없음'})`,
                placeholder: '직원을 선택하세요'
            });
        } catch (error) {
            this.handleApiError(error, employeeSelect, '직원 목록을 불러오는 중 오류가 발생했습니다.');
        }
    }

    addItem() {
        const itemSelect = document.getElementById('item-select');
        const quantityInput = document.getElementById('item-quantity');
        const selectedItemId = itemSelect.value;
        const quantity = parseInt(quantityInput.value, 10);

        if (!selectedItemId || isNaN(quantity) || quantity <= 0) {
            Toast.warning('품목을 선택하고 유효한 수량을 입력하세요.');
            return;
        }

        const item = this.availableItems.find(i => i.id == selectedItemId);
        if (!item) return;

        if (quantity > item.current_stock) {
            Toast.error('선택한 수량이 재고보다 많습니다.');
            return;
        }

        const existingItem = this.documentItems.find(i => i.id == selectedItemId);
        if (existingItem) {
            Toast.info('이미 추가된 품목입니다.');
            return;
        }

        this.documentItems.push({ ...item, quantity });
        this.renderDocumentLists();
    }

    addEmployee() {
        const employeeSelect = document.getElementById('employee-select');
        const selectedEmployeeId = employeeSelect.value;

        if (!selectedEmployeeId) {
            Toast.warning('직원을 선택하세요.');
            return;
        }

        const employee = this.employeesByDept.find(e => e.id == selectedEmployeeId);
        if (!employee) return;
        
        const existingEmployee = this.documentEmployees.find(e => e.id == selectedEmployeeId);
        if (existingEmployee) {
            Toast.info('이미 추가된 직원입니다.');
            return;
        }

        this.documentEmployees.push(employee);
        this.renderDocumentLists();
    }

    removeItem(itemId) {
        this.documentItems = this.documentItems.filter(i => i.id != itemId);
        this.renderDocumentLists();
    }

    removeEmployee(employeeId) {
        this.documentEmployees = this.documentEmployees.filter(e => e.id != employeeId);
        this.renderDocumentLists();
    }

    renderDocumentLists() {
        const itemList = document.getElementById('item-list');
        const employeeList = document.getElementById('employee-list');

        if(itemList) {
            itemList.innerHTML = this.documentItems.map(item => `
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    ${this.escapeHtml(item.item_name)}
                    <span class="badge bg-primary rounded-pill">${item.quantity} ${item.unit}</span>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn" data-id="${item.id}">&times;</button>
                </li>
            `).join('');
        }

        if(employeeList) {
            employeeList.innerHTML = this.documentEmployees.map(employee => `
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    ${this.escapeHtml(employee.name)} (${this.escapeHtml(employee.employee_number)})
                    <button type="button" class="btn btn-sm btn-outline-danger remove-employee-btn" data-id="${employee.id}">&times;</button>
                </li>
            `).join('');
        }
    }

    async handleSaveDocument() {
        const title = document.getElementById('document-title').value.trim();
        
        if (!title) {
            Toast.error('문서 제목을 입력해주세요.');
            return;
        }
        if (this.documentItems.length === 0) {
            Toast.error('지급할 품목을 하나 이상 추가해주세요.');
            return;
        }
        if (this.documentEmployees.length === 0) {
            Toast.error('지급받을 직원을 하나 이상 추가해주세요.');
            return;
        }

        const data = {
            title: title,
            items: this.documentItems.map(item => ({ id: item.id, quantity: item.quantity })),
            employees: this.documentEmployees.map(employee => ({ id: employee.id }))
        };

        this.setButtonLoading('#save-document-btn', '저장 중...');

        try {
            await this.apiCall('/api/supply-distributions/documents', {
                method: 'POST',
                body: JSON.stringify(data),
                headers: { 'Content-Type': 'application/json' }
            });

            Toast.success('지급 문서가 성공적으로 저장되었습니다.');

            const modalEl = document.getElementById('createDocumentModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }

            // Reset form
            const form = document.getElementById('create-document-form');
            if (form) form.reset();
            this.documentItems = [];
            this.documentEmployees = [];
            this.renderDocumentLists();

            this.loadDocumentsData(); // Refresh table
        } catch (error) {
            this.handleApiError(error, null, '문서 저장 중 오류가 발생했습니다.');
        } finally {
            this.resetButtonLoading('#save-document-btn', '문서 저장');
        }
    }

    showCancelModal(id, itemName) {
        this.currentDistributionId = id;
        const modalEl = document.getElementById('cancelDistributionModal');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            const infoDiv = document.getElementById('cancel-distribution-info');

            infoDiv.innerHTML = `
                <div class="alert alert-info">
                    <p class="mb-0"><strong>품목:</strong> ${itemName}</p>
                </div>
            `;

            document.getElementById('cancel-reason').value = '';
            modal.show();
        }
    }

    async handleCancelDistribution() {
        const cancelReason = document.getElementById('cancel-reason').value.trim();
        
        if (!cancelReason) {
            Toast.warning('취소 사유를 입력해주세요.');
            return;
        }

        this.setButtonLoading('#confirm-cancel-distribution-btn', '처리 중...');

        try {
            await this.apiCall(`/api${this.config.API_URL}/${this.currentDistributionId}/cancel`, {
                method: 'POST',
                body: { cancel_reason: cancelReason }
            });

            Toast.success('지급이 성공적으로 취소되었습니다.');
            this.loadDocumentsData();

            const modalEl = document.getElementById('cancelDistributionModal');
            if(modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if(modal) modal.hide();
            }

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
            searchInput.addEventListener('input', this.debounce(() => this.loadDocumentsData(), 300));
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
