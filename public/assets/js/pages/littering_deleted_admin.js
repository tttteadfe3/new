class LitteringDeletedAdminApp extends BaseApp {
    constructor() {
        super({
            API_URL: '/api/littering_admin/reports' // Updated API Base URL
        });

        this.state = {
            ...this.state,
            deletedList: []
        };
    }

    init() {
        this.loadData();
    }

    async _fetch(url, options = {}) {
        const defaultHeaders = {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        };
        const fetchOptions = { ...options, headers: { ...defaultHeaders, ...(options.headers || {}) } };

        const response = await fetch(url, fetchOptions);
        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'API 요청에 실패했습니다.');
        }
        return result;
    }

    async loadData() {
        try {
            const response = await this._fetch(`${this.config.API_URL}?status=deleted`);
            this.state.deletedList = response.data || [];
            this.renderDeletedList();
        } catch (error) {
            console.error('삭제 목록 로드 실패:', error);
            document.getElementById('deleted-items-list').innerHTML = `<tr><td colspan="6" class="text-center text-danger">목록을 불러오는데 실패했습니다: ${error.message}</td></tr>`;
        }
    }

    renderDeletedList() {
        const listContainer = document.getElementById('deleted-items-list');
        listContainer.innerHTML = '';

        if (this.state.deletedList.length === 0) {
            listContainer.innerHTML = '<tr><td colspan="6" class="text-center text-muted">삭제된 항목이 없습니다.</td></tr>';
            return;
        }

        this.state.deletedList.forEach(item => {
            const registrantName = item.employee_name || item.user_name || '알 수 없음';
            const itemHtml = `
                <tr data-id="${item.id}">
                    <td>${item.id}</td>
                    <td>${item.address}</td>
                    <td>${item.waste_type}</td>
                    <td>${registrantName}</td>
                    <td>${new Date(item.deleted_at).toLocaleString()}</td>
                    <td>
                        <button class="btn btn-sm btn-warning restore-btn">복원</button>
                        <button class="btn btn-sm btn-danger permanent-delete-btn">영구 삭제</button>
                    </td>
                </tr>
            `;
            const itemNode = document.createRange().createContextualFragment(itemHtml).firstChild;

            itemNode.querySelector('.restore-btn').addEventListener('click', () => this.restoreReport(item.id));
            itemNode.querySelector('.permanent-delete-btn').addEventListener('click', () => this.permanentlyDeleteReport(item.id));

            listContainer.appendChild(itemNode);
        });
    }

    async restoreReport(caseId) {
        const result = await Confirm.fire('복원 확인', `ID ${caseId} 항목을 복원하시겠습니까?`);
        if (result.isConfirmed) {
            try {
                await this._fetch(`${this.config.API_URL}/${caseId}/restore`, { method: 'POST' });
                Toast.success('항목이 성공적으로 복원되었습니다.');
                this.loadData();
            } catch (error) {
                Toast.error('복원에 실패했습니다: ' + error.message);
            }
        }
    }

    async permanentlyDeleteReport(caseId) {
        const result = await Confirm.fire('영구 삭제 확인', `ID ${caseId} 항목을 영구적으로 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.`);
        if (result.isConfirmed) {
            try {
                await this._fetch(`${this.config.API_URL}/${caseId}/permanent`, { method: 'DELETE' });
                Toast.success('항목이 성공적으로 영구 삭제되었습니다.');
                this.loadData();
            } catch (error) {
                Toast.error('영구 삭제에 실패했습니다: ' + error.message);
            }
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new LitteringDeletedAdminApp().init();
});