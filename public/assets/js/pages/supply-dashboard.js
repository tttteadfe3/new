/**
 * Supply Dashboard JavaScript
 */

class SupplyDashboardPage extends BasePage {
    constructor() {
        super({
            apiBaseUrl: '/supply'
        });
    }

    setupEventListeners() {
        // 대시보드 카드 클릭 이벤트
        this.initializeCardLinks();
    }

    loadInitialData() {
        // 필요시 대시보드 통계 데이터 로드
        this.loadDashboardStats();
    }

    initializeCardLinks() {
        // 카드 클릭 시 해당 페이지로 이동
        document.querySelectorAll('.card-animate').forEach(card => {
            const link = card.querySelector('a.btn');
            if (link) {
                card.style.cursor = 'pointer';
                card.addEventListener('click', (e) => {
                    if (e.target.tagName !== 'A' && e.target.tagName !== 'BUTTON') {
                        link.click();
                    }
                });
            }
        });
    }

    async loadDashboardStats() {
        try {
            // 필요시 API를 통해 통계 데이터 로드
            // const stats = await this.apiCall(`${this.config.apiBaseUrl}/dashboard-stats`);
            // this.renderStats(stats);
        } catch (error) {
            console.error('Error loading dashboard stats:', error);
        }
    }
}

// 인스턴스 생성
new SupplyDashboardPage();
