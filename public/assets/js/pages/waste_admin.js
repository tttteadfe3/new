document.addEventListener('DOMContentLoaded', () => {

    class WasteAdminApp {
        constructor() {
            this.config = {
                API_URL: '../api/v1/waste/admin',
            };
            this.state = {
                fileParseResultModal: new bootstrap.Modal(document.getElementById('fileParseResultModal')),
            };
            this.bindEvents();
            this.loadCollections();
        }

        bindEvents() {
            document.getElementById('searchBtn').addEventListener('click', (e) => { e.preventDefault(); this.loadCollections(); });
            document.getElementById('resetBtn').addEventListener('click', () => { document.getElementById('listForm').reset(); this.loadCollections(); });
            document.getElementById('htmlUpload').addEventListener('change', (e) => this.handleFileUpload(e));

            const dataTableBody = document.getElementById('data-table-body');

            dataTableBody.addEventListener('click', (e) => {
                const target = e.target;
                if (target.classList.contains('btn-save-items')) {
                    const collectionId = target.dataset.id;
                    const itemsContainer = document.querySelector(`.items-container[data-id="${collectionId}"]`);
                    const items = [];
                    itemsContainer.querySelectorAll('.item-row').forEach(row => {
                        const name = row.querySelector('.item-name').value.trim();
                        const quantity = parseInt(row.querySelector('.item-quantity').value, 10);
                        if (name && quantity > 0) {
                            items.push({ name, quantity });
                        }
                    });
                    const itemsJson = JSON.stringify(items);
                    this.saveItems(collectionId, itemsJson);
                } else if (target.classList.contains('btn-add-item')) {
                    const collectionId = target.dataset.id;
                    const itemsContainer = document.querySelector(`.items-container[data-id="${collectionId}"]`);
                    const newRowHtml = this.generateItemRow();
                    itemsContainer.insertAdjacentHTML('beforeend', newRowHtml);
                } else if (target.classList.contains('btn-remove-item')) {
                    target.closest('.item-row').remove();
                } else if (target.classList.contains('btn-process')) {
                    const collectionId = target.dataset.id;
                    this.handleProcessCollection(collectionId);
                }
            });

            document.getElementById('fileParseResultModal').addEventListener('click', (e) => {
                if(e.target.id === 'batchRegisterBtn') {
                    this.handleBatchRegistration();
                }
            });
            document.getElementById('clearInternetBtn').addEventListener('click', () => this.handleClearInternet());
        }

        async loadCollections() {
            const searchData = {
                action: 'get_collections',
                searchDischargeNumber: document.getElementById('searchDischargeNumber').value,
                searchName: document.getElementById('searchName').value,
                searchPhone: document.getElementById('searchPhone').value,
                searchStatus: document.getElementById('searchStatus').value,
            };

            try {
                const response = await this.apiCall(searchData, 'GET');
                if(response.success) {
                    this.renderTable(response.data);
                    document.getElementById('total-count').textContent = response.data.length;
                } else { Toast.error('데이터 로드 실패: ' + response.message); }
            } catch(e) {
                Toast.error('데이터 로드 중 오류가 발생했습니다.');
                console.error(e);
            }
        }

        renderTable(collections) {
            const tbody = document.getElementById('data-table-body');
            tbody.innerHTML = '';
            if (collections.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9">데이터가 없습니다.</td></tr>';
                return;
            }

            collections.forEach(item => {
                let items;
                try {
                    items = JSON.parse(item.items);
                } catch (e) {
                    items = [];
                }

                const itemRowsHtml = items.map(d => this.generateItemRow(d)).join('');
                
                const processButtonHtml = item.status !== 'processed'
                    ? `<button type="button" class="btn btn-sm btn-success btn-process" data-id="${item.id}">처리</button>`
                    : `<button type="button" class="btn btn-sm btn-secondary" disabled>완료</button>`;

                const itemsManagementHtml = `
                    <div class="items-container" data-id="${item.id}">
                        ${itemRowsHtml}
                    </div>
                    <div class="mt-2 d-flex justify-content-between">
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-success btn-add-item" data-id="${item.id}">품목 추가</button>
                            ${processButtonHtml}
                        </div>
                        <button type="button" class="btn btn-sm btn-primary btn-save-items" data-id="${item.id}">품목 저장</button>
                    </div>
                `;

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.discharge_number || '-'}</td>
                    <td>${item.submitter_name || '-'}</td>
                    <td>${item.submitter_phone || '-'}</td>
                    <td class="text-start">
                        ${item.address}
                        ${item.geocoding_status === 'failure' ? '<i class="ri-error-warning-fill text-danger ms-1" title="주소 변환 실패"></i>' : ''}
                    </td>
                    <td>${item.item_count}</td>
                    <td>${(item.fee || 0).toLocaleString()}원</td>
                    <td>${item.issue_date}</td>
                    <td><span class="badge bg-${item.status === 'processed' ? 'secondary' : 'warning'}">${item.status === 'processed' ? '처리완료' : '미처리'}</span></td>
                    <td style="min-width: 450px;">${itemsManagementHtml}</td>
                `;
                tbody.appendChild(row);
            });
        }

        async handleFileUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('action', 'parse_html_file');
            formData.append('htmlFile', file);

            try {
                const response = await this.apiCall(formData);
                if (response.success) {
                    this.showParsedResultInModal(response.data);
                } else {
                    Toast.error('파싱 실패: ' + response.message);
                }
            } catch(e) {
                Toast.error('파일 업로드 또는 파싱 중 오류 발생');
                console.error(e);
            } finally {
                event.target.value = '';
            }
        }

        showParsedResultInModal(parsedData) {
            const tbody = document.getElementById('parsed-data-tbody');
            tbody.innerHTML = '';
            if (parsedData.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6">파싱된 데이터가 없습니다.</td></tr>';
            } else {
                parsedData.forEach((rowData) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${rowData.receiptNumber}</td>
                        <td>${rowData.name}</td>
                        <td>${rowData.phone}</td>
                        <td>${rowData.address}</td>
                        <td>${(rowData.fee || 0).toLocaleString()}원</td>
                        <td>${rowData.dischargeDate}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }
            this.state.fileParseResultModal.show();
        }

        async handleBatchRegistration() {
            const parsedData = [];
            document.querySelectorAll('#parsed-data-tbody tr').forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length > 1) {
                    const feeText = cells[4].textContent;
                    const fee = parseInt(feeText.replace(/[^0-9]/g, ''), 10) || 0;
                    parsedData.push({
                        receiptNumber: cells[0].textContent,
                        name:          cells[1].textContent,
                        phone:         cells[2].textContent,
                        address:       cells[3].textContent,
                        fee:           fee,
                        dischargeDate: cells[5].textContent,
                    });
                }
            });

            if (parsedData.length === 0) {
                Toast.error('등록할 데이터가 없습니다.');
                return;
            }

            try {
                const response = await this.apiCall({
                    collections: parsedData
                }, 'POST', 'batch_register');

                if (response.success) {
                    const { count, failures, duplicates } = response.data;
                    let message = `${count}건 성공`;
                    if (failures > 0) {
                        message += `, ${failures}건 실패`;
                    }
                    if (duplicates > 0) {
                        message += `, ${duplicates}건 중복`;
                    }
                    message += ' 처리되었습니다.';
                    Toast.success(message);
                    this.state.fileParseResultModal.hide();
                    this.loadCollections();
                } else {
                    Toast.error('일괄 등록 실패: ' + response.message);
                }
            } catch(e) {
                Toast.error(`일괄 등록 중 오류 발생: ${e.message}`);
                console.error(e);
            }
        }

        generateItemRow(item = { name: '', quantity: 1 }) {
            return `
                <div class="row gx-2 mb-2 item-row">
                    <div class="col">
                        <input type="text" class="form-control form-control-sm item-name" placeholder="품목명" value="${item.name || ''}">
                    </div>
                    <div class="col-auto">
                        <input type="number" class="form-control form-control-sm item-quantity" placeholder="수량" min="1" value="${item.quantity || 1}" style="width: 80px;">
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item">삭제</button>
                    </div>
                </div>
            `;
        }

        async saveItems(collectionId, itemsJson) {
            try {
                const response = await this.apiCall({
                    action: 'update_items',
                    id: collectionId,
                    items: itemsJson
                });
                if (response.success) {
                    Toast.success('품목이 저장되었습니다.');
                    this.loadCollections();
                } else {
                    Toast.error('품목 저장 실패: ' + response.message);
                }
            } catch(e) {
                Toast.error('품목 저장 중 오류 발생');
                console.error(e);
            }
        }

        async handleClearInternet() {
            Confirm.fire('[주의] 정말로 모든 인터넷 배출 데이터를 삭제하시겠습니까?', '이 작업은 되돌릴 수 없습니다.').then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const response = await this.apiCall({ action: 'clear_online_submissions' });
                        if (response.success) {
                            Toast.success('모든 인터넷 배출 데이터가 삭제되었습니다.');
                            this.loadCollections();
                        } else {
                            Toast.error('삭제 실패: ' + response.message);
                        }
                    } catch(e) {
                        Toast.error('삭제 중 오류 발생');
                        console.error(e);
                    }
                }
            });
        }

        apiCall(data = {}, method = 'POST', action = null) {
            let requestAction = action;
            if (!requestAction) {
                if (data instanceof FormData) {
                    requestAction = data.get('action');
                } else {
                    requestAction = data.action;
                }
            }
            
            // For GET requests, the action can be part of the data object.
            if (method.toUpperCase() === 'GET' && data.action) {
                requestAction = requestAction || data.action;
            }

            // Remove action from data if it exists, to avoid duplication in JSON body
            if (!(data instanceof FormData) && data.action) {
                delete data.action;
            }

            return ApiService.request(this.config.API_URL, { action: requestAction, data, method });
        }
    }

    window.wasteAdminApp = new WasteAdminApp();

    // Add this new method to the class
    WasteAdminApp.prototype.handleProcessCollection = async function(collectionId) {
        Confirm.fire('이 항목을 처리완료로 변경하시겠습니까?').then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const response = await this.apiCall({
                        id: collectionId
                    }, 'POST', 'process_collection');
                    if (response.success) {
                        Toast.success('항목이 처리되었습니다.');
                        this.loadCollections();
                    } else {
                        Toast.error('처리 실패: ' + response.message);
                    }
                } catch(e) {
                    Toast.error(`처리 중 오류 발생: ${e.message}`);
                    console.error(e);
                }
            }
        });
    }
});