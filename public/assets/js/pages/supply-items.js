/**
 * 지급품 품목 관리 JavaScript
 */

class SupplyItemsPage extends BasePage {
    constructor() {
        super({
            API_URL: '/supply/items'
        });
        
        this.dataTable = null;
        this.currentItemId = null;
    }

    setupEventListeners() {
        // 필터 이벤트
        document.getElementById('filter-category')?.addEventListener('change', () => {
            this.loadItems();
        });

        document.getElementById('filter-status')?.addEventListener('change', () => {
            this.loadItems();
        });

        document.getElementById('search-input')?.addEventListener('keyup', () => {
            this.loadItems();
        });

        // 상태 변경 확인
        document.getElementById('confirm-status-btn')?.addEventListener('click', () => {
            this.confirmStatusChange();
        });

        // 삭제 확인
        document.getElementById('confirm-delete-btn')?.addEventListener('click', () => {
            this.confirmDelete();
        });
    }

    loadInitialData() {
        this.loadCategories();
        this.initializeDataTable();
        this.loadItems(); // 데이터 로드
    }

    async loadCategories() {
        try {
            const data = await this.apiCall('/supply/categories');
            const categories = data.data || [];
            
            const select = document.getElementById('filter-category');
            if (select) {
                categories.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat.id;
                    option.textContent = cat.category_name;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error loading categories:', error);
        }
    }

    async loadItems() {
        try {
            const params = {
                category_id: document.getElementById('filter-category')?.value || '',
                is_active: document.getElementById('filter-status')?.value || '',
                search: document.getElementById('search-input')?.value || ''
            };

            const queryString = new URLSearchParams(params).toString();
            const result = await this.apiCall(`${this.config.API_URL}?${queryString}`);

            this.dataTable.clear().rows.add(result.data || []).draw();

        } catch (error) {
            console.error('Error loading items:', error);
            Toast.error('품목을 불러오는 중 오류가 발생했습니다.');
        }
    }

    initializeDataTable() {
        const self = this;
        
        this.dataTable = $('#items-table').DataTable({
            processing: true,
            serverSide: false, // 데이터를 서버에서 가져오지만, DataTable의 serverSide 기능은 사용 안함
            columns: [
                { data: 'item_code' },
                { data: 'item_name' },
                { data: 'category_name' },
                { data: 'unit' },
                { 
                    data: 'is_active',
                    render: function(data) {
                        return data == 1 ? 
                            '<span class="badge bg-success">활성</span>' : 
                            '<span class="badge bg-secondary">비활성</span>';
                    }
                },
                { 
                    data: 'created_at',
                    render: function(data) {
                        return data ? new Date(data).toLocaleDateString('ko-KR') : '-';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        return `
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="/supply/items/show?id=${row.id}" class="btn btn-info" title="상세">
                                    <i class="ri-eye-line"></i>
                                </a>
                                <a href="/supply/items/edit?id=${row.id}" class="btn btn-primary" title="수정">
                                    <i class="ri-edit-line"></i>
                                </a>
                                <button type="button" class="btn btn-warning toggle-status-btn" data-id="${row.id}" data-status="${row.is_active}" title="상태 변경">
                                    <i class="ri-refresh-line"></i>
                                </button>
                                <button type="button" class="btn btn-danger delete-btn" data-id="${row.id}" data-name="${row.item_name}" title="삭제">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        `;
                    }
                }
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/2.3.5/i18n/ko.json'
            },
            order: [[0, 'asc']],
            pageLength: 25,
            searching: false // DataTable 자체 검색 기능 비활성화
        });

        // 이벤트 위임
        $('#items-table').on('click', '.toggle-status-btn', function() {
            const id = $(this).data('id');
            const status = $(this).data('status');
            self.showStatusModal(id, status);
        });

        $('#items-table').on('click', '.delete-btn', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            self.showDeleteModal(id, name);
        });
    }

    showStatusModal(id, currentStatus) {
        this.currentItemId = id;
        const newStatus = currentStatus == 1 ? '비활성' : '활성';
        document.getElementById('status-change-message').textContent = 
            `이 품목을 ${newStatus} 상태로 변경하시겠습니까?`;
        
        const modal = new bootstrap.Modal(document.getElementById('statusModal'));
        modal.show();
    }

    async confirmStatusChange() {
        try {
            await this.apiCall(`${this.config.API_URL}/${this.currentItemId}/toggle-status`, {
                method: 'PUT'
            });
            
            Toast.success('품목 상태가 변경되었습니다.');
            this.loadItems();
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('statusModal'));
            modal.hide();
        } catch (error) {
            Toast.error(error.message || '상태 변경 중 오류가 발생했습니다.');
        }
    }

    showDeleteModal(id, name) {
        this.currentItemId = id;
        document.getElementById('delete-item-info').innerHTML = 
            `<strong>${name}</strong>`;
        
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }

    async confirmDelete() {
        try {
            await this.apiCall(`${this.config.API_URL}/${this.currentItemId}`, {
                method: 'DELETE'
            });
            
            Toast.success('품목이 삭제되었습니다.');
            this.loadItems();
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
            modal.hide();
        } catch (error) {
            Toast.error(error.message || '품목 삭제 중 오류가 발생했습니다.');
        }
    }
}

new SupplyItemsPage();
