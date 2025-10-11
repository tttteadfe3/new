/**
 * MapService - Manages the interactive map component.
 *
 * This service is responsible for initializing the map, handling map-related
 * state, and cleaning up resources. It should be instantiated only on pages
 * that require a map.
 */
class MapService {
    /**
     * @param {object} config - Configuration for the map.
     * @param {string} config.mapId - The ID of the map container element.
     * @param {object} [config.center] - The initial center of the map.
     * @param {number} [config.level] - The initial zoom level of the map.
     */
    constructor(config = {}) {
        const defaults = {
            mapId: 'map',
            center: { lat: 37.340187, lng: 126.743888 },
            level: 3,
            allowedRegions: window.AppConfig?.allowedRegions || ['정왕1동'] // Use global config or hardcoded default
        };

        this.config = {
            ...defaults,
            ...config
        };

        this.mapManager = null;
        this.initialize();
    }

    /**
     * Initializes the InteractiveMapManager if the map container exists.
     */
    initialize() {
        const mapContainer = document.getElementById(this.config.mapId);
        if (!mapContainer) {
            console.warn(`Map container #${this.config.mapId} not found. MapService will not be initialized.`);
            return;
        }

        this.mapManager = new InteractiveMap(this.config);
    }

    /**
     * Cleans up the map resources to prevent memory leaks.
     * This should be called when the page is unloaded.
     */
    destroy() {
        if (this.mapManager && typeof this.mapManager.destroy === 'function') {
            this.mapManager.destroy();
            this.mapManager = null;
            console.log('MapService destroyed.');
        }
    }

    // Add other map-specific methods here if needed
    // e.g., getMapInstance(), addMarker(), etc.
}
