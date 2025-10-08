/**
 * ApiService - 서버와의 통신을 관리하는 유틸리티 클래스
 *
 * 주요 기능:
 * - GET, POST 등 다양한 HTTP 메서드 지원
 * - JSON, FormData 등 다양한 데이터 타입 처리
 * - 일관된 에러 처리 및 타임아웃 관리
 */
class ApiService {
    /**
     * 서버에 API 요청을 보냅니다.
     * @param {string} url - 요청을 보낼 API 엔드포인트 URL
     * @param {object} options - 요청 옵션
     * @param {string} [options.method='POST'] - HTTP 메서드 (GET, POST 등)
     * @param {object|FormData} [options.data={}] - 전송할 데이터
     * @param {string} [options.action] - URL 파라미터로 전달할 action
     * @param {number} [options.timeout=30000] - 요청 타임아웃 (ms)
     * @returns {Promise<any>} - 서버 응답을 resolve하는 Promise
     */
    static request(url, options = {}) {
        const {
            method = 'POST',
            data = {},
            action,
            timeout = 30000
        } = options;

        if (!url) {
            return Promise.reject(new Error('API URL이 제공되지 않았습니다.'));
        }

        // AbortController를 이용한 타임아웃 처리
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeout);

        const fetchOptions = {
            method: method.toUpperCase(),
            signal: controller.signal,
            headers: {}
        };

        let requestUrl = url;

        if (fetchOptions.method === 'GET') {
            // GET 요청: 데이터를 URL 쿼리 파라미터로 변환
            const params = new URLSearchParams(data);
            if (action) {
                params.set('action', action);
            }
            const queryString = params.toString();
            if (queryString) {
                requestUrl = `${url}?${queryString}`;
            }
        } else {
            // POST 및 기타 요청
            if (action) {
                // action은 URL에 쿼리 파라미터로 추가
                requestUrl = `${url}?action=${encodeURIComponent(action)}`;
            }

            if (data instanceof FormData) {
                fetchOptions.body = data;
                // FormData의 경우 Content-Type 헤더는 브라우저가 자동으로 설정
            } else if (Object.keys(data).length > 0) {
                fetchOptions.body = JSON.stringify(data);
                fetchOptions.headers['Content-Type'] = 'application/json';
            }
        }

        return fetch(requestUrl, fetchOptions)
            .finally(() => {
                clearTimeout(timeoutId);
            })
            .then(response => {
                if (!response.ok) {
                    let msg = `서버 통신 오류: ${response.status}`;
                    if (response.status >= 500) {
                        msg = '서버 내부 오류';
                    }
                    throw new Error(msg);
                }
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json();
                }
                return response.text();
            })
            .catch(error => {
                if (error.name === 'AbortError') {
                    throw new Error('요청 시간 초과');
                }
                // 다른 모든 오류 다시 던지기
                throw error;
            });
    }
}
