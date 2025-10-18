/**
 * Application for the Littering Map page.
 * Handles registration and processing of littering reports.
 */
class LitteringMapPage extends BasePage {
    constructor() {
        const currentScript = document.currentScript;
        let scriptConfig = {};
        if (currentScript) {
            const options = currentScript.getAttribute('data-options');
            if (options) {
                try {
                    scriptConfig = JSON.parse(options);
                } catch (e) {
                    console.error('Failed to parse script options for LitteringMapPage:', e);
                }
            }
        }

        super({
            ...scriptConfig,
            API_URL: '/littering',
            WASTE_TYPES: ['생활폐기물', '음식물', '재활용', '대형', '소각'],
            FILE: { MAX_SIZE: 5 * 1024 * 1024, ALLOWED_TYPES: ['image/jpeg', 'image/png'], COMPRESS: { MAX_WIDTH: 1200, MAX_HEIGHT: 1200, QUALITY: 0.8 } }
        });
        this.state = { ...this.state, reportList: [], modals: {}, currentReportIndex: null, mapService: null };
    }

    /**
     * @override
     */
    initializeApp() {
        const mapOptions = {
            ...this.config, // Pass all page configs to the map
            enableTempMarker: true,
            markerTypes: this.generateMarkerTypes(),
            markerSize: { width: 34, height: 40 },
            longPressDelay: 800,
            duplicateThreshold: 10,
            onTempMarkerClick: (locationData) => this.openRegistrationModal(locationData),
            onAddressResolved: (addressData) => {
                document.getElementById('address').textContent = addressData.isValid ? addressData.address : '주소를 가져올 수 없습니다.';
            },
            onRegionValidation: (isValid, message) => {
                if (!isValid) {
                    Toast.error(message);
                }
            }
        };
        this.state.mapService = new MapService(mapOptions);

        this.setupModals();
        this.setupEventListeners();
        this.setupLightbox();
        this.loadInitialData();
    }

    /**
     * @override
     */
    async loadInitialData() {
        try {
            const response = await this.apiCall(`${this.config.API_URL}?status=active`);
            this.state.reportList = [];
            response.data.forEach(item => this.addReportMarkerToMap(item));
        } catch (error) {
            console.error('Failed to load active reports:', error);
            Toast.error(`데이터 로딩 실패: ${error.message}`);
        }
    }

    /**
     * @override
     */
    setupEventListeners() {
        document.getElementById('registerBtn').addEventListener('click', () => this.submitNewReport());
        document.getElementById('processBtn').addEventListener('click', () => this.submitReportProcessing());
        document.getElementById('mixed').addEventListener('change', () => this.toggleSubWasteType());
        document.getElementById('waste_type').addEventListener('change', () => {
            if (document.getElementById('mixed').checked) this.toggleSubWasteType();
        });
        document.querySelectorAll('input[name="corrected"]').forEach(el => el.addEventListener('change', () => this.updateProcessingPhotoRequirement()));
        document.getElementById('currentLocationBtn').addEventListener('click', () => this.state.mapService.mapManager.setUserLocation());
        document.getElementById('addCenterMarkerBtn').addEventListener('click', () => this.registerReportAtMapCenter());

        document.querySelectorAll('#regPhoto1, #regPhoto2, #procPhoto').forEach(input => {
            input.addEventListener('change', (e) => this.handlePhotoUpload(e));
        });
    }

    setupModals() {
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

    async submitNewReport() {
        try {
            const validation = this.validateRegistrationForm();
            if (!validation.isValid) {
                Toast.error(validation.message);
                return;
            }
            const formData = this.buildRegistrationFormData();
            this.setButtonLoading('#registerBtn', '등록 중...');

            const response = await this.apiCall(this.config.API_URL, { method: 'POST', body: formData });

            this.addReportMarkerToMap(response.data);
            this.state.modals.register.hide();
            this.state.mapService.mapManager.removeTempMarker();
            Toast.success(response.message);
        } catch (error) {
            Toast.error(`등록 실패: ${error.message}`);
        } finally {
            this.resetButtonLoading('#registerBtn', '등록');
        }
    }

    async submitReportProcessing() {
        try {
            const validation = this.validateProcessForm();
            if (!validation.isValid) {
                Toast.error(validation.message);
                return;
            }
            const reportId = this.state.reportList[this.state.currentReportIndex].data.id;
            const formData = this.buildProcessFormData();
            this.setButtonLoading('#processBtn', '처리 중...');

            await this.apiCall(`${this.config.API_URL}/${reportId}/process`, { method: 'POST', body: formData });

            this.removeProcessedMarkerFromMap();
            this.state.modals.process.hide();
            Toast.success('성공적으로 처리되었습니다.');
        } catch (error) {
            Toast.error(`처리 실패: ${error.message}`);
        } finally {
            this.resetButtonLoading('#processBtn', '처리 등록');
        }
    }

    addReportMarkerToMap(data) {
        const wasteType = data.waste_type || '생활폐기물';
        const status = data.status || 'pending';
        const markerTypeKey = `${wasteType}_${status}`;

        const markerInfo = this.state.mapService.mapManager.addMarker({
            position: { lat: data.latitude, lng: data.longitude },
            type: markerTypeKey,
            data: { ...data, id: data.id },
            onClick: (marker, markerData) => {
                const index = this.state.reportList.findIndex(item => item && item.data.id === markerData.id);
                if (index !== -1) {
                    this.openProcessingModal(index);
                }
            }
        });

        this.state.reportList.push({
            marker: markerInfo.marker,
            data: { ...data, id: data.id },
            mapManagerData: markerInfo
        });
    }

    removeProcessedMarkerFromMap() {
        const idx = this.state.currentReportIndex;
        if (this.state.reportList[idx]) {
            this.state.mapService.mapManager.removeMarker(this.state.reportList[idx].mapManagerData);
            this.state.reportList.splice(idx, 1);
        }
    }

    openRegistrationModal(locationData) {
        document.getElementById('lat').value = locationData.latitude;
        document.getElementById('lng').value = locationData.longitude;
        if (locationData.address) {
            document.getElementById('address').textContent = locationData.address;
        }
        this.setTodaysDate('#issueDate');
        this.state.modals.register.show();
    }

    openProcessingModal(index) {
        const reportData = this.state.reportList[index]?.data;
        if (!reportData) {
            Toast.error('해당 신고 정보를 찾을 수 없습니다.');
            return;
        }

        this.state.currentReportIndex = index;
        document.getElementById('procAddress').textContent = reportData.address || '-';
        document.getElementById('procWasteType').textContent = reportData.waste_type || '-';
        this.setTodaysDate('#collectDate');
        this.renderExistingPhotos(reportData);

        const formFields = document.getElementById('processFormFields');
        const statusMessage = document.getElementById('procStatusMessage');
        const processBtn = document.getElementById('processBtn');

        if (reportData.status === 'pending') {
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

    // --- Helper and Utility methods ---

    generateMarkerTypes() {
        const markerTypes = {};
        const statuses = ['pending', 'confirmed', 'active'];
        const wasteTypeColors = {
            '생활폐기물': '#666666', '음식물': '#FF9800', '재활용': '#00A6FB',
            '대형': '#DC2626', '소각': '#FF5722'
        };

        this.config.WASTE_TYPES.forEach(type => {
            statuses.forEach(status => {
                const key = `${type}_${status}`;
                markerTypes[key] = MarkerFactory.createSVGIcon({
                    type: 'default',
                    color: wasteTypeColors[type] || '#666666',
                    text: type[0],
                    status: status
                });
            });
        });

        markerTypes.TEMP = MarkerFactory.createSVGIcon({ type: 'default', color: '#2563EB', text: '+' });
        return markerTypes;
    }

    buildRegistrationFormData() {
        const form = document.querySelector('#registerModal form');
        const formData = new FormData(form);
        formData.append('lat', document.getElementById('lat').value);
        formData.append('lng', document.getElementById('lng').value);
        formData.append('address', document.getElementById('address').textContent);
        formData.append('waste_type', document.getElementById('waste_type').value);
        if (document.getElementById('mixed').checked) {
            formData.append('waste_type2', document.getElementById('waste_type2').value);
        }


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

    validateRegistrationForm() {
        if (!document.getElementById('waste_type').value) {
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

        if (document.getElementById('mixed').checked && !document.getElementById('waste_type2').value) {
            return { isValid: false, message: '혼합 배출 시 부성상을 선택해주세요.' };
        }

        const lat = parseFloat(document.getElementById('lat').value);
        const lng = parseFloat(document.getElementById('lng').value);
        if (this.state.mapService.mapManager.checkDuplicateLocation({ lat, lng }, 1)) {
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

        if (this.state.currentReportIndex === null || !this.state.reportList[this.state.currentReportIndex]) {
            return { isValid: false, message: '해당 신고 정보를 찾을 수 없습니다.' };
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

    async handlePhotoUpload(e) {
        const file = e.target.files[0];
        const statusEl = document.getElementById(`${e.target.id}Status`);

        if (file && file.type.startsWith('image/')) {
            statusEl.innerHTML = '<i class="ri-loader-4-line"></i> 이미지 압축 중...';
            statusEl.classList.add('text-info');
            try {
                const compressedFile = await this.compressImageFile(file);
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
                console.error('Image compression failed:', error);
                statusEl.innerHTML = '<i class="ri-error-warning-line text-danger"></i> 압축 실패, 원본 사용';
                statusEl.classList.remove('text-info');
                statusEl.classList.add('text-danger');
            }
        }
    }

    compressImageFile(file) {
        const { MAX_WIDTH, MAX_HEIGHT, QUALITY } = this.config.FILE.COMPRESS;
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => {
                let { width, height } = img;
                const ratio = Math.min(MAX_WIDTH / width, MAX_HEIGHT / height, 1);
                width = Math.round(width * ratio);
                height = Math.round(height * ratio);

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                canvas.toBlob((blob) => {
                    if (blob) {
                        resolve(new File([blob], file.name, { type: 'image/jpeg', lastModified: Date.now() }));
                    } else {
                        reject(new Error('Canvas to Blob conversion failed'));
                    }
                }, 'image/jpeg', QUALITY);
            };
            img.onerror = () => reject(new Error('Image loading failed'));
            img.src = URL.createObjectURL(file);
        });
    }

    setTodaysDate(selector) {
        const today = new Date();
        const dateString = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
        document.querySelector(selector).value = dateString;
    }

    toggleSubWasteType() {
        const isChecked = document.getElementById('mixed').checked;
        const subTypeContainer = document.getElementById('waste_type2Container');
        const subTypeEl = document.getElementById('waste_type2');

        subTypeContainer.style.display = isChecked ? '' : 'none';
        subTypeEl.disabled = !isChecked;

        if (isChecked) {
            const mainType = document.getElementById('waste_type').value;
            const availableTypes = this.config.WASTE_TYPES.filter(type => type !== mainType);
            const options = availableTypes.map(type => `<option value="${type}">${type}</option>`).join('');
            subTypeEl.innerHTML = '<option value="">선택하세요</option>' + options;
        } else {
            subTypeEl.value = '';
        }
    }

    updateProcessingPhotoRequirement() {
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

    registerReportAtMapCenter() {
        const center = this.state.mapService.mapManager.getMap().getCenter();
        this.state.mapService.mapManager.handleLocationSelect(center);
    }

    setupLightbox() {
        this.lightbox = GLightbox({
            selector: '.gallery-lightbox'
        });
    }

    renderExistingPhotos(reportData) {
        const container = document.getElementById('photo-container');
        container.innerHTML = '';
        const photos = [];

        if (reportData.reg_photo_path) photos.push({ src: reportData.reg_photo_path, title: '작업전' });
        if (reportData.reg_photo_path2) photos.push({ src: reportData.reg_photo_path2, title: '작업후' });
        if (reportData.proc_photo_path) photos.push({ src: reportData.proc_photo_path, title: '처리 사진' });

        if (photos.length > 0) {
            const imageWidthClass = photos.length > 1 ? 'w-50' : 'w-100';
            photos.forEach(photo => {
                const imgHTML = `
                    <div class="${imageWidthClass}">
                        <a href="${photo.src}" class="gallery-lightbox" data-gallery="littering-map" title="${photo.title}" style="cursor: pointer;">
                            <div class="image-container-16-9">
                                <img src="${photo.src}" alt="${photo.title}">
                            </div>
                        </a>
                    </div>
                `;
                const imgNode = document.createRange().createContextualFragment(imgHTML).firstElementChild;
                container.appendChild(imgNode);
            });
        } else {
            container.innerHTML = '<div class="text-center p-5 text-muted">등록된 사진이 없습니다.</div>';
        }

        if (this.lightbox) {
            this.lightbox.reload();
        }
    }

    /**
     * @override
     */
    cleanup() {
        super.cleanup(); // Call parent cleanup
        if (this.state.mapService) {
            this.state.mapService.destroy();
        }
    }
}

new LitteringMapPage();