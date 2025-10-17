/**
 * Application for the Littering History page.
 * Handles fetching and displaying processed littering reports on a map.
 */
class LitteringHistoryPage extends BasePage {
    constructor() {
        const currentScript = document.currentScript;
        let scriptConfig = {};
        if (currentScript) {
            const options = currentScript.getAttribute('data-options');
            if (options) {
                try {
                    scriptConfig = JSON.parse(options);
                } catch (e) {
                    console.error('Failed to parse script options for LitteringHistoryPage:', e);
                }
            }
        }

        super({
            ...scriptConfig,
            API_URL: '/littering',
            WASTE_TYPES: ['생활폐기물', '음식물', '재활용', '대형', '소각']
        });

        this.state = {
            ...this.state,
            processedReports: [],
            groupedData: {},
            detailsOffcanvas: null
        };
    }

    /**
     * @override
     */
    initializeApp() {
        const mapOptions = {
            enableTempMarker: false,
            markerTypes: this.generateMarkerTypes(),
            markerSize: { width: 34, height: 40 },
            onMarkerClick: (marker, data) => this.openDetailsOffcanvas(data.address, data.items)
        };
        this.state.mapService = new MapService(mapOptions);
        this.setupOffcanvas();
        this.setupLightbox();
        this.loadInitialData();
    }

    /**
     * @override
     */
    async loadInitialData() {
        try {
            const response = await this.apiCall(`${this.config.API_URL}?status=completed`);
            this.state.processedReports = response.data || [];
            this.groupReportsByAddress();
            this.displayReportsOnMap();
        } catch (error) {
            console.error('Failed to load processed reports:', error);
            Toast.error(`처리 내역 로딩 실패: ${error.message}`);
        }
    }

    generateMarkerTypes() {
        const markerTypes = {};
        const wasteTypeColors = {
            '생활폐기물': '#666666', '음식물': '#FF9800', '재활용': '#00A6FB',
            '대형': '#DC2626', '소각': '#FF5722'
        };
        this.config.WASTE_TYPES.forEach(type => {
            markerTypes[`${type}_processed`] = MarkerFactory.createSVGIcon({
                type: 'default',
                color: wasteTypeColors[type] || '#666666',
                text: type[0],
                status: 'processed'
            });
        });
        return markerTypes;
    }

    setupOffcanvas() {
        const offcanvasElement = document.getElementById('markerOffcanvas');
        if (offcanvasElement) {
            this.state.detailsOffcanvas = new bootstrap.Offcanvas(offcanvasElement);
        }
    }

    setupLightbox() {
        this.lightbox = GLightbox({ selector: '.gallery-lightbox' });
    }

    groupReportsByAddress() {
        this.state.groupedData = this.state.processedReports.reduce((acc, item) => {
            const address = item.address || '주소 없음';
            if (!acc[address]) {
                acc[address] = [];
            }
            acc[address].push(item);
            return acc;
        }, {});
    }

    displayReportsOnMap() {
        Object.entries(this.state.groupedData).forEach(([address, items]) => {
            const firstItem = items[0];
            const dominantType = this.getDominantWasteType(items);
            this.state.mapService.mapManager.addMarker({
                position: { lat: firstItem.latitude, lng: firstItem.longitude },
                type: `${dominantType}_processed`,
                data: { address, items },
                onClick: (marker, markerData) => this.openDetailsOffcanvas(markerData.address, markerData.items)
            });
        });
    }

    getDominantWasteType(items) {
        const typeCounts = items.reduce((acc, item) => {
            const type = item.waste_type || '생활폐기물';
            acc[type] = (acc[type] || 0) + 1;
            return acc;
        }, {});
        return Object.keys(typeCounts).reduce((a, b) => typeCounts[a] > typeCounts[b] ? a : b);
    }

    openDetailsOffcanvas(address, items) {
        const offcanvasAddress = document.getElementById('offcanvasAddress');
        const processList = document.getElementById('processList');
        if (!offcanvasAddress || !processList) return;

        offcanvasAddress.textContent = address;
        processList.innerHTML = '';
        items.sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at));
        items.forEach(item => processList.appendChild(this.createReportItemElement(item)));

        if (this.state.detailsOffcanvas) {
            this.state.detailsOffcanvas.show();
        }
    }

    createReportItemElement(item) {
        const correctedMap = { 'o': '개선', 'x': '미개선', '=': '없어짐' };
        const correctedClassMap = { 'o': 'bg-success', 'x': 'bg-danger', '=': 'bg-warning text-dark' };
        const wasteTypeText = item.waste_type + (item.waste_type2 ? ` + ${item.waste_type2}` : '');
        const correctedStatus = correctedMap[item.corrected] || 'N/A';
        const correctedClass = correctedClassMap[item.corrected] || 'bg-secondary';

        const itemDiv = document.createElement('div');
        itemDiv.className = 'card mb-3';
        itemDiv.innerHTML = `
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="badge bg-secondary fs-6">${wasteTypeText}</span>
                    <span class="badge ${correctedClass} fs-6">${correctedStatus}</span>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between small text-muted mb-2">
                    <span>배출일: ${this.formatDate(item.issue_date)}</span>
                    <span>수거일: ${this.formatDate(item.collect_date)}</span>
                </div>
                ${item.note ? `<p class="mt-2 mb-2 p-2 bg-light border rounded small">${item.note}</p>` : ''}
                <div class="d-flex gap-2 mt-2 flex-wrap">${this.renderPhotoElements(item)}</div>
                <div class="small text-muted mt-2">처리 완료: ${this.formatDateTime(item.updated_at)}</div>
            </div>`;
        return itemDiv;
    }

    renderPhotoElements(item) {
        const photos = [];
        if (item.reg_photo_path) photos.push({ src: item.reg_photo_path, title: '작업전' });
        if (item.reg_photo_path2) photos.push({ src: item.reg_photo_path2, title: '작업후' });
        if (item.proc_photo_path) photos.push({ src: item.proc_photo_path, title: '처리' });
        return photos.map(photo => `
            <a href="${photo.src}" class="gallery-lightbox" data-gallery="gallery-${item.id}" title="${photo.title}">
                <img src="${photo.src}" alt="${photo.title}" class="process-item-photo rounded">
                <small class="photo-label">${photo.title}</small>
            </a>`).join('');
    }

    formatDate(dateString) {
        return dateString ? new Date(dateString).toLocaleDateString('ko-KR') : 'N/A';
    }

    formatDateTime(dateTimeString) {
        return dateTimeString ? new Date(dateTimeString).toLocaleString('ko-KR') : 'N/A';
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

new LitteringHistoryPage();