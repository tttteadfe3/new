class WasteAdminApp extends BaseApp {
    constructor() {
        super();

        this.state = {
            ...this.state,
            fileParseResultModal: null
        };
    }

    initializeApp() {
        // BaseApp.initializeApp() is not called because it has its own logic that we are overriding.
        this.state.fileParseResultModal = new bootstrap.Modal(document.getElementById('fileParseResultModal'));
        this.setupEventListeners();
        this.loadInitialData();
    }

    setupEventListeners() {
        document.getElementById('searchBtn').addEventListener('click', (e) => { e.preventDefault(); this.loadInitialData(); });
        document.getElementById('resetBtn').addEventListener('click', () => { document.getElementById('listForm').reset(); this.loadInitialData(); });
        document.getElementById('htmlUpload').addEventListener('change', (e) => this.handleFileUpload(e));
        document.getElementById('data-table-body').addEventListener('click', (e) => this.handleTableEvents(e));
        document.getElementById('batchRegisterBtn')?.addEventListener('click', () => this.handleBatchRegistration());
        document.getElementById('clearInternetBtn')?.addEventListener('click', () => this.clearInternetSubmissions());
    }

    async loadInitialData() {
        const form = document.getElementById('listForm');
        const params = new URLSearchParams(new FormData(form)).toString();
        try {
            const response = await this.apiCall(`/waste-collections/admin?${params}`);
            this.renderTable(response.data);
            document.getElementById('total-count').textContent = response.data.length;
        } catch (e) {
            Toast.error(`데이터 로드 실패: ${e.message}`);
        }
    }

    renderTable(collections) {
        const tbody = document.getElementById('data-table-body');
        tbody.innerHTML = collections.length === 0 ? '<tr><td colspan="9">데이터가 없습니다.</td></tr>' : collections.map(item => this.generateTableRow(item)).join('');
    }

    generateTableRow(item) {
        const items = (typeof item.items === 'string' ? JSON.parse(item.items) : item.items) || [];
        const itemRowsHtml = items.map(d => this.generateItemRow(d)).join('');
        const processButtonHtml = item.status !== 'processed'
            ? `<button type="button" class="btn btn-sm btn-success btn-process" data-id="${item.id}">처리</button>`
            : `<button type="button" class="btn btn-sm btn-secondary" disabled>완료</button>`;
        const itemsManagementHtml = `
            <div class="items-container" data-id="${item.id}">${itemRowsHtml}</div>
            <div class="mt-2 d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-sm btn-outline-success btn-add-item" data-id="${item.id}">품목 추가</button>
                    ${processButtonHtml}
                </div>
                <button type="button" class="btn btn-sm btn-primary btn-save-items" data-id="${item.id}">품목 저장</button>
            </div>`;

        return `<tr>
            <td>${item.discharge_number || '-'}</td>
            <td>${item.submitter_name || '-'}</td>
            <td>${item.submitter_phone || '-'}</td>
            <td class="text-start">${item.address} ${item.geocoding_status === 'failure' ? '<i class="ri-error-warning-fill text-danger ms-1" title="주소 변환 실패"></i>' : ''}</td>
            <td>${item.item_count}</td>
            <td>${(item.fee || 0).toLocaleString()}원</td>
            <td>${item.issue_date}</td>
            <td><span class="badge bg-${item.status === 'processed' ? 'secondary' : 'warning'}">${item.status === 'processed' ? '처리완료' : '미처리'}</span></td>
            <td style="min-width: 450px;">${itemsManagementHtml}</td>
        </tr>`;
    }

    handleTableEvents(e) {
        const target = e.target;
        const id = target.dataset.id;
        if (!id) return;

        if (target.classList.contains('btn-save-items')) {
            const itemsContainer = document.querySelector(`.items-container[data-id="${id}"]`);
            const items = Array.from(itemsContainer.querySelectorAll('.item-row')).map(row => ({
                name: row.querySelector('.item-name').value.trim(),
                quantity: parseInt(row.querySelector('.item-quantity').value, 10)
            })).filter(item => item.name && item.quantity > 0);
            this.saveItems(id, JSON.stringify(items));
        } else if (target.classList.contains('btn-add-item')) {
            document.querySelector(`.items-container[data-id="${id}"]`).insertAdjacentHTML('beforeend', this.generateItemRow());
        } else if (target.classList.contains('btn-remove-item')) {
            target.closest('.item-row').remove();
        } else if (target.classList.contains('btn-process')) {
            this.processCollection(id);
        }
    }

    async handleFileUpload(event) {
        const file = event.target.files[0];
        if (!file) return;
        const formData = new FormData();
        formData.append('htmlFile', file);
        try {
            const response = await this.apiCall('/waste-collections/admin/parse-html', { method: 'POST', body: formData });
            this.showParsedResultInModal(response.data);
        } catch (e) {
            Toast.error(`파일 파싱 실패: ${e.message}`);
        } finally {
            event.target.value = '';
        }
    }

    showParsedResultInModal(parsedData) {
        const tbody = document.getElementById('parsed-data-tbody');
        tbody.innerHTML = parsedData.length > 0 ? parsedData.map(d => `<tr><td>${d.receiptNumber}</td><td>${d.name}</td><td>${d.phone}</td><td>${d.address}</td><td>${(d.fee||0).toLocaleString()}원</td><td>${d.dischargeDate}</td></tr>`).join('') : '<tr><td colspan="6">파싱된 데이터가 없습니다.</td></tr>';
        this.state.fileParseResultModal.show();
    }

    async handleBatchRegistration() {
        const collections = Array.from(document.querySelectorAll('#parsed-data-tbody tr')).map(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length < 6) return null;
            return {
                receiptNumber: cells[0].textContent, name: cells[1].textContent, phone: cells[2].textContent,
                address: cells[3].textContent, fee: parseInt(cells[4].textContent.replace(/[^0-9]/g, '')) || 0, dischargeDate: cells[5].textContent,
            };
        }).filter(Boolean);

        if (collections.length === 0) {
            return Toast.error('등록할 데이터가 없습니다.');
        }

        try {
            const response = await this.apiCall('/waste-collections/admin/batch-register', { method: 'POST', body: { collections } });
            const { count, failures, duplicates } = response.data;
            Toast.success(`${count}건 성공, ${failures}건 실패, ${duplicates}건 중복 처리되었습니다.`);
            this.state.fileParseResultModal.hide();
            this.loadInitialData();
        } catch (e) {
            Toast.error(`일괄 등록 실패: ${e.message}`);
        }
    }

    generateItemRow(item = { name: '', quantity: 1 }) {
        return `<div class="row gx-2 mb-2 item-row"><div class="col"><input type="text" class="form-control form-control-sm item-name" placeholder="품목명" value="${item.name || ''}"></div><div class="col-auto"><input type="number" class="form-control form-control-sm item-quantity" placeholder="수량" min="1" value="${item.quantity || 1}" style="width: 80px;"></div><div class="col-auto"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-item">삭제</button></div></div>`;
    }

    async saveItems(id, itemsJson) {
        try {
            await this.apiCall(`/waste-collections/admin/${id}/items`, { method: 'PUT', body: { items: itemsJson } });
            Toast.success('품목이 저장되었습니다.');
            this.loadInitialData();
        } catch (e) {
            Toast.error(`품목 저장 실패: ${e.message}`);
        }
    }

    async processCollection(id) {
        const result = await Confirm.fire('이 항목을 처리완료로 변경하시겠습니까?');
        if (result.isConfirmed) {
            try {
                await this.apiCall(`/waste-collections/admin/${id}/process`, { method: 'POST' });
                Toast.success('항목이 처리되었습니다.');
                this.loadInitialData();
            } catch(e) {
                Toast.error(`처리 실패: ${e.message}`);
            }
        }
    }

    async clearInternetSubmissions() {
        const result = await Confirm.fire('[주의] 정말로 모든 인터넷 배출 데이터를 삭제하시겠습니까?', '이 작업은 되돌릴 수 없습니다.');
        if (result.isConfirmed) {
            try {
                await this.apiCall('/waste-collections/admin/online-submissions', { method: 'DELETE' });
                Toast.success('모든 인터넷 배출 데이터가 삭제되었습니다.');
                this.loadInitialData();
            } catch(e) {
                Toast.error(`삭제 실패: ${e.message}`);
            }
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new WasteAdminApp();
});