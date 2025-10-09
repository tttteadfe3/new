/**
 * MarkerFactory - 다양한 종류의 SVG 마커 아이콘을 생성하는 유틸리티 클래스
 */
class MarkerFactory {
    /**
     * SVG 마커 아이콘을 생성합니다.
     * @param {object} options - 마커 생성 옵션
     * @param {string} [options.type='default'] - 마커의 기본 형태 ('default', 'waste')
     * @param {string} [options.color='#2563EB'] - 마커의 주 색상
     * @param {object} [options.size] - 마커 크기 { width, height }
     * @param {string} [options.text] - 마커 중앙에 표시될 텍스트
     * @param {string} [options.status] - 마커 상태 아이콘 ('pending', 'confirmed', 'processed')
     * @returns {string} - Base64로 인코딩된 SVG 이미지 데이터 URI
     */
    static createSVGIcon(options = {}) {
        const config = this.getConfig(options.type);
        const {
            color = config.defaultColor,
            size = config.size,
            text = '',
            status = null
        } = options;

        const statusIconSVG = this.getStatusIconSVG(status);

        const iconSVG = `
            <svg width="${size.width}" height="${size.height}" viewBox="0 0 ${size.width} ${size.height}" xmlns="http://www.w3.org/2000/svg">
                <path d="${config.path}" fill="${color}" stroke="#ffffff" stroke-width="1.5"/>
                <circle cx="${size.width / 2}" cy="${config.circleCy}" r="${config.circleR}" fill="#fff"/>
                <text x="${size.width / 2}" y="${config.textY}" text-anchor="middle" fill="${color}" font-size="${config.fontSize}" font-weight="bold" font-family="Arial, sans-serif">${text}</text>
                ${statusIconSVG}
            </svg>
        `;

        const utf8Base64 = btoa(unescape(encodeURIComponent(iconSVG)));
        return 'data:image/svg+xml;base64,' + utf8Base64;
    }

    /**
     * 마커 타입에 따른 기본 설정을 반환합니다.
     * @param {string} type - 마커 타입
     * @returns {object} - 타입별 설정 객체
     */
    static getConfig(type = 'default') {
        const configs = {
            'default': {
                size: { width: 34, height: 40 },
                path: "M17 40C17 40 3 22 3 15C3 6.71572 9.71572 0 17 0C24.2843 0 31 6.71572 31 15C31 22 17 40 17 40Z",
                circleCy: 15,
                circleR: 11,
                textY: 20,
                fontSize: 12,
                defaultColor: '#666666' // 생활폐기물 기본 색상
            },
            'waste': {
                size: { width: 38, height: 44 },
                path: "M19 44C19 44 4 24.2 4 16.5C4 7.38781 10.835 0 19 0C27.165 0 34 7.38781 34 16.5C34 24.2 19 44 19 44Z",
                circleCy: 16,
                circleR: 12,
                textY: 20.5,
                fontSize: 12,
                defaultColor: '#28A745' // 대형폐기물 기본 색상
            }
        };
        return configs[type] || configs['default'];
    }

    /**
     * 상태에 따른 SVG 아이콘 문자열을 반환합니다.
     * @param {string|null} status - 'pending', 'confirmed', 'processed'
     * @returns {string} - SVG 그래픽 요소 문자열
     */
    static getStatusIconSVG(status) {
        const iconSpecs = {
            'pending': {
                fill: '#ffc107', // 노란색 (대기)
                path: '' // 시계 아이콘 등 추가 가능
            },
            'confirmed': {
                fill: '#0d6efd', // 파란색 (확인)
                path: '<path d="M21 8L23 10L27 6" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
            },
            'processed': {
                fill: '#28a745', // 녹색 (처리 완료)
                path: '<path d="M21 8L23 10L27 6" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
            }
        };

        if (!status || !iconSpecs[status]) {
            return '';
        }
        const spec = iconSpecs[status];
        // 아이콘 위치는 기본 마커(width: 34) 기준으로 우측 상단에 위치시킴
        return `
            <g transform="translate(5, -2)">
                <circle cx="24" cy="8" r="7" fill="${spec.fill}" stroke="#fff" stroke-width="1.5"/>
                ${spec.path}
            </g>
        `;
    }
}