/**
 * InteractiveMapManager - 지도 기반 위치 선택 및 마커 관리 컴포넌트
 *
 * 주요 기능:
 * - 지도 초기화 및 사용자 위치 표시
 * - 클릭/터치로 임시 마커 생성 및 드래그
 * - 좌표-주소 변환 및 지역 검증
 * - 마커 등록/처리/삭제 관리
 * - 모바일 터치 이벤트 처리
 */
class InteractiveMap {
    constructor(options = {}) {
        this.config = {
            // 지도 설정
            mapId: options.mapId || 'map',
            center: options.center || { lat: 37.340187, lng: 126.743888 },
            level: options.level || 3,

            // 지도 상태 저장 설정
            saveMapState: options.saveMapState !== false,
            storageKey: options.storageKey || 'wasteMapState',

            // 임시 마커 생성 활성화/비활성화 설정
            enableTempMarker: options.enableTempMarker !== false,

            // 마커 설정
            markerTypes: options.markerTypes || {},
            markerSize: options.markerSize || { width: 34, height: 40 },

            // 터치 설정
            longPressDelay: options.longPressDelay || 800,
            duplicateThreshold: options.duplicateThreshold || 10,

            // 지역 제한
            allowedRegions: options.allowedRegions || [],

            // 임시 마커 설정
            tempMarker: {
                color: options.tempMarkerColor || '#2563EB',
                size: options.tempMarkerSize || 80,
                icon: options.tempMarkerIcon || 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzQiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCAzNCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTE3IDQwQzE3IDQwIDMgMjIgMyAxNUMzIDYuNzE1NzIgOS43MTU3MiAwIDE3IDBDMjQuMjg0MyAwIDMxIDYuNzE1NzIgMzEgMTVDMzEgMjIgMTcgNDAgMTcgNDBaIiBmaWxsPSIjMjU2M0VCIiBzdHJva2U9IiNmZmYiIHN0cm9rZS13aWR0aD0iMSIvPgo8Y2lyY2xlIGN4PSIxNyIgY3k9IjE1IiByPSIxMSIgZmlsbD0iI2ZmZiIvPgo8Y2lyY2xlIGN4PSIxNyIgY3k9IjE1IiByPSI1IiBmaWxsPSIjMjU2M0VCIi8+Cjwvc3ZnPg=='
            }
        };

        this.state = {
            map: null,
            geocoder: null,
            markers: [],
            tempMarker: null,
            tempMarkerHandle: null,
            userLocationMarker: null,
            isMobile: window.innerWidth < 992,
            longPressTimer: null,
            isMarkerClick: false,
            // 이벤트 리스너 참조 저장
            eventHandlers: {
                resize: null,
                beforeunload: null,
                visibilitychange: null,
                touchStart: null,
                touchEnd: null,
                touchMove: null,
            },
            // 카카오맵 이벤트 리스너 저장
            kakaoMapListeners: []
        };

        this.callbacks = {
            onTempMarkerCreate: options.onTempMarkerCreate || (() => {}),
            onTempMarkerClick: options.onTempMarkerClick || (() => {}),
            onMarkerClick: options.onMarkerClick || (() => {}),
            onAddressResolved: options.onAddressResolved || (() => {}),
            onRegionValidation: options.onRegionValidation || (() => {})
        };

        this.init();
    }

    /**
     * 컴포넌트 초기화
     */
    async init() {
        try {
            await this.initMap();
            this.initGeocoder();
            this.bindEvents();
            // 자동 위치 감지 제거 - 수동 버튼으로만 실행
            console.log('InteractiveMapManager 초기화 완료');
        } catch (error) {
            console.error('InteractiveMapManager 초기화 실패:', error);
            throw error;
        }
    }

    /**
     * 저장된 지도 상태 불러오기
     */
    loadMapState() {
        if (!this.config.saveMapState) return null;

        try {
            const savedState = localStorage.getItem(this.config.storageKey);
            if (savedState) {
                const state = JSON.parse(savedState);
                console.log('저장된 지도 상태 불러오기:', state);
                return state;
            }
        } catch (error) {
            console.error('지도 상태 불러오기 실패:', error);
        }
        return null;
    }

    /**
     * 지도 상태 저장
     */
    saveMapState() {
        if (!this.config.saveMapState || !this.state.map) return;

        try {
            const center = this.state.map.getCenter();
            const level = this.state.map.getLevel();

            const mapState = {
                center: {
                    lat: center.getLat(),
                    lng: center.getLng()
                },
                level: level,
                timestamp: Date.now()
            };

            localStorage.setItem(this.config.storageKey, JSON.stringify(mapState));
            console.log('지도 상태 저장됨:', mapState);
        } catch (error) {
            console.error('지도 상태 저장 실패:', error);
        }
    }

    /**
     * 저장된 지도 상태 초기화
     */
    clearMapState() {
        try {
            localStorage.removeItem(this.config.storageKey);
            console.log('저장된 지도 상태가 초기화되었습니다.');
        } catch (error) {
            console.error('지도 상태 초기화 실패:', error);
        }
    }

    /**
     * 지도를 기본 위치로 리셋
     */
    resetToDefaultPosition() {
        this.clearMapState();
        this.setCenter(this.config.center, this.config.level);
    }

    /**
     * 지도 초기화
     */
    initMap() {
        return new Promise((resolve, reject) => {
            try {
                const mapContainer = document.getElementById(this.config.mapId);
                if (!mapContainer) {
                    throw new Error(`지도 컨테이너를 찾을 수 없습니다: ${this.config.mapId}`);
                }

                // 저장된 지도 상태 불러오기
                const savedState = this.loadMapState();
                const initialCenter = savedState ? savedState.center : this.config.center;
                const initialLevel = savedState ? savedState.level : this.config.level;

                this.state.map = new kakao.maps.Map(mapContainer, {
                    center: new kakao.maps.LatLng(initialCenter.lat, initialCenter.lng),
                    level: initialLevel
                });

                resolve();
            } catch (error) {
                reject(error);
            }
        });
    }

    /**
     * 지오코더 초기화
     */
    initGeocoder() {
        this.state.geocoder = new kakao.maps.services.Geocoder();
    }

    /**
     * 이벤트 바인딩
     */
    bindEvents() {
        // 핸들러들을 state에 저장하여 나중에 제거할 수 있도록 함
        this.state.eventHandlers.resize = () => {
            this.state.isMobile = window.innerWidth < 992;
        };
        this.state.eventHandlers.beforeunload = () => {
            this.saveMapState();
        };
        this.state.eventHandlers.visibilitychange = () => {
            if (document.visibilityState === 'hidden') {
                this.saveMapState();
            }
        };

        // 리사이즈 이벤트
        window.addEventListener('resize', this.state.eventHandlers.resize);

        // 페이지 종료 시 지도 상태 저장
        window.addEventListener('beforeunload', this.state.eventHandlers.beforeunload);

        // 페이지 숨김 시 지도 상태 저장 (모바일 대응)
        document.addEventListener('visibilitychange', this.state.eventHandlers.visibilitychange);

        // 지도 이벤트
        this.bindMapEvents();
    }

    /**
     * 지도 이벤트 바인딩
     */
    bindMapEvents() {
        // 기존 카카오맵 리스너 제거
        this.state.kakaoMapListeners.forEach(({ target, event, handler }) => {
            try {
                kakao.maps.event.removeListener(target, event, handler);
            } catch (e) {
                console.warn('Failed to remove Kakao Maps event listener:', e);
            }
        });
        this.state.kakaoMapListeners = [];

        const mapContainer = document.getElementById(this.config.mapId);

        // 지도 상태 변경 시 저장 (디바운스 적용)
        let saveStateTimeout;
        const debouncedSaveState = () => {
            clearTimeout(saveStateTimeout);
            saveStateTimeout = setTimeout(() => {
                this.saveMapState();
            }, 500); // 500ms 후 저장
        };

        const addKakaoListener = (target, event, handler) => {
            kakao.maps.event.addListener(target, event, handler);
            this.state.kakaoMapListeners.push({ target, event, handler });
        };

        // 지도 중심 이동 시 상태 저장
        addKakaoListener(this.state.map, 'center_changed', debouncedSaveState);

        // 지도 줌 레벨 변경 시 상태 저장
        addKakaoListener(this.state.map, 'zoom_changed', debouncedSaveState);

        // 임시 마커 생성이 활성화된 경우에만 이벤트 바인딩
        if (this.config.enableTempMarker) {
            // 데스크톱: 우클릭
            if (!this.state.isMobile) {
                const rightClickHandler = (mouseEvent) => {
                    this.handleLocationSelect(mouseEvent.latLng);
                };
                addKakaoListener(this.state.map, 'rightclick', rightClickHandler);
            }

            // 모바일: 롱프레스
            if (this.state.isMobile) {
                this.bindTouchEvents(mapContainer);
            }
        }

        // 지도 클릭: 임시 마커 제거
        let mapClickTimeout;
        const clickHandler = () => {
            clearTimeout(mapClickTimeout);
            mapClickTimeout = setTimeout(() => {
                if (!this.state.isMarkerClick) {
                    this.removeTempMarker();
                }
                this.state.isMarkerClick = false;
            }, 100);
        };
        addKakaoListener(this.state.map, 'click', clickHandler);
    }

    /**
     * 터치 이벤트 바인딩 (모바일)
     */
    bindTouchEvents(element) {
        // 핸들러 저장
        this.state.eventHandlers.touchStart = (e) => this.handleTouchStart(e);
        this.state.eventHandlers.touchEnd = () => this.handleTouchEnd();
        this.state.eventHandlers.touchMove = () => this.handleTouchMove();

        element.addEventListener('touchstart', this.state.eventHandlers.touchStart, { passive: false });
        element.addEventListener('touchend', this.state.eventHandlers.touchEnd, { passive: false });
        element.addEventListener('touchmove', this.state.eventHandlers.touchMove, { passive: false });
    }

    /**
     * 터치 시작 처리
     */
    handleTouchStart(e) {
        if (e.touches.length !== 1) return;

        clearTimeout(this.state.longPressTimer);

        const touch = e.touches[0];
        this.state.longPressTimer = setTimeout(() => {
            // 진동 피드백
            if (navigator.vibrate) navigator.vibrate(100);

            // 좌표 계산
            const rect = this.state.map.getNode().getBoundingClientRect();
            const point = new kakao.maps.Point(
                touch.clientX - rect.left,
                touch.clientY - rect.top
            );
            const latlng = this.state.map.getProjection().coordsFromContainerPoint(point);

            this.handleLocationSelect(latlng);
        }, this.config.longPressDelay);
    }

    /**
     * 터치 종료 처리
     */
    handleTouchEnd() {
        clearTimeout(this.state.longPressTimer);
    }

    /**
     * 터치 이동 처리
     */
    handleTouchMove() {
        clearTimeout(this.state.longPressTimer);
    }

    /**
     * 사용자 위치 설정 (수동 호출용)
     */
    setUserLocation() {
        return new Promise((resolve) => {
            if (!navigator.geolocation) {
                alert('위치 정보를 지원하지 않는 브라우저입니다.');
                resolve(false);
                return;
            }

            // 로딩 상태 표시
            const btn = document.getElementById('currentLocationBtn');
            if (btn) {
                btn.innerHTML = '<i class="ri-loader-4-line" style="font-size: 20px; animation: spin 1s linear infinite;"></i>';
                btn.disabled = true;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const { latitude, longitude } = position.coords;
                    const currentPos = new kakao.maps.LatLng(latitude, longitude);

                    // 기존 사용자 위치 마커 제거
                    if (this.state.userLocationMarker) {
                        this.state.userLocationMarker.setMap(null);
                    }
                    if (this.state.userLocationInfo) {
                        this.state.userLocationInfo.setMap(null);
                    }

                    // 지도 중심 이동
                    this.state.map.setCenter(currentPos);

                    // 사용자 위치 마커 생성
                    this.createUserLocationMarker(currentPos);

                    // 버튼 상태 복원
                    if (btn) {
                        btn.innerHTML = '<i class="ri-navigation-line" style="font-size: 20px;"></i>';
                        btn.disabled = false;
                    }

                    resolve(true);
                },
                (error) => {
                    console.log('위치 정보를 가져올 수 없습니다:', error);
                    alert('위치 정보를 가져올 수 없습니다. GPS가 활성화되어 있는지 확인해주세요.');

                    // 버튼 상태 복원
                    if (btn) {
                        btn.innerHTML = '<i class="ri-navigation-line" style="font-size: 20px;"></i>';
                        btn.disabled = false;
                    }

                    resolve(false);
                }
            );
        });
    }

    /**
     * 사용자 위치 마커 생성
     */
    createUserLocationMarker(position) {
        const markerContent = `
            <div style="
                width: 18px;
                height: 18px;
                background: red;
                border: 2px solid black;
                border-radius: 50%;
                box-shadow: 0 0 5px rgba(0,0,0,0.3);
            "></div>
        `;

        this.state.userLocationMarker = new kakao.maps.CustomOverlay({
            position,
            content: markerContent,
            yAnchor: 0.5
        });

        this.state.userLocationMarker.setMap(this.state.map);

        // 위치 정보 오버레이
        this.state.userLocationInfo = new kakao.maps.CustomOverlay({
            position,
            content: `
                <div style="
                    position: relative;
                    display: inline-block;
                    padding: 8px 12px;
                    background: white;
                    border: 1px solid #ccc;
                    border-radius: 10px;
                    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
                    font-size: 14px;
                    color: #333;
                    white-space: nowrap;
                ">
                    <strong>현재 위치</strong>
                    <div style="
                        position: absolute;
                        bottom: -10px;
                        left: 50%;
                        transform: translateX(-50%);
                        width: 0;
                        height: 0;
                        border-left: 8px solid transparent;
                        border-right: 8px solid transparent;
                        border-top: 10px solid white;
                    "></div>
                    <div style="
                        position: absolute;
                        bottom: -11px;
                        left: 50%;
                        transform: translateX(-50%);
                        width: 0;
                        height: 0;
                        border-left: 9px solid transparent;
                        border-right: 9px solid transparent;
                        border-top: 11px solid #ccc;
                        z-index: -1;
                    "></div>
                </div>
            `,
            yAnchor: 1.5
        });

        this.state.userLocationInfo.setMap(this.state.map);
    }


    /**
     * 표준 locationData 객체 생성
     */
    createLocationData(position, addressData = null) {
        const latlng = position instanceof kakao.maps.LatLng
            ? position
            : new kakao.maps.LatLng(position.lat || position.latitude, position.lng || position.longitude);

        return {
            position: latlng,               // kakao.maps.LatLng 객체
            latitude: latlng.getLat(),      // 숫자
            longitude: latlng.getLng(),     // 숫자
            address: addressData?.address || null,     // 문자열 또는 null
            region: addressData?.region || null       // 문자열 또는 null
        };
    }

    /**
     * 위치 선택 처리 (우클릭/롱프레스)
     */
    async handleLocationSelect(latlng) {
        // 임시 마커 생성이 비활성화된 경우 처리하지 않음
        if (!this.config.enableTempMarker) {
            console.log('임시 마커 생성이 비활성화되었습니다.');
            return;
        }

        try {
            // 기존 임시 마커 제거
            this.removeTempMarker();

            // 주소 검증
            const addressData = await this.resolveAddress(latlng);

            if (!addressData.isValid) {
                this.callbacks.onRegionValidation(false, addressData.message);
                return;
            }

            // 임시 마커 생성
            await this.createTempMarker(latlng);

            const locationData = this.createLocationData(latlng, addressData);

            // 임시 마커 생성 콜백 호출
            this.callbacks.onTempMarkerCreate(locationData);

        } catch (error) {
            console.error('위치 선택 처리 오류:', error);
            this.callbacks.onRegionValidation(false, '위치 처리 중 오류가 발생했습니다.');
        }
    }

    /**
     * 주소 변환 및 검증
     */
    resolveAddress(latlng) {
        return new Promise((resolve) => {
			console.log(this.config.allowedRegions);
            // 1단계: 행정동 확인
            this.state.geocoder.coord2RegionCode(latlng.getLng(), latlng.getLat(), (regionResult, regionStatus) => {
                if (regionStatus === kakao.maps.services.Status.OK) {
                    const hRegion = regionResult.find(item => item.region_type === "H");

                    if (hRegion) {
                        const region = hRegion.region_3depth_name;

                        // 지역 검증
                        if (this.config.allowedRegions.length > 0 && !this.config.allowedRegions.includes(region)) {
                            resolve({
                                isValid: false,
                                message: '허용되지 않은 지역입니다.',
                                region
                            });
                            return;
                        }

                        // 2단계: 상세 주소 가져오기
                        this.state.geocoder.coord2Address(latlng.getLng(), latlng.getLat(), (addressResult, addressStatus) => {
                            if (addressStatus === kakao.maps.services.Status.OK) {
                                const address = addressResult[0].address ?
                                    addressResult[0].address.address_name :
                                    addressResult[0].road_address?.address_name || '주소 정보 없음';

                                resolve({
                                    isValid: true,
                                    address,
                                    region
                                });
                            } else {
                                resolve({
                                    isValid: false,
                                    message: '상세 주소를 가져올 수 없습니다.',
                                    region
                                });
                            }
                        });
                    } else {
                        resolve({
                            isValid: false,
                            message: '지역 정보를 찾을 수 없습니다.'
                        });
                    }
                } else {
                    resolve({
                        isValid: false,
                        message: '행정동 확인에 실패했습니다.'
                    });
                }
            });
        });
    }

    /**
     * 임시 마커 생성 활성화/비활성화
     */
    setTempMarkerEnabled(enabled) {
        const wasEnabled = this.config.enableTempMarker;
        this.config.enableTempMarker = enabled;

        // 상태가 변경된 경우 이벤트 재바인딩
        if (wasEnabled !== enabled) {
            this.rebindMapEvents();

            // 비활성화시 기존 임시 마커 제거
            if (!enabled) {
                this.removeTempMarker();
            }

            console.log(`임시 마커 생성이 ${enabled ? '활성화' : '비활성화'}되었습니다.`);
        }
    }

    /**
     * 임시 마커 생성 활성화 상태 확인
     */
    isTempMarkerEnabled() {
        return this.config.enableTempMarker;
    }

    /**
     * 설정 업데이트
     */
    updateConfig(newConfig) {
        const oldTempMarkerEnabled = this.config.enableTempMarker;

        // 설정 병합
        Object.assign(this.config, newConfig);

        // 임시 마커 활성화 상태가 변경된 경우
        if (oldTempMarkerEnabled !== this.config.enableTempMarker) {
            this.rebindMapEvents();

            if (!this.config.enableTempMarker) {
                this.removeTempMarker();
            }
        }
    }

    /**
     * 지도 이벤트 재바인딩
     */
    rebindMapEvents() {
        // bindMapEvents 내부에서 기존 리스너를 제거하고 새로 바인딩합니다.
        this.bindMapEvents();
    }

    /**
     * 임시 마커 생성 토글
     */
    toggleTempMarker() {
        this.setTempMarkerEnabled(!this.config.enableTempMarker);
        return this.config.enableTempMarker;
    }

    /**
     * 임시 마커 생성 - 수정된 버전
     */
    async createTempMarker(latlng) {
        const tempMarkerImage = new kakao.maps.MarkerImage(
            this.config.tempMarker.icon,
            new kakao.maps.Size(this.config.markerSize.width, this.config.markerSize.height)
        );

        this.state.tempMarker = new kakao.maps.Marker({
            position: latlng,
            map: this.state.map,
            image: tempMarkerImage,
            draggable: true
        });

        // 드래그 핸들 생성
        this.createTempMarkerHandle(latlng);

        const addKakaoListener = (target, event, handler) => {
            kakao.maps.event.addListener(target, event, handler);
            this.state.kakaoMapListeners.push({ target, event, handler });
        };

        // --- 네이티브 마커 이벤트 핸들링 ---
        let originalPosition = null;

        // 드래그 시작: 원래 위치 저장
        addKakaoListener(this.state.tempMarker, 'dragstart', () => {
            originalPosition = this.state.tempMarker.getPosition();
        });

        // 드래그 중: 핸들 위치 업데이트
        addKakaoListener(this.state.tempMarker, 'drag', () => {
            const newPosition = this.state.tempMarker.getPosition();
            this.updateTempMarkerHandle(newPosition);
        });

        // 드래그 종료: 위치 유효성 검사
        const dragEndHandler = async () => {
            const newPosition = this.state.tempMarker.getPosition();
            this.updateTempMarkerHandle(newPosition);
            const addressData = await this.resolveAddress(newPosition);

            if (!addressData.isValid) {
                this.callbacks.onRegionValidation(false, addressData.message);
                if (originalPosition) {
                    this.state.tempMarker.setPosition(originalPosition);
                    this.updateTempMarkerHandle(originalPosition);
                }
                return;
            }

            const locationData = this.createLocationData(newPosition, addressData);
            this.callbacks.onAddressResolved(locationData, addressData);
        };
        addKakaoListener(this.state.tempMarker, 'dragend', dragEndHandler);

        // 클릭 이벤트: 모달 열기 콜백 호출
        const clickHandler = async () => {
            this.state.isMarkerClick = true;
            const currentPosition = this.state.tempMarker.getPosition();
            const addressData = await this.resolveAddress(currentPosition);
            const locationData = this.createLocationData(currentPosition, addressData);
            this.callbacks.onTempMarkerClick(locationData);
        };
        addKakaoListener(this.state.tempMarker, 'click', clickHandler);
    }

    /**
     * 임시 마커 드래그 핸들 생성
     */
    createTempMarkerHandle(latlng) {
        const handleContent = `
            <div class="temp-marker-handle" style="
                position: absolute;
                width: ${this.config.tempMarker.size}px;
                height: ${this.config.tempMarker.size}px;
                background: rgba(37, 99, 235, 0.15);
                border: 3px solid ${this.config.tempMarker.color};
                border-radius: 50%;
                cursor: grab;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
                backdrop-filter: blur(2px);
                left: -${this.config.tempMarker.size/2}px;
                top: 20px;
                z-index: 999;
                user-select: none;
                touch-action: none;
                pointer-events: none; /* Pass clicks to the marker below */
            ">
                <div style="
                    width: 24px;
                    height: 24px;
                    background: ${this.config.tempMarker.color};
                    border-radius: 50%;
                    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
                    position: relative;
                ">
                    <div style="
                        position: absolute;
                        width: 8px;
                        height: 8px;
                        background: white;
                        border-radius: 50%;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                    "></div>
                </div>
            </div>
        `;

        this.state.tempMarkerHandle = new kakao.maps.CustomOverlay({
            content: handleContent,
            position: latlng,
            xAnchor: 0.5,
            yAnchor: 0.5,
            zIndex: 999
        });

        this.state.tempMarkerHandle.setMap(this.state.map);

        this.state.tempMarkerHandle.setMap(this.state.map);
    }

    /**
     * 임시 마커 핸들 위치 업데이트
     */
    updateTempMarkerHandle(position) {
        if (this.state.tempMarkerHandle) {
            this.state.tempMarkerHandle.setPosition(position);
        }
    }
    /**
     * 현재 임시 마커의 완전한 정보 가져오기
     */
    async getTempMarkerData() {
        if (!this.state.tempMarker) {
            return null;
        }

        const position = this.state.tempMarker.getPosition();
        const addressData = await this.resolveAddress(position);

        return this.createLocationData(position, addressData);
    }
    /**
     * 임시 마커 제거
     */
    removeTempMarker() {
        if (this.state.tempMarker) {
            this.state.tempMarker.setMap(null);
            this.state.tempMarker = null;
        }
        if (this.state.tempMarkerHandle) {
            this.state.tempMarkerHandle.setMap(null);
            this.state.tempMarkerHandle = null;
        }
    }

    /**
     * 일반 마커 추가
     */
    addMarker(options) {
        const {
            position,
            type = 'default',
            data = {},
            draggable = false, // draggable 기본값 설정
            onClick = null,
            onDragEnd = null   // onDragEnd 콜백 추가
        } = options;

        const latlng = position instanceof kakao.maps.LatLng
            ? position
            : new kakao.maps.LatLng(position.lat || position.latitude, position.lng || position.longitude);

        const marker = new kakao.maps.Marker({
            position: latlng,
            map: this.state.map,
            draggable: draggable // draggable 속성 적용
        });

        // 마커 타입별 이미지 설정
        if (this.config.markerTypes[type]) {
            const markerImage = new kakao.maps.MarkerImage(
                this.config.markerTypes[type],
                new kakao.maps.Size(this.config.markerSize.width, this.config.markerSize.height)
            );
            marker.setImage(markerImage);
        }

        const addKakaoListener = (target, event, handler) => {
            kakao.maps.event.addListener(target, event, handler);
            this.state.kakaoMapListeners.push({ target, event, handler });
        };

        // 클릭 이벤트
        if (onClick) {
            const clickHandler = () => {
                this.state.isMarkerClick = true;
                onClick(marker, data);
            };
            addKakaoListener(marker, 'click', clickHandler);
        }

        // 드래그 종료 이벤트
        if (draggable && onDragEnd) {
            let originalPosition = null;

            addKakaoListener(marker, 'dragstart', () => {
                originalPosition = marker.getPosition();
            });

            const dragEndHandler = async () => {
                const newPosition = marker.getPosition();
                const addressData = await this.resolveAddress(newPosition);

                if (!addressData.isValid) {
                    this.callbacks.onRegionValidation(false, addressData.message);
                    if (originalPosition) {
                        marker.setPosition(originalPosition);
                    }
                    // Notify caller that drag ended, but position was reverted
                    onDragEnd({
                        lat: (originalPosition || newPosition).getLat(),
                        lng: (originalPosition || newPosition).getLng()
                    });
                    return;
                }

                const locationData = this.createLocationData(newPosition, addressData);
                if (this.callbacks.onAddressResolved) {
                    this.callbacks.onAddressResolved(locationData, addressData);
                }

                // Notify caller of the new valid position
                onDragEnd({
                    lat: newPosition.getLat(),
                    lng: newPosition.getLng()
                });
            };
            addKakaoListener(marker, 'dragend', dragEndHandler);
        }

        const markerData = {
            marker,
            data,
            type,
            position: latlng
        };

        this.state.markers.push(markerData);
        return markerData;
    }

    /**
     * 마커 제거
     */
    removeMarker(markerData) {
        if (markerData && markerData.marker) {
            markerData.marker.setMap(null);
            const index = this.state.markers.indexOf(markerData);
            if (index > -1) {
                this.state.markers.splice(index, 1);
            }
        }
    }

    /**
     * 모든 마커 제거
     */
    clearMarkers() {
        this.state.markers.forEach(markerData => {
            if (markerData.marker) {
                markerData.marker.setMap(null);
            }
        });
        this.state.markers = [];
    }

    /**
     * 중복 위치 확인
     */
    checkDuplicateLocation(position, threshold = null) {
        const checkThreshold = threshold || this.config.duplicateThreshold;
        const targetCoords = LocationUtils.normalizeCoords(position);

        return this.state.markers.some(markerData => {
            const markerCoords = LocationUtils.normalizeCoords(markerData.position);
            const distance = LocationUtils.calculateDistance(targetCoords, markerCoords);
            return distance < checkThreshold;
        });
    }


    /**
     * 지도 중심 이동
     */
    setCenter(position, level = null) {
        const latlng = position instanceof kakao.maps.LatLng
            ? position
            : new kakao.maps.LatLng(position.lat || position.latitude, position.lng || position.longitude);

        this.state.map.setCenter(latlng);

        if (level !== null) {
            this.state.map.setLevel(level);
        }

        // 상태 저장 (약간의 지연 후)
        setTimeout(() => {
            this.saveMapState();
        }, 100);
    }

    /**
     * 현재 임시 마커 위치 가져오기
     */
    getTempMarkerPosition() {
        return this.state.tempMarker ? this.state.tempMarker.getPosition() : null;
    }

    /**
     * 지도 인스턴스 가져오기
     */
    getMap() {
        return this.state.map;
    }

    /**
     * 모든 마커 가져오기
     */
    getMarkers() {
        return [...this.state.markers];
    }

    /**
     * 컴포넌트 정리
     */
    destroy() {
        console.log('Destroying InteractiveMapManager...');

        // 1. 모든 마커 제거
        this.clearMarkers();
        this.removeTempMarker();

        // 3. 사용자 위치 마커 및 정보창 제거
        if (this.state.userLocationMarker) {
            this.state.userLocationMarker.setMap(null);
            this.state.userLocationMarker = null;
        }
        if (this.state.userLocationInfo) {
            this.state.userLocationInfo.setMap(null);
            this.state.userLocationInfo = null;
        }

        // 4. 타이머 정리
        if (this.state.longPressTimer) {
            clearTimeout(this.state.longPressTimer);
            this.state.longPressTimer = null;
        }

        // 5. Kakao Map 이벤트 리스너 제거
        this.state.kakaoMapListeners.forEach(({ target, event, handler }) => {
            try {
                kakao.maps.event.removeListener(target, event, handler);
            } catch (e) {
                console.warn('Failed to remove Kakao Maps event listener:', e);
            }
        });
        this.state.kakaoMapListeners = [];

        // 6. 전역(window, document) 이벤트 리스너 제거
        if (this.state.eventHandlers.resize) {
            window.removeEventListener('resize', this.state.eventHandlers.resize);
        }
        if (this.state.eventHandlers.beforeunload) {
            window.removeEventListener('beforeunload', this.state.eventHandlers.beforeunload);
        }
        if (this.state.eventHandlers.visibilitychange) {
            document.removeEventListener('visibilitychange', this.state.eventHandlers.visibilitychange);
        }

        // 7. Touch 이벤트 리스너 제거
        const mapContainer = document.getElementById(this.config.mapId);
        if (mapContainer) {
            if (this.state.eventHandlers.touchStart) {
                mapContainer.removeEventListener('touchstart', this.state.eventHandlers.touchStart);
            }
            if (this.state.eventHandlers.touchEnd) {
                mapContainer.removeEventListener('touchend', this.state.eventHandlers.touchEnd);
            }
            if (this.state.eventHandlers.touchMove) {
                mapContainer.removeEventListener('touchmove', this.state.eventHandlers.touchMove);
            }
        }

        // 8. 지도 객체 참조 해제
        this.state.map = null;
        this.state.geocoder = null;

        console.log('InteractiveMapManager destroyed.');
    }
}