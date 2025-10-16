if (document.querySelectorAll("[toast-list], [data-choices], [data-provider]").length > 0) {
    const scripts = [
        'assets/libs/choices.js/public/assets/scripts/choices.min.js',
        'assets/libs/flatpickr/flatpickr.min.js'
    ];
    
    scripts.forEach(src => {
        const script = document.createElement('script');
        script.src = src;
        document.head.appendChild(script);
    });
}