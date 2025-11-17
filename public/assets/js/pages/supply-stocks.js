/**
 * 재고 현황 페이지 스크립트
 */

$(document).ready(function() {
    let stocksTable;

    // DataTable 초기화
    function initDataTable() {
        stocksTable = $('#stocks-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/supply/stocks',
                type: 'GET',
                data: function(d) {
                    d.category_id = $('#filter-category').val();
                    d.stock_status = $('#filter-stock-status').val();
                    d.search = $('#search-input').val();
                }
            },
            columns: [
                { data: 'item_code' },
                { data: 'item_name' },
                { data: 'category_name' },
                { data: 'unit' },
                { 
                    data: 'current_stock',
                    render: function(data) {
                        return data ? parseInt(data).toLocaleString() : '0';
                    }
                },
                { 
                    data: 'safety_stock',
                    render: function(data) {
                        return data ? parseInt(data).toLocaleString() : '0';
                    }
                },
                { 
                    data: 'stock_status',
                    render: function(data, type, row) {
                        const current = parseInt(row.current_stock) || 0;
                        const safety = parseInt(row.safety_stock) || 0;
                        
                        if (current === 0) {
                            return '<span class="badge bg-danger">품절</span>';
                        } else if (current < safety) {
                            return '<span class="badge bg-warning">부족</span>';
                        } else {
                            return '<span class="badge bg-success">충분</span>';
                        }
                    }
                },
                { data: 'last_received_at' },
                {
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        return `
                            <button class="btn btn-sm btn-info view-detail-btn" data-id="${row.id}">
                                <i class="ri-eye-line"></i> 상세
                            </button>
                        `;
                    }
                }
            ],
            order: [[0, 'asc']],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/ko.json'
            }
        });
    }

    // 분류 목록 로드
    function loadCategories() {
        $.get('/supply/categories', function(response) {
            if (response.success) {
                const select = $('#filter-category');
                response.data.forEach(function(category) {
                    select.append(`<option value="${category.id}">${category.name}</option>`);
                });
            }
        });
    }

    // 필터 변경 이벤트
    $('#filter-category, #filter-stock-status').on('change', function() {
        stocksTable.ajax.reload();
    });

    // 검색 이벤트
    $('#search-input').on('keyup', debounce(function() {
        stocksTable.ajax.reload();
    }, 500));

    // 새로고침 버튼
    $('#refresh-btn').on('click', function() {
        stocksTable.ajax.reload();
    });

    // 상세 보기 버튼
    $(document).on('click', '.view-detail-btn', function() {
        const itemId = $(this).data('id');
        // 상세 정보 로드 및 모달 표시
        $('#stockDetailModal').modal('show');
    });

    // Debounce 함수
    function debounce(func, wait) {
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

    // 초기화
    loadCategories();
    initDataTable();
});
