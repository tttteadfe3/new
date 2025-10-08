/**
 * BaseApp - 공통 애플리케이션 로직을 위한 기본 클래스
 * 
 * 주요 기능:
 * - 공통 설정 및 상태 관리
 * - 애플리케이션 초기화 플로우 제공 (init)
 * - MapManager 인스턴스화
 * - 공통 API 호출 메서드 (jQuery Ajax 기반)
 * - 로딩 상태 표시 유틸리티
 * - 리소스 정리 (destroy)
 */
class BaseApp {
    constructor(config = {}) {
        // 기본 설정과 자식 클래스의 설정을 병합
        this.config = {
            mapId: 'map',
            ...config
        };

        this.state = {
            mapManager: null
        };

        // DOM이 로드된 후 초기화 실행
        document.addEventListener('DOMContentLoaded', () => {
            this.init();
            
            // 페이지를 떠날 때 리소스 정리
            window.addEventListener('beforeunload', () => {
                this.destroy();
            });
        });
    }

    /**
     * 애플리케이션 초기화 (자식 클래스에서 확장 가능)
     */
    init() {
        this.initMapManager();
        this.bindEvents();
        this.loadData();
    }

    /**
     * InteractiveMapManager 초기화
     */
    initMapManager(options = {}) {
        const defaultMapOptions = {
            mapId: this.config.mapId,
            center: { lat: 37.340187, lng: 126.743888 },
            level: 3,
            allowedRegions: this.config.ALLOWED_REGIONS || []
        };
        
        this.state.mapManager = new InteractiveMapManager({
            ...defaultMapOptions,
            ...options // 자식 클래스에서 전달된 옵션으로 덮어쓰기
        });
    }

    /**
     * 이벤트 바인딩 (자식 클래스에서 구현)
     */
    bindEvents() {
        // 이 메서드는 자식 클래스에서 구체적인 이벤트들을 바인딩하기 위해 오버라이드합니다.
    }

    /**
     * 데이터 로드 (자식 클래스에서 구현)
     */
    loadData() {
        // 이 메서드는 자식 클래스에서 초기 데이터를 로드하기 위해 오버라이드합니다.
    }

    /**
     * API 호출 (ApiService 사용)
     */
    apiCall(action, data = {}, method = 'POST') {
        return ApiService.request(this.config.API_URL, { action, data, method });
    }

    /**
     * 버튼 로딩 상태 설정
     */
    setButtonLoading(selector, text) {
        const btn = document.querySelector(selector);
        if (!btn) return;
        btn.dataset.originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<i class="spinner-border spinner-border-sm me-1"></i>${text}`;
    }

    /**
     * 버튼 로딩 상태 초기화
     */
    resetButtonLoading(selector, originalText) {
        const btn = document.querySelector(selector);
        if (!btn) return;
        btn.disabled = false;
        btn.innerHTML = originalText || btn.dataset.originalText;
    }

    /**
     * 애플리케이션 리소스 정리
     */
    destroy() {
        console.log(`Destroying ${this.constructor.name}...`);
        if (this.state.mapManager) {
            this.state.mapManager.destroy();
            this.state.mapManager = null;
        }
        // 추가적인 공통 정리 작업이 있다면 여기에...
    }
}
