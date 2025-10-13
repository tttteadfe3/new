/**
 * InteractiveMap - 지도 기반 위치 선택 및 마커 관리 컴포넌트
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
            // 吏��� �ㅼ젙
            mapId: options.mapId || 'map',
            center: options.center || { lat: 37.340187, lng: 126.743888 },
            level: options.level || 3,

            // 吏��� �곹깭 ���� �ㅼ젙
            saveMapState: options.saveMapState !== false,
            storageKey: options.storageKey || 'wasteMapState',

            // �꾩떆 留덉빱 �앹꽦 �쒖꽦��/鍮꾪솢�깊솕 �ㅼ젙
            enableTempMarker: options.enableTempMarker !== false,

            // 留덉빱 �ㅼ젙
            markerTypes: options.markerTypes || {},
            markerSize: options.markerSize || { width: 34, height: 40 },

            // �곗튂 �ㅼ젙
            longPressDelay: options.longPressDelay || 800,
            duplicateThreshold: options.duplicateThreshold || 10,

            // 吏��� �쒗븳
            allowedRegions: options.allowedRegions || [],

            // �꾩떆 留덉빱 �ㅼ젙
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
            // �대깽�� 由ъ뒪�� 李몄“ ����
            eventHandlers: {
                resize: null,
                beforeunload: null,
                visibilitychange: null,
                touchStart: null,
                touchEnd: null,
                touchMove: null,
            },
            // 移댁뭅�ㅻ㏊ �대깽�� 由ъ뒪�� ����
            kakaoMapListeners: [],
            // Draggable �몄뒪�댁뒪�� destroy �⑥닔 ����
            draggableDestroyer: null
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
     * 而댄룷�뚰듃 珥덇린��
     */
    async init() {
        try {
            await this.initMap();
            this.initGeocoder();
            this.bindEvents();
            // 자동 위치 감지 제거 - 수동 버튼으로만 실행
            console.log('InteractiveMap 초기화 완료');
        } catch (error) {
            console.error('InteractiveMap 초기화 실패:', error);
            throw error;
        }
    }

    /**
     * ���λ맂 吏��� �곹깭 遺덈윭�ㅺ린
     */
    loadMapState() {
        if (!this.config.saveMapState) return null;

        try {
            const savedState = localStorage.getItem(this.config.storageKey);
            if (savedState) {
                const state = JSON.parse(savedState);
                console.log('���λ맂 吏��� �곹깭 遺덈윭�ㅺ린:', state);
                return state;
            }
        } catch (error) {
            console.error('吏��� �곹깭 遺덈윭�ㅺ린 �ㅽ뙣:', error);
        }
        return null;
    }

    /**
     * 吏��� �곹깭 ����
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
            console.log('吏��� �곹깭 ���λ맖:', mapState);
        } catch (error) {
            console.error('吏��� �곹깭 ���� �ㅽ뙣:', error);
        }
    }

    /**
     * ���λ맂 吏��� �곹깭 珥덇린��
     */
    clearMapState() {
        try {
            localStorage.removeItem(this.config.storageKey);
            console.log('���λ맂 吏��� �곹깭媛� 珥덇린�붾릺�덉뒿�덈떎.');
        } catch (error) {
            console.error('吏��� �곹깭 珥덇린�� �ㅽ뙣:', error);
        }
    }

    /**
     * 吏��꾨� 湲곕낯 �꾩튂濡� 由ъ뀑
     */
    resetToDefaultPosition() {
        this.clearMapState();
        this.setCenter(this.config.center, this.config.level);
    }

    /**
     * 吏��� 珥덇린��
     */
    initMap() {
        return new Promise((resolve, reject) => {
            try {
                const mapContainer = document.getElementById(this.config.mapId);
                if (!mapContainer) {
                    throw new Error(`吏��� 而⑦뀒�대꼫瑜� 李얠쓣 �� �놁뒿�덈떎: ${this.config.mapId}`);
                }

                // ���λ맂 吏��� �곹깭 遺덈윭�ㅺ린
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
     * 吏��ㅼ퐫�� 珥덇린��
     */
    initGeocoder() {
        this.state.geocoder = new kakao.maps.services.Geocoder();
    }

    /**
     * �대깽�� 諛붿씤��
     */
    bindEvents() {
        // �몃뱾�щ뱾�� state�� ���ν븯�� �섏쨷�� �쒓굅�� �� �덈룄濡� ��
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

        // 由ъ궗�댁쫰 �대깽��
        window.addEventListener('resize', this.state.eventHandlers.resize);

        // �섏씠吏� 醫낅즺 �� 吏��� �곹깭 ����
        window.addEventListener('beforeunload', this.state.eventHandlers.beforeunload);

        // �섏씠吏� �④� �� 吏��� �곹깭 ���� (紐⑤컮�� ����)
        document.addEventListener('visibilitychange', this.state.eventHandlers.visibilitychange);

        // 吏��� �대깽��
        this.bindMapEvents();
    }

    /**
     * 吏��� �대깽�� 諛붿씤��
     */
    bindMapEvents() {
        // 湲곗〈 移댁뭅�ㅻ㏊ 由ъ뒪�� �쒓굅
        this.state.kakaoMapListeners.forEach(({ target, event, handler }) => {
            try {
                kakao.maps.event.removeListener(target, event, handler);
            } catch (e) {
                console.warn('Failed to remove Kakao Maps event listener:', e);
            }
        });
        this.state.kakaoMapListeners = [];

        const mapContainer = document.getElementById(this.config.mapId);

        // 吏��� �곹깭 蹂�寃� �� ���� (�붾컮�댁뒪 �곸슜)
        let saveStateTimeout;
        const debouncedSaveState = () => {
            clearTimeout(saveStateTimeout);
            saveStateTimeout = setTimeout(() => {
                this.saveMapState();
            }, 500); // 500ms �� ����
        };

        const addKakaoListener = (target, event, handler) => {
            kakao.maps.event.addListener(target, event, handler);
            this.state.kakaoMapListeners.push({ target, event, handler });
        };

        // 吏��� 以묒떖 �대룞 �� �곹깭 ����
        addKakaoListener(this.state.map, 'center_changed', debouncedSaveState);

        // 吏��� 以� �덈꺼 蹂�寃� �� �곹깭 ����
        addKakaoListener(this.state.map, 'zoom_changed', debouncedSaveState);

        // �꾩떆 留덉빱 �앹꽦�� �쒖꽦�붾맂 寃쎌슦�먮쭔 �대깽�� 諛붿씤��
        if (this.config.enableTempMarker) {
            // �곗뒪�ы넲: �고겢由�
            if (!this.state.isMobile) {
                const rightClickHandler = (mouseEvent) => {
                    this.handleLocationSelect(mouseEvent.latLng);
                };
                addKakaoListener(this.state.map, 'rightclick', rightClickHandler);
            }

            // 紐⑤컮��: 濡깊봽�덉뒪
            if (this.state.isMobile) {
                this.bindTouchEvents(mapContainer);
            }
        }

        // 吏��� �대┃: �꾩떆 留덉빱 �쒓굅
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
     * �곗튂 �대깽�� 諛붿씤�� (紐⑤컮��)
     */
    bindTouchEvents(element) {
        // �몃뱾�� ����
        this.state.eventHandlers.touchStart = (e) => this.handleTouchStart(e);
        this.state.eventHandlers.touchEnd = () => this.handleTouchEnd();
        this.state.eventHandlers.touchMove = () => this.handleTouchMove();

        element.addEventListener('touchstart', this.state.eventHandlers.touchStart, { passive: false });
        element.addEventListener('touchend', this.state.eventHandlers.touchEnd, { passive: false });
        element.addEventListener('touchmove', this.state.eventHandlers.touchMove, { passive: false });
    }

    /**
     * �곗튂 �쒖옉 泥섎━
     */
    handleTouchStart(e) {
        if (e.touches.length !== 1) return;

        clearTimeout(this.state.longPressTimer);

        const touch = e.touches[0];
        this.state.longPressTimer = setTimeout(() => {
            // 吏꾨룞 �쇰뱶諛�
            if (navigator.vibrate) navigator.vibrate(100);

            // 醫뚰몴 怨꾩궛
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
     * �곗튂 醫낅즺 泥섎━
     */
    handleTouchEnd() {
        clearTimeout(this.state.longPressTimer);
    }

    /**
     * �곗튂 �대룞 泥섎━
     */
    handleTouchMove() {
        clearTimeout(this.state.longPressTimer);
    }

    /**
     * �ъ슜�� �꾩튂 �ㅼ젙 (�섎룞 �몄텧��)
     */
    setUserLocation() {
        return new Promise((resolve) => {
            if (!navigator.geolocation) {
                alert('�꾩튂 �뺣낫瑜� 吏��먰븯吏� �딅뒗 釉뚮씪�곗��낅땲��.');
                resolve(false);
                return;
            }

            // 濡쒕뵫 �곹깭 �쒖떆
            const btn = document.getElementById('currentLocationBtn');
            if (btn) {
                btn.innerHTML = '<i class="ri-loader-4-line" style="font-size: 20px; animation: spin 1s linear infinite;"></i>';
                btn.disabled = true;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const { latitude, longitude } = position.coords;
                    const currentPos = new kakao.maps.LatLng(latitude, longitude);

                    // 湲곗〈 �ъ슜�� �꾩튂 留덉빱 �쒓굅
                    if (this.state.userLocationMarker) {
                        this.state.userLocationMarker.setMap(null);
                    }
                    if (this.state.userLocationInfo) {
                        this.state.userLocationInfo.setMap(null);
                    }

                    // 吏��� 以묒떖 �대룞
                    this.state.map.setCenter(currentPos);

                    // �ъ슜�� �꾩튂 留덉빱 �앹꽦
                    this.createUserLocationMarker(currentPos);

                    // 踰꾪듉 �곹깭 蹂듭썝
                    if (btn) {
                        btn.innerHTML = '<i class="ri-navigation-line" style="font-size: 20px;"></i>';
                        btn.disabled = false;
                    }

                    resolve(true);
                },
                (error) => {
                    console.log('�꾩튂 �뺣낫瑜� 媛��몄삱 �� �놁뒿�덈떎:', error);
                    alert('�꾩튂 �뺣낫瑜� 媛��몄삱 �� �놁뒿�덈떎. GPS媛� �쒖꽦�붾릺�� �덈뒗吏� �뺤씤�댁＜�몄슂.');

                    // 踰꾪듉 �곹깭 蹂듭썝
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
     * �ъ슜�� �꾩튂 留덉빱 �앹꽦
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

        // �꾩튂 �뺣낫 �ㅻ쾭�덉씠
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
                    <strong>�꾩옱 �꾩튂</strong>
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
     * �쒖� locationData 媛앹껜 �앹꽦
     */
    createLocationData(position, addressData = null) {
        const latlng = position instanceof kakao.maps.LatLng
            ? position
            : new kakao.maps.LatLng(position.lat || position.latitude, position.lng || position.longitude);

        return {
            position: latlng,               // kakao.maps.LatLng 媛앹껜
            latitude: latlng.getLat(),      // �レ옄
            longitude: latlng.getLng(),     // �レ옄
            address: addressData?.address || null,     // 臾몄옄�� �먮뒗 null
            region: addressData?.region || null       // 臾몄옄�� �먮뒗 null
        };
    }

    /**
     * �꾩튂 �좏깮 泥섎━ (�고겢由�/濡깊봽�덉뒪)
     */
    async handleLocationSelect(latlng) {
        // �꾩떆 留덉빱 �앹꽦�� 鍮꾪솢�깊솕�� 寃쎌슦 泥섎━�섏� �딆쓬
        if (!this.config.enableTempMarker) {
            console.log('�꾩떆 留덉빱 �앹꽦�� 鍮꾪솢�깊솕�섏뿀�듬땲��.');
            return;
        }

        try {
            // 湲곗〈 �꾩떆 留덉빱 �쒓굅
            this.removeTempMarker();

            // 二쇱냼 寃�利�
            const addressData = await this.resolveAddress(latlng);

            if (!addressData.isValid) {
                this.callbacks.onRegionValidation(false, addressData.message);
                return;
            }

            // �꾩떆 留덉빱 �앹꽦
            await this.createTempMarker(latlng);

            const locationData = this.createLocationData(latlng, addressData);

            // �꾩떆 留덉빱 �앹꽦 肄쒕갚 �몄텧
            this.callbacks.onTempMarkerCreate(locationData);

        } catch (error) {
            console.error('�꾩튂 �좏깮 泥섎━ �ㅻ쪟:', error);
            this.callbacks.onRegionValidation(false, '�꾩튂 泥섎━ 以� �ㅻ쪟媛� 諛쒖깮�덉뒿�덈떎.');
        }
    }

    /**
     * 二쇱냼 蹂��� 諛� 寃�利�
     */
    resolveAddress(latlng) {
        return new Promise((resolve) => {
			console.log(this.config.allowedRegions);
            // 1�④퀎: �됱젙�� �뺤씤
            this.state.geocoder.coord2RegionCode(latlng.getLng(), latlng.getLat(), (regionResult, regionStatus) => {
                if (regionStatus === kakao.maps.services.Status.OK) {
                    const hRegion = regionResult.find(item => item.region_type === "H");

                    if (hRegion) {
                        const region = hRegion.region_3depth_name;

                        // 吏��� 寃�利�
                        if (this.config.allowedRegions.length > 0 && !this.config.allowedRegions.includes(region)) {
                            resolve({
                                isValid: false,
                                message: '�덉슜�섏� �딆� 吏���엯�덈떎.',
                                region
                            });
                            return;
                        }

                        // 2�④퀎: �곸꽭 二쇱냼 媛��몄삤湲�
                        this.state.geocoder.coord2Address(latlng.getLng(), latlng.getLat(), (addressResult, addressStatus) => {
                            if (addressStatus === kakao.maps.services.Status.OK) {
                                const address = addressResult[0].address ?
                                    addressResult[0].address.address_name :
                                    addressResult[0].road_address?.address_name || '二쇱냼 �뺣낫 �놁쓬';

                                resolve({
                                    isValid: true,
                                    address,
                                    region
                                });
                            } else {
                                resolve({
                                    isValid: false,
                                    message: '�곸꽭 二쇱냼瑜� 媛��몄삱 �� �놁뒿�덈떎.',
                                    region
                                });
                            }
                        });
                    } else {
                        resolve({
                            isValid: false,
                            message: '吏��� �뺣낫瑜� 李얠쓣 �� �놁뒿�덈떎.'
                        });
                    }
                } else {
                    resolve({
                        isValid: false,
                        message: '�됱젙�� �뺤씤�� �ㅽ뙣�덉뒿�덈떎.'
                    });
                }
            });
        });
    }

    /**
     * �꾩떆 留덉빱 �앹꽦 �쒖꽦��/鍮꾪솢�깊솕
     */
    setTempMarkerEnabled(enabled) {
        const wasEnabled = this.config.enableTempMarker;
        this.config.enableTempMarker = enabled;

        // �곹깭媛� 蹂�寃쎈맂 寃쎌슦 �대깽�� �щ컮�몃뵫
        if (wasEnabled !== enabled) {
            this.rebindMapEvents();

            // 鍮꾪솢�깊솕�� 湲곗〈 �꾩떆 留덉빱 �쒓굅
            if (!enabled) {
                this.removeTempMarker();
            }

            console.log(`�꾩떆 留덉빱 �앹꽦�� ${enabled ? '�쒖꽦��' : '鍮꾪솢�깊솕'}�섏뿀�듬땲��.`);
        }
    }

    /**
     * �꾩떆 留덉빱 �앹꽦 �쒖꽦�� �곹깭 �뺤씤
     */
    isTempMarkerEnabled() {
        return this.config.enableTempMarker;
    }

    /**
     * �ㅼ젙 �낅뜲�댄듃
     */
    updateConfig(newConfig) {
        const oldTempMarkerEnabled = this.config.enableTempMarker;

        // �ㅼ젙 蹂묓빀
        Object.assign(this.config, newConfig);

        // �꾩떆 留덉빱 �쒖꽦�� �곹깭媛� 蹂�寃쎈맂 寃쎌슦
        if (oldTempMarkerEnabled !== this.config.enableTempMarker) {
            this.rebindMapEvents();

            if (!this.config.enableTempMarker) {
                this.removeTempMarker();
            }
        }
    }

    /**
     * 吏��� �대깽�� �щ컮�몃뵫
     */
    rebindMapEvents() {
        // bindMapEvents �대��먯꽌 湲곗〈 由ъ뒪�덈� �쒓굅�섍퀬 �덈줈 諛붿씤�⑺빀�덈떎.
        this.bindMapEvents();
    }

    /**
     * �꾩떆 留덉빱 �앹꽦 �좉�
     */
    toggleTempMarker() {
        this.setTempMarkerEnabled(!this.config.enableTempMarker);
        return this.config.enableTempMarker;
    }

    /**
     * �꾩떆 留덉빱 �앹꽦 - �섏젙�� 踰꾩쟾
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
            draggable: false // The marker itself is not draggable
        });

        // The handle is created and made draggable inside this function
        this.createTempMarkerHandle(latlng);
    }

    /**
     * �꾩떆 留덉빱 �쒕옒洹� �몃뱾 �앹꽦
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

        // �쒕옒洹� �대깽�� �ㅼ젙
        setTimeout(() => {
            const handleElement = document.querySelector('.temp-marker-handle');
            if (handleElement) {
                // 湲곗〈 draggable destroyer媛� �덉쑝硫� �뚭눼
                if (this.state.draggableDestroyer) {
                    this.state.draggableDestroyer();
                }
                // �덈줈 留뚮뱾怨� destroyer ����
                this.state.draggableDestroyer = this.makeDraggable(handleElement);
            }
        }, 100);
    }

    /**
     * �꾩떆 留덉빱 �몃뱾 �꾩튂 �낅뜲�댄듃
     */
    updateTempMarkerHandle(position) {
        if (this.state.tempMarkerHandle) {
            this.state.tempMarkerHandle.setPosition(position);
        }
    }

    /**
     * �쒕옒洹� �몃뱾 �쒕옒洹�/�대┃ 泥섎━
     */
    makeDraggable(handleElement) {
        let isDragging = false;
        let hasMoved = false;
        let startPos = null;
        let initialMarkerPos = null;

        const getEventPos = (e) => {
            if (e.touches && e.touches.length > 0) {
                return { x: e.touches[0].clientX, y: e.touches[0].clientY };
            }
            return { x: e.clientX, y: e.clientY };
        };

        const handleStart = (e) => {
            e.preventDefault();
            e.stopPropagation();

            isDragging = true;
            hasMoved = false;
            startPos = getEventPos(e);

            if (this.state.tempMarker) {
                initialMarkerPos = this.state.tempMarker.getPosition();
            }

            handleElement.style.cursor = 'grabbing';
            handleElement.style.transform = 'scale(1.1)';

            if (navigator.vibrate) navigator.vibrate(50);
        };

        const handleMove = (e) => {
            if (!isDragging || !startPos || !initialMarkerPos) return;

            e.preventDefault();
            e.stopPropagation();

            const currentPos = getEventPos(e);
            const deltaX = currentPos.x - startPos.x;
            const deltaY = currentPos.y - startPos.y;

            if (Math.abs(deltaX) > 5 || Math.abs(deltaY) > 5) {
                hasMoved = true;
            }

            const initialPoint = this.state.map.getProjection().containerPointFromCoords(initialMarkerPos);
            const newPoint = new kakao.maps.Point(initialPoint.x + deltaX, initialPoint.y + deltaY);
            const latlng = this.state.map.getProjection().coordsFromContainerPoint(newPoint);

            if (this.state.tempMarker) {
                this.state.tempMarker.setPosition(latlng);
                this.updateTempMarkerHandle(latlng);
            }
        };

        const handleEnd = async (e) => {
            if (!isDragging) return;

            e.preventDefault();
            e.stopPropagation();

            const wasDragging = isDragging && hasMoved;

            isDragging = false;
            startPos = null;
            handleElement.style.cursor = 'grab';
            handleElement.style.transform = 'scale(1)';

            if (!this.state.tempMarker) return; // 留덉빱媛� �녿뒗 寃쎌슦 醫낅즺

            const currentPosition = this.state.tempMarker.getPosition();
            const addressData = await this.resolveAddress(currentPosition);
            const locationData = this.createLocationData(currentPosition, addressData);

            if (wasDragging) {
                this.callbacks.onAddressResolved(locationData, addressData);
            } else {
                this.callbacks.onTempMarkerClick(locationData);
            }

            hasMoved = false;
        };

        // �대깽�� 由ъ뒪�� �깅줉
        handleElement.addEventListener('mousedown', handleStart);
        document.addEventListener('mousemove', handleMove);
        document.addEventListener('mouseup', handleEnd);

        handleElement.addEventListener('touchstart', handleStart, { passive: false });
        document.addEventListener('touchmove', handleMove, { passive: false });
        document.addEventListener('touchend', handleEnd, { passive: false });

        // �쒓굅 �⑥닔 諛섑솚
        return () => {
            handleElement.removeEventListener('mousedown', handleStart);
            document.removeEventListener('mousemove', handleMove);
            document.removeEventListener('mouseup', handleEnd);
            handleElement.removeEventListener('touchstart', handleStart);
            document.removeEventListener('touchmove', handleMove);
            document.removeEventListener('touchend', handleEnd);
        };
    }
    /**
     * �꾩옱 �꾩떆 留덉빱�� �꾩쟾�� �뺣낫 媛��몄삤湲�
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
     * �꾩떆 留덉빱 �쒓굅
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
        // �쒕옒洹� �몃뱾�щ룄 �뚭눼
        if (this.state.draggableDestroyer) {
            this.state.draggableDestroyer();
            this.state.draggableDestroyer = null;
        }
    }

    /**
     * �쇰컲 留덉빱 異붽�
     */
    addMarker(options) {
        const {
            position,
            type = 'default',
            data = {},
            draggable = false, // draggable 湲곕낯媛� �ㅼ젙
            onClick = null,
            onDragEnd = null   // onDragEnd 肄쒕갚 異붽�
        } = options;

        const latlng = position instanceof kakao.maps.LatLng
            ? position
            : new kakao.maps.LatLng(position.lat || position.latitude, position.lng || position.longitude);

        const marker = new kakao.maps.Marker({
            position: latlng,
            map: this.state.map,
            draggable: draggable // draggable �띿꽦 �곸슜
        });

        // 留덉빱 ���낅퀎 �대�吏� �ㅼ젙
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

        // �대┃ �대깽��
        if (onClick) {
            const clickHandler = () => {
                this.state.isMarkerClick = true;
                onClick(marker, data);
            };
            addKakaoListener(marker, 'click', clickHandler);
        }

        // �쒕옒洹� 醫낅즺 �대깽��
        if (draggable && onDragEnd) {
            const dragEndHandler = () => {
                const newPosition = marker.getPosition();

                (async () => {
                    const addressData = await this.resolveAddress(newPosition);
					console.log(addressData);

                    if (!addressData.isValid) {
                        this.callbacks.onRegionValidation(false, addressData.message);
                        return;
                    }

                    const locationData = this.createLocationData(newPosition, addressData);

                    if (this.callbacks.onAddressResolved) {
                        this.callbacks.onAddressResolved(locationData);
                    }
                })();

                if (onDragEnd) {
                    onDragEnd({
                        lat: newPosition.getLat(),
                        lng: newPosition.getLng()
                    });
                }
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
     * 留덉빱 �쒓굅
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
     * 紐⑤뱺 留덉빱 �쒓굅
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
     * 以묐났 �꾩튂 �뺤씤
     */
    checkDuplicateLocation(position, threshold = null) {
        const checkThreshold = threshold || this.config.duplicateThreshold;
        const targetLat = position.lat || position.latitude || position.getLat();
        const targetLng = position.lng || position.longitude || position.getLng();

        return this.state.markers.some(markerData => {
            const markerPos = markerData.position;
            const distance = this.calculateDistance(
                targetLat, targetLng,
                markerPos.getLat(), markerPos.getLng()
            );
            return distance < checkThreshold;
        });
    }

    /**
     * 嫄곕━ 怨꾩궛 (誘명꽣)
     */
    calculateDistance(lat1, lng1, lat2, lng2) {
        const R = 6371e3;
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
     * 吏��� 以묒떖 �대룞
     */
    setCenter(position, level = null) {
        const latlng = position instanceof kakao.maps.LatLng
            ? position
            : new kakao.maps.LatLng(position.lat || position.latitude, position.lng || position.longitude);

        this.state.map.setCenter(latlng);

        if (level !== null) {
            this.state.map.setLevel(level);
        }

        // �곹깭 ���� (�쎄컙�� 吏��� ��)
        setTimeout(() => {
            this.saveMapState();
        }, 100);
    }

    /**
     * �꾩옱 �꾩떆 留덉빱 �꾩튂 媛��몄삤湲�
     */
    getTempMarkerPosition() {
        return this.state.tempMarker ? this.state.tempMarker.getPosition() : null;
    }

    /**
     * 吏��� �몄뒪�댁뒪 媛��몄삤湲�
     */
    getMap() {
        return this.state.map;
    }

    /**
     * 紐⑤뱺 留덉빱 媛��몄삤湲�
     */
    getMarkers() {
        return [...this.state.markers];
    }

    /**
     * 컴포넌트 정리
     */
    destroy() {
        console.log('Destroying InteractiveMap...');

        // 1. Draggable 이벤트 리스너 제거
        if (this.state.draggableDestroyer) {
            this.state.draggableDestroyer();
            this.state.draggableDestroyer = null;
        }

        // 2. 紐⑤뱺 留덉빱 �쒓굅
        this.clearMarkers();
        this.removeTempMarker();

        // 3. �ъ슜�� �꾩튂 留덉빱 諛� �뺣낫李� �쒓굅
        if (this.state.userLocationMarker) {
            this.state.userLocationMarker.setMap(null);
            this.state.userLocationMarker = null;
        }
        if (this.state.userLocationInfo) {
            this.state.userLocationInfo.setMap(null);
            this.state.userLocationInfo = null;
        }

        // 4. ���대㉧ �뺣━
        if (this.state.longPressTimer) {
            clearTimeout(this.state.longPressTimer);
            this.state.longPressTimer = null;
        }

        // 5. Kakao Map �대깽�� 由ъ뒪�� �쒓굅
        this.state.kakaoMapListeners.forEach(({ target, event, handler }) => {
            try {
                kakao.maps.event.removeListener(target, event, handler);
            } catch (e) {
                console.warn('Failed to remove Kakao Maps event listener:', e);
            }
        });
        this.state.kakaoMapListeners = [];

        // 6. �꾩뿭(window, document) �대깽�� 由ъ뒪�� �쒓굅
        if (this.state.eventHandlers.resize) {
            window.removeEventListener('resize', this.state.eventHandlers.resize);
        }
        if (this.state.eventHandlers.beforeunload) {
            window.removeEventListener('beforeunload', this.state.eventHandlers.beforeunload);
        }
        if (this.state.eventHandlers.visibilitychange) {
            document.removeEventListener('visibilitychange', this.state.eventHandlers.visibilitychange);
        }

        // 7. Touch �대깽�� 由ъ뒪�� �쒓굅
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

        // 8. 吏��� 媛앹껜 李몄“ �댁젣
        this.state.map = null;
        this.state.geocoder = null;

        console.log('InteractiveMap destroyed.');
    }
}
