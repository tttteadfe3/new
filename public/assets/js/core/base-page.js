/**
 * BasePage - A base class for page-specific application logic.
 *
 * Key Features:
 * - Manages common configurations and state.
 * - Provides an application initialization flow (initializeApp).
 * - Instantiates ApiService for consistent API calls.
 * - Provides utility methods for UI state (e.g., button loading).
 * - Handles resource cleanup on page unload (cleanup).
 */
class BasePage {
    constructor(config = {}) {
        // Merge base config with child class config
        this.config = { ...config };
        this.state = {};
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
     * The default order is: set up event listeners, then load data.
     */
    initializeApp() {
        this.setupEventListeners();
        this.loadInitialData();
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
     * Cleans up application resources to prevent memory leaks.
     */
    cleanup() {
        console.log(`Cleaning up ${this.constructor.name}...`);
        // Common cleanup tasks can be added here.
    }
}