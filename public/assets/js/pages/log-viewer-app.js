class LogViewerApp extends BaseApp {
    constructor() {
        super({
            API_URL: '/logs'
        });
        this.elements = {};
    }

    initializeApp() {
        this.cacheDOMElements();
        this.setupEventListeners();
        this.loadInitialData();
    }

    cacheDOMElements() {
        this.elements = {
            filterForm: document.getElementById('log-filter-form'),
            logTableBody: document.getElementById('log-table-body'),
            clearLogsBtn: document.getElementById('clear-logs-btn')
        };
    }

    setupEventListeners() {
        this.elements.filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            this.loadInitialData();
        });

        if (this.elements.clearLogsBtn) {
            this.elements.clearLogsBtn.addEventListener('click', () => this.clearAllLogs());
        }
    }

    async loadInitialData() {
        this.elements.logTableBody.innerHTML = `<tr><td colspan="6" class="text-center"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>`;

        const formData = new FormData(this.elements.filterForm);
        const params = new URLSearchParams(formData).toString();

        try {
            const response = await this.apiCall(`${this.config.API_URL}?${params}`);
            this.renderTable(response.data);
        } catch (error) {
            console.error('Error searching logs:', error);
            this.elements.logTableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">로그를 불러오는 중 오류가 발생했습니다.</td></tr>`;
        }
    }

    renderTable(logs) {
        this.elements.logTableBody.innerHTML = '';
        if (logs.length === 0) {
            this.elements.logTableBody.innerHTML = `<tr><td colspan="6" class="text-center">일치하는 로그가 없습니다.</td></tr>`;
            return;
        }

        const rowsHtml = logs.map(log => `
            <tr>
                <td>${this._sanitizeHTML(log.created_at)}</td>
                <td>${this._sanitizeHTML(log.user_id)}</td>
                <td>${this._sanitizeHTML(log.user_name)}</td>
                <td>${this._sanitizeHTML(log.action)}</td>
                <td>${this._sanitizeHTML(log.details)}</td>
                <td>${this._sanitizeHTML(log.ip_address)}</td>
            </tr>`
        ).join('');
        this.elements.logTableBody.innerHTML = rowsHtml;
    }

    async clearAllLogs() {
        const confirmResult = await Confirm.fire('로그 비우기', '정말로 모든 로그를 비우시겠습니까? 이 작업은 되돌릴 수 없습니다.');
        if (confirmResult.isConfirmed) {
            try {
                const response = await this.apiCall(this.config.API_URL, { method: 'DELETE' });
                Toast.success('로그가 성공적으로 비워졌습니다.');
                this.loadInitialData();
            } catch (error) {
                console.error('Error clearing logs:', error);
                Toast.error('로그를 비우는 중 오류가 발생했습니다: ' + error.message);
            }
        }
    }

    _sanitizeHTML(str) {
        if (str === null || typeof str === 'undefined') return '';
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }
}

new LogViewerApp();