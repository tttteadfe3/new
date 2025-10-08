// js/main.js

window.addEventListener('DOMContentLoaded', event => {
    // 사이드바 토글 기능
    const sidebarToggle = document.body.querySelector('#sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', event => {
            event.preventDefault();
            document.body.classList.toggle('sb-sidenav-toggled');
            // 로컬 스토리지에 상태 저장 (선택사항)
            localStorage.setItem('sb|sidebar-toggle', document.body.classList.contains('sb-sidenav-toggled'));
        });
    }

    // 페이지 로드 시 사이드바 상태 복원 (선택사항)
    if (localStorage.getItem('sb|sidebar-toggle') === 'true') {
        document.body.classList.toggle('sb-sidenav-toggled');
    }
});