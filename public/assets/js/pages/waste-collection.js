/**
 * Application for the Waste Collection page.
 * Handles registration of large waste collections and displaying them on the map.
 */
class WasteCollectionPage extends BasePage {
    constructor() {
        super({
            API_URL: '/waste-collections',
            ITEMS: ['매트리스', '침대틀', '장롱', '쇼파', '의자', '책상', '기타(가구)', '건폐', '소각', '변기', '캐리어', '기타'],
            FILE: { MAX_SIZE: 5 * 1024 * 1024, ALLOWED_TYPES: ['image/jpeg', 'image/png'], COMPRESS: { MAX_WIDTH: 1200, MAX_HEIGHT: 1200, QUALITY: 0.8 } }
        });

        this.state = {
            ...this.state,
            collectionList: [],
            modals: {},
            currentOverlay: null,
            mapService: null
        };

        // This is needed for the closeOverlay button in the custom overlay HTML.
        // A better approach would be to add this event listener programmatically.
        window.wasteCollectionApp = this;
    }

    /**
     * @override
     */
    async initializeApp() {
        const mapOptions = {
            enableTempMarker: true,
            markerTypes: this.generateMarkerTypes(),
            onTempMarkerClick: (locationData) => this.openRegistrationModal(locationData),
            onAddressResolved: (locData) => document.getElementById('address').textContent = locData.isValid ? locData.address : '주소 확인 불가',
            onRegionValidation: (isValid, message) => !isValid && Toast.error(message)
        };

        this.state.mapService = new MapService(mapOptions);
        this.setupModals();
        this.populateSelectableItems();
        this.setupEventListeners();

        await this.waitForMapReady();
        this.loadInitialData();
    }

    /**
     * @override
     */
    async loadInitialData() {
        try {
            const response = await this.apiCall(this.config.API_URL);
            this.state.collectionList = [];
            this.groupAndDisplayCollections(response.data || []);
        } catch (error) {
            console.error('Failed to load collection list:', error);
            Toast.error(`데이터 로딩 실패: ${error.message}`);
        }
    }

    /**
     * @override
     */
    setupEventListeners() {
        document.getElementById('registerBtn').addEventListener('click', () => this.submitNewCollection());
        document.getElementById('currentLocationBtn').addEventListener('click', () => this.state.mapService.mapManager.setUserLocation());
        document.getElementById('addCenterMarkerBtn')?.addEventListener('click', () => {
            const center = this.state.mapService.mapManager.getMap().getCenter();
            this.state.mapService.mapManager.handleLocationSelect(center);
        });
        document.getElementById('photo').addEventListener('change', (e) => this.handlePhotoUpload(e));
    }

    setupModals() {
        this.state.modals.register = new bootstrap.Offcanvas(document.getElementById('registerCollectionModal'));
        document.getElementById('registerCollectionModal').addEventListener('hidden.bs.offcanvas', () => {
            document.getElementById('wasteCollectionForm').reset();
            document.getElementById('photoStatus').innerHTML = '사진을 촬영하거나 갤러리에서 선택하세요';
            document.querySelectorAll('.item-quantity-input').forEach(input => input.value = 0);
        });
    }

    async submitNewCollection() {
        if (!this.validateRegistrationForm()) return;

        const formData = this.buildRegistrationFormData();
        this.setButtonLoading('#registerBtn', '등록 중...');
        try {
            const response = await this.apiCall(this.config.API_URL, { method: 'POST', body: formData });
            this.addCollectionToMap(response.data);
            this.state.modals.register.hide();
            this.state.mapService.mapManager.removeTempMarker();
            Toast.success('폐기물 수거 정보가 등록되었습니다.');
        } catch (error) {
            Toast.error(`등록 실패: ${error.message}`);
        } finally {
            this.resetButtonLoading('#registerBtn', '<i class="ri-save-line me-1"></i>등록');
        }
    }

    // --- UI and Data Handling Methods ---

    populateSelectableItems() {
        const itemList = document.getElementById('item-list');
        if (!itemList) return;

        this.config.ITEMS.forEach(item => {
            itemList.insertAdjacentHTML('beforeend', `
                <div class="col-6 mb-2">
                    <div class="d-flex justify-content-between align-items-center border rounded p-2">
                        <span class="item-name">${item}</span>
                        <div class="input-group input-group-sm" style="width: 120px;">
                            <button class="btn btn-outline-secondary btn-minus" type="button">-</button>
                            <input type="text" class="form-control text-center item-quantity-input" value="0" readonly data-item-name="${item}">
                            <button class="btn btn-outline-secondary btn-plus" type="button">+</button>
                        </div>
                    </div>
                </div>`);
        });

        itemList.addEventListener('click', (e) => {
            const btn = e.target;
            const input = btn.classList.contains('btn-plus') ? btn.previousElementSibling : (btn.classList.contains('btn-minus') ? btn.nextElementSibling : null);
            if (!input) return;
            let val = parseInt(input.value);
            if (btn.classList.contains('btn-plus')) val++;
            else if (val > 0) val--;
            input.value = val;
        });
    }

    groupAndDisplayCollections(data) {
        const onlineSubmissions = data.filter(item => item.type === 'online');
        const fieldSubmissions = data.filter(item => item.type === 'field');

        fieldSubmissions.forEach(item => this.addCollectionToMap(item));

        const groupedOnline = onlineSubmissions.reduce((acc, c) => {
            (acc[c.address.trim()] = acc[c.address.trim()] || []).push(c);
            return acc;
        }, {});

        Object.values(groupedOnline).forEach(group => {
            if (group.length > 1) this.addClusterMarker(group);
            else if (group.length === 1) this.addCollectionToMap(group[0]);
        });
    }

    addClusterMarker(group) {
        const firstItem = group[0];
        const marker = this.state.mapService.mapManager.addMarker({
            position: { lat: firstItem.latitude, lng: firstItem.longitude },
            type: 'online', // Cluster is always for online submissions
            data: { isCluster: true, collections: group },
            onClick: (m, markerData) => this.openCollectionOverlay(markerData)
        });
        this.state.collectionList.push({ isCluster: true, marker: marker.marker, data: { collections: group } });
    }

    addCollectionToMap(data) {
        if (data.status === 'processed') return;
        const collectionInfo = this.state.mapService.mapManager.addMarker({
            position: { lat: data.latitude, lng: data.longitude },
            type: data.type,
            data: { isCluster: false, collections: [data], id: data.id },
            onClick: (m, markerData) => this.openCollectionOverlay(markerData)
        });
        this.state.collectionList.push({ marker: collectionInfo.marker, data: { ...data, id: data.id } });
    }

    openCollectionOverlay(markerData) {
        if (this.state.currentOverlay) this.state.currentOverlay.setMap(null);

        const { collections, isCluster } = markerData;
        const first = collections[0];
        const position = new kakao.maps.LatLng(first.latitude, first.longitude);
        
        const collectionsContent = collections.map(c => {
            const items = (typeof c.items === 'string' ? JSON.parse(c.items) : c.items) || [];
            const itemsContent = items.map(item => `<li class="list-group-item d-flex justify-content-between align-items-center p-1">${item.name}<span class="badge bg-secondary rounded-pill">${item.quantity}</span></li>`).join('');
            const typeBadge = c.type === 'field' ? `<span class="badge bg-info">현장등록</span>` : `<span class="badge bg-success">인터넷배출</span>`;
            const feeBadge = (c.type === 'online' && c.fee > 0) ? `<span class="badge bg-dark ms-1">${c.fee.toLocaleString()}원</span>` : '';
            return `<div class="card mb-2"><div class="card-body p-2"><div class="d-flex justify-content-between"><div>${typeBadge}${feeBadge}</div><small>${new Date(c.issue_date).toLocaleDateString()}</small></div><ul class="list-group list-group-flush">${itemsContent || '<li>품목 없음</li>'}</ul></div></div>`;
        }).join('');

        const headerTitle = isCluster ? `${first.address} (${collections.length}건)` : first.address;
        const content = `<div class="card shadow-sm" style="min-width:300px;"><div class="card-header bg-primary text-white p-2 d-flex justify-content-between"><h6>${headerTitle}</h6><button type="button" class="btn-close btn-close-white" onclick="window.wasteCollectionApp.closeOverlay()"></button></div><div class="card-body p-2" style="max-height:350px; overflow-y:auto;">${collectionsContent}</div></div>`;
        
        this.state.currentOverlay = new kakao.maps.CustomOverlay({ content, map: this.state.mapService.mapManager.getMap(), position, yAnchor: 1.1 });
    }

    closeOverlay() {
        if (this.state.currentOverlay) {
            this.state.currentOverlay.setMap(null);
            this.state.currentOverlay = null;
        }
    }

    openRegistrationModal(locationData) {
        document.getElementById('lat').value = locationData.latitude;
        document.getElementById('lng').value = locationData.longitude;
        if (locationData.address) document.getElementById('address').textContent = locationData.address;
        this.setTodaysDate('#issue_date');
        this.state.modals.register.show();
    }

    // --- Helper and Utility Methods ---

    generateMarkerTypes() {
        return {
            field: MarkerFactory.createSVGIcon({ type: 'waste', color: '#28A745', text: '현장' }),
            online: MarkerFactory.createSVGIcon({ type: 'waste', color: '#DC2626', text: '시청' })
        };
    }

    async waitForMapReady() {
        return new Promise(resolve => {
            const check = () => (this.state.mapService.mapManager?.getMap()) ? resolve() : setTimeout(check, 100);
            check();
        });
    }

    buildRegistrationFormData() {
        const items = Array.from(document.querySelectorAll('.item-quantity-input')).filter(i => parseInt(i.value) > 0).map(i => ({ name: i.dataset.itemName, quantity: parseInt(i.value) }));
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

    validateRegistrationForm() {
        const items = Array.from(document.querySelectorAll('.item-quantity-input')).filter(i => parseInt(i.value) > 0);
        if (items.length === 0) {
            Toast.error('수량이 1 이상인 품목을 하나 이상 추가해주세요.');
            return false;
        }
        const lat = parseFloat(document.getElementById('lat').value);
        const lng = parseFloat(document.getElementById('lng').value);
        if (this.state.mapService.mapManager.checkDuplicateLocation({ lat, lng }, 5)) {
            Toast.error('같은 위치에 이미 수거 정보가 등록되어 있습니다.');
            return false;
        }
        const photoFile = document.getElementById('photo').files[0];
        if (photoFile && !this.validateFile(photoFile)) {
            return false;
        }
        return true;
    }

    validateFile(file) {
        if (!this.config.FILE.ALLOWED_TYPES.includes(file.type)) {
            Toast.error('이미지 파일만 업로드 가능합니다.');
            return false;
        }
        if (file.size > this.config.FILE.MAX_SIZE) {
            Toast.error('파일 크기는 5MB 이하여야 합니다.');
            return false;
        }
        return true;
    }

    async handlePhotoUpload(e) {
        const file = e.target.files[0];
        if (!file) return;
        const statusEl = document.getElementById('photoStatus');
        if (!this.validateFile(file)) { e.target.value = ''; return; }
        statusEl.innerHTML = '<i class="ri-loader-4-line"></i> 압축 중...';
        try {
            const compressedFile = await this.compressImageFile(file);
            const dt = new DataTransfer();
            dt.items.add(compressedFile);
            e.target.files = dt.files;
            statusEl.innerHTML = `<i class="ri-check-line text-success"></i> 압축 완료: ${(file.size/1024/1024).toFixed(2)}MB → ${(compressedFile.size/1024/1024).toFixed(2)}MB`;
        } catch (error) {
            statusEl.innerHTML = '<i class="ri-error-warning-line text-danger"></i> 압축 실패, 원본 사용';
        }
    }

    compressImageFile(file) {
        const { MAX_WIDTH, MAX_HEIGHT, QUALITY } = this.config.FILE.COMPRESS;
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => {
                const ratio = Math.min(MAX_WIDTH / img.width, MAX_HEIGHT / img.height, 1);
                const canvas = document.createElement('canvas');
                canvas.width = img.width * ratio;
                canvas.height = img.height * ratio;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                ctx.canvas.toBlob(blob => {
                    if (blob) {
                        resolve(new File([blob], file.name, { type: 'image/jpeg' }))
                    } else {
                        reject(new Error('Canvas to Blob conversion failed'));
                    }
                }, 'image/jpeg', QUALITY);
            };
            img.onerror = () => reject(new Error('Image could not be loaded.'));
            img.src = URL.createObjectURL(file);
        });
    }

    setTodaysDate(selector) {
        const today = new Date();
        document.querySelector(selector).value = `${today.getFullYear()}-${String(today.getMonth()+1).padStart(2,'0')}-${String(today.getDate()).padStart(2,'0')}`;
    }

    /**
     * @override
     */
    cleanup() {
        super.cleanup();
        if (this.state.mapService) {
            this.state.mapService.destroy();
        }
    }
}

new WasteCollectionPage();