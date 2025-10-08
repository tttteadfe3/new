// js/logs.js
document.addEventListener('DOMContentLoaded', () => {
    const filterForm = document.getElementById('log-filter-form');
    const logTableBody = document.getElementById('log-table-body');

    // 공통 fetch 옵션
    const fetchOptions = (options = {}) => {
        const defaultHeaders = {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        };
        return { ...options, headers: { ...defaultHeaders, ...options.headers } };
    };

    // HTML 인코딩 함수
    const sanitizeHTML = (str) => {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    };

    /**
     * 현재 필터 값 기준으로 API를 호출하고 로그 목록을 렌더링하는 함수
     */
    const searchLogs = async () => {
        // 테이블을 로딩 상태로 변경
        logTableBody.innerHTML = `<tr><td colspan="6" class="text-center"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>`;

        // 폼에서 현재 필터 값을 가져와 URL 쿼리 스트링 생성
        const formData = new FormData(filterForm);
        const params = new URLSearchParams(formData).toString();
        
        try {
            const response = await fetch(`../api/v1/logs?${params}`, fetchOptions());
            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            logTableBody.innerHTML = ''; // 기존 내용 초기화
            if (result.data.length === 0) {
                logTableBody.innerHTML = `<tr><td colspan="6" class="text-center">일치하는 로그가 없습니다.</td></tr>`;
                return;
            }

            result.data.forEach(log => {
                const row = `
                    <tr>
                        <td>${sanitizeHTML(log.created_at)}</td>
                        <td>${sanitizeHTML(log.user_id)}</td>
                        <td>${sanitizeHTML(log.user_name)}</td>
                        <td>${sanitizeHTML(log.action)}</td>
                        <td>${sanitizeHTML(log.details)}</td>
                        <td>${sanitizeHTML(log.ip_address)}</td>
                    </tr>`;
                logTableBody.insertAdjacentHTML('beforeend', row);
            });
        } catch (error) {
            console.error('Error searching logs:', error);
            logTableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">로그를 불러오는 중 오류가 발생했습니다.</td></tr>`;
        }
    };

    /**
     * 필터 폼 제출 시 페이지 새로고침을 막고 비동기 검색 실행
     */
    filterForm.addEventListener('submit', (e) => {
        e.preventDefault();
        searchLogs();
    });

    // 페이지 첫 로드 시 전체 로그를 한번 불러옴
    searchLogs();

    // 로그 비우기 버튼 이벤트 리스너
    const clearLogsBtn = document.getElementById('clear-logs-btn');
    if (clearLogsBtn) {
        clearLogsBtn.addEventListener('click', async () => {
            const confirmResult = await Confirm.fire('로그 비우기', '정말로 모든 로그를 비우시겠습니까? 이 작업은 되돌릴 수 없습니다.');
            if (confirmResult.isConfirmed) {
                try {
                    const response = await fetch('../api/v1/logs/clear', {
                        ...fetchOptions(),
                        method: 'DELETE'
                    });
                    const result = await response.json();
                    if (!result.success) throw new Error(result.message);
                    
                    Toast.success('로그가 성공적으로 비워졌습니다.');
                    searchLogs(); // 로그 목록 새로고침
                } catch (error) {
                    console.error('Error clearing logs:', error);
                    Toast.error('로그를 비우는 중 오류가 발생했습니다.');
                }
            }
        });
    }
});