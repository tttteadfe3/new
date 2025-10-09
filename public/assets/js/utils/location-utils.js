/**
 * LocationUtils - 위치 관련 유틸리티 함수들을 제공하는 클래스
 */
class LocationUtils {
    /**
     * 두 지점 간의 거리 계산 (미터)
     * @param {object} point1 - {lat, lng} 또는 kakao.maps.LatLng 객체
     * @param {object} point2 - {lat, lng} 또는 kakao.maps.LatLng 객체
     * @returns {number} - 두 지점 간의 거리 (미터)
     */
    static calculateDistance(point1, point2) {
        const lat1 = point1.lat || point1.latitude || (point1.getLat && point1.getLat());
        const lng1 = point1.lng || point1.longitude || (point1.getLng && point1.getLng());
        const lat2 = point2.lat || point2.latitude || (point2.getLat && point2.getLat());
        const lng2 = point2.lng || point2.longitude || (point2.getLng && point2.getLng());

        if (lat1 === undefined || lng1 === undefined || lat2 === undefined || lng2 === undefined) {
            throw new Error("Invalid point format provided to calculateDistance");
        }

        const R = 6371e3; // 지구 반지름 (미터)
        const φ1 = lat1 * Math.PI/180;
        const φ2 = lat2 * Math.PI/180;
        const Δφ = (lat2-lat1) * Math.PI/180;
        const Δλ = (lng2-lng1) * Math.PI/180;

        const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                  Math.cos(φ1) * Math.cos(φ2) *
                  Math.sin(Δλ/2) * Math.sin(Δλ/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

        return R * c;
    }

    /**
     * 특정 지점이 주어진 경계 안에 있는지 확인
     * @param {object} point - {lat, lng} 또는 kakao.maps.LatLng 객체
     * @param {object} bounds - {north, south, east, west}
     * @returns {boolean} - 경계 내 포함 여부
     */
    static isPointInBounds(point, bounds) {
        const lat = point.lat || point.latitude || (point.getLat && point.getLat());
        const lng = point.lng || point.longitude || (point.getLng && point.getLng());

        return lat >= bounds.south && lat <= bounds.north &&
               lng >= bounds.west && lng <= bounds.east;
    }

    /**
     * 다양한 좌표 형식을 {lat, lng} 객체로 정규화
     * @param {object} coords - kakao.maps.LatLng 또는 {lat, lng}, {latitude, longitude}
     * @returns {{lat: number, lng: number}} - 정규화된 좌표 객체
     */
    static normalizeCoords(coords) {
        if (coords.getLat && coords.getLng) {
            return {
                lat: coords.getLat(),
                lng: coords.getLng()
            };
        }

        const lat = coords.lat || coords.latitude;
        const lng = coords.lng || coords.longitude;

        if (lat === undefined || lng === undefined) {
            throw new Error("Invalid coordinate format for normalization");
        }

        return { lat, lng };
    }

    /**
     * 주소 문자열 정리
     * @param {string} address - 정리할 주소 문자열
     * @returns {string} - 공백이 정리된 주소 또는 기본 메시지
     */
    static formatAddress(address) {
        if (!address) return '주소 정보 없음';

        // 불필요한 공백 제거 및 정리
        return address.trim().replace(/\s+/g, ' ');
    }
}