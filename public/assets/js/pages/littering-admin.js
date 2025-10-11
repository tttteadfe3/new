class LitteringAdminPage extends BasePage {
    constructor() {
        super({
            API_URL: '/littering_admin/reports',
            allowedRegions: ['정왕1동']
        });

        this.state = {
            ...this.state,
            pendingReports: [],
            selectedReport: null,
            currentMarker: null
        };
    }

    initializeApp() {
        const mapOptions = {
            ...this.config, // Pass all page configs to the map
            enableTempMarker: false,
            onAddressResolved: (locationData) => {
                document.getElementById('address').value = locationData.address;
            }
        };
        this.state.mapService = new MapService(mapOptions);
        this.setupEventListeners();
        this.loadInitialData();
    }

    setupEventListeners() {
        document.getElementById('confirm-btn').addEventListener('click', () => this.confirmReport());
        document.getElementById('delete-btn').addEventListener('click', () => this.deleteReport());
    }

    async loadInitialData() {
        try {
            const response = await this.apiCall(`${this.config.API_URL}?status=pending`);
            this.state.pendingReports = response.data || [];
            this.renderPendingList();
        } catch (error) {
            console.error('대기 목록 로드 실패:', error);
            document.getElementById('pending-list').innerHTML = `<div class="list-group-item text-center text-danger">목록을 불러오는데 실패했습니다: ${error.message}</div>`;
        }
    }

    renderPendingList() {
        const listContainer = document.getElementById('pending-list');
        listContainer.innerHTML = '';

        if (this.state.pendingReports.length === 0) {
            listContainer.innerHTML = '<div class="list-group-item text-center text-muted">확인 대기 중인 자료가 없습니다.</div>';
            return;
        }

        this.state.pendingReports.forEach(item => {
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
            const itemNode = document.createRange().createContextualFragment(itemHtml).firstChild;
            itemNode.addEventListener('click', (e) => {
                e.preventDefault();
                this.selectReport(parseInt(item.id));
                const currentActive = listContainer.querySelector('.active');
                if(currentActive) currentActive.classList.remove('active');
                itemNode.classList.add('active');
            });
            listContainer.appendChild(itemNode);
        });
    }

    selectReport(reportId) {
        const selected = this.state.pendingReports.find(item => item.id === reportId);
        if (!selected) return;

        this.state.selectedReport = selected;

        document.getElementById('case-id').value = selected.id;
        document.getElementById('latitude').value = selected.latitude;
        document.getElementById('longitude').value = selected.longitude;
        document.getElementById('address').value = selected.address;
        document.getElementById('mainType').value = selected.waste_type;
        document.getElementById('subType').value = selected.waste_type2;
        document.getElementById('registrant-info').textContent = `등록자: ${selected.employee_name || selected.user_name || '알 수 없음'} (${selected.employee_name ? '직원' : '일반'})`;
        
        this.renderExistingPhotos(selected);

        const position = { lat: selected.latitude, lng: selected.longitude };
        this.state.mapService.mapManager.setCenter(position);

        if (this.state.currentMarker) this.state.mapService.mapManager.removeMarker(this.state.currentMarker);

        this.state.currentMarker = this.state.mapService.mapManager.addMarker({
            position: position,
            draggable: true,
            onDragEnd: (newPosition) => {
                document.getElementById('latitude').value = newPosition.lat;
                document.getElementById('longitude').value = newPosition.lng;
            }
        });

        document.getElementById('detail-view').classList.remove('d-none');
        if (window.SplitLayout) {
            SplitLayout.show();
        }
    }

    renderExistingPhotos(reportData) {
        const wrapper = document.getElementById('photoSwiperWrapper');
        wrapper.innerHTML = '';
        const basePath = '/storage/';
        const photos = [];
        if (reportData.reg_photo_path) photos.push({ src: basePath + reportData.reg_photo_path, title: '등록 사진' });
    
        if (photos.length > 0) {
            const photo = photos[0];
            const slideHTML = `<img src="${photo.src}" class="d-block w-100" alt="${photo.title}">`;
            const slideNode = document.createRange().createContextualFragment(slideHTML).firstChild;
            slideNode.addEventListener('click', () => this.openPhotoModal(photo.src, photo.title));
            wrapper.appendChild(slideNode);
        } else {
            wrapper.innerHTML = '<div class="text-center p-5 text-muted">등록된 사진이 없습니다.</div>';
        }
    }

    openPhotoModal(imageSrc, title) {
        document.getElementById('photoViewModalLabel').textContent = title;
        document.getElementById('photoViewModalImage').src = imageSrc;
        const modal = new bootstrap.Modal(document.getElementById('photoViewModal'));
        modal.show();
    }

    async confirmReport() {
        if (!this.state.selectedReport) return;

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
            await this.apiCall(`${this.config.API_URL}/${updatedData.id}/confirm`, {
                method: 'POST',
                body: updatedData
            });
            Toast.success('성공적으로 확인 및 저장되었습니다.');
            this.removeReportFromList(updatedData.id);
        } catch (error) {
            Toast.error('저장에 실패했습니다: ' + error.message);
        } finally {
            this.resetButtonLoading('#confirm-btn', '<i class="ri-check-double-line me-1"></i>확인 및 저장');
        }
    }

    async deleteReport() {
        if (!this.state.selectedReport) return;

        const result = await Confirm.fire('삭제 확인', '정말로 이 항목을 삭제하시겠습니까?');
        if (result.isConfirmed) {
            const reportId = this.state.selectedReport.id;
            this.setButtonLoading('#delete-btn', '삭제 중...');
            try {
                await this.apiCall(`${this.config.API_URL}/${reportId}`, { method: 'DELETE' });
                Toast.success('성공적으로 삭제되었습니다.');
                this.removeReportFromList(reportId);
            } catch (error) {
                Toast.error('삭제에 실패했습니다: ' + error.message);
            } finally {
                this.resetButtonLoading('#delete-btn', '<i class="ri-delete-bin-line me-1"></i>삭제');
            }
        }
    }

    removeReportFromList(reportId) {
        this.state.pendingReports = this.state.pendingReports.filter(item => item.id !== parseInt(reportId));
        this.renderPendingList();
        document.getElementById('detail-view').classList.add('d-none');
        document.getElementById('confirm-form').reset();
        this.state.selectedReport = null;
        if (this.state.currentMarker) {
            this.state.mapService.mapManager.removeMarker(this.state.currentMarker);
            this.state.currentMarker = null;
        }
        const activeItem = document.querySelector('#pending-list .active');
        if (activeItem) activeItem.classList.remove('active');
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

new LitteringAdminPage();