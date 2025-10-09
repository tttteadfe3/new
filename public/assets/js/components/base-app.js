/**
 * BaseApp - A base class for common application logic.
 *
 * Key Features:
 * - Manages common configurations and state.
 * - Provides an application initialization flow (initializeApp).
 * - Instantiates ApiService for consistent API calls.
 * - Instantiates InteractiveMapManager if a map container is present.
 * - Provides utility methods for UI state (e.g., button loading).
 * - Handles resource cleanup on page unload (cleanup).
 */
class BaseApp {
    constructor(config = {}) {
        // Merge base config with child class config
        this.config = {
            mapId: 'map',
            ...config
        };

        this.state = {
            mapManager: null
        };

        // Instantiate the ApiService for this app instance
        this.apiService = new ApiService();

        // Run initialization after the DOM is fully loaded
        document.addEventListener('DOMContentLoaded', () => {
            this.initializeApp();

            // Cleanup resources when the page is about to be unloaded
            window.addEventListener('beforeunload', () => {
                this.cleanup();
            });
        });
    }

    /**
     * Initializes the application. This can be extended by child classes.
     * The default order is: initialize components, set up event listeners, then load data.
     */
    initializeApp() {
        this.initializeMapManager();
        this.setupEventListeners();
        this.loadInitialData();
    }

    /**
     * Initializes InteractiveMapManager if a map container element exists on the page.
     * @param {object} options - Options to override the default map settings.
     */
    initializeMapManager(options = {}) {
        const mapContainer = document.getElementById(this.config.mapId);
        if (!mapContainer) {
            // If there's no map container on the page, do not proceed.
            return;
        }

        const defaultMapOptions = {
            mapId: this.config.mapId,
            center: { lat: 37.340187, lng: 126.743888 },
            level: 3,
            allowedRegions: this.config.ALLOWED_REGIONS || []
        };

        this.state.mapManager = new InteractiveMapManager({
            ...defaultMapOptions,
            ...options // Override with options from child class
        });
    }

    /**
     * Binds event listeners. This is a placeholder to be implemented by child classes.
     */
    setupEventListeners() {
        // This method should be overridden in child classes to bind specific events.
    }

    /**
     * Loads initial data. This is a placeholder to be implemented by child classes.
     */
    loadInitialData() {
        // This method should be overridden in child classes to load initial data.
    }

    /**
     * Makes an API call using the ApiService instance.
     * @param {string} endpoint - The API endpoint (e.g., '/users').
     * @param {object} options - Options for the fetch() call (method, body, etc.).
     * @returns {Promise<any>}
     */
    apiCall(endpoint, options = {}) {
        // Use the instance of ApiService, ensuring all calls are standardized.
        return this.apiService.request(endpoint, options);
    }

    /**
     * Sets a button to its loading state.
     * @param {string} selector - The CSS selector for the button.
     * @param {string} text - The text to display while loading.
     */
    setButtonLoading(selector, text = '로딩 중...') {
        const btn = document.querySelector(selector);
        if (!btn) return;
        btn.dataset.originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<i class="spinner-border spinner-border-sm me-1"></i>${text}`;
    }

    /**
     * Resets a button from its loading state.
     * @param {string} selector - The CSS selector for the button.
     * @param {string} [originalText] - Optional text to restore. If not provided, it uses the stored original text.
     */
    resetButtonLoading(selector, originalText) {
        const btn = document.querySelector(selector);
        if (!btn) return;
        btn.disabled = false;
        btn.innerHTML = originalText || btn.dataset.originalText;
    }

    /**
     * Cleans up application resources, like the map manager, to prevent memory leaks.
     */
    cleanup() {
        console.log(`Cleaning up ${this.constructor.name}...`);
        if (this.state.mapManager && typeof this.state.mapManager.destroy === 'function') {
            this.state.mapManager.destroy();
            this.state.mapManager = null;
        }
        // Additional common cleanup tasks can be added here.
    }
}