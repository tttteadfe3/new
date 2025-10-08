class WasteCollectionMarkerFactory {
    static createSVGIcon(options = {}) {
        const {
            color = '#28A745',
            size = { width: 38, height: 44 },
            text = '현장'
        } = options;

        const baseIcon = `
            <svg width="${size.width}" height="${size.height}" viewBox="0 0 38 44" xmlns="http://www.w3.org/2000/svg">
                <path d="M19 44C19 44 4 24.2 4 16.5C4 7.38781 10.835 0 19 0C27.165 0 34 7.38781 34 16.5C34 24.2 19 44 19 44Z" 
                      fill="${color}" stroke="#ffffff" stroke-width="1.5"/>
                <circle cx="19" cy="16" r="12" fill="#fff"/>
                <text x="19" y="20.5" text-anchor="middle" fill="${color}" font-size="12" font-weight="bold" font-family="Arial, sans-serif">${text}</text>
            </svg>
        `;
        
        const utf8Base64 = btoa(unescape(encodeURIComponent(baseIcon)));
        return 'data:image/svg+xml;base64,' + utf8Base64;
    }

    static createClusterIcon(options = {}) {
        const {
            color = '#DC2626',
            size = { width: 38, height: 44 },
            text = '시청'
        } = options;

        // The 'count' parameter is no longer used.
        const clusterIcon = `
            <svg width="${size.width}" height="${size.height}" viewBox="0 0 38 44" xmlns="http://www.w3.org/2000/svg">
                <path d="M19 44C19 44 4 24.2 4 16.5C4 7.38781 10.835 0 19 0C27.165 0 34 7.38781 34 16.5C34 24.2 19 44 19 44Z" 
                      fill="${color}" stroke="#ffffff" stroke-width="1.5"/>
                <circle cx="19" cy="16" r="12" fill="#fff"/>
                <text x="19" y="20.5" text-anchor="middle" fill="${color}" font-size="12" font-weight="bold" font-family="Arial, sans-serif">${text}</text>
            </svg>
        `;
        
        const utf8Base64 = btoa(unescape(encodeURIComponent(clusterIcon)));
        return 'data:image/svg+xml;base64,' + utf8Base64;
    }
}

class WasteCollectionApp extends BaseApp {
    constructor() {
        super({
            API_URL: '../api/v1/waste',
            ITEMS: [
                '매트리스', '침대틀', '장롱', '쇼파', '의자', '책상',
                '기타(가구)', '건폐', '소각', '변기', '캐리어', '기타'
            ],
            FILE: {
                MAX_SIZE: 5 * 1024 * 1024, // 5MB
                ALLOWED_TYPES: ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'],
                COMPRESS: {
                    MAX_WIDTH: 1200,
                    MAX_HEIGHT: 1200,
                    QUALITY: 0.8
                }
            },
            ALLOWED_REGIONS: ['정왕1동']
        });

        this.state = {
            ...this.state,
            collectionList: [],
            modals: {},
            currentProcessIndex: null,
            currentOverlay: null,
            isMapReady: false
        };
    }

    async init() {
        const mapOptions = {
            enableTempMarker: true,
            markerTypes: this.generateMarkerTypes(),
            markerSize: { width: 34, height: 40 },
            longPressDelay: 800,
            duplicateThreshold: 10,
            onTempMarkerClick: (locationData) => this.showRegisterModal(locationData),
            onAddressResolved: (locationData) => {
                const addressEl = document.getElementById('address');
                if (locationData.address) {
                    addressEl.textContent = locationData.address;
                } else {
                    addressEl.textContent = '주소를 가져올 수 없습니다.';
                }
            },
            onRegionValidation: (isValid, message) => {
                if (!isValid) {
                    Toast.error(message);
                    this.state.mapManager.removeTempMarker();
                }
            }
        };
        this.initMapManager(mapOptions);
        this.initModals();
        this.populateItems();
        this.bindEvents();
        
        await this.waitForMapReady();
        this.loadData();
    }

    generateMarkerTypes() {
        return {
            field: WasteCollectionMarkerFactory.createSVGIcon({
                color: '#28A745', // DodgerBlue
                text: '현장'
            }),
            online: WasteCollectionMarkerFactory.createSVGIcon({
                color: '#DC2626', // LimeGreen
                text: '시청'
            })
        };
    }

    // 맵이 준비될 때까지 대기
    async waitForMapReady() {
        return new Promise((resolve) => {
            const checkMap = () => {
                if (this.state.mapManager && this.state.mapManager.getMap()) {
                    console.log('맵 준비 완료');
                    this.state.isMapReady = true;
                    resolve();
                } else {
                    console.log('맵 준비 대기 중...');
                    setTimeout(checkMap, 100);
                }
            };
            checkMap();
        });
    }

    initModals() {
        this.state.modals = {
            register: new bootstrap.Offcanvas(document.getElementById('registerCollectionModal')),
            result: new bootstrap.Modal(document.getElementById('resultModal'))
        };
        
        document.getElementById('registerCollectionModal').addEventListener('hidden.bs.offcanvas', () => {
            document.getElementById('wasteCollectionForm').reset();
            const photoStatus = document.getElementById('photoStatus');
            photoStatus.innerHTML = '사진을 촬영하거나 갤러리에서 선택하세요';
            photoStatus.classList.remove('text-info', 'text-success', 'text-warning', 'text-danger');
            document.querySelectorAll('.item-quantity-input').forEach(input => input.value = 0);
        });
    }
    
    populateItems() {
        const itemList = document.getElementById('item-list');
        if(!itemList) return;
        
        itemList.innerHTML = '';
        this.config.ITEMS.forEach(item => {
            const itemHtml = `
                <div class="col-6 mb-2">
                    <div class="d-flex justify-content-between align-items-center border rounded p-2">
                        <span class="item-name" data-item-name="${item}">${item}</span>
                        <div class="input-group input-group-sm" style="width: 120px;">
                            <button class="btn btn-outline-secondary btn-minus" type="button">-</button>
                            <input type="text" class="form-control text-center item-quantity-input" 
                                   value="0" readonly data-item-name="${item}">
                            <button class="btn btn-outline-secondary btn-plus" type="button">+</button>
                        </div>
                    </div>
                </div>
            `;
            itemList.insertAdjacentHTML('beforeend', itemHtml);
        });
        
        itemList.addEventListener('click', (e) => {
            const target = e.target;
            if (target.classList.contains('btn-plus')) {
                const input = target.previousElementSibling;
                input.value = parseInt(input.value) + 1;
            } else if (target.classList.contains('btn-minus')) {
                const input = target.nextElementSibling;
                const currentValue = parseInt(input.value);
                if (currentValue > 0) {
                    input.value = currentValue - 1;
                }
            }
        });
    }

    bindEvents() {
        document.getElementById('registerBtn').addEventListener('click', () => this.registerCollection());
        document.getElementById('currentLocationBtn').addEventListener('click', () => this.state.mapManager.setUserLocation());
        
        const addCenterMarkerBtn = document.getElementById('addCenterMarkerBtn');
        if(addCenterMarkerBtn) {
            addCenterMarkerBtn.addEventListener('click', () => {
                if (this.state.mapManager) {
                    const center = this.state.mapManager.getMap().getCenter();
                    this.state.mapManager.handleLocationSelect(center);
                }
            });
        }
        
        document.getElementById('photo').addEventListener('change', (e) => this.handleFileUpload(e));
    }

    showRegisterModal(locationData) {
        document.getElementById('lat').value = locationData.latitude;
        document.getElementById('lng').value = locationData.longitude;
        
        if (locationData.address) {
            document.getElementById('address').textContent = locationData.address;
        }
        
        // 오늘 날짜로 설정
        this.setTodayDate('#issue_date');
        
        this.state.modals.register.show();
    }

    async loadData() {
        if (!this.state.isMapReady) {
            console.log('맵이 준비되지 않아 컬렉션 로드 지연');
            return;
        }

        try {
            console.log('기존 수거 목록 로드 시작');
            const response = await this.apiCall('get_collections', {}, 'GET');
            console.log('API 응답:', response);
            
            if (response.success && Array.isArray(response.data)) {
                console.log(`${response.data.length}개의 컬렉션을 받았습니다.`);
                this.state.collectionList = []; // Reset the list
                this.groupAndDisplayCollections(response.data);
            } else {
                console.log('응답에 데이터가 없거나 형식이 올바르지 않습니다.');
            }
        } catch (error) {
            console.error('기존 수거 목록 로드 실패:', error);
        }
    }

    groupAndDisplayCollections(data) {
        const onlineSubmissions = data.filter(item => item.type === 'online');
        const fieldSubmissions = data.filter(item => item.type === 'field');

        // 현장 등록은 항상 개별적으로 표시
        fieldSubmissions.forEach(item => this.addCollectionToMap(item));

        // 온라인 배출건만 주소별로 그룹화
        const groupedOnline = onlineSubmissions.reduce((acc, collection) => {
            const address = collection.address.trim();
            if (!acc[address]) {
                acc[address] = [];
            }
            acc[address].push(collection);
            return acc;
        }, {});

        console.log('온라인 배출 그룹화 완료:', groupedOnline);

        Object.values(groupedOnline).forEach(group => {
            if (group.length > 1) {
                this.addClusterMarker(group);
            } else if (group.length === 1) {
                this.addCollectionToMap(group[0]);
            }
        });
    }

    addClusterMarker(group) {
        const firstItem = group[0];
        const clusterIconUrl = WasteCollectionMarkerFactory.createClusterIcon();
        
        const map = this.state.mapManager.getMap();
        const position = new kakao.maps.LatLng(firstItem.latitude, firstItem.longitude);

        const markerImage = new kakao.maps.MarkerImage(
            clusterIconUrl,
            new kakao.maps.Size(this.state.mapManager.config.markerSize.width, this.state.mapManager.config.markerSize.height)
        );

        const marker = new kakao.maps.Marker({
            position: position,
            image: markerImage,
            map: map
        });

        const markerDataPayload = { isCluster: true, collections: group };

        kakao.maps.event.addListener(marker, 'click', () => {
            this.state.mapManager.state.isMarkerClick = true;
            this.showCollectionOverlay(markerDataPayload);
        });

        const managedMarkerData = {
            marker: marker,
            data: markerDataPayload,
            type: 'cluster',
            position: position
        };
        
        // Manually add to the map manager's internal list
        this.state.mapManager.state.markers.push(managedMarkerData);

        this.state.collectionList.push({
            isCluster: true,
            marker: marker,
            data: { collections: group },
            mapManagerData: managedMarkerData
        });
    }

    addCollectionToMap(data) {
        if (data.status === 'processed') {
            return;
        }

        const collectionInfo = this.state.mapManager.addMarker({
            position: { lat: data.latitude, lng: data.longitude },
            type: data.type,
            data: { isCluster: false, collections: [data], id: data.id },
            onClick: (marker, markerData) => {
                this.showCollectionOverlay(markerData);
            }
        });

        this.state.collectionList.push({
            marker: collectionInfo.marker,
            data: { ...data, id: data.id },
            mapManagerData: collectionInfo
        });
    }

    showCollectionOverlay(markerData) {
        if (!markerData || !markerData.collections || markerData.collections.length === 0) {
            Toast.error('해당 수거 정보를 찾을 수 없습니다.');
            return;
        }

        if (this.state.currentOverlay) {
            this.state.currentOverlay.setMap(null);
        }

        const { collections, isCluster } = markerData;
        const firstCollection = collections[0];
        const position = new kakao.maps.LatLng(firstCollection.latitude, firstCollection.longitude);
        const map = this.state.mapManager.getMap();
        
        const collectionsContent = collections.map((collectionData) => {
            let items = [];
            try {
                if (typeof collectionData.items === 'string') items = JSON.parse(collectionData.items);
                else if (Array.isArray(collectionData.items)) items = collectionData.items;
            } catch (e) { console.error('아이템 파싱 실패:', collectionData, e); }

            const itemsContent = items.map(item =>
                `<li class="list-group-item d-flex justify-content-between align-items-center p-1">
                    ${item.name} <span class="badge bg-secondary rounded-pill">${item.quantity}</span>
                </li>`
            ).join('');

            const typeBadge = collectionData.type === 'field'
                ? `<span class="badge bg-info">현장등록</span>`
                : `<span class="badge bg-success">인터넷배출</span>`;
            
            const feeBadge = (collectionData.type === 'online' && collectionData.fee > 0)
                ? `<span class="badge bg-dark ms-1">${collectionData.fee.toLocaleString()}원</span>`
                : '';

            const collectionDate = new Date(collectionData.issue_date).toLocaleDateString('ko-KR', { 
                year: 'numeric', month: '2-digit', day: '2-digit' 
            });

            const originalIndex = this.state.collectionList.findIndex(
                item => !item.isCluster && item.data.id === collectionData.id
            );

            const processButton = (collectionData.type === 'field' && collectionData.status !== 'completed' && originalIndex !== -1)
                ? `<button class="btn btn-sm btn-success w-100 mt-2" 
                          onclick="window.wasteCollectionApp.processCollectionByIndex(${originalIndex})">처리완료</button>`
                : '';

            return `
                <div class="card mb-2">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>${typeBadge}${feeBadge}</div>
                            <small class="text-muted">${collectionDate}</small>
                        </div>
                        <ul class="list-group list-group-flush" style="font-size: 0.8rem;">
                            ${itemsContent || '<li class="list-group-item p-1">품목 정보 없음</li>'}
                        </ul>
                        ${processButton}
                    </div>
                </div>
            `;
        }).join('');

        const headerTitle = isCluster 
            ? `${firstCollection.address} (${collections.length}건)`
            : firstCollection.address;

        const content = `
            <div class="card shadow-sm" style="min-width: 300px; max-width: 350px;">
                <div class="card-header bg-primary text-white p-2 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0" style="font-size: 0.9rem;">
                        ${headerTitle || '주소 정보 없음'}
                    </h6>
                    <button type="button" class="btn-close btn-close-white" style="font-size:0.5rem;" 
                            onclick="window.wasteCollectionApp.closeOverlay()"></button>
                </div>
                <div class="card-body p-2" style="max-height: 350px; overflow-y: auto;">
                    ${collectionsContent}
                </div>
            </div>
        `;
        
        this.state.currentOverlay = new kakao.maps.CustomOverlay({ 
            content, 
            map: map, 
            position, 
            yAnchor: 1.1 
        });
    }
        
    closeOverlay() {
        if (this.state.currentOverlay) {
            this.state.currentOverlay.setMap(null);
            this.state.currentOverlay = null;
        }
    }

    async processCollectionByIndex(index) {
        const collectionData = this.state.collectionList[index]?.data;
        if (!collectionData) {
            Toast.error('해당 수거 정보를 찾을 수 없습니다.');
            return;
        }

        try {
            this.closeOverlay();

            const formData = new FormData();
            formData.append('id', collectionData.id);

            const response = await this.apiCall('process_collection', formData, 'POST');
            
            if (response.success) {
                // 마커 업데이트
                this.updateCollectionAfterProcess(index);
                Toast.success('해당 수거건이 처리되었습니다.');
            } else {
                Toast.error(response.message || '알 수 없는 오류');
            }
        } catch (error) {
            Toast.error(error.message);
        }
    }

    updateCollectionAfterProcess(index) {
        const collectionItem = this.state.collectionList[index];
        if (collectionItem && collectionItem.mapManagerData) {
            // 마커를 맵에서 제거
            this.state.mapManager.removeMarker(collectionItem.mapManagerData);
            
            // 리스트에서 해당 아이템 제거 또는 상태 업데이트
            // 여기서는 리스트에서 제거하여 맵에 다시 표시되지 않도록 함
            this.state.collectionList.splice(index, 1);
        }
    }

    async registerCollection() {
        try {
            // 유효성 검사
            const validation = this.validateRegistrationForm();
            if (!validation.isValid) {
                Toast.error(validation.message);
                return;
            }

            const formData = this.buildRegistrationFormData();
            this.setButtonLoading('#registerBtn', '등록 중...');
            
            const response = await this.apiCall('register_collection', formData);
            
            if (response.success) {
                this.addCollectionToMap(response.data);
                this.state.modals.register.hide();
                this.state.mapManager.removeTempMarker();
                Toast.success('폐기물 수거 정보가 등록되었습니다.');
            } else {
                Toast.error(response.message || '알 수 없는 오류');
            }
        } catch (error) {
            Toast.error('서버와의 통신에 실패했습니다.');
        } finally {
            this.resetButtonLoading('#registerBtn', '<i class="ri-save-line me-1"></i>등록');
        }
    }

    // 등록 폼 유효성 검사
    validateRegistrationForm() {
        // 아이템 선택 검사
        const items = [];
        document.querySelectorAll('.item-quantity-input').forEach(input => {
            const quantity = parseInt(input.value, 10);
            if (quantity > 0) {
                items.push({ name: input.dataset.itemName, quantity });
            }
        });

        if (items.length === 0) {
            return { 
                isValid: false, 
                message: '수량이 1 이상인 품목을 하나 이상 추가해주세요.' 
            };
        }

        // 위치 중복 검사
        const lat = parseFloat(document.getElementById('lat').value);
        const lng = parseFloat(document.getElementById('lng').value);
        if (this.state.mapManager.checkDuplicateLocation({ lat, lng }, 5)) {
            return { 
                isValid: false, 
                message: '같은 위치에 이미 수거 정보가 등록되어 있습니다.' 
            };
        }

        // 사진 파일 검사 (선택사항)
        const photoFile = document.getElementById('photo').files[0];
        if (photoFile) {
            const fileValidation = this.validateFile(photoFile);
            if (!fileValidation.isValid) {
                return { isValid: false, message: '사진 파일: ' + fileValidation.message };
            }
        }

        return { isValid: true };
    }

    // 파일 유효성 검사
    validateFile(file) {
        if (!this.config.FILE.ALLOWED_TYPES.includes(file.type)) {
            return { isValid: false, message: '이미지 파일만 업로드 가능합니다.' };
        }
        if (file.size > this.config.FILE.MAX_SIZE) {
            return { isValid: false, message: '파일 크기는 5MB 이하여야 합니다.' };
        }
        return { isValid: true };
    }

    buildRegistrationFormData() {
        const items = [];
        document.querySelectorAll('.item-quantity-input').forEach(input => {
            const quantity = parseInt(input.value, 10);
            if (quantity > 0) {
                items.push({ name: input.dataset.itemName, quantity });
            }
        });

        const formData = new FormData();
        formData.append('lat', document.getElementById('lat').value);
        formData.append('lng', document.getElementById('lng').value);
        formData.append('address', document.getElementById('address').textContent);
        formData.append('issue_date', document.getElementById('issue_date').value);
        formData.append('items', JSON.stringify(items));
        
        const photoFile = document.getElementById('photo').files[0];
        if (photoFile) formData.append('photo', photoFile);
        
        return formData;
    }

    async handleFileUpload(e) {
        const file = e.target.files[0];
        if (!file) return;

        const statusElement = document.getElementById('photoStatus');
        
        // 파일 검증
        const fileValidation = this.validateFile(file);
        if (!fileValidation.isValid) {
            statusElement.innerHTML = fileValidation.message;
            statusElement.className = 'text-danger';
            e.target.value = '';
            return;
        }

        statusElement.innerHTML = '<i class="ri-loader-4-line"></i> 이미지 압축 중...';
        statusElement.className = 'text-info';

        try {
            const compressedFile = await this.compressImage(file);
            const dt = new DataTransfer();
            dt.items.add(compressedFile);
            e.target.files = dt.files;
            
            const originalSize = (file.size / 1024 / 1024).toFixed(2);
            const compressedSize = (compressedFile.size / 1024 / 1024).toFixed(2);
            const reduction = ((1 - compressedFile.size / file.size) * 100).toFixed(0);
            
            statusElement.innerHTML = `<i class="ri-check-line text-success"></i> 압축 완료: ${originalSize}MB → ${compressedSize}MB (${reduction}% 감소)`;
            statusElement.className = 'text-success';
            
        } catch (error) {
            console.error('이미지 압축 실패:', error);
            statusElement.innerHTML = '<i class="ri-error-warning-line text-danger"></i> 압축 실패, 원본 사용';
            statusElement.className = 'text-danger';
        }
    }

    compressImage(file) {
        const { MAX_WIDTH, MAX_HEIGHT, QUALITY } = this.config.FILE.COMPRESS;
        return new Promise((resolve) => {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const img = new Image();
            
            img.onload = () => {
                let { width, height } = img;
                const ratio = Math.min(MAX_WIDTH / width, MAX_HEIGHT / height);
                
                if (ratio < 1) {
                    width = Math.round(width * ratio);
                    height = Math.round(height * ratio);
                }
                
                canvas.width = width;
                canvas.height = height;
                ctx.drawImage(img, 0, 0, width, height);
                
                canvas.toBlob((blob) => {
                    resolve(new File([blob], file.name, { 
                        type: 'image/jpeg', 
                        lastModified: Date.now() 
                    }));
                }, 'image/jpeg', QUALITY);
            };
            
            img.src = URL.createObjectURL(file);
        });
    }

    apiCall(action, data = {}, method = 'POST') {
        // FormData의 경우 BaseApp의 apiCall을 직접 사용하지 않고,
        // action을 명시적으로 전달하기 위해 ApiService.request를 직접 호출합니다.
        if (data instanceof FormData) {
            return ApiService.request(this.config.API_URL, { action, data, method });
        }
        // FormData가 아닌 경우, 부모 클래스(BaseApp)의 apiCall을 사용합니다.
        return super.apiCall(action, data, method);
    }

    setTodayDate(selector) {
        const today = new Date();
        const dateString = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
        document.querySelector(selector).value = dateString;
    }


    setButtonLoading(selector, text) {
        const btn = document.querySelector(selector);
        if (!btn) return;
        btn.dataset.originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<i class="spinner-border spinner-border-sm me-1"></i>${text}`;
    }

    resetButtonLoading(selector, originalText) {
        const btn = document.querySelector(selector);
        if (!btn) return;
        btn.disabled = false;
        btn.innerHTML = originalText || btn.dataset.originalText;
    }

    destroy() {
        super.destroy(); // 부모의 destroy를 호출하여 mapManager 등을 정리
        this.closeOverlay(); // 이 클래스 고유의 정리 작업
    }
}

// 전역 인스턴스 생성
window.wasteCollectionApp = new WasteCollectionApp();