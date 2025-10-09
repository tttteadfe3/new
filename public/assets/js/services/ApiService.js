/**
 * ApiService - 서버와의 통신을 관리하는 유틸리티 클래스
 *
 * 주요 기능:
 * - GET, POST, PUT, DELETE 등 다양한 HTTP 메서드 지원
 * - JSON, FormData 등 다양한 데이터 타입 처리
 * - 일관된 에러 처리 및 타임아웃 관리
 */
class ApiService {
    /**
     * 서버에 API 요청을 보냅니다.
     * @param {string} endpoint - 요청을 보낼 API 엔드포인트 (e.g., '/users/1')
     * @param {object} options - fetch()에 전달할 옵션
     * @param {string} [options.method='GET'] - HTTP 메서드
     * @param {object|FormData} [options.body] - 요청 본문
     * @param {number} [options.timeout=30000] - 요청 타임아웃 (ms)
     * @returns {Promise<any>} - 성공 시 API 응답의 data 필드를, 실패 시 에러를 resolve/reject하는 Promise
     */
    static async request(endpoint, options = {}) {
        const { timeout = 30000 } = options;

        if (!endpoint) {
            throw new Error('API 엔드포인트가 제공되지 않았습니다.');
        }

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeout);

        const fetchOptions = {
            ...options,
            signal: controller.signal,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.headers || {}),
            },
        };

        // FormData가 아니고 body가 객체일 경우, JSON으로 변환하고 Content-Type 설정
        if (fetchOptions.body && typeof fetchOptions.body === 'object' && !(fetchOptions.body instanceof FormData)) {
            fetchOptions.body = JSON.stringify(fetchOptions.body);
            fetchOptions.headers['Content-Type'] = 'application/json';
        }

        const API_BASE_URL = '/api';
        try {
            const response = await fetch(`${API_BASE_URL}${endpoint}`, fetchOptions);

            clearTimeout(timeoutId);

            // 응답이 비어있는 경우 (e.g., 204 No Content)
            if (response.status === 204) {
                return Promise.resolve({ success: true, message: '요청이 성공적으로 처리되었지만 응답 본문이 없습니다.' });
            }

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || `API 요청 실패: ${response.status}`);
            }

            // 항상 result 객체 전체를 반환하여 호출부에서 message, data 등을 사용할 수 있게 함
            return result;

        } catch (error) {
            clearTimeout(timeoutId);
            if (error.name === 'AbortError') {
                throw new Error('요청 시간이 초과되었습니다.');
            }
            // 다른 모든 오류 다시 던지기
            throw error;
        }
    }
}