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
            onAddressResolved: (locationData, addressData) => {
                document.getElementById('address').value = addressData.address;
                document.getElementById('jibun_address').value = addressData.jibun_address || '';
                document.getElementById('road_address').value = addressData.road_address || '';
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
                this.apiCall(`${this.config.API_URL}?status=대기`),
                this.apiCall(`${this.config.API_URL}?status=처리완료`)
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
            '대기'
        );
    }

    renderProcessedList() {
        this.renderList(
            'processed-list',
            this.state.processedReports,
            '승인 대기 중인 자료가 없습니다.',
            '처리완료'
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
            let personInfo = '';
            if (type === '대기') {
                personInfo = `등록 : ${item.created_by_name}`;
            } else if (type === '처리완료') {
                personInfo = `등록 : ${item.created_by_name} 처리 : ${item.processed_by_name}`;
            }

            let correctedBadge = '';
            if (type === '처리완료' && item.corrected) {
                let badgeClass = '';
                let correctedText = '';
                switch (item.corrected) {
                    case 'o':
                        badgeClass = 'bg-success';
                        correctedText = '개선';
                        break;
                    case 'x':
                        badgeClass = 'bg-danger';
                        correctedText = '미개선';
                        break;
                    case '=':
                        badgeClass = 'bg-warning';
                        correctedText = '없어짐';
                        break;
                }
                if(correctedText) {
                    correctedBadge = `<span class="badge ${badgeClass} ms-2">${correctedText}</span>`;
                }
            }

            const itemHtml = `
                <a href="#" class="list-group-item list-group-item-action" data-id="${item.id}" data-type="${type}">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1 list-title ">${item.waste_type}${correctedBadge}</h6>
                        <small>${new Date(item.created_at).toLocaleDateString()}</small>
                    </div>
                    <p class="mb-1 small text-muted">${item.jibun_address || item.road_address || '주소 없음'}</p>
                    <small class="text-muted">${personInfo}</small>
                </a>
            `;
            const itemNode = document.createRange().createContextualFragment(itemHtml).firstElementChild;
            itemNode.addEventListener('click', (e) => {
                e.preventDefault();
                this.selectReport(parseInt(item.id), type);

                document.querySelectorAll('.list-group-item.active').forEach(active => active.classList.remove('active'));
                itemNode.classList.add('active');
            });
            listContainer.appendChild(itemNode);
        });
    }

    selectReport(reportId, type) {
        const sourceList = type === '대기' ? this.state.pendingReports : this.state.processedReports;
        const selected = sourceList.find(item => item.id === reportId);
        if (!selected) return;

        this.state.selectedReport = selected;

        document.getElementById('case-id').value = selected.id;
        document.getElementById('latitude').value = selected.latitude;
        document.getElementById('longitude').value = selected.longitude;
        document.getElementById('address').value = selected.jibun_address || selected.road_address || '';
        document.getElementById('jibun_address').value = selected.jibun_address || '';
        document.getElementById('road_address').value = selected.road_address || '';
        document.getElementById('waste_type').value = selected.waste_type;
        document.getElementById('waste_type2').value = selected.waste_type2;

        let personInfo = '';
        if (selected.status === '대기') {
            personInfo = `등록 : ${selected.created_by_name}`;
        } else if (selected.status === '처리완료') {
            personInfo = `등록 : ${selected.created_by_name} 처리 : ${selected.processed_by_name}`;
        }
        document.getElementById('registrant-info').textContent = personInfo;
        
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

        document.getElementById('confirm-btn').classList.toggle('d-none', type !== '대기');
        document.getElementById('approve-btn').classList.toggle('d-none', type !== '처리완료');
        document.getElementById('delete-btn').classList.toggle('d-none', type !== '대기' && type !== '처리완료');

        // 개선여부 드롭다운 표시 로직
        const improvementWrapper = document.getElementById('improvement-status-wrapper');
        if (type === '처리완료') {
            const improvementSelect = document.getElementById('improvement-status-select');
            improvementSelect.value = selected.corrected || 'o'; // 'o'를 기본값으로 설정
            improvementWrapper.classList.remove('d-none');
        } else {
            improvementWrapper.classList.add('d-none');
        }

        document.getElementById('detail-view').classList.remove('d-none');
        if (window.SplitLayout) {
            SplitLayout.show();
        }
    }

    renderExistingPhotos(reportData) {
        const container = document.getElementById('photo-container');
        container.innerHTML = '';

        let photoSlots = [];
        if (reportData.status === '대기') {
            photoSlots = [
                { title: '작업전', src: reportData.reg_photo_path },
                { title: '작업후', src: reportData.reg_photo_path2 }
            ];
        } else { // '처리완료'
            photoSlots = [
                { title: '작업전', src: reportData.reg_photo_path },
                { title: '작업후', src: reportData.reg_photo_path2 },
                { title: '처리완료', src: reportData.proc_photo_path }
            ];
        }

        const grid = document.createElement('div');
        grid.className = 'photo-grid';
        let hasPhotos = false;

        photoSlots.forEach(slot => {
            const item = document.createElement('div');
            item.className = 'photo-item';

            const container169 = document.createElement('div');
            container169.className = 'image-container-16-9';

            if (slot.src) {
                hasPhotos = true;
                container169.style.cursor = 'pointer';
                container169.addEventListener('click', () => this.openPhotoModal(slot.src, slot.title));

                const img = document.createElement('img');
                img.src = slot.src;
                img.alt = slot.title;
                container169.appendChild(img);
            } else {
                const placeholder = document.createElement('div');
                placeholder.className = 'no-image-placeholder';
                placeholder.innerHTML = `<div>${slot.title}</div><small>(이미지 없음)</small>`;
                container169.appendChild(placeholder);
            }
            item.appendChild(container169);
            grid.appendChild(item);
        });

        if (!hasPhotos) {
             container.innerHTML = '<div class="text-center p-5 text-muted">등록된 사진이 없습니다.</div>';
        } else {
            container.appendChild(grid);
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
            jibun_address: document.getElementById('address').value,
            road_address: document.getElementById('road_address').value,
            waste_type: document.getElementById('waste_type').value,
            waste_type2: document.getElementById('waste_type2').value
        };

        const originalDisplayAddress = this.state.selectedReport.jibun_address || this.state.selectedReport.road_address || '';
        if (document.getElementById('address').value !== originalDisplayAddress) {
            updatedData.road_address = '';
        }

        this.setButtonLoading('#confirm-btn', '저장 중...');
        try {
            await this.apiCall(`${this.config.API_URL}/${updatedData.id}/confirm`, {
                method: 'POST',
                body: updatedData
            });
            Toast.success('성공적으로 확인 및 저장되었습니다.');
            this.removeReportFromList(updatedData.id, '대기');
        } catch (error) {
            Toast.error('저장에 실패했습니다: ' + error.message);
        } finally {
            this.resetButtonLoading('#confirm-btn', '<i class="ri-check-double-line me-1"></i>확인 및 저장');
        }
    }

    async approveReport() {
        if (!this.state.selectedReport || this.state.selectedReport.status !== '처리완료') return;

        const reportId = this.state.selectedReport.id;
        const correctedStatus = document.getElementById('improvement-status-select').value;

        this.setButtonLoading('#approve-btn', '승인 중...');
        try {
            await this.apiCall(`${this.config.API_URL}/${reportId}/approve`, {
                method: 'POST',
                body: { corrected: correctedStatus }
            });
            Toast.success('성공적으로 승인되었습니다.');
            this.removeReportFromList(reportId, '처리완료');
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
                this.removeReportFromList(reportId, '대기');
            } catch (error) {
                Toast.error('삭제에 실패했습니다: ' + error.message);
            } finally {
                this.resetButtonLoading('#delete-btn', '<i class="ri-delete-bin-line me-1"></i>삭제');
            }
        }
    }

    removeReportFromList(reportId, type) {
        if (type === '대기') {
            this.state.pendingReports = this.state.pendingReports.filter(item => item.id !== parseInt(reportId));
            this.renderPendingList();
        } else if (type === '처리완료') {
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

    cleanup() {
        super.cleanup();
        if (this.state.mapService) {
            this.state.mapService.destroy();
        }
    }
}

new LitteringAdminPage();
