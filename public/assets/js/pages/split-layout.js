const SplitLayout = {
    init: function() {
        this.bindEvents();
    },
    bindEvents: function() {
        // Handle the click on the remove button to hide the right panel
        $(document).on('click', '.split-layout-remove', () => {
            this.hide();
        });
    },
    show: function() {
        $('.split-layout-right-content').addClass('split-layout-right-content-show');
    },
    hide: function() {
        $('.split-layout-right-content').removeClass('split-layout-right-content-show');
    }
};

// Initialize the split layout functionality when the document is ready
$(document).ready(function() {
    SplitLayout.init();
});
