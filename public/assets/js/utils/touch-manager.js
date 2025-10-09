/**
 * TouchManager - 터치 및 마우스 이벤트를 관리하여 탭, 롱프레스, 드래그 등을 감지하는 클래스
 */
class TouchManager {
    /**
     * @param {object} options - 설정 옵션
     * @param {number} [options.longPressDelay=800] - 롱프레스로 간주할 시간 (ms)
     * @param {number} [options.tapThreshold=10] - 탭으로 간주할 최대 이동 거리 (px)
     * @param {function} [options.onTap] - 탭 이벤트 콜백
     * @param {function} [options.onLongPress] - 롱프레스 이벤트 콜백
     * @param {function} [options.onDragStart] - 드래그 시작 이벤트 콜백
     * @param {function} [options.onDragMove] - 드래그 이동 이벤트 콜백
     * @param {function} [options.onDragEnd] - 드래그 종료 이벤트 콜백
     */
    constructor(options = {}) {
        this.options = {
            longPressDelay: options.longPressDelay || 800,
            tapThreshold: options.tapThreshold || 10,
            ...options
        };

        this.state = {
            longPressTimer: null,
            startPos: null,
            hasMoved: false,
            isDragging: false,
            boundElement: null,
            listeners: {} // 이벤트 리스너 참조 저장
        };

        this.callbacks = {
            onTap: options.onTap || (() => {}),
            onLongPress: options.onLongPress || (() => {}),
            onDragStart: options.onDragStart || (() => {}),
            onDragMove: options.onDragMove || (() => {}),
            onDragEnd: options.onDragEnd || (() => {})
        };
    }

    /**
     * 특정 HTML 요소에 터치 및 마우스 이벤트를 바인딩
     * @param {HTMLElement} element - 이벤트를 바인딩할 요소
     */
    bindToElement(element) {
        if (!element) {
            console.error("TouchManager: 바인딩할 요소가 없습니다.");
            return;
        }
        this.state.boundElement = element;

        // 리스너들을 객체에 저장하여 나중에 제거할 수 있도록 함
        this.state.listeners = {
            touchStart: (e) => this.handleTouchStart(e),
            touchMove: (e) => this.handleTouchMove(e),
            touchEnd: (e) => this.handleTouchEnd(e),
            mouseDown: (e) => this.handleMouseStart(e),
            mouseMove: (e) => this.handleMouseMove(e),
            mouseUp: (e) => this.handleMouseEnd(e)
        };

        element.addEventListener('touchstart', this.state.listeners.touchStart, { passive: false });
        element.addEventListener('touchmove', this.state.listeners.touchMove, { passive: false });
        element.addEventListener('touchend', this.state.listeners.touchEnd, { passive: false });

        element.addEventListener('mousedown', this.state.listeners.mouseDown);
        // mousemove와 mouseup은 document에 바인딩하여 요소 밖으로 나가도 추적
        document.addEventListener('mousemove', this.state.listeners.mouseMove);
        document.addEventListener('mouseup', this.state.listeners.mouseUp);
    }

    handleTouchStart(e) {
        if (e.touches.length !== 1) return;

        this.clearTimer();
        this.state.isDragging = true;
        this.state.hasMoved = false;
        this.state.startPos = this.getEventPos(e.touches[0]);

        this.state.longPressTimer = setTimeout(() => {
            if (!this.state.hasMoved) {
                if (navigator.vibrate) navigator.vibrate(100);
                this.callbacks.onLongPress(e, this.state.startPos);
            }
        }, this.options.longPressDelay);

        this.callbacks.onDragStart(e, this.state.startPos);
    }

    handleTouchMove(e) {
        if (!this.state.isDragging || !this.state.startPos) return;

        const currentPos = this.getEventPos(e.touches[0]);
        const distance = this.calculateDistance(this.state.startPos, currentPos);

        if (distance > this.options.tapThreshold) {
            this.state.hasMoved = true;
            this.clearTimer();
        }

        this.callbacks.onDragMove(e, currentPos, this.state.startPos);
    }

    handleTouchEnd(e) {
        if (!this.state.isDragging) return;

        this.clearTimer();

        if (!this.state.hasMoved) {
            this.callbacks.onTap(e, this.state.startPos);
        }

        this.callbacks.onDragEnd(e, this.state.startPos);
        this.state.isDragging = false;
        this.state.startPos = null;
        this.state.hasMoved = false;
    }

    handleMouseStart(e) {
        this.state.isDragging = true;
        this.state.hasMoved = false;
        this.state.startPos = this.getEventPos(e);
        this.callbacks.onDragStart(e, this.state.startPos);
    }

    handleMouseMove(e) {
        if (!this.state.isDragging) return;

        const currentPos = this.getEventPos(e);
        if (!this.state.hasMoved) {
            const distance = this.calculateDistance(this.state.startPos, currentPos);
            if (distance > this.options.tapThreshold) {
                this.state.hasMoved = true;
            }
        }

        this.callbacks.onDragMove(e, currentPos, this.state.startPos);
    }

    handleMouseEnd(e) {
        if (!this.state.isDragging) return;

        if (!this.state.hasMoved) {
            this.callbacks.onTap(e, this.state.startPos);
        }

        this.callbacks.onDragEnd(e, this.state.startPos);
        this.state.isDragging = false;
        this.state.startPos = null;
        this.state.hasMoved = false;
    }

    getEventPos(e) {
        return {
            x: e.clientX || e.pageX,
            y: e.clientY || e.pageY
        };
    }

    calculateDistance(pos1, pos2) {
        const dx = pos2.x - pos1.x;
        const dy = pos2.y - pos1.y;
        return Math.sqrt(dx * dx + dy * dy);
    }

    clearTimer() {
        if (this.state.longPressTimer) {
            clearTimeout(this.state.longPressTimer);
            this.state.longPressTimer = null;
        }
    }

    /**
     * 바인딩된 모든 이벤트 리스너를 제거하고 상태를 초기화
     */
    destroy() {
        this.clearTimer();

        if (this.state.boundElement && this.state.listeners) {
            this.state.boundElement.removeEventListener('touchstart', this.state.listeners.touchStart);
            this.state.boundElement.removeEventListener('touchmove', this.state.listeners.touchMove);
            this.state.boundElement.removeEventListener('touchend', this.state.listeners.touchEnd);
            this.state.boundElement.removeEventListener('mousedown', this.state.listeners.mouseDown);
        }

        // document에 바인딩된 리스너 제거
        document.removeEventListener('mousemove', this.state.listeners.mouseMove);
        document.removeEventListener('mouseup', this.state.listeners.mouseUp);

        this.state = {
            longPressTimer: null,
            startPos: null,
            hasMoved: false,
            isDragging: false,
            boundElement: null,
            listeners: {}
        };
        console.log('TouchManager destroyed.');
    }
}