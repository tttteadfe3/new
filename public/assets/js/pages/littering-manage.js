class LitteringAdminPage extends BasePage {
    constructor() {
        const currentScript = document.currentScript;
        let scriptConfig = {};
        if (currentScript) {
            const options = currentScript.getAttribute('data-options');
            if (options) {
                try {
                    scriptConfig = JSON.parse(options);
                } catch (e) {
                    console.error('Failed to parse script options for LitteringAdminPage:', e);
                }
            }
        }

        super({
            ...scriptConfig,
            API_URL: '/littering_admin/reports'
        });

        this.state = {
            ...this.state,
            pendingReports: [],
            processedReports: [],
            selectedReport: null,
            currentMarker: null
        };
    }

    initializeApp() {
        const mapOptions = {
            ...this.config,
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
        document.getElementById('approve-btn').addEventListener('click', () => this.approveReport());
        document.getElementById('delete-btn').addEventListener('click', () => this.deleteReport());
    }

    async loadInitialData() {
        try {
            const [pendingResponse, processedResponse] = await Promise.all([
                this.apiCall(`${this.config.API_URL}?status=pending`),
                this.apiCall(`${this.config.API_URL}?status=processed_for_approval`)
            ]);
            this.state.pendingReports = pendingResponse.data || [];
            this.state.processedReports = processedResponse.data || [];
            this.renderPendingList();
            this.renderProcessedList();
        } catch (error) {
            console.error('데이터 로드 실패:', error);
            Toast.error('데이터를 불러오는 중 오류가 발생했습니다.');
        }
    }

    renderPendingList() {
        this.renderList(
            'pending-list',
            this.state.pendingReports,
            '확인 대기 중인 자료가 없습니다.',
            'pending'
        );
    }

    renderProcessedList() {
        this.renderList(
            'processed-list',
            this.state.processedReports,
            '승인 대기 중인 자료가 없습니다.',
            'processed'
        );
    }

    renderList(containerId, items, emptyMessage, type) {
        const listContainer = document.getElementById(containerId);
        listContainer.innerHTML = '';

        if (items.length === 0) {
            listContainer.innerHTML = `<div class="list-group-item text-center text-muted">${emptyMessage}</div>`;
            return;
        }

        items.forEach(item => {
            const registrantName = item.employee_name || item.user_name || '알 수 없음';
            const itemHtml = `
                <a href="#" class="list-group-item list-group-item-action" data-id="${item.id}" data-type="${type}">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1 list-title ">${item.waste_type}</h6>
                        <small>${new Date(item.created_at).toLocaleDateString()}</small>
                    </div>
                    <p class="mb-1 small text-muted">${item.address}</p>
                    <small class="text-muted">등록자: ${registrantName}</small>
                </a>
            `;
            const itemNode = document.createRange().createContextualFragment(itemHtml).firstElementChild;
            itemNode.addEventListener('click', (e) => {
                e.preventDefault();
                this.selectReport(parseInt(item.id), type);

                // Remove active class from all lists
                document.querySelectorAll('.list-group-item.active').forEach(active => active.classList.remove('active'));

                itemNode.classList.add('active');
            });
            listContainer.appendChild(itemNode);
        });
    }

    selectReport(reportId, type) {
        const sourceList = type === 'pending' ? this.state.pendingReports : this.state.processedReports;
        const selected = sourceList.find(item => item.id === reportId);
        if (!selected) return;

        this.state.selectedReport = { ...selected, type };

        document.getElementById('case-id').value = selected.id;
        document.getElementById('latitude').value = selected.latitude;
        document.getElementById('longitude').value = selected.longitude;
        document.getElementById('address').value = selected.address;
        document.getElementById('waste_type').value = selected.waste_type;
        document.getElementById('waste_type2').value = selected.waste_type2;
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

        // Toggle button visibility based on type
        document.getElementById('confirm-btn').classList.toggle('d-none', type !== 'pending');
        document.getElementById('approve-btn').classList.toggle('d-none', type !== 'processed');
        document.getElementById('delete-btn').classList.toggle('d-none', type !== 'pending'); // Can only delete pending reports

        document.getElementById('detail-view').classList.remove('d-none');
        if (window.SplitLayout) {
            SplitLayout.show();
        }
    }

    renderExistingPhotos(reportData) {
        const wrapper = document.getElementById('photoSwiperWrapper');
        wrapper.innerHTML = '';
        const photos = [];
        if (reportData.reg_photo_path) photos.push({ src: reportData.reg_photo_path, title: '등록 사진' });
    
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
            waste_type: document.getElementById('waste_type').value,
            waste_type2: document.getElementById('waste_type2').value
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

    async approveReport() {
        if (!this.state.selectedReport || this.state.selectedReport.type !== 'processed') return;

        const reportId = this.state.selectedReport.id;
        this.setButtonLoading('#approve-btn', '승인 중...');
        try {
            await this.apiCall(`${this.config.API_URL}/${reportId}/approve`, {
                method: 'POST'
            });
            Toast.success('성공적으로 승인되었습니다.');
            this.removeReportFromList(reportId, 'processed');
        } catch (error) {
            Toast.error('승인에 실패했습니다: ' + error.message);
        } finally {
            this.resetButtonLoading('#approve-btn', '<i class="ri-check-line me-1"></i>승인');
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
                this.removeReportFromList(reportId, 'pending');
            } catch (error) {
                Toast.error('삭제에 실패했습니다: ' + error.message);
            } finally {
                this.resetButtonLoading('#delete-btn', '<i class="ri-delete-bin-line me-1"></i>삭제');
            }
        }
    }

    removeReportFromList(reportId, type) {
        if (type === 'pending') {
            this.state.pendingReports = this.state.pendingReports.filter(item => item.id !== parseInt(reportId));
            this.renderPendingList();
        } else if (type === 'processed') {
            this.state.processedReports = this.state.processedReports.filter(item => item.id !== parseInt(reportId));
            this.renderProcessedList();
        }

        document.getElementById('detail-view').classList.add('d-none');
        document.getElementById('confirm-form').reset();
        this.state.selectedReport = null;
        if (this.state.currentMarker) {
            this.state.mapService.mapManager.removeMarker(this.state.currentMarker);
            this.state.currentMarker = null;
        }
        const activeItem = document.querySelector('.list-group-item.active');
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