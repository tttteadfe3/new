class LitteringDeletedAdminPage extends BasePage {
    constructor() {
        const currentScript = document.currentScript;
        let scriptConfig = {};
        if (currentScript) {
            const options = currentScript.getAttribute('data-options');
            if (options) {
                try {
                    scriptConfig = JSON.parse(options);
                } catch (e) {
                    console.error('Failed to parse script options for LitteringDeletedAdminPage:', e);
                }
            }
        }
        super({
            ...scriptConfig,
            API_URL: '/littering_admin/reports'
        });
        this.state = {
            ...this.state,
            deletedList: []
        };
    }

    initializeApp() {
        this.loadInitialData();
    }

    async loadInitialData() {
        try {
            const response = await this.apiCall(`${this.config.API_URL}?status=삭제`);
            this.state.deletedList = response.data || [];
            this.renderDeletedList();
        } catch (error) {
            console.error('삭제 목록 로드 실패:', error);
            const listContainer = document.getElementById('deleted-items-list');
            if (listContainer) {
                listContainer.innerHTML = `<tr><td colspan="6" class="text-center text-danger">목록을 불러오는데 실패했습니다: ${error.message}</td></tr>`;
            }
        }
    }

    renderDeletedList() {
        const listContainer = document.getElementById('deleted-items-list');
        
        // 컨테이너가 존재하는지 확인
        if (!listContainer) {
            console.error('deleted-items-list 요소를 찾을 수 없습니다.');
            return;
        }

        listContainer.innerHTML = '';

        if (this.state.deletedList.length === 0) {
            listContainer.innerHTML = '<tr><td colspan="6" class="text-center text-muted">삭제된 항목이 없습니다.</td></tr>';
            return;
        }

        this.state.deletedList.forEach(item => {
            const registrantName = item.employee_name || item.user_name || '알 수 없음';
            const itemHtml = `<tr data-id="${item.id}">
                    <td>${item.id}</td>
                    <td>${item.jibun_address || item.road_address || '주소 없음'}</td>
                    <td>${item.waste_type}</td>
                    <td>${registrantName}</td>
                    <td>${new Date(item.deleted_at).toLocaleString()}</td>
                    <td>
                        <button class="btn btn-sm btn-warning restore-btn">복원</button>
                        <button class="btn btn-sm btn-danger permanent-delete-btn">영구 삭제</button>
                    </td>
                </tr>`;
            
            // table에 직접 innerHTML 할당 (div가 아닌 table에!)
            const tempTable = document.createElement('table');
            tempTable.innerHTML = itemHtml;
            const itemNode = tempTable.querySelector('tr');
            
            if (!itemNode) {
                console.error('tr 요소를 찾을 수 없습니다!');
                return;
            }
            
            // 버튼 요소 존재 확인 후 이벤트 리스너 추가
            const restoreBtn = itemNode.querySelector('.restore-btn');
            const deleteBtn = itemNode.querySelector('.permanent-delete-btn');
            
            if (restoreBtn) {
                restoreBtn.addEventListener('click', () => this.restoreReport(item.id));
            } else {
                console.warn(`복원 버튼을 찾을 수 없습니다 (ID: ${item.id})`);
            }
            
            if (deleteBtn) {
                deleteBtn.addEventListener('click', () => this.permanentlyDeleteReport(item.id));
            } else {
                console.warn(`삭제 버튼을 찾을 수 없습니다 (ID: ${item.id})`);
            }
            
            listContainer.appendChild(itemNode);
        });
    }

    async restoreReport(caseId) {
        const result = await Confirm.fire('복원 확인', `ID ${caseId} 항목을 복원하시겠습니까?`);
        if (result.isConfirmed) {
            try {
                await this.apiCall(`${this.config.API_URL}/${caseId}/restore`, { method: 'POST' });
                Toast.success('항목이 성공적으로 복원되었습니다.');
                this.loadInitialData();
            } catch (error) {
                Toast.error('복원에 실패했습니다: ' + error.message);
            }
        }
    }

    async permanentlyDeleteReport(caseId) {
        const result = await Confirm.fire('영구 삭제 확인', `ID ${caseId} 항목을 영구적으로 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.`);
        if (result.isConfirmed) {
            try {
                await this.apiCall(`${this.config.API_URL}/${caseId}/permanent`, { method: 'DELETE' });
                Toast.success('항목이 성공적으로 영구 삭제되었습니다.');
                this.loadInitialData();
            } catch (error) {
                Toast.error('영구 삭제에 실패했습니다: ' + error.message);
            }
        }
    }
}

new LitteringDeletedAdminPage();
