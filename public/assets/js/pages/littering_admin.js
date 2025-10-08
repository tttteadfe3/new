class LitteringAdminApp extends BaseApp {
    constructor() {
        super({
            API_URL: '../api/littering_admin.php',
            ALLOWED_REGIONS: ['정왕1동']
        });

        this.state = {
            ...this.state,
            pendingList: [],
            selectedCase: null,
            currentMarker: null
        };
    }

    init() {
        const mapOptions = {
            enableTempMarker: false,
            onAddressResolved: (locationData) => {
                document.getElementById('address').value = locationData.address;
            }
        };
        this.initMapManager(mapOptions);
        this.bindEvents();
        this.loadData();
    }

    bindEvents() {
        super.bindEvents();
        document.getElementById('confirm-btn').addEventListener('click', () => this.confirmReport());
        document.getElementById('delete-btn').addEventListener('click', () => this.deleteReport());
    }

    async loadData() {
        try {
            const response = await this.apiCall('get_pending_littering', {}, 'GET');
            if (response.success && Array.isArray(response.data)) {
                this.state.pendingList = response.data;
                this.renderPendingList();
            } else {
                this.renderPendingList();
            }
        } catch (error) {
            console.error('대기 목록 로드 실패:', error);
            document.getElementById('pending-list').innerHTML = '<div class="list-group-item text-center text-danger">목록을 불러오는데 실패했습니다.</div>';
        }
    }

    renderPendingList() {
        const listContainer = document.getElementById('pending-list');
        listContainer.innerHTML = '';

        if (this.state.pendingList.length === 0) {
            listContainer.innerHTML = '<div class="list-group-item text-center text-muted">확인 대기 중인 자료가 없습니다.</div>';
            return;
        }

        this.state.pendingList.forEach(item => {
            const registrantName = item.employee_name || item.user_name || '알 수 없음';
            const itemHtml = `
                <a href="#" class="list-group-item list-group-item-action" data-id="${item.id}">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1 list-title ">${item.waste_type}</h6>
                        <small>${new Date(item.created_at).toLocaleDateString()}</small>
                    </div>
                    <p class="mb-1 small text-muted">${item.address}</p>
                    <small class="text-muted">등록자: ${registrantName}</small>
                </a>
            `;
            const itemEl = document.createElement('div');
            itemEl.innerHTML = itemHtml;
            const itemNode = itemEl.firstElementChild;

            itemNode.addEventListener('click', (e) => {
                e.preventDefault();
                this.selectCase(parseInt(item.id));
                const currentActive = listContainer.querySelector('.active');
                if(currentActive) currentActive.classList.remove('active');
                itemNode.classList.add('active');
            });
            listContainer.appendChild(itemNode);
        });
    }

    selectCase(caseId) {
        const selected = this.state.pendingList.find(item => item.id === caseId);
        if (!selected) return;

        this.state.selectedCase = selected;

        // Populate form fields
        document.getElementById('case-id').value = selected.id;
        document.getElementById('latitude').value = selected.latitude;
        document.getElementById('longitude').value = selected.longitude;
        document.getElementById('address').value = selected.address;
        document.getElementById('mainType').value = selected.waste_type;
        document.getElementById('subType').value = selected.waste_type2;

        const registrantName = selected.employee_name || selected.user_name || '알 수 없음';
        const registrantType = selected.employee_name ? '(직원)' : '(일반)';
        document.getElementById('registrant-info').textContent = `등록자: ${registrantName} ${registrantType}`;
        
        this.displayExistingPhoto(selected);

        const position = { lat: selected.latitude, lng: selected.longitude };
        this.state.mapManager.setCenter(position);

        if (this.state.currentMarker) {
            this.state.mapManager.removeMarker(this.state.currentMarker);
        }

        this.state.currentMarker = this.state.mapManager.addMarker({
            position: position,
            draggable: true,
            onDragEnd: (newPosition) => {
                document.getElementById('latitude').value = newPosition.lat;
                document.getElementById('longitude').value = newPosition.lng;
                // The onAddressResolved callback in init() handles the address update.
            }
        });

        document.getElementById('detail-view').classList.remove('d-none');
        SplitLayout.show();
    }

    displayExistingPhoto(markerData) {
        const wrapper = document.getElementById('photoSwiperWrapper');
        wrapper.innerHTML = '';
        const basePath = '../storage/';
        const photos = [];
        if (markerData.reg_photo_path) photos.push({ src: basePath + markerData.reg_photo_path, title: '작업전' });
        if (markerData.reg_photo_path2) photos.push({ src: basePath + markerData.reg_photo_path2, title: '작업후' });
    
        if (photos.length > 0) {
            photos.forEach((photo, index) => {
                const activeClass = index === 0 ? 'active' : '';
                const slideHTML = `
                    <div class="carousel-item ${activeClass}" data-bs-interval="false">
                        <img src="${photo.src}" class="d-block w-100" alt="${photo.title}">
                    </div>`;
                const slideEl = document.createElement('div');
                slideEl.innerHTML = slideHTML.trim();
                const slideNode = slideEl.firstChild;
                slideNode.addEventListener('click', () => this.showPhotoModal(photo.src, photo.title));
                wrapper.appendChild(slideNode);
            });
        } else {
            wrapper.innerHTML = '<div class="carousel-item active"><div class="text-center p-5 text-muted">등록된 사진이 없습니다.</div></div>';
        }
    }

    showPhotoModal(imageSrc, title) {
        document.getElementById('photoViewModalLabel').textContent = title;
        document.getElementById('photoViewModalImage').src = imageSrc;
        const modal = new bootstrap.Modal(document.getElementById('photoViewModal'));
        modal.show();
    }

    async confirmReport() {
        if (!this.state.selectedCase) return;

        const updatedData = {
            id: document.getElementById('case-id').value,
            latitude: document.getElementById('latitude').value,
            longitude: document.getElementById('longitude').value,
            address: document.getElementById('address').value,
            mainType: document.getElementById('mainType').value,
            subType: document.getElementById('subType').value
        };

        this.setButtonLoading('#confirm-btn', '저장 중...');

        try {
            const response = await this.apiCall('confirm_littering', updatedData, 'POST');
            if (response.success) {
                Toast.success('성공적으로 확인 및 저장되었습니다.');
                this.removeConfirmedItem(updatedData.id);
            } else {
                Toast.error('저장에 실패했습니다: ' + response.message);
            }
        } catch (error) {
            Toast.error('오류가 발생했습니다: ' + error.message);
        } finally {
            this.resetButtonLoading('#confirm-btn', '<i class="ri-check-double-line me-1"></i>확인 및 저장');
        }
    }

    async deleteReport() {
        if (!this.state.selectedCase) return;

        Confirm.fire('삭제 확인', '정말로 이 항목을 삭제하시겠습니까?').then(async (result) => {
            if (result.isConfirmed) {
                const data = { id: this.state.selectedCase.id };

                this.setButtonLoading('#delete-btn', '삭제 중...');

                try {
                    const response = await this.apiCall('delete_littering', data, 'POST');
                    if (response.success) {
                        Toast.success('성공적으로 삭제되었습니다.');
                        this.removeConfirmedItem(data.id);
                    } else {
                        Toast.error('삭제에 실패했습니다: ' + response.message);
                    }
                } catch (error) {
                    Toast.error('오류가 발생했습니다: ' + error.message);
                } finally {
                    this.resetButtonLoading('#delete-btn', '<i class="ri-delete-bin-line me-1"></i>삭제');
                }
            }
        });
    }

    removeConfirmedItem(caseId) {
        this.state.pendingList = this.state.pendingList.filter(item => item.id !== parseInt(caseId));
        this.renderPendingList();
        document.getElementById('detail-view').classList.add('d-none');
        document.getElementById('confirm-form').reset();
        this.state.selectedCase = null;
        if (this.state.currentMarker) {
            this.state.mapManager.removeMarker(this.state.currentMarker);
            this.state.currentMarker = null;
        }
        const activeItem = document.querySelector('#pending-list .active');
        if (activeItem) {
            activeItem.classList.remove('active');
        }
    }
}

new LitteringAdminApp();
