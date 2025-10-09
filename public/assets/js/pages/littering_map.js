class MarkerFactory {
    static createSVGIcon(options = {}) {
        // ... (This class remains unchanged, so it's omitted for brevity)
    }
}

class WasteManagementApp extends BaseApp {
    constructor() {
        super({
            API_URL: '/api/littering', // Updated API Base URL
            WASTE_TYPES: ['생활폐기물', '음식물', '재활용', '대형', '소각'],
            FILE: { /* ... */ },
            ALLOWED_REGIONS: ['정왕1동']
        });
        this.state = { ...this.state, markerList: [], modals: {}, currentProcessIndex: null };
    }

    init() {
        // ... (init method logic remains the same)
        const mapOptions = {
            enableTempMarker: true,
            markerTypes: this.generateMarkerTypes(),
            markerSize: { width: 34, height: 40 },
            longPressDelay: 800,
            duplicateThreshold: 10,
            onTempMarkerClick: (locationData) => this.showRegisterModal(locationData),
            onAddressResolved: (addressData) => {
                document.getElementById('address').textContent = addressData.isValid ? addressData.address : '주소를 가져올 수 없습니다.';
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
        this.bindEvents();
        this.initPhotoSwiper();
        this.initGlightbox();
        this.loadData();
    }

    async loadData() {
        try {
            const response = await this.apiCall(`${this.config.API_URL}?status=active`, { method: 'GET' });
            this.state.markerList = [];
            response.data.forEach(item => this.addMarkerToMap(item));
        } catch (error) {
            console.error('기존 마커 로드 실패:', error);
            Toast.error(`데이터 로딩 실패: ${error.message}`);
        }
    }

    async registerMarker() {
        try {
            const validation = this.validateRegistrationForm();
            if (!validation.isValid) {
                Toast.error(validation.message);
                return;
            }
            const formData = this.buildRegistrationFormData();
            this.setButtonLoading('#registerBtn', '등록 중...');

            const response = await this.apiCall(this.config.API_URL, {
                method: 'POST',
                body: formData
            });

            this.addMarkerToMap(response.data);
            this.state.modals.register.hide();
            this.state.mapManager.removeTempMarker();
            Toast.success(response.message);
        } catch (error) {
            Toast.error(`등록 실패: ${error.message}`);
        } finally {
            this.resetButtonLoading('#registerBtn', '등록');
        }
    }

    async submitProcess() {
        try {
            const validation = this.validateProcessForm();
            if (!validation.isValid) {
                Toast.error(validation.message);
                return;
            }
            const markerId = this.state.markerList[this.state.currentProcessIndex].data.id;
            const formData = this.buildProcessFormData();
            this.setButtonLoading('#processBtn', '처리 중...');

            await this.apiCall(`${this.config.API_URL}/${markerId}/process`, {
                method: 'POST',
                body: formData
            });

            this.updateMarkerAfterProcess();
            this.state.modals.process.hide();
            Toast.success('성공적으로 처리되었습니다.');
        } catch (error) {
            Toast.error(`처리 실패: ${error.message}`);
        } finally {
            this.resetButtonLoading('#processBtn', '처리 등록');
        }
    }

    buildRegistrationFormData() {
        const form = document.querySelector('#registerModal form');
        const formData = new FormData(form);
        formData.append('lat', document.getElementById('lat').value);
        formData.append('lng', document.getElementById('lng').value);
        formData.append('address', document.getElementById('address').textContent);

        // Use compressed files if available
        const photo1Input = document.getElementById('regPhoto1');
        if (photo1Input.files.length > 0) formData.set('photo1', photo1Input.files[0]);

        const photo2Input = document.getElementById('regPhoto2');
        if (photo2Input.files.length > 0) formData.set('photo2', photo2Input.files[0]);

        return formData;
    }

    buildProcessFormData() {
        const form = document.querySelector('#processModal form');
        const formData = new FormData(form);
        const correctedEl = document.querySelector('input[name="corrected"]:checked');
        formData.set('corrected', correctedEl ? correctedEl.value : '');

        const procPhotoInput = document.getElementById('procPhoto');
        if (procPhotoInput.files.length > 0) formData.set('procPhoto', procPhotoInput.files[0]);

        return formData;
    }

    // All other methods (initModals, bindEvents, UI helpers, etc.) remain largely the same.
    // Omitted for brevity. The key changes are in the API-calling methods above.

    // Paste the rest of the original class methods here, without the old apiCall override.
    // ... (generateMarkerTypes, initModals, bindEvents, etc. ... )
    generateMarkerTypes() {
        const markerTypes = {};
        const statuses = ['pending', 'confirmed'];
        const wasteTypeColors = {
            '생활폐기물': '#666666', 
            '음식물': '#FF9800', 
            '재활용': '#00A6FB',
            '대형': '#DC2626', 
            '소각': '#FF5722'
        };

        this.config.WASTE_TYPES.forEach(type => {
            statuses.forEach(status => {
                const key = `${type}_${status}`;
                markerTypes[key] = MarkerFactory.createSVGIcon({
                    color: wasteTypeColors[type] || '#666666',
                    text: type[0],
                    status: status
                });
            });
        });

        markerTypes.TEMP = MarkerFactory.createSVGIcon({ color: '#2563EB', text: '+' });
        return markerTypes;
    }
    
    initModals() {
        this.state.modals = {
            register: new bootstrap.Offcanvas(document.getElementById('registerModal')),
            process: new bootstrap.Offcanvas(document.getElementById('processModal')),
            result: new bootstrap.Modal(document.getElementById('resultModal'))
        };

        document.getElementById('registerModal').addEventListener('hidden.bs.offcanvas', () => {
            document.querySelector('#registerModal form').reset();
            document.querySelectorAll('#regPhoto1Status, #regPhoto2Status').forEach(el => {
                el.innerHTML = '사진을 촬영하거나 갤러리에서 선택하세요';
                el.classList.remove('text-info', 'text-success', 'text-warning', 'text-danger');
            });
        });
        document.getElementById('processModal').addEventListener('hidden.bs.offcanvas', () => {
            document.querySelector('#processModal form').reset();
            const procPhotoStatus = document.getElementById('procPhotoStatus');
            procPhotoStatus.innerHTML = '처리 완료 사진을 촬영해주세요';
            procPhotoStatus.classList.remove('text-info', 'text-success', 'text-warning', 'text-danger');
        });
    }

    bindEvents() {
        document.getElementById('registerBtn').addEventListener('click', () => this.registerMarker());
        document.getElementById('processBtn').addEventListener('click', () => this.submitProcess());
        document.getElementById('mixed').addEventListener('change', () => this.toggleSubType());
        document.getElementById('mainType').addEventListener('change', () => {
            if (document.getElementById('mixed').checked) this.toggleSubType();
        });
        document.querySelectorAll('input[name="corrected"]').forEach(el => el.addEventListener('change', () => this.updatePhotoRequirement()));
        document.getElementById('currentLocationBtn').addEventListener('click', () => this.state.mapManager.setUserLocation());
        document.getElementById('addCenterMarkerBtn').addEventListener('click', () => this.registerAtCenter());

        document.querySelectorAll('#regPhoto1, #regPhoto2, #procPhoto').forEach(input => {
            input.addEventListener('change', async (e) => {
                const file = e.target.files[0];
                const statusEl = document.getElementById(`${e.target.id}Status`);

                if (file && file.type.startsWith('image/')) {
                    statusEl.innerHTML = '<i class="ri-loader-4-line"></i> 이미지 압축 중...';
                    statusEl.classList.add('text-info');
                    try {
                        const compressedFile = await this.compressImage(file);
                        const dt = new DataTransfer();
                        dt.items.add(compressedFile);
                        e.target.files = dt.files;
                        const oSize = (file.size / 1024 / 1024).toFixed(2);
                        const cSize = (compressedFile.size / 1024 / 1024).toFixed(2);
                        const reduction = ((1 - compressedFile.size / file.size) * 100).toFixed(0);
                        statusEl.innerHTML = `<i class="ri-check-line text-success"></i> 압축 완료: ${oSize}MB → ${cSize}MB (${reduction}% 감소)`;
                        statusEl.classList.remove('text-info');
                        statusEl.classList.add('text-success');
                    } catch (error) {
                        console.error('이미지 압축 실패:', error);
                        statusEl.innerHTML = '<i class="ri-error-warning-line text-danger"></i> 압축 실패, 원본 사용';
                        statusEl.classList.remove('text-info');
                        statusEl.classList.add('text-danger');
                    }
                }
            });
        });
    }

    registerAtCenter() {
        const center = this.state.mapManager.getMap().getCenter();
        this.state.mapManager.handleLocationSelect(center);
    }

    showRegisterModal(locationData) {
        document.getElementById('lat').value = locationData.latitude;
        document.getElementById('lng').value = locationData.longitude;
        if (locationData.address) {
            document.getElementById('address').textContent = locationData.address;
        }
        this.setTodayDate('#issueDate');
        this.state.modals.register.show();
    }

    showProcessModal(index) {
        const markerData = this.state.markerList[index]?.data;
        if (!markerData) {
            Toast.error('해당 마커 정보를 찾을 수 없습니다.');
            return;
        }

        this.state.currentProcessIndex = index;
        document.getElementById('procAddress').textContent = markerData.address || '-';
        document.getElementById('procWasteType').textContent = markerData.waste_type || '-';
        this.setTodayDate('#collectDate');
        this.displayExistingPhoto(markerData);

        const formFields = document.getElementById('processFormFields');
        const statusMessage = document.getElementById('procStatusMessage');
        const processBtn = document.getElementById('processBtn');

        if (markerData.status === 'pending') {
            statusMessage.innerHTML = '<i class="ri-error-warning-line me-1"></i>관리자 확인 대기 중인 민원입니다.';
            statusMessage.style.display = 'block';
            formFields.style.display = 'block';
            formFields.querySelectorAll('input, textarea, select').forEach(el => el.disabled = true);
            processBtn.style.display = 'block';
            processBtn.disabled = true;
        } else { // 'confirmed'
            statusMessage.style.display = 'none';
            formFields.style.display = 'block';
            formFields.querySelectorAll('input, textarea, select').forEach(el => el.disabled = false);
            processBtn.style.display = 'block';
            processBtn.disabled = false;
        }
        
        this.state.modals.process.show();
    }

    addMarkerToMap(data) {
        const wasteType = data.waste_type || '생활폐기물';
        const status = data.status || 'pending';
        const markerTypeKey = `${wasteType}_${status}`;

        const markerInfo = this.state.mapManager.addMarker({
            position: { lat: data.latitude, lng: data.longitude },
            type: markerTypeKey,
            data: { ...data, id: data.id },
            onClick: (marker, markerData) => {
                const index = this.state.markerList.findIndex(item => item && item.data.id === markerData.id);
                if (index !== -1) {
                    this.showProcessModal(index);
                }
            }
        });

        this.state.markerList.push({
            marker: markerInfo.marker,
            data: { ...data, id: data.id },
            mapManagerData: markerInfo
        });
    }

    validateRegistrationForm() {
        if (!document.getElementById('mainType').value) {
            return { isValid: false, message: '주성상을 선택하세요.' };
        }
        const photoFile2 = document.getElementById('regPhoto2').files[0];
        if (!photoFile2) {
            return { isValid: false, message: '작업후 사진은 필수 항목입니다.' };
        }
        const fileValidation2 = this.validateFile(photoFile2);
        if (!fileValidation2.isValid) {
            return { isValid: false, message: '작업후 사진: ' + fileValidation2.message };
        }

        const photoFile1 = document.getElementById('regPhoto1').files[0];
        if (photoFile1) {
            const fileValidation1 = this.validateFile(photoFile1);
            if (!fileValidation1.isValid) {
                return { isValid: false, message: '작업전 사진: ' + fileValidation1.message };
            }
        }

        if (document.getElementById('mixed').checked && !document.getElementById('subType').value) {
            return { isValid: false, message: '혼합 배출 시 부성상을 선택해주세요.' };
        }

        const lat = parseFloat(document.getElementById('lat').value);
        const lng = parseFloat(document.getElementById('lng').value);
        if (this.state.mapManager.checkDuplicateLocation({ lat, lng }, 1)) {
            return { isValid: false, message: '같은 위치에 이미 신고가 등록되어 있습니다.' };
        }
        return { isValid: true };
    }

    validateProcessForm() {
        const correctedEl = document.querySelector('input[name="corrected"]:checked');
        const corrected = correctedEl ? correctedEl.value : null;
        if (!corrected) {
            return { isValid: false, message: '개선 여부를 선택해주세요.' };
        }

        const photoFile = document.getElementById('procPhoto').files[0];
        if ((corrected === 'o' || corrected === 'x') && !photoFile) {
            return { isValid: false, message: '개선/미개선의 경우 처리 사진은 필수입니다.' };
        }

        if (photoFile) {
            const fileValidation = this.validateFile(photoFile);
            if (!fileValidation.isValid) {
                return { isValid: false, message: '처리 사진: ' + fileValidation.message };
            }
        }

        if (!document.getElementById('collectDate').value.trim()) {
            return { isValid: false, message: '수거일자를 입력해주세요.' };
        }

        if (this.state.currentProcessIndex === null || !this.state.markerList[this.state.currentProcessIndex]) {
            return { isValid: false, message: '해당 마커 정보를 찾을 수 없습니다.' };
        }
        return { isValid: true };
    }

    validateFile(file) {
        if (!this.config.FILE.ALLOWED_TYPES.includes(file.type)) {
            return { isValid: false, message: '이미지 파일만 업로드 가능합니다.' };
        }
        if (file.size > this.config.FILE.MAX_SIZE) {
            return { isValid: false, message: '파일 크기는 5MB 이하여야 합니다.' };
        }
        return { isValid: true };
    }

    updateMarkerAfterProcess() {
        const idx = this.state.currentProcessIndex;
        if (this.state.markerList[idx]) {
            this.state.mapManager.removeMarker(this.state.markerList[idx].mapManagerData);
            this.state.markerList.splice(idx, 1);
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
                    resolve(new File([blob], file.name, { type: 'image/jpeg', lastModified: Date.now() }));
                }, 'image/jpeg', QUALITY);
            };
            img.src = URL.createObjectURL(file);
        });
    }

    setTodayDate(selector) {
        const today = new Date();
        const dateString = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
        document.querySelector(selector).value = dateString;
    }

    toggleSubType() {
        const isChecked = document.getElementById('mixed').checked;
        const subTypeContainer = document.getElementById('subTypeContainer');
        const subTypeEl = document.getElementById('subType');

        subTypeContainer.style.display = isChecked ? '' : 'none';
        subTypeEl.disabled = !isChecked;

        if (isChecked) {
            const mainType = document.getElementById('mainType').value;
            const availableTypes = this.config.WASTE_TYPES.filter(type => type !== mainType);
            const options = availableTypes.map(type => `<option value="${type}">${type}</option>`).join('');
            subTypeEl.innerHTML = '<option value="">선택하세요</option>' + options;
        } else {
            subTypeEl.value = '';
        }
    }

    updatePhotoRequirement() {
        const correctedEl = document.querySelector('input[name="corrected"]:checked');
        const corrected = correctedEl ? correctedEl.value : null;
        const procPhotoRequired = document.getElementById('procPhotoRequired');
        const procPhoto = document.getElementById('procPhoto');
        const procPhotoStatus = document.getElementById('procPhotoStatus');
        
        if (corrected === 'o' || corrected === 'x') {
            procPhotoRequired.style.display = '';
            procPhoto.required = true;
            procPhotoStatus.innerHTML = '처리 완료 사진을 촬영해주세요 (필수)';
        } else {
            procPhotoRequired.style.display = 'none';
            procPhoto.required = false;
            procPhotoStatus.innerHTML = corrected === '=' ? '처리 완료 사진을 촬영해주세요 (선택)' : '처리 완료 사진을 촬영해주세요';
        }
    }


    initPhotoSwiper() {
        this.photoSwiper = new Swiper('#photoSwiper', {
            slidesPerView: 1,
            spaceBetween: 10,
            pagination: { el: '.swiper-pagination', clickable: true },
            navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
        });
    }

    initGlightbox() {
        this.lightbox = GLightbox({
            selector: '.gallery-lightbox'
        });
    }

    displayExistingPhoto(markerData) {
        const wrapper = document.getElementById('photoSwiperWrapper');
        wrapper.innerHTML = '';
        const basePath = '/storage/';
        const photos = [];

        if (markerData.reg_photo_path) photos.push({ src: basePath + markerData.reg_photo_path, title: '작업전' });
        if (markerData.reg_photo_path2) photos.push({ src: basePath + markerData.reg_photo_path2, title: '작업후' });
        if (markerData.proc_photo_path) photos.push({ src: basePath + markerData.proc_photo_path, title: '처리 사진' });

        if (photos.length > 0) {
            photos.forEach(photo => {
                const slide = document.createElement('div');
                slide.className = 'swiper-slide';
                slide.innerHTML = `
                    <a href="${photo.src}" class="gallery-lightbox" data-gallery="littering-map" title="${photo.title}">
                        <img src="${photo.src}" alt="${photo.title}" class="img-fluid">
                        <div class="photo-label">${photo.title}</div>
                    </a>`;
                wrapper.appendChild(slide);
            });
        } else {
            const noPhotoSlide = document.createElement('div');
            noPhotoSlide.className = 'swiper-slide no-photo-slide';
            noPhotoSlide.textContent = '등록된 사진이 없습니다.';
            wrapper.appendChild(noPhotoSlide);
        }
        this.photoSwiper.update();
        this.photoSwiper.slideTo(0, 0);

        if (this.lightbox) {
            this.lightbox.reload();
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new WasteManagementApp().init();
});