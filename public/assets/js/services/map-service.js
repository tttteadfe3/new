/**
 * MapService - 인터랙티브 지도 컴포넌트를 관리합니다.
 *
 * 이 서비스는 지도 초기화, 지도 관련 상태 처리,
 * 그리고 리소스 정리를 담당합니다. 지도가 필요한 페이지에서만
 * 인스턴스화해야 합니다.
 */
class MapService {
    /**
     * @param {object} config - 지도 설정 객체.
     * @param {string} config.mapId - 지도 컨테이너 요소의 ID.
     * @param {object} [config.center] - 지도의 초기 중심점.
     * @param {number} [config.level] - 지도의 초기 확대 레벨.
     */
    constructor(config = {}) {
        const defaults = {
            mapId: 'map',
            center: { lat: 37.340187, lng: 126.743888 },
            level: 3,
            allowedRegions: window.AppConfig?.allowedRegions || ['정왕1동'] // 전역 설정 또는 하드코딩된 기본값 사용
        };

        this.config = {
            ...defaults,
            ...config
        };

        this.mapManager = null;
        this.initialize();
    }

    /**
     * 지도 컨테이너가 존재할 경우 InteractiveMapManager를 초기화합니다.
     */
    initialize() {
        const mapContainer = document.getElementById(this.config.mapId);
        if (!mapContainer) {
            console.warn(`지도 컨테이너 #${this.config.mapId}를 찾을 수 없습니다. MapService가 초기화되지 않습니다.`);
            return;
        }

        this.mapManager = new InteractiveMap(this.config);
    }

    /**
     * 메모리 누수를 방지하기 위해 지도 리소스를 정리합니다.
     * 페이지가 언로드될 때 호출되어야 합니다.
     */
    destroy() {
        if (this.mapManager && typeof this.mapManager.destroy === 'function') {
            this.mapManager.destroy();
            this.mapManager = null;
            console.log('MapService가 파괴되었습니다.');
        }
    }

    // 필요한 경우 다른 지도 관련 메서드를 여기에 추가할 수 있습니다.
    // e.g., getMapInstance(), addMarker() 등.
}
