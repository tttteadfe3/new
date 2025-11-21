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
        this.currentEditingId = null;

        // For document creation modal
        this.documentItems = [];
        this.documentEmployees = [];

        // For document editing modal
        this.editDocumentItems = [];
        this.editDocumentEmployees = [];

        // Shared data
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
            const response = await this.apiCall(`/supply/distributions?${queryString}`);

            this.dataTable.clear().rows.add(response.data.distributions || []).draw();
        } catch (error) {
            console.error('Error loading documents data:', error);
            Toast.error('문서 데이터를 불러오는 중 오류가 발생했습니다.');
        }
    }

    initializeDataTable() {
        const self = this;
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
            const response = await this.apiCall(`/supply/distributions/available-items`);
            this.availableItems = response.data?.items || [];
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
            const response = await this.apiCall('/organization/managable-departments');
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
        addEmployeeBtn?.addEventListener('click', () => this.addSelectedEmployees());
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
        const employeeContainer = document.getElementById('employee-select');
        if (!employeeContainer) return;

        if (!departmentId) {
            employeeContainer.innerHTML = '<p class="text-muted small mb-0">부서를 먼저 선택하세요</p>';
            this.employeesByDept = [];
            return;
        }

        employeeContainer.innerHTML = '<p class="text-muted small mb-0">불러오는 중...</p>';

        try {
            const response = await this.apiCall(`/employees?department_id=${departmentId}`);
            this.employeesByDept = response.data || [];

            if (this.employeesByDept.length === 0) {
                employeeContainer.innerHTML = '<p class="text-muted small mb-0">해당 부서에 직원이 없습니다.</p>';
                return;
            }

            const selectAllHtml = `
                <div class="form-check mb-2 border-bottom pb-2">
                    <input class="form-check-input" type="checkbox" id="select-all-employees">
                    <label class="form-check-label fw-bold" for="select-all-employees">
                        전체 선택
                    </label>
                </div>
            `;

            const employeesHtml = this.employeesByDept.map(employee => `
                <div class="form-check">
                    <input class="form-check-input employee-checkbox" type="checkbox" value="${employee.id}" id="employee-${employee.id}">
                    <label class="form-check-label" for="employee-${employee.id}">
                        ${this.escapeHtml(employee.name)} (${this.escapeHtml(employee.employee_number) || '번호 없음'})
                    </label>
                </div>
            `).join('');

            employeeContainer.innerHTML = selectAllHtml + employeesHtml;

            // 전체 선택 이벤트 리스너 추가
            const selectAllCheckbox = document.getElementById('select-all-employees');
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', (e) => {
                    const isChecked = e.target.checked;
                    const checkboxes = employeeContainer.querySelectorAll('.employee-checkbox');
                    checkboxes.forEach(box => box.checked = isChecked);
                });
            }
        } catch (error) {
            this.handleApiError(error, employeeContainer, '직원 목록을 불러오는 중 오류가 발생했습니다.');
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

        // 현재 선택된 직원 수
        const employeeCount = this.documentEmployees.length;
        const totalQuantityNeeded = quantity * Math.max(employeeCount, 1);

        if (totalQuantityNeeded > item.current_stock) {
            const message = employeeCount > 0
                ? `재고가 부족합니다. (필요: ${quantity} × ${employeeCount}명 = ${totalQuantityNeeded} ${item.unit}, 현재 재고: ${item.current_stock} ${item.unit})`
                : `재고가 부족합니다. (현재 재고: ${item.current_stock} ${item.unit})`;
            Toast.error(message);
            return;
        }

        const existingItem = this.documentItems.find(i => i.id == selectedItemId);
        if (existingItem) {
            Toast.info('이미 추가된 품목입니다.');
            return;
        }

        this.documentItems.push({ ...item, quantity });
        this.renderDocumentLists();

        // 품목 입력 필드 초기화
        quantityInput.value = 1;
    }

    addSelectedEmployees() {
        const selectedCheckboxes = document.querySelectorAll('#employee-select .form-check-input:checked');

        if (selectedCheckboxes.length === 0) {
            Toast.warning('직원을 선택하세요.');
            return;
        }

        let addedCount = 0;
        selectedCheckboxes.forEach(checkbox => {
            const employeeId = checkbox.value;
            const employee = this.employeesByDept.find(e => e.id == employeeId);

            if (employee) {
                const isAlreadyAdded = this.documentEmployees.some(e => e.id == employeeId);
                if (!isAlreadyAdded) {
                    this.documentEmployees.push(employee);
                    addedCount++;
                }
            }
        });

        if (addedCount > 0) {
            Toast.success(`${addedCount}명의 직원을 추가했습니다.`);
            this.renderDocumentLists();
        } else {
            Toast.info('이미 추가된 직원이거나, 선택된 직원이 없습니다.');
        }

        // Uncheck all checkboxes
        selectedCheckboxes.forEach(checkbox => checkbox.checked = false);
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
        const employeeCount = this.documentEmployees.length;

        if (itemList) {
            itemList.innerHTML = this.documentItems.map(item => {
                const quantityPerEmployee = item.quantity;
                const totalQuantity = quantityPerEmployee * employeeCount;

                return `
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div>${this.escapeHtml(item.item_name)}</div>
                                <small class="text-muted">1인당 ${quantityPerEmployee} ${item.unit}</small>
                                ${employeeCount > 0 ? `<br><small class="text-primary fw-bold">총 차감: ${totalQuantity} ${item.unit}</small>` : ''}
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn" data-id="${item.id}">&times;</button>
                        </div>
                    </li>
                `;
            }).join('');
        }

        if (employeeList) {
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
        const distributionDate = document.getElementById('distribution-date').value;

        if (!title) {
            Toast.error('문서 제목을 입력해주세요.');
            return;
        }
        if (!distributionDate) {
            Toast.error('지급일자를 선택해주세요.');
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
            distribution_date: distributionDate,
            items: this.documentItems.map(item => ({ id: item.id, quantity: item.quantity })),
            employees: this.documentEmployees.map(employee => employee.id)
        };

        this.setButtonLoading('#save-document-btn', '저장 중...');

        try {
            await this.apiCall('/supply/distributions/documents', {
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

    async showDetailModal(id) {
        try {
            const response = await this.apiCall(`/supply/distributions/${id}`);
            const doc = response.data;

            // 기본 정보 표시
            document.getElementById('detail-title').textContent = doc.title;
            document.getElementById('detail-distribution-date').textContent = doc.distribution_date ? new Date(doc.distribution_date).toLocaleDateString('ko-KR') : '-';
            document.getElementById('detail-created-by').textContent = doc.created_by_name || '알 수 없음';
            document.getElementById('detail-created-at').textContent = doc.created_at ? new Date(doc.created_at).toLocaleDateString('ko-KR') : '-';

            // 품목 목록 표시
            const itemsList = document.getElementById('detail-items-list');
            const employeeCount = doc.employees?.length || 0;
            itemsList.innerHTML = doc.items?.map(item => {
                const totalQty = item.quantity * employeeCount;
                return `
                    <li class="list-group-item">
                        <div>${this.escapeHtml(item.item_name)}</div>
                        <small class="text-muted">1인당 ${item.quantity}개</small>
                        ${employeeCount > 0 ? `<br><small class="text-primary">총 ${totalQty}개 지급</small>` : ''}
                    </li>
                `;
            }).join('') || '<li class="list-group-item">품목 없음</li>';

            // 직원 목록 표시
            const employeesList = document.getElementById('detail-employees-list');
            employeesList.innerHTML = doc.employees?.map(emp => `
                <li class="list-group-item">${this.escapeHtml(emp.employee_name)} (${this.escapeHtml(emp.department_name || '')})</li>
            `).join('') || '<li class="list-group-item">직원 없음</li>';

            // 상태에 따라 버튼 표시/숨김
            const editBtn = document.getElementById('edit-from-detail-btn');
            const cancelBtn = document.getElementById('cancel-from-detail-btn');
            if (doc.status === '취소') {
                editBtn.style.display = 'none';
                cancelBtn.style.display = 'none';
            } else {
                editBtn.style.display = 'inline-block';
                cancelBtn.style.display = 'inline-block';
                editBtn.onclick = () => {
                    bootstrap.Modal.getInstance(document.getElementById('detailViewModal')).hide();
                    this.showEditModal(id);
                };
                cancelBtn.onclick = () => {
                    bootstrap.Modal.getInstance(document.getElementById('detailViewModal')).hide();
                    this.showCancelModal(id);
                };
            }

            const modal = new bootstrap.Modal(document.getElementById('detailViewModal'));
            modal.show();
        } catch (error) {
            Toast.error('상세 정보를 불러오는데 실패했습니다.');
            console.error(error);
        }
    }

    async showEditModal(id) {
        this.currentEditingId = id;

        try {
            const response = await this.apiCall(`/supply/distributions/${id}`);
            const doc = response.data;

            // UI 데이터 로드
            await this.loadEditModalData();

            // 기본 정보 설정
            document.getElementById('edit-document-id').value = id;
            document.getElementById('edit-document-title').value = doc.title;
            document.getElementById('edit-distribution-date').value = doc.distribution_date;

            // 품목 및 직원 정보 설정
            this.editDocumentItems = doc.items?.map(item => ({
                id: item.item_id,
                item_name: item.item_name,
                quantity: item.quantity,
                unit: item.unit || '개',
                current_stock: 9999 // 임시값, 실제로는 available items에서 가져와야 함
            })) || [];

            this.editDocumentEmployees = doc.employees?.map(emp => ({
                id: emp.employee_id,
                name: emp.employee_name,
                employee_number: emp.employee_id,
                department_name: emp.department_name
            })) || [];

            this.renderEditDocumentLists();

            const modal = new bootstrap.Modal(document.getElementById('editDocumentModal'));
            modal.show();
        } catch (error) {
            Toast.error('문서를 불러오는데 실패했습니다.');
            console.error(error);
        }
    }

    async loadEditModalData() {
        const itemSelect = document.getElementById('edit-item-select');
        const deptSelect = document.getElementById('edit-department-select');

        try {
            const [itemsResponse, deptsResponse] = await Promise.all([
                this.apiCall(`/supply/distributions/available-items`),
                this.apiCall('/organization/managable-departments')
            ]);

            this.availableItems = itemsResponse.data?.items || [];
            this.departments = deptsResponse.data || [];

            this.renderOptions(itemSelect, this.availableItems, {
                value: 'id',
                text: item => `${item.item_name} (재고: ${this.formatNumber(item.current_stock)} ${item.unit})`,
                placeholder: '품목을 선택하세요'
            });

            this.renderOptions(deptSelect, this.departments, {
                value: 'id',
                text: 'name',
                placeholder: '부서를 선택하세요'
            });

            // 이벤트 리스너 설정
            this.setupEditModalHandlers();
        } catch (error) {
            console.error('Error loading edit modal data:', error);
        }
    }

    setupEditModalHandlers() {
        const addItemBtn = document.getElementById('edit-add-item-btn');
        const addEmployeeBtn = document.getElementById('edit-add-employee-btn');
        const updateDocumentBtn = document.getElementById('update-document-btn');
        const departmentSelect = document.getElementById('edit-department-select');
        const itemList = document.getElementById('edit-item-list');
        const employeeList = document.getElementById('edit-employee-list');

        // 중복 이벤트 방지를 위해 기존 리스너 제거 후 추가
        addItemBtn?.replaceWith(addItemBtn.cloneNode(true));
        addEmployeeBtn?.replaceWith(addEmployeeBtn.cloneNode(true));
        updateDocumentBtn?.replaceWith(updateDocumentBtn.cloneNode(true));
        departmentSelect?.replaceWith(departmentSelect.cloneNode(true));

        document.getElementById('edit-add-item-btn')?.addEventListener('click', () => this.addEditItem());
        document.getElementById('edit-add-employee-btn')?.addEventListener('click', () => this.addEditEmployees());
        document.getElementById('update-document-btn')?.addEventListener('click', () => this.handleUpdateDocument());
        document.getElementById('edit-department-select')?.addEventListener('change', (e) => this.loadEditEmployeesByDepartment(e.target.value));

        document.getElementById('edit-item-list')?.addEventListener('click', (e) => {
            const removeBtn = e.target.closest('.remove-edit-item-btn');
            if (removeBtn) {
                const itemId = removeBtn.dataset.id;
                this.editDocumentItems = this.editDocumentItems.filter(i => i.id != itemId);
                this.renderEditDocumentLists();
            }
        });

        document.getElementById('edit-employee-list')?.addEventListener('click', (e) => {
            const removeBtn = e.target.closest('.remove-edit-employee-btn');
            if (removeBtn) {
                const employeeId = removeBtn.dataset.id;
                this.editDocumentEmployees = this.editDocumentEmployees.filter(e => e.id != employeeId);
                this.renderEditDocumentLists();
            }
        });
    }

    async loadEditEmployeesByDepartment(departmentId) {
        const employeeContainer = document.getElementById('edit-employee-select');
        if (!employeeContainer) return;

        if (!departmentId) {
            employeeContainer.innerHTML = '<p class="text-muted small mb-0">부서를 먼저 선택하세요</p>';
            this.employeesByDept = [];
            return;
        }

        employeeContainer.innerHTML = '<p class="text-muted small mb-0">불러오는 중...</p>';

        try {
            const response = await this.apiCall(`/employees?department_id=${departmentId}`);
            this.employeesByDept = response.data || [];

            if (this.employeesByDept.length === 0) {
                employeeContainer.innerHTML = '<p class="text-muted small mb-0">해당 부서에 직원이 없습니다.</p>';
                return;
            }

            const employeesHtml = this.employeesByDept.map(employee => `
                <div class="form-check">
                    <input class="form-check-input edit-employee-checkbox" type="checkbox" value="${employee.id}" id="edit-employee-${employee.id}">
                    <label class="form-check-label" for="edit-employee-${employee.id}">
                        ${this.escapeHtml(employee.name)} (${this.escapeHtml(employee.employee_number) || '번호 없음'})
                    </label>
                </div>
            `).join('');

            employeeContainer.innerHTML = employeesHtml;
        } catch (error) {
            employeeContainer.innerHTML = '<div class="text-danger small">직원 목록을 불러오는데 실패했습니다.</div>';
        }
    }

    addEditItem() {
        const itemSelect = document.getElementById('edit-item-select');
        const quantityInput = document.getElementById('edit-item-quantity');
        const selectedItemId = itemSelect.value;
        const quantity = parseInt(quantityInput.value, 10);

        if (!selectedItemId || isNaN(quantity) || quantity <= 0) {
            Toast.warning('품목을 선택하고 유효한 수량을 입력하세요.');
            return;
        }

        const item = this.availableItems.find(i => i.id == selectedItemId);
        if (!item) return;

        const existingItem = this.editDocumentItems.find(i => i.id == selectedItemId);
        if (existingItem) {
            Toast.info('이미 추가된 품목입니다.');
            return;
        }

        this.editDocumentItems.push({ ...item, quantity });
        this.renderEditDocumentLists();
        quantityInput.value = 1;
    }

    addEditEmployees() {
        const selectedCheckboxes = document.querySelectorAll('#edit-employee-select .edit-employee-checkbox:checked');

        if (selectedCheckboxes.length === 0) {
            Toast.warning('직원을 선택하세요.');
            return;
        }

        let addedCount = 0;
        selectedCheckboxes.forEach(checkbox => {
            const employeeId = checkbox.value;
            const employee = this.employeesByDept.find(e => e.id == employeeId);

            if (employee) {
                const isAlreadyAdded = this.editDocumentEmployees.some(e => e.id == employeeId);
                if (!isAlreadyAdded) {
                    this.editDocumentEmployees.push(employee);
                    addedCount++;
                }
            }
        });

        if (addedCount > 0) {
            Toast.success(`${addedCount}명의 직원을 추가했습니다.`);
            this.renderEditDocumentLists();
        } else {
            Toast.info('이미 추가된 직원이거나, 선택된 직원이 없습니다.');
        }

        selectedCheckboxes.forEach(checkbox => checkbox.checked = false);
    }

    renderEditDocumentLists() {
        const itemList = document.getElementById('edit-item-list');
        const employeeList = document.getElementById('edit-employee-list');
        const employeeCount = this.editDocumentEmployees.length;

        if (itemList) {
            itemList.innerHTML = this.editDocumentItems.map(item => {
                const quantityPerEmployee = item.quantity;
                const totalQuantity = quantityPerEmployee * employeeCount;

                return `
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div>${this.escapeHtml(item.item_name)}</div>
                                <small class="text-muted">1인당 ${quantityPerEmployee} ${item.unit}</small>
                                ${employeeCount > 0 ? `<br><small class="text-primary fw-bold">총 차감: ${totalQuantity} ${item.unit}</small>` : ''}
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-edit-item-btn" data-id="${item.id}">&times;</button>
                        </div>
                    </li>
                `;
            }).join('');
        }

        if (employeeList) {
            employeeList.innerHTML = this.editDocumentEmployees.map(employee => `
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    ${this.escapeHtml(employee.name)} (${this.escapeHtml(employee.employee_number)})
                    <button type="button" class="btn btn-sm btn-outline-danger remove-edit-employee-btn" data-id="${employee.id}">&times;</button>
                </li>
            `).join('');
        }
    }

    async handleUpdateDocument() {
        const id = document.getElementById('edit-document-id').value;
        const title = document.getElementById('edit-document-title').value.trim();
        const distributionDate = document.getElementById('edit-distribution-date').value;

        if (!title) {
            Toast.error('문서 제목을 입력해주세요.');
            return;
        }
        if (!distributionDate) {
            Toast.error('지급일자를 선택해주세요.');
            return;
        }
        if (this.editDocumentItems.length === 0) {
            Toast.error('지급할 품목을 하나 이상 추가해주세요.');
            return;
        }
        if (this.editDocumentEmployees.length === 0) {
            Toast.error('지급받을 직원을 하나 이상 추가해주세요.');
            return;
        }

        const data = {
            title: title,
            distribution_date: distributionDate,
            items: this.editDocumentItems.map(item => ({ id: item.id, quantity: item.quantity })),
            employees: this.editDocumentEmployees.map(employee => employee.id)
        };

        this.setButtonLoading('#update-document-btn', '수정 중...');

        try {
            await this.apiCall(`/supply/distributions/documents/${id}`, {
                method: 'PUT',
                body: JSON.stringify(data),
                headers: { 'Content-Type': 'application/json' }
            });

            Toast.success('지급 문서가 성공적으로 수정되었습니다.');

            const modalEl = document.getElementById('editDocumentModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }

            this.editDocumentItems = [];
            this.editDocumentEmployees = [];

            this.loadDocumentsData(); // Refresh table
        } catch (error) {
            this.handleApiError(error, null, '문서 수정 중 오류가 발생했습니다.');
        } finally {
            this.resetButtonLoading('#update-document-btn', '저장');
        }
    }

    showCancelModal(id) {
        this.currentDistributionId = id;
        const modalEl = document.getElementById('cancelDistributionModal');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            const infoDiv = document.getElementById('cancel-distribution-info');

            infoDiv.innerHTML = `
                <div class="alert alert-info">
                    <p class="mb-0"><strong>문서 ID:</strong> ${id}</p>
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
            await this.apiCall(`/supply/distributions/documents/${this.currentDistributionId}/cancel`, {
                method: 'POST',
                body: JSON.stringify({ cancel_reason: cancelReason }),
                headers: { 'Content-Type': 'application/json' }
            });

            Toast.success('지급 문서가 성공적으로 취소되었습니다. 재고가 복원되었습니다.');
            this.loadDocumentsData();

            const modalEl = document.getElementById('cancelDistributionModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }

        } catch (error) {
            console.error('Error canceling distribution:', error);
            Toast.error(error.message || '지급 취소에 실패했습니다.');
        } finally {
            this.resetButtonLoading('#confirm-cancel-distribution-btn', '취소 처리');
        }
    }

    confirmDeleteDocument(id) {
        if (confirm('정말 이 문서를 삭제하시겠습니까?\n\n문서를 삭제하면 재고가 복원됩니다.')) {
            this.deleteDocument(id);
        }
    }

    async deleteDocument(id) {
        try {
            await this.apiCall(`/supply/distributions/documents/${id}`, {
                method: 'DELETE'
            });

            Toast.success('문서가 성공적으로 삭제되었습니다. 재고가 복원되었습니다.');
            this.loadDocumentsData();
        } catch (error) {
            Toast.error(error.message || '문서 삭제에 실패했습니다.');
            console.error(error);
        }
    }

    initializeSearchAndFilter() {
        const searchInput = document.getElementById('search-documents');
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

    /**
     * Handles API errors by logging them and showing a toast notification.
     * Optionally updates a UI element to reflect the error state.
     * @param {Error} error - The error object.
     * @param {HTMLElement} [targetElement] - Optional element to update with error message.
     * @param {string} [customMessage] - Custom error message to display.
     */
    handleApiError(error, targetElement = null, customMessage = '오류가 발생했습니다.') {
        console.error(customMessage, error);
        const message = customMessage || error.message;

        if (typeof Toast !== 'undefined') {
            Toast.error(message);
        } else {
            alert(message);
        }

        if (targetElement) {
            if (targetElement.tagName === 'SELECT') {
                targetElement.innerHTML = `<option value="">${message}</option>`;
                targetElement.disabled = true;
            } else {
                targetElement.innerHTML = `<div class="text-danger small">${message}</div>`;
            }
        }
    }

    /**
     * Renders options into a select element.
     * @param {HTMLSelectElement} element - The select element.
     * @param {Array} data - Array of data objects.
     * @param {Object} config - Configuration for rendering options.
     * @param {string} config.value - Key for the option value.
     * @param {string|Function} config.text - Key or function for the option text.
     * @param {string} [config.placeholder] - Placeholder text.
     */
    renderOptions(element, data, config) {
        if (!element) return;

        const { value, text, placeholder } = config;
        let html = '';

        if (placeholder) {
            html += `<option value="">${placeholder}</option>`;
        }

        html += data.map(item => {
            const optionValue = item[value];
            const optionText = typeof text === 'function' ? text(item) : item[text];
            return `<option value="${optionValue}">${optionText}</option>`;
        }).join('');

        element.innerHTML = html;
    }

    /**
     * Formats a number with comma separators.
     * @param {number|string} number - The number to format.
     * @returns {string} Formatted number string.
     */
    formatNumber(number) {
        if (number === null || number === undefined) return '0';
        return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
}

// 전역 인스턴스 생성
new SupplyDistributionsPage();
