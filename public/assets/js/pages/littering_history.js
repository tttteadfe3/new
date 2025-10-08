/**
 * 처리 내역 조회 페이지 (littering_history.php) 스크립트
 */
class MarkerFactory {
    static createSVGIcon(options = {}) {
        const {
            color = '#2563EB',
            size = { width: 34, height: 40 },
            text = '',
            status = 'processed' // 'processed'만 사용
        } = options;
        
        let statusIcon = '';
        
        // 처리 완료 상태 아이콘 (완료 체크마크)
        statusIcon = `
            <circle cx="24" cy="8" r="6" fill="#28a745"/>
            <path d="M21 8L23 10L27 6" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        `;
        
        const baseIcon = `
            <svg width="${size.width}" height="${size.height}" viewBox="0 0 34 40" xmlns="http://www.w3.org/2000/svg">
                <path d="M17 40C17 40 3 22 3 15C3 6.71572 9.71572 0 17 0C24.2843 0 31 6.71572 31 15C31 22 17 40 17 40Z" 
                      fill="${color}" stroke="#ffffff" stroke-width="1"/>
                <circle cx="17" cy="15" r="11" fill="#fff"/>
                <text x="17" y="20" text-anchor="middle" fill="${color}" font-size="12" font-weight="bold" font-family="Arial">${text}</text>
                ${statusIcon}
            </svg>
        `;
        
        const utf8Base64 = btoa(unescape(encodeURIComponent(baseIcon)));
        return 'data:image/svg+xml;base64,' + utf8Base64;
    }
}

class LitteringHistoryApp extends BaseApp {
    constructor() {
        super({
            API_URL: '../api/littering.php',
            WASTE_TYPES: ['생활폐기물', '음식물', '재활용', '대형', '소각'],
            UPLOADS_BASE_PATH: '../storage/'
        });

        this.state = {
            ...this.state,
            processedMarkers: [],
            groupedData: {},
            offcanvas: null
        };
    }

    init() {
        const mapOptions = {
            enableTempMarker: false,
            markerTypes: this.generateMarkerTypes(),
            markerSize: { width: 34, height: 40 },
            onMarkerClick: (marker, data) => this.showOffcanvas(data.address, data.items)
        };
        this.initMapManager(mapOptions);
        this.initOffcanvas();
        this.initGlightbox();
        this.loadData();
    }

    generateMarkerTypes() {
        const markerTypes = {};
        const wasteTypeColors = {
            '생활폐기물': '#666666', 
            '음식물': '#FF9800', 
            '재활용': '#00A6FB',
            '대형': '#DC2626', 
            '소각': '#FF5722'
        };

        this.config.WASTE_TYPES.forEach(type => {
            const key = `${type}_processed`;
            markerTypes[key] = MarkerFactory.createSVGIcon({
                color: wasteTypeColors[type] || '#666666',
                text: type[0],
                status: 'processed'
            });
        });

        return markerTypes;
    }

    initOffcanvas() {
        const offcanvasElement = document.getElementById('markerOffcanvas');
        if (offcanvasElement) {
            this.state.offcanvas = new bootstrap.Offcanvas(offcanvasElement);
        }
    }

    async loadData() {
        try {
            const response = await this.apiCall('get_processed_littering', {}, 'GET');
            
            if (response.success && Array.isArray(response.data)) {
                this.state.processedMarkers = response.data;
                this.groupDataByAddress();
                this.displayMarkersOnMap();
            } else {
                console.warn('처리된 데이터를 불러올 수 없습니다:', response.message);
            }
        } catch (error) {
            console.error('처리 완료 마커 로드 실패:', error);
        }
    }

    groupDataByAddress() {
        this.state.groupedData = this.state.processedMarkers.reduce((acc, item) => {
            const address = item.address || '주소 없음';
            if (!acc[address]) {
                acc[address] = [];
            }
            acc[address].push(item);
            return acc;
        }, {});
    }

    displayMarkersOnMap() {
        Object.entries(this.state.groupedData).forEach(([address, items]) => {
            const firstItem = items[0];
            const dominantType = this.getDominantWasteType(items);
            const markerTypeKey = `${dominantType}_processed`;

            this.state.mapManager.addMarker({
                position: { lat: firstItem.latitude, lng: firstItem.longitude },
                type: markerTypeKey,
                data: { address, items },
                onClick: (marker, markerData) => {
                    this.showOffcanvas(markerData.address, markerData.items);
                }
            });
        });
    }

    getDominantWasteType(items) {
        const typeCounts = items.reduce((acc, item) => {
            const type = item.waste_type || '생활폐기물';
            acc[type] = (acc[type] || 0) + 1;
            return acc;
        }, {});

        return Object.keys(typeCounts).reduce((a, b) => 
            typeCounts[a] > typeCounts[b] ? a : b
        );
    }

    showOffcanvas(address, items) {
        const offcanvasAddress = document.getElementById('offcanvasAddress');
        const processList = document.getElementById('processList');
        
        if (!offcanvasAddress || !processList) {
            console.error('Offcanvas 요소를 찾을 수 없습니다.');
            return;
        }

        offcanvasAddress.textContent = address;
        processList.innerHTML = '';

        items.sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at));

        items.forEach(item => {
            const itemElement = this.createProcessItemElement(item);
            processList.appendChild(itemElement);
        });

        if (this.state.offcanvas) {
            this.state.offcanvas.show();
        }
    }

    createProcessItemElement(item) {
        const correctedMap = { 'o': '개선', 'x': '미개선', '=': '없어짐' };
        const correctedClassMap = { 'o': 'bg-success', 'x': 'bg-danger', '=': 'bg-warning text-dark' };

        const itemDiv = document.createElement('div');
        itemDiv.className = 'card mb-3';
        
        const wasteTypeText = item.waste_type + (item.waste_type2 ? ` + ${item.waste_type2}` : '');
        const correctedStatus = correctedMap[item.corrected] || 'N/A';
        const correctedClass = correctedClassMap[item.corrected] || 'bg-secondary';

        itemDiv.innerHTML = `
            <div class="card-body">
                <div class.d-flex justify-content-between align-items-start mb-2">
                    <span class="badge bg-secondary fs-6">${wasteTypeText}</span>
                    <span class="badge ${correctedClass} fs-6">${correctedStatus}</span>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between small text-muted mb-2">
                    <span>배출일: ${this.formatDate(item.issue_date)}</span>
                    <span>수거일: ${this.formatDate(item.collect_date)}</span>
                </div>
                ${item.note ? `<p class="mt-2 mb-2 p-2 bg-light border rounded small">${item.note}</p>` : ''}
                <div class="d-flex gap-2 mt-2 flex-wrap">
                    ${this.generatePhotoElements(item)}
                </div>
                <div class="small text-muted mt-2">
                    처리 완료: ${this.formatDateTime(item.updated_at)}
                </div>
            </div>
        `;

        return itemDiv;
    }

    generatePhotoElements(item) {
        const photos = [];
        const basePath = this.config.UPLOADS_BASE_PATH;
        
        if (item.reg_photo_path) photos.push({ src: basePath + item.reg_photo_path, title: '작업전' });
        if (item.reg_photo_path2) photos.push({ src: basePath + item.reg_photo_path2, title: '작업후' });
        if (item.proc_photo_path) photos.push({ src: basePath + item.proc_photo_path, title: '처리' });

        return photos.map(photo => `
            <a href="${photo.src}" 
                class="gallery-lightbox" 
                data-gallery="gallery-${item.id}"
                title="${photo.title}">
                <img src="${photo.src}" 
                     alt="${photo.title}" 
                     class="process-item-photo rounded" 
                     style="width: 80px; height: 80px; object-fit: cover;">
                <small class="position-absolute bottom-0 start-0 bg-dark text-white px-1 py-0" style="font-size: 10px; border-bottom-right-radius: 0.25rem; border-top-right-radius: 0.25rem;">${photo.title}</small>
            </a>
        `).join('');
    }

    initGlightbox() {
        this.lightbox = GLightbox({
            selector: '.gallery-lightbox'
        });
    }

    formatDate(dateString) {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('ko-KR');
    }

    formatDateTime(dateTimeString) {
        if (!dateTimeString) return 'N/A';
        return new Date(dateTimeString).toLocaleString('ko-KR');
    }
}

// 전역 인스턴스 생성
const litteringHistoryApp = new LitteringHistoryApp();