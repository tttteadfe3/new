/**
 * LeaveAdminManagement - 연차 관리자 관리 페이지 클래스
 * 
 * 주요 기능:
 * - 연차 부여/계산 관리
 * - 연차 조정 관리
 * - 연차 소멸 처리
 * - 일괄 처리 기능
 * - 데이터 내보내기
 * 
 * 요구사항: 7.1, 7.2, 7.3, 7.4, 10.1, 10.2, 10.3, 10.4
 */
class LeaveAdminManagement extends BasePage {
    constructor() {
        super({
            API_URL: '/leaves_admin'
        });
        
        this.elements = {};
        this.state = {
            currentTab: 'grant',
            grantResults: [],
            adjustHistory: [],
            expireTargets: [],
            departments: [],
            employees: []
        };
    }

    initializeApp() {
        this.cacheDOMElements();
        this.setupEventListeners();
        this.loadInitialData();
    }

    cacheDOMElements() {
        // 탭 관련 요소들
        this.elements.grantTab = document.querySelector('[data-bs-toggle="tab"][href="#grant-tab"]');
        this.elements.adjustTab = document.querySelector('[data-bs-toggle="tab"][href="#adjust-tab"]');
        this.elements.expireTab = document.querySelector('[data-bs-toggle="tab"][href="#expire-tab"]');
        this.elements.bulkTab = document.querySelector('[data-bs-toggle="tab"][href="#bulk-tab"]');

        // 연차 부여 관련 요소들
        this.elements.grantSettingsForm = document.getElementById('grant-settings-form');
        this.elements.grantTargetYear = document.getElementById('grant-target-year');
        this.elements.grantTargetDepartment = document.getElementById('grant-target-department');
        this.elements.grantPreviewMode = document.getElementById('grant-preview-mode');
        this.elements.grantTargetList = document.getElementById('grant-target-list');
        this.elements.grantSelectAll = document.getElementById('grant-select-all');
        this.elements.executeGrantBtn = document.getElementById('execute-grant-btn');
        this.elements.grantAllBtn = document.getElementById('grant-all-btn');

        // 연차 조정 관련 요소들
        this.elements.adjustForm = document.getElementById('adjust-form');
        this.elements.adjustEmployee = document.getElementById('adjust-employee');
        this.elements.adjustAmount = document.getElementById('adjust-amount');
        this.elements.adjustGrantYear = document.getElementById('adjust-grant-year');
        this.elements.adjustGrantYearGroup = document.getElementById('adjust-grant-year-group');
        this.elements.adjustReasonType = document.getElementById('adjust-reason-type');
        this.elements.adjustDetailReason = document.getElementById('adjust-detail-reason');
        this.elements.adjustHistoryList = document.getElementById('adjust-history-list');

        // 연차 소멸 관련 요소들
        this.elements.expireForm = document.getElementById('expire-form');
        this.elements.expireYear = document.getElementById('expire-year');
        this.elements.expireDepartment = document.getElementById('expire-department');
        this.elements.expirePreviewMode = document.getElementById('expire-preview-mode');
        this.elements.expireTargetList = document.getElementById('expire-target-list');
        this.elements.expireSelectAll = document.getElementById('expire-select-all');
        this.elements.executeExpireBtn = document.getElementById('execute-expire-btn');

        // 일괄 처리 관련 요소들
        this.elements.bulkApproveForm = document.getElementById('bulk-approve-form');
        this.elements.bulkDepartment = document.getElementById('bulk-department');
        this.elements.bulkDateFrom = document.getElementById('bulk-date-from');
        this.elements.bulkDateTo = document.getElementById('bulk-date-to');
        this.elements.bulkApproveReason = document.getElementById('bulk-approve-reason');

        // 데이터 내보내기 관련 요소들
        this.elements.exportForm = document.getElementById('export-form');
        this.elements.exportType = document.getElementById('export-type');
        this.elements.exportDepartment = document.getElementById('export-department');
        this.elements.exportYear = document.getElementById('export-year');

        // 확인 모달
        this.elements.confirmModal = document.getElementById('confirm-action-modal');
        this.elements.confirmModalTitle = document.getElementById('confirm-modal-title');
        this.elements.confirmModalContent = document.getElementById('confirm-modal-content');
        this.elements.confirmActionBtn = document.getElementById('confirm-action-btn');
    }

    setupEventListeners() {
        // 탭 전환 이벤트
        [this.elements.grantTab, this.elements.adjustTab, this.elements.expireTab, this.elements.bulkTab].forEach(tab => {
            if (tab) {
                tab.addEventListener('shown.bs.tab', (e) => {
                    const tabId = e.target.getAttribute('href').replace('#', '').replace('-tab', '');
                    this.state.currentTab = tabId;
                    this.onTabChanged(tabId);
                });
            }
        });

        // 연차 부여 관련 이벤트
        if (this.elements.grantSettingsForm) {
            this.elements.grantSettingsForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.calculateGrantTargets();
            });
        }

        if (this.elements.grantSelectAll) {
            this.elements.grantSelectAll.addEventListener('change', (e) => {
                this.toggleAllGrantSelections(e.target.checked);
            });
        }

        if (this.elements.executeGrantBtn) {
            this.elements.executeGrantBtn.addEventListener('click', () => {
                this.executeSelectedGrants();
            });
        }

        if (this.elements.grantAllBtn) {
            this.elements.grantAllBtn.addEventListener('click', () => {
                this.executeAllGrants();
            });
        }

        // 연차 조정 관련 이벤트
        if (this.elements.adjustForm) {
            this.elements.adjustForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.executeAdjustment();
            });
        }

        if (this.elements.adjustAmount) {
            this.elements.adjustAmount.addEventListener('input', (e) => {
                this.updateAdjustmentPreview(e.target.value);
            });
        }

        // 연차 소멸 관련 이벤트
        if (this.elements.expireForm) {
            this.elements.expireForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.searchExpireTargets();
            });
        }

        if (this.elements.expireSelectAll) {
            this.elements.expireSelectAll.addEventListener('change', (e) => {
                this.toggleAllExpireSelections(e.target.checked);
            });
        }

        if (this.elements.executeExpireBtn) {
            this.elements.executeExpireBtn.addEventListener('click', () => {
                this.executeSelectedExpires();
            });
        }

        // 일괄 처리 관련 이벤트
        if (this.elements.bulkApproveForm) {
            this.elements.bulkApproveForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.executeBulkApproval();
            });
        }

        // 데이터 내보내기 관련 이벤트
        if (this.elements.exportForm) {
            this.elements.exportForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.executeDataExport();
            });
        }

        // 확인 모달 이벤트
        if (this.elements.confirmActionBtn) {
            this.elements.confirmActionBtn.addEventListener('click', () => {
                this.executeConfirmedAction();
            });
        }
    }

    loadInitialData() {
        this.loadDepartments();
        this.loadEmployees();
        this.onTabChanged('grant');
    }

    /**
     * 부서 목록 로드
     */
    async loadDepartments() {
        try {
            const response = await this.apiCall('/organization/managable-departments');
            this.state.departments = response.data;
            
            // 부서 드롭다운 업데이트
            const departmentSelects = [
                this.elements.grantTargetDepartment,
                this.elements.expireDepartment,
                this.elements.bulkDepartment,
                this.elements.exportDepartment
            ];

            departmentSelects.forEach(select => {
                if (select) {
                    select.innerHTML = '<option value="">전체 부서</option>';
                    this.state.departments.forEach(dept => {
                        const option = new Option(dept.name, dept.id);
                        select.add(option);
                    });
                }
            });
            
        } catch (error) {
            console.error('Failed to load departments:', error);
            Toast.error('부서 목록을 불러오는데 실패했습니다.');
        }
    }

    /**
     * 직원 목록 로드
     */
    async loadEmployees() {
        try {
            const response = await this.apiCall('/employees?status=active');
            this.state.employees = response.data;
            
            // 직원 드롭다운 업데이트
            if (this.elements.adjustEmployee) {
                this.elements.adjustEmployee.innerHTML = '<option value="">직원을 선택해주세요</option>';
                this.state.employees.forEach(emp => {
                    const option = new Option(`${emp.name} (${emp.department_name || '부서 미지정'})`, emp.id);
                    this.elements.adjustEmployee.add(option);
                });
            }
            
        } catch (error) {
            console.error('Failed to load employees:', error);
            Toast.error('직원 목록을 불러오는데 실패했습니다.');
        }
    }

    /**
     * 탭 변경 시 처리
     */
    onTabChanged(tabId) {
        switch (tabId) {
            case 'grant':
                // 연차 부여 탭 활성화 시 처리
                break;
            case 'adjust':
                this.loadAdjustmentHistory();
                break;
            case 'expire':
                // 연차 소멸 탭 활성화 시 처리
                break;
            case 'bulk':
                // 일괄 처리 탭 활성화 시 처리
                break;
        }
    }

    /**
     * 연차 부여 대상자 계산
     */
    async calculateGrantTargets() {
        const formData = new FormData(this.elements.grantSettingsForm);
        const year = formData.get('year');
        const departmentId = formData.get('department_id');
        const previewMode = this.elements.grantPreviewMode.checked;

        if (!year) {
            Toast.error('부여 연도를 선택해주세요.');
            return;
        }

        try {
            this.setTableLoading(this.elements.grantTargetList, '연차 계산 중...');
            
            const response = await this.apiCall(`${this.config.API_URL}/calculate-grant-targets`, {
                method: 'POST',
                body: {
                    year: parseInt(year),
                    department_id: departmentId || null,
                    preview_mode: previewMode
                }
            });
            
            this.state.grantResults = response.data;
            this.renderGrantTargetsTable(response.data);
            
            // 버튼 활성화
            this.elements.executeGrantBtn.disabled = false;
            this.elements.grantAllBtn.disabled = false;
            
        } catch (error) {
            console.error('Failed to calculate grant targets:', error);
            this.setTableError(this.elements.grantTargetList, `계산 실패: ${error.message}`);
        }
    }

    /**
     * 연차 부여 대상자 테이블 렌더링
     */
    renderGrantTargetsTable(targets) {
        if (!this.elements.grantTargetList) return;

        if (targets.length === 0) {
            this.elements.grantTargetList.innerHTML = `
                <tr><td colspan="10" class="text-center">부여 대상자가 없습니다.</td></tr>
            `;
            return;
        }

        this.elements.grantTargetList.innerHTML = targets.map(target => `
            <tr class="grant-result-row ${target.status}">
                <td>
                    <input type="checkbox" class="form-check-input grant-checkbox" 
                           value="${target.employee_id}" ${target.can_grant ? '' : 'disabled'}>
                </td>
                <td>${target.employee_name}</td>
                <td>${target.department_name || '<i>미지정</i>'}</td>
                <td>${target.hire_date}</td>
                <td>${target.years_of_service}년</td>
                <td>${target.base_days}일</td>
                <td>${target.seniority_days}일</td>
                <td>${target.monthly_days || 0}일</td>
                <td class="fw-bold">${target.total_days}일</td>
                <td>
                    <span class="grant-status-badge ${target.status}">
                        ${this.getGrantStatusText(target.status)}
                    </span>
                </td>
            </tr>
        `).join('');

        // 체크박스 이벤트 리스너 추가
        this.elements.grantTargetList.querySelectorAll('.grant-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                this.updateGrantSelectionCount();
            });
        });
    }

    /**
     * 부여 상태 텍스트 반환
     */
    getGrantStatusText(status) {
        const statusMap = {
            'preview': '미리보기',
            'success': '부여 완료',
            'error': '부여 실패',
            'already_granted': '이미 부여됨',
            'not_eligible': '부여 대상 아님'
        };
        return statusMap[status] || status;
    }

    /**
     * 전체 선택/해제
     */
    toggleAllGrantSelections(checked) {
        const checkboxes = this.elements.grantTargetList.querySelectorAll('.grant-checkbox:not(:disabled)');
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
        });
        this.updateGrantSelectionCount();
    }

    /**
     * 선택된 항목 수 업데이트
     */
    updateGrantSelectionCount() {
        const selectedCount = this.elements.grantTargetList.querySelectorAll('.grant-checkbox:checked').length;
        this.elements.executeGrantBtn.textContent = `선택 항목 부여 (${selectedCount}개)`;
        this.elements.executeGrantBtn.disabled = selectedCount === 0;
    }

    /**
     * 선택된 연차 부여 실행
     */
    async executeSelectedGrants() {
        const selectedIds = Array.from(this.elements.grantTargetList.querySelectorAll('.grant-checkbox:checked'))
            .map(cb => parseInt(cb.value));

        if (selectedIds.length === 0) {
            Toast.error('부여할 직원을 선택해주세요.');
            return;
        }

        this.showConfirmModal(
            '선택 항목 연차 부여',
            `선택된 ${selectedIds.length}명의 직원에게 연차를 부여하시겠습니까?`,
            () => this.executeGrant(selectedIds)
        );
    }

    /**
     * 전체 연차 부여 실행
     */
    async executeAllGrants() {
        const allIds = this.state.grantResults
            .filter(result => result.can_grant)
            .map(result => result.employee_id);

        if (allIds.length === 0) {
            Toast.error('부여 가능한 직원이 없습니다.');
            return;
        }

        this.showConfirmModal(
            '전체 연차 부여',
            `총 ${allIds.length}명의 직원에게 연차를 부여하시겠습니까?`,
            () => this.executeGrant(allIds)
        );
    }

    /**
     * 연차 부여 실행
     */
    async executeGrant(employeeIds) {
        const year = this.elements.grantTargetYear.value;
        
        try {
            this.showProcessingOverlay('연차 부여 중...');
            
            const response = await this.apiCall(`${this.config.API_URL}/execute-grant`, {
                method: 'POST',
                body: {
                    year: parseInt(year),
                    employee_ids: employeeIds
                }
            });
            
            Toast.success(`${response.data.success_count}명의 연차 부여가 완료되었습니다.`);
            
            if (response.data.failed_count > 0) {
                Toast.warning(`${response.data.failed_count}명의 부여에 실패했습니다.`);
            }
            
            // 결과 다시 로드
            this.calculateGrantTargets();
            
        } catch (error) {
            console.error('Failed to execute grant:', error);
            Toast.error(`연차 부여 실패: ${error.message}`);
        } finally {
            this.hideProcessingOverlay();
        }
    }

    /**
     * 연차 조정 내역 로드
     */
    async loadAdjustmentHistory() {
        if (!this.elements.adjustHistoryList) return;

        try {
            this.setTableLoading(this.elements.adjustHistoryList, '조정 내역 로딩 중...');
            
            const response = await this.apiCall(`${this.config.API_URL}/adjustment-history`);
            this.renderAdjustmentHistoryTable(response.data);
            
        } catch (error) {
            console.error('Failed to load adjustment history:', error);
            this.setTableError(this.elements.adjustHistoryList, `로딩 실패: ${error.message}`);
        }
    }

    /**
     * 연차 조정 내역 테이블 렌더링
     */
    renderAdjustmentHistoryTable(history) {
        if (!this.elements.adjustHistoryList) return;

        if (history.length === 0) {
            this.elements.adjustHistoryList.innerHTML = `
                <tr><td colspan="6" class="text-center">조정 내역이 없습니다.</td></tr>
            `;
            return;
        }

        this.elements.adjustHistoryList.innerHTML = history.map(item => `
            <tr>
                <td>${new Date(item.created_at).toLocaleString()}</td>
                <td>${item.employee_name}</td>
                <td class="${item.amount > 0 ? 'text-success' : 'text-danger'}">
                    ${item.amount > 0 ? '+' : ''}${item.amount}일
                </td>
                <td>${item.grant_year ? `<span class="badge bg-info">${item.grant_year}년</span>` : '<span class="text-muted">-</span>'}</td>
                <td>${item.reason}</td>
                <td>${item.created_by_name}</td>
            </tr>
        `).join('');
    }

    /**
     * 조정 미리보기 업데이트
     */
    updateAdjustmentPreview(amount) {
        const numAmount = parseFloat(amount);
        if (isNaN(numAmount)) return;

        if (this.elements.adjustAmount) {
            this.elements.adjustAmount.classList.remove('positive', 'negative');
            if (numAmount > 0) {
                this.elements.adjustAmount.classList.add('positive');
                // 연차 추가 시 부여연도 필수
                if (this.elements.adjustGrantYearGroup) {
                    this.elements.adjustGrantYearGroup.style.display = 'block';
                    if (this.elements.adjustGrantYear) {
                        this.elements.adjustGrantYear.required = true;
                    }
                }
            } else if (numAmount < 0) {
                this.elements.adjustAmount.classList.add('negative');
                // 연차 차감 시 부여연도 선택사항
                if (this.elements.adjustGrantYearGroup) {
                    this.elements.adjustGrantYearGroup.style.display = 'block';
                    if (this.elements.adjustGrantYear) {
                        this.elements.adjustGrantYear.required = false;
                    }
                }
            } else {
                // 0일 경우
                if (this.elements.adjustGrantYearGroup) {
                    this.elements.adjustGrantYearGroup.style.display = 'block';
                }
            }
        }
    }

    /**
     * 연차 조정 실행
     */
    async executeAdjustment() {
        const formData = new FormData(this.elements.adjustForm);
        const employeeId = formData.get('employee_id');
        const amount = formData.get('amount');
        const grantYear = formData.get('grant_year');
        const reasonType = formData.get('reason_type');
        const detailReason = formData.get('detail_reason');

        if (!employeeId || !amount || !detailReason) {
            Toast.error('모든 필드를 입력해주세요.');
            return;
        }

        const numAmount = parseFloat(amount);
        
        // 연차 추가 시 부여연도 필수 체크
        if (numAmount > 0 && !grantYear) {
            Toast.error('연차 추가 시 부여연도는 필수입니다.');
            return;
        }

        if (detailReason.trim().length < 5) {
            Toast.error('상세 사유를 5자 이상 입력해주세요.');
            return;
        }

        const employee = this.state.employees.find(emp => emp.id == employeeId);
        const employeeName = employee ? employee.name : '선택된 직원';
        
        const grantYearText = grantYear ? ` (${grantYear}년 연차)` : '';

        this.showConfirmModal(
            '연차 조정 확인',
            `${employeeName}의 연차를 ${amount}일${grantYearText} 조정하시겠습니까?<br><br>사유: ${reasonType} - ${detailReason}`,
            async () => {
                try {
                    const requestBody = {
                        employee_id: parseInt(employeeId),
                        amount: numAmount,
                        reason: `${reasonType} - ${detailReason.trim()}`
                    };
                    
                    // grant_year가 있으면 추가
                    if (grantYear) {
                        requestBody.grant_year = parseInt(grantYear);
                    }
                    
                    await this.apiCall(`${this.config.API_URL}/adjust-leave`, {
                        method: 'POST',
                        body: requestBody
                    });
                    
                    Toast.success('연차 조정이 완료되었습니다.');
                    this.elements.adjustForm.reset();
                    this.loadAdjustmentHistory();
                    
                } catch (error) {
                    console.error('Failed to adjust leave:', error);
                    Toast.error(`연차 조정 실패: ${error.message}`);
                }
            }
        );
    }

    /**
     * 연차 소멸 대상자 검색
     */
    async searchExpireTargets() {
        const formData = new FormData(this.elements.expireForm);
        const year = formData.get('year');
        const departmentId = formData.get('department_id');
        const previewMode = this.elements.expirePreviewMode.checked;

        try {
            this.setTableLoading(this.elements.expireTargetList, '소멸 대상자 검색 중...');
            
            const response = await this.apiCall(`${this.config.API_URL}/search-expire-targets`, {
                method: 'POST',
                body: {
                    year: parseInt(year),
                    department_id: departmentId || null,
                    preview_mode: previewMode
                }
            });
            
            this.state.expireTargets = response.data;
            this.renderExpireTargetsTable(response.data);
            
            this.elements.executeExpireBtn.disabled = response.data.length === 0;
            
        } catch (error) {
            console.error('Failed to search expire targets:', error);
            this.setTableError(this.elements.expireTargetList, `검색 실패: ${error.message}`);
        }
    }

    /**
     * 연차 소멸 대상자 테이블 렌더링
     */
    renderExpireTargetsTable(targets) {
        if (!this.elements.expireTargetList) return;

        if (targets.length === 0) {
            this.elements.expireTargetList.innerHTML = `
                <tr><td colspan="6" class="text-center">소멸 대상자가 없습니다.</td></tr>
            `;
            return;
        }

        this.elements.expireTargetList.innerHTML = targets.map(target => `
            <tr class="expire-target-row">
                <td>
                    <input type="checkbox" class="form-check-input expire-checkbox" value="${target.employee_id}">
                </td>
                <td>${target.employee_name}</td>
                <td>${target.department_name || '<i>미지정</i>'}</td>
                <td><i>재직중</i></td>
                <td class="fw-bold text-warning">${target.current_balance}일</td>
                <td>
                    <span class="badge bg-warning">소멸 대상</span>
                </td>
            </tr>
        `).join('');

        // 체크박스 이벤트 리스너 추가
        this.elements.expireTargetList.querySelectorAll('.expire-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                this.updateExpireSelectionCount();
            });
        });
    }

    /**
     * 전체 소멸 선택/해제
     */
    toggleAllExpireSelections(checked) {
        const checkboxes = this.elements.expireTargetList.querySelectorAll('.expire-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
        });
        this.updateExpireSelectionCount();
    }

    /**
     * 소멸 선택된 항목 수 업데이트
     */
    updateExpireSelectionCount() {
        const selectedCount = this.elements.expireTargetList.querySelectorAll('.expire-checkbox:checked').length;
        this.elements.executeExpireBtn.textContent = `선택 항목 소멸 (${selectedCount}개)`;
        this.elements.executeExpireBtn.disabled = selectedCount === 0;
    }

    /**
     * 선택된 연차 소멸 실행
     */
    async executeSelectedExpires() {
        const selectedIds = Array.from(this.elements.expireTargetList.querySelectorAll('.expire-checkbox:checked'))
            .map(cb => parseInt(cb.value));

        if (selectedIds.length === 0) {
            Toast.error('소멸할 직원을 선택해주세요.');
            return;
        }

        this.showConfirmModal(
            '연차 소멸 확인',
            `선택된 ${selectedIds.length}명의 연차를 소멸시키시겠습니까?<br><br><strong>주의: 소멸된 연차는 복구할 수 없습니다.</strong>`,
            async () => {
                try {
                    const response = await this.apiCall(`${this.config.API_URL}/execute-expire`, {
                        method: 'POST',
                        body: { employee_ids: selectedIds }
                    });
                    
                    Toast.success(`${response.data.success_count}명의 연차 소멸이 완료되었습니다.`);
                    this.searchExpireTargets();
                    
                } catch (error) {
                    console.error('Failed to execute expire:', error);
                    Toast.error(`연차 소멸 실패: ${error.message}`);
                }
            }
        );
    }

    /**
     * 일괄 승인 실행
     */
    async executeBulkApproval() {
        const formData = new FormData(this.elements.bulkApproveForm);
        const departmentId = formData.get('department_id');
        const dateFrom = formData.get('date_from');
        const dateTo = formData.get('date_to');
        const reason = formData.get('reason');

        try {
            const response = await this.apiCall(`${this.config.API_URL}/bulk-approve`, {
                method: 'POST',
                body: {
                    department_id: departmentId || null,
                    date_from: dateFrom || null,
                    date_to: dateTo || null,
                    reason: reason || null
                }
            });
            
            Toast.success(`${response.data.approved_count}건의 신청이 일괄 승인되었습니다.`);
            this.elements.bulkApproveForm.reset();
            
        } catch (error) {
            console.error('Failed to execute bulk approval:', error);
            Toast.error(`일괄 승인 실패: ${error.message}`);
        }
    }

    /**
     * 데이터 내보내기 실행
     */
    async executeDataExport() {
        const formData = new FormData(this.elements.exportForm);
        const type = formData.get('type');
        const departmentId = formData.get('department_id');
        const year = formData.get('year');

        try {
            const params = new URLSearchParams({
                type: type,
                year: year
            });
            
            if (departmentId) {
                params.append('department_id', departmentId);
            }

            // 파일 다운로드
            window.open(`${this.config.API_URL}/export?${params.toString()}`, '_blank');
            
            Toast.success('데이터 내보내기가 시작되었습니다.');
            
        } catch (error) {
            console.error('Failed to execute export:', error);
            Toast.error(`데이터 내보내기 실패: ${error.message}`);
        }
    }

    /**
     * 확인 모달 표시
     */
    showConfirmModal(title, content, callback) {
        if (!this.elements.confirmModal) return;

        this.elements.confirmModalTitle.textContent = title;
        this.elements.confirmModalContent.innerHTML = content;
        
        this.pendingAction = callback;
        
        const modal = new bootstrap.Modal(this.elements.confirmModal);
        modal.show();
    }

    /**
     * 확인된 액션 실행
     */
    async executeConfirmedAction() {
        if (this.pendingAction) {
            const modal = bootstrap.Modal.getInstance(this.elements.confirmModal);
            if (modal) {
                modal.hide();
            }
            
            await this.pendingAction();
            this.pendingAction = null;
        }
    }

    /**
     * 처리 중 오버레이 표시
     */
    showProcessingOverlay(message) {
        const overlay = document.createElement('div');
        overlay.className = 'processing-overlay';
        overlay.innerHTML = `
            <div class="processing-content">
                <div class="processing-spinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <div>${message}</div>
            </div>
        `;
        document.body.appendChild(overlay);
        this.processingOverlay = overlay;
    }

    /**
     * 처리 중 오버레이 숨기기
     */
    hideProcessingOverlay() {
        if (this.processingOverlay) {
            this.processingOverlay.remove();
            this.processingOverlay = null;
        }
    }

    /**
     * 테이블 로딩 상태 설정
     */
    setTableLoading(tableBody, message) {
        if (tableBody) {
            const colCount = tableBody.closest('table').querySelectorAll('thead th').length;
            tableBody.innerHTML = `
                <tr><td colspan="${colCount}" class="text-center">
                    <span class="spinner-border spinner-border-sm me-2"></span>${message}
                </td></tr>
            `;
        }
    }

    /**
     * 테이블 에러 상태 설정
     */
    setTableError(tableBody, message) {
        if (tableBody) {
            const colCount = tableBody.closest('table').querySelectorAll('thead th').length;
            tableBody.innerHTML = `
                <tr><td colspan="${colCount}" class="text-center text-danger">${message}</td></tr>
            `;
        }
    }

    /**
     * 리소스 정리
     */
    cleanup() {
        super.cleanup();
        this.hideProcessingOverlay();
    }
}

// 페이지 로드 시 인스턴스 생성
new LeaveAdminManagement();