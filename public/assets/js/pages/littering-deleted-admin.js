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
            deletedReports: [],
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
            const response = await this.apiCall(`${this.config.API_URL}?status=삭제`);
            this.state.deletedReports = response.data || [];
            this.renderDeletedList();
        } catch (error) {
            console.error('데이터 로드 실패:', error);
            Toast.error('삭제된 목록을 불러오는 중 오류가 발생했습니다.');
        }
    }

    renderDeletedList() {
        const listContainer = document.getElementById('deleted-list');
        listContainer.innerHTML = '';

        if (this.state.deletedReports.length === 0) {
            listContainer.innerHTML = `<div class="list-group-item text-center text-muted">삭제된 항목이 없습니다.</div>`;
            return;
        }

        this.state.deletedReports.forEach(item => {
            const personInfo = `등록: ${item.created_by_name} / 삭제: ${item.deleted_by_name}`;
            const itemHtml = `
                <a href="#" class="list-group-item list-group-item-action" data-id="${item.id}">
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
                this.selectReport(parseInt(item.id));
                document.querySelectorAll('.list-group-item.active').forEach(active => active.classList.remove('active'));
                itemNode.classList.add('active');
            });
            listContainer.appendChild(itemNode);
        });
    }

    selectReport(reportId) {
        const selected = this.state.deletedReports.find(item => item.id === reportId);
        if (!selected) return;

        this.state.selectedReport = selected;

        document.getElementById('case-id').value = selected.id;
        document.getElementById('address').textContent = selected.road_address || selected.jibun_address || '';
        document.getElementById('waste_type').textContent = selected.waste_type;
        document.getElementById('waste_type2').textContent = selected.waste_type2 || '없음';

        const personInfo = `등록: ${selected.created_by_name} / 삭제: ${selected.deleted_by_name} (${new Date(selected.deleted_at).toLocaleString()})`;
        document.getElementById('registrant-info').textContent = personInfo;

        this.renderExistingPhotos(selected);

        const position = { lat: selected.latitude, lng: selected.longitude };
        this.state.mapService.mapManager.setCenter(position);

        if (this.state.currentMarker) this.state.mapService.mapManager.removeMarker(this.state.currentMarker);

        this.state.currentMarker = this.state.mapService.mapManager.addMarker({ position });

        document.getElementById('detail-view').classList.remove('d-none');
        if (window.SplitLayout) {
            SplitLayout.show();
        }
    }

    renderExistingPhotos(reportData) {
        const container = document.getElementById('photo-container');
        container.innerHTML = '';
        const photoPaths = [
            { title: '작업전', src: reportData.reg_photo_path },
            { title: '작업후', src: reportData.reg_photo_path2 },
            { title: '처리완료', src: reportData.proc_photo_path }
        ].filter(p => p.src);

        if (photoPaths.length === 0) {
            container.innerHTML = '<div class="text-center p-5 text-muted">등록된 사진이 없습니다.</div>';
            return;
        }

        photoPaths.forEach(photo => {
            const imgHtml = `
                <div class="photo-item" style="cursor: pointer;">
                    <img src="${photo.src}" alt="${photo.title}" class="img-fluid rounded">
                    <p class="text-center small mt-1">${photo.title}</p>
                </div>`;
            const photoNode = document.createRange().createContextualFragment(imgHtml).firstElementChild;
            photoNode.addEventListener('click', () => this.openPhotoModal(photo.src, photo.title));
            container.appendChild(photoNode);
        });
    }

    openPhotoModal(imageSrc, title) {
        document.getElementById('photoViewModalLabel').textContent = title;
        document.getElementById('photoViewModalImage').src = imageSrc;
        const modal = new bootstrap.Modal(document.getElementById('photoViewModal'));
        modal.show();
    }

    async restoreReport() {
        if (!this.state.selectedReport) return;

        const caseId = this.state.selectedReport.id;
        const result = await Confirm.fire('복원 확인', `ID ${caseId} 항목을 복원하시겠습니까?`);
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
        this.state.deletedReports = this.state.deletedReports.filter(item => item.id !== parseInt(reportId));
        this.renderDeletedList();

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
