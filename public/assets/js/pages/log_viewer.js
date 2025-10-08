document.addEventListener('DOMContentLoaded', () => {
    const API_URL = '/api/logs';
    const filterForm = document.getElementById('log-filter-form');
    const logTableBody = document.getElementById('log-table-body');

    const fetchOptions = (options = {}) => {
        const defaultHeaders = {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        };
        return { ...options, headers: { ...defaultHeaders, ...options.headers } };
    };

    const sanitizeHTML = (str) => {
        if (str === null || typeof str === 'undefined') return '';
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    };

    const searchLogs = async () => {
        logTableBody.innerHTML = `<tr><td colspan="6" class="text-center"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>`;

        const formData = new FormData(filterForm);
        const params = new URLSearchParams(formData).toString();
        
        try {
            const response = await fetch(`${API_URL}?${params}`, fetchOptions());
            const result = await response.json();
            if (!result.success) throw new Error(result.message);

            logTableBody.innerHTML = '';
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

    filterForm.addEventListener('submit', (e) => {
        e.preventDefault();
        searchLogs();
    });

    searchLogs();

    const clearLogsBtn = document.getElementById('clear-logs-btn');
    if (clearLogsBtn) {
        clearLogsBtn.addEventListener('click', async () => {
            const confirmResult = await Confirm.fire('로그 비우기', '정말로 모든 로그를 비우시겠습니까? 이 작업은 되돌릴 수 없습니다.');
            if (confirmResult.isConfirmed) {
                try {
                    const response = await fetch(API_URL, { ...fetchOptions(), method: 'DELETE' });
                    const result = await response.json();
                    if (!result.success) throw new Error(result.message);
                    
                    Toast.success('로그가 성공적으로 비워졌습니다.');
                    searchLogs();
                } catch (error) {
                    console.error('Error clearing logs:', error);
                    Toast.error('로그를 비우는 중 오류가 발생했습니다: ' + error.message);
                }
            }
        });
    }
});