/**
 * LocationUtils - 위치 관련 유틸리티
 */
class LocationUtils {
    /**
     * 두 지점 간의 거리 계산 (미터)
     */
    static calculateDistance(point1, point2) {
        const lat1 = point1.lat || point1.latitude || (point1.getLat && point1.getLat());
        const lng1 = point1.lng || point1.longitude || (point1.getLng && point1.getLng());
        const lat2 = point2.lat || point2.latitude || (point2.getLat && point2.getLat());
        const lng2 = point2.lng || point2.longitude || (point2.getLng && point2.getLng());

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
     * 영역 내 포인트 검사
     */
    static isPointInBounds(point, bounds) {
        const lat = point.lat || point.latitude || (point.getLat && point.getLat());
        const lng = point.lng || point.longitude || (point.getLng && point.getLng());

        return lat >= bounds.south && lat <= bounds.north &&
               lng >= bounds.west && lng <= bounds.east;
    }

    /**
     * 좌표 형식 변환
     */
    static normalizeCoords(coords) {
        if (coords.getLat && coords.getLng) {
            return {
                lat: coords.getLat(),
                lng: coords.getLng()
            };
        }

        return {
            lat: coords.lat || coords.latitude,
            lng: coords.lng || coords.longitude
        };
    }

    /**
     * 주소 문자열 정리
     */
    static formatAddress(address) {
        if (!address) return '주소 정보 없음';

        // 불필요한 공백 제거 및 정리
        return address.trim().replace(/\s+/g, ' ');
    }
}