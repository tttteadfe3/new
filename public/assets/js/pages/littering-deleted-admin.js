class LitteringDeletedPage extends BasePage {
    constructor() {
        const currentScript = document.currentScript;
        let scriptConfig = {};
        if (currentScript) {
            const options = currentScript.getAttribute('data-options');
            if (options) {
                try {
                    scriptConfig = JSON.parse(options);
                } catch (e) {
                    console.error('Failed to parse script options for LitteringDeletedPage:', e);
                }
            }
        }

        super({
            ...scriptConfig,
            API_URL: '/littering_admin/reports'
        });

        this.state = {
            ...this.state,
            pendingDeletedReports: [],
            processedDeletedReports: [],
            selectedReport: null,
            currentMarker: null
        };
    }

    initializeApp() {
        const mapOptions = { ...this.config, enableTempMarker: false };
        this.state.mapService = new MapService(mapOptions);
        this.setupEventListeners();
        this.loadInitialData();
    }

    setupEventListeners() {
        document.getElementById('restore-btn').addEventListener('click', () => this.restoreReport());
        document.getElementById('permanent-delete-btn').addEventListener('click', () => this.permanentlyDeleteReport());
    }

    async loadInitialData() {
        try {
            const [pendingResponse, processedResponse] = await Promise.all([
                this.apiCall(`${this.config.API_URL}?status=대기삭제`),
                this.apiCall(`${this.config.API_URL}?status=처리삭제`)
            ]);
            this.state.pendingDeletedReports = pendingResponse.data || [];
            this.state.processedDeletedReports = processedResponse.data || [];
            this.renderPendingDeletedList();
            this.renderProcessedDeletedList();
        } catch (error) {
            console.error('데이터 로드 실패:', error);
            Toast.error('삭제된 목록을 불러오는 중 오류가 발생했습니다.');
        }
    }

    renderPendingDeletedList() {
        this.renderList(
            'pending-deleted-list',
            this.state.pendingDeletedReports,
            '확인 전 삭제된 자료가 없습니다.',
            '대기삭제'
        );
    }

    renderProcessedDeletedList() {
        this.renderList(
            'processed-deleted-list',
            this.state.processedDeletedReports,
            '처리 후 삭제된 자료가 없습니다.',
            '처리삭제'
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
            const personInfo = `등록: ${item.created_by_name} / 삭제: ${item.deleted_by_name}`;
            const itemHtml = `
                <a href="#" class="list-group-item list-group-item-action" data-id="${item.id}" data-type="${type}">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1 list-title">${item.waste_type}</h6>
                        <small>${new Date(item.deleted_at).toLocaleDateString()}</small>
                    </div>
                    <p class="mb-1 small text-muted">${item.road_address || item.jibun_address || '주소 없음'}</p>
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
        const sourceList = type === '대기삭제' ? this.state.pendingDeletedReports : this.state.processedDeletedReports;
        const selected = sourceList.find(item => item.id === reportId);
        if (!selected) return;

        this.state.selectedReport = { ...selected, type: type };

        document.getElementById('case-id').value = selected.id;
        document.getElementById('address').textContent = selected.road_address || selected.jibun_address || '';
        document.getElementById('waste_type').textContent = selected.waste_type;
        document.getElementById('waste_type2').textContent = selected.waste_type2 || '없음';

        const personInfo = `등록: ${selected.created_by_name} / 삭제: ${selected.deleted_by_name} (${new Date(selected.deleted_at).toLocaleString()})`;
        document.getElementById('registrant-info').textContent = personInfo;

        this.renderExistingPhotos(selected, type);

        const position = { lat: selected.latitude, lng: selected.longitude };
        this.state.mapService.mapManager.setCenter(position);

        if (this.state.currentMarker) this.state.mapService.mapManager.removeMarker(this.state.currentMarker);
        this.state.currentMarker = this.state.mapService.mapManager.addMarker({ position });

        document.getElementById('detail-view').classList.remove('d-none');
        if (window.SplitLayout) {
            SplitLayout.show();
        }
    }

    renderExistingPhotos(reportData, type) {
        const container = document.getElementById('photo-container');
        container.innerHTML = '';

        let photoSlots = [
            { title: '작업전', src: reportData.reg_photo_path },
            { title: '작업후', src: reportData.reg_photo_path2 }
        ];

        if (type === '처리삭제') {
            photoSlots.push({ title: '처리완료', src: reportData.proc_photo_path });
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
                const anchor = document.createElement('a');
                anchor.href = slot.src;
                anchor.className = 'glightbox';
                anchor.dataset.gallery = `gallery-${reportData.id}`;
                anchor.dataset.title = slot.title;

                const img = document.createElement('img');
                img.src = slot.src;
                img.alt = slot.title;

                anchor.appendChild(img);
                container169.appendChild(anchor);
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
            if (this.lightbox) this.lightbox.destroy();
            this.lightbox = GLightbox({ selector: `[data-gallery="gallery-${reportData.id}"]` });
        }
    }

    async restoreReport() {
        if (!this.state.selectedReport) return;

        const { id: caseId, type } = this.state.selectedReport;
        const restoredStatus = type === '처리삭제' ? '처리완료' : '대기';

        const result = await Confirm.fire('복원 확인', `ID ${caseId} 항목을 복원하시겠습니까? (복원 후 상태: '${restoredStatus}')`);
        if (result.isConfirmed) {
            this.setButtonLoading('#restore-btn', '복원 중...');
            try {
                await this.apiCall(`${this.config.API_URL}/${caseId}/restore`, { method: 'POST' });
                Toast.success('항목이 성공적으로 복원되었습니다.');
                this.removeReportFromList(caseId);
            } catch (error) {
                Toast.error('복원에 실패했습니다: ' + error.message);
            } finally {
                this.resetButtonLoading('#restore-btn', '<i class="ri-arrow-go-back-line me-1"></i>복원');
            }
        }
    }

    async permanentlyDeleteReport() {
        if (!this.state.selectedReport) return;

        const caseId = this.state.selectedReport.id;
        const result = await Confirm.fire('영구 삭제 확인', `ID ${caseId} 항목을 영구적으로 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.`);
        if (result.isConfirmed) {
            this.setButtonLoading('#permanent-delete-btn', '삭제 중...');
            try {
                await this.apiCall(`${this.config.API_URL}/${caseId}`, { method: 'DELETE' });
                Toast.success('항목이 성공적으로 영구 삭제되었습니다.');
                this.removeReportFromList(caseId);
            } catch (error) {
                Toast.error('영구 삭제에 실패했습니다: ' + error.message);
            } finally {
                this.resetButtonLoading('#permanent-delete-btn', '<i class="ri-delete-bin-2-line me-1"></i>영구 삭제');
            }
        }
    }

    removeReportFromList(reportId) {
        const type = this.state.selectedReport.type;
        if (type === '대기삭제') {
             this.state.pendingDeletedReports = this.state.pendingDeletedReports.filter(item => item.id !== parseInt(reportId));
             this.renderPendingDeletedList();
        } else if (type === '처리삭제') {
            this.state.processedDeletedReports = this.state.processedDeletedReports.filter(item => item.id !== parseInt(reportId));
            this.renderProcessedDeletedList();
        }

        document.getElementById('detail-view').classList.add('d-none');
        document.getElementById('action-form').reset();
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

new LitteringDeletedPage();
