class DashboardPage extends BasePage {
    constructor() {
        super();
        this.elements = {};
        this.API_URL_BALANCE = '/api/leave/balance';
    }

    initializeApp() {
        this.setupSidebarToggle();
        this.loadLeaveBalanceWidget();
    }

    setupSidebarToggle() {
        const sidebarToggle = document.body.querySelector('#sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', event => {
                event.preventDefault();
                document.body.classList.toggle('sb-sidenav-toggled');
                localStorage.setItem('sb|sidebar-toggle', document.body.classList.contains('sb-sidenav-toggled'));
            });
        }

        if (localStorage.getItem('sb|sidebar-toggle') === 'true') {
            document.body.classList.toggle('sb-sidenav-toggled');
        }
    }

    async loadLeaveBalanceWidget() {
        const annualLeaveEl = document.getElementById('dashboard-annual-leave');
        const monthlyLeaveEl = document.getElementById('dashboard-monthly-leave');

        if (!annualLeaveEl || !monthlyLeaveEl) {
            // 대시보드에 위젯이 없는 페이지일 수 있으므로, 오류 없이 조용히 종료
            return;
        }

        try {
            const response = await this.apiCall(this.API_URL_BALANCE);
            annualLeaveEl.textContent = response.data.annual.toFixed(1);
            monthlyLeaveEl.textContent = response.data.monthly.toFixed(1);
        } catch (error) {
            console.error('Failed to load leave balance for dashboard:', error);
            annualLeaveEl.textContent = 'N/A';
            monthlyLeaveEl.textContent = 'N/A';
        }
    }
}

new DashboardPage();
