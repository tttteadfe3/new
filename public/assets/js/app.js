!function() {
    var d = document.querySelector(".navbar-menu").innerHTML;
    
    // 네비게이션 Collapse 관리
    function initNavbarCollapse() {
        var collapseElements = document.querySelectorAll(".navbar-nav .collapse");
        if (!collapseElements) return;
        
        Array.from(collapseElements).forEach(function(element) {
            var collapseInstance = new bootstrap.Collapse(element, { toggle: false });
            
            element.addEventListener("show.bs.collapse", function(e) {
                e.stopPropagation();
                var parentCollapse = element.parentElement.closest(".collapse");
                
                if (parentCollapse) {
                    var childCollapses = parentCollapse.querySelectorAll(".collapse");
                    Array.from(childCollapses).forEach(function(child) {
                        var childInstance = bootstrap.Collapse.getInstance(child);
                        if (childInstance !== collapseInstance) {
                            childInstance.hide();
                        }
                    });
                } else {
                    var siblings = getSiblings(element.parentElement);
                    Array.from(siblings).forEach(function(sibling) {
                        if (sibling.childNodes.length > 2) {
                            sibling.firstElementChild.setAttribute("aria-expanded", "false");
                        }
                        var siblingCollapses = sibling.querySelectorAll("*[id]");
                        Array.from(siblingCollapses).forEach(function(collapse) {
                            collapse.classList.remove("show");
                        });
                    });
                }
            });
            
            element.addEventListener("hide.bs.collapse", function(e) {
                e.stopPropagation();
                var childCollapses = element.querySelectorAll(".collapse");
                Array.from(childCollapses).forEach(function(child) {
                    var childInstance = bootstrap.Collapse.getInstance(child);
                    if (childInstance) childInstance.hide();
                });
            });
        });
    }
    
    // 형제 요소 가져오기
    function getSiblings(element) {
        var siblings = [];
        var sibling = element.parentNode.firstChild;
        while (sibling) {
            if (sibling.nodeType === 1 && sibling !== element) {
                siblings.push(sibling);
            }
            sibling = sibling.nextSibling;
        }
        return siblings;
    }
    
    // 레이아웃 초기화
    function initLayout() {
        var layout = document.documentElement.getAttribute("data-layout");
        
        if (layout === "vertical") {
            setupVerticalLayout();
        }
        
        if (layout === "horizontal") {
            document.getElementById("scrollbar").removeAttribute("data-simplebar");
            document.getElementById("scrollbar").classList.remove("h-100");
        }
        
        if (layout !== "horizontal") {
            document.getElementById("scrollbar").setAttribute("data-simplebar", "");
            document.getElementById("navbar-nav").setAttribute("data-simplebar", "");
            document.getElementById("scrollbar").classList.add("h-100");
        }
    }
    
    // Vertical 레이아웃 설정
    function setupVerticalLayout() {
        document.querySelector(".navbar-menu").innerHTML = d;
        document.getElementById("scrollbar").setAttribute("data-simplebar", "");
        document.getElementById("navbar-nav").setAttribute("data-simplebar", "");
        document.getElementById("scrollbar").classList.add("h-100");
    }
    
    // 반응형 처리
    function handleResponsive() {
        feather.replace();
        var windowWidth = document.documentElement.clientWidth;
        
        if (windowWidth >= 1025) {
            document.body.classList.remove("vertical-sidebar-enable");
            document.querySelector(".hamburger-icon")?.classList.remove("open");
        } else if (windowWidth <= 767) {
            document.body.classList.remove("vertical-sidebar-enable");
            document.documentElement.setAttribute("data-sidebar-size", "lg");
            document.querySelector(".hamburger-icon")?.classList.add("open");
        }
    }
    
    // 햄버거 메뉴 토글
    function toggleHamburger() {
        var windowWidth = document.documentElement.clientWidth;
        
        if (windowWidth > 767) {
            document.querySelector(".hamburger-icon")?.classList.toggle("open");
        }
        
        var layout = document.documentElement.getAttribute("data-layout");
        
        if (layout === "vertical") {
            if (windowWidth > 1025) {
                document.body.classList.remove("vertical-sidebar-enable");
                var currentSize = document.documentElement.getAttribute("data-sidebar-size");
                if (currentSize === "lg") {
                    document.documentElement.setAttribute("data-sidebar-size", "sm");
                } else {
                    document.documentElement.setAttribute("data-sidebar-size", "lg");
                }
            } else if (windowWidth <= 767) {
                document.body.classList.add("vertical-sidebar-enable");
                document.documentElement.setAttribute("data-sidebar-size", "lg");
            }
        }
    }
    
    // 활성 메뉴 하이라이트
    function highlightActiveMenu() {
        var currentPath = location.pathname + location.search; // 경로 + 쿼리 파라미터
        
        console.log("현재 경로 (쿼리 포함):", currentPath);
        
        // 루트 경로 처리
        if (currentPath === "/" || currentPath === "") {
            currentPath = "index.html";
        }
        
        if (!currentPath) return;
        
        // 정확히 일치하는 링크 찾기 (쿼리 파라미터 포함)
        var activeLink = document.getElementById("navbar-nav")?.querySelector('[href="' + currentPath + '"]');
        
        console.log("매칭된 링크:", activeLink);
        
        if (!activeLink) {
            console.log("활성 링크를 찾을 수 없음:", currentPath);
            return;
        }
        
        console.log("최종 활성 링크:", activeLink);
        
        activeLink.classList.add("active");
        
        var parentCollapse = activeLink.closest(".collapse.menu-dropdown");
        if (parentCollapse) {
            parentCollapse.classList.add("show");
            parentCollapse.parentElement.children[0].classList.add("active");
            parentCollapse.parentElement.children[0].setAttribute("aria-expanded", "true");
            
            var grandParentCollapse = parentCollapse.parentElement.closest(".collapse.menu-dropdown");
            if (grandParentCollapse) {
                grandParentCollapse.classList.add("show");
                if (grandParentCollapse.previousElementSibling) {
                    grandParentCollapse.previousElementSibling.classList.add("active");
                }
            }
        }
    }
    
    // 전체화면 토글
    function toggleFullscreen() {
        document.body.classList.toggle("fullscreen-enable");
        
        if (!document.fullscreenElement && !document.mozFullScreenElement && !document.webkitFullscreenElement) {
            if (document.documentElement.requestFullscreen) {
                document.documentElement.requestFullscreen();
            } else if (document.documentElement.mozRequestFullScreen) {
                document.documentElement.mozRequestFullScreen();
            } else if (document.documentElement.webkitRequestFullscreen) {
                document.documentElement.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
            }
        } else {
            if (document.cancelFullScreen) {
                document.cancelFullScreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.webkitCancelFullScreen) {
                document.webkitCancelFullScreen();
            }
        }
    }
    
    function exitFullscreen() {
        if (!document.webkitIsFullScreen && !document.mozFullScreen && !document.msFullscreenElement) {
            document.body.classList.remove("fullscreen-enable");
        }
    }
    
    // 라이트/다크 모드 토글
    function toggleColorMode() {
        var html = document.getElementsByTagName("HTML")[0];
        
        if (html.hasAttribute("data-bs-theme") && html.getAttribute("data-bs-theme") === "dark") {
            html.setAttribute("data-bs-theme", "light");
        } else {
            html.setAttribute("data-bs-theme", "dark");
        }
    }
    
    // Back to top 버튼
    function initBackToTop() {
        var backToTopBtn = document.getElementById("back-to-top");
        if (!backToTopBtn) return;
        
        window.onscroll = function() {
            if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
                backToTopBtn.style.display = "block";
            } else {
                backToTopBtn.style.display = "none";
            }
        };
        
        backToTopBtn.onclick = function() {
            document.body.scrollTop = 0;
            document.documentElement.scrollTop = 0;
        };
    }
    
    // Topbar shadow on scroll
    function initTopbarShadow() {
        document.addEventListener("scroll", function() {
            var topbar = document.getElementById("page-topbar");
            if (!topbar) return;
            
            if (document.body.scrollTop >= 50 || document.documentElement.scrollTop >= 50) {
                topbar.classList.add("topbar-shadow");
            } else {
                topbar.classList.remove("topbar-shadow");
            }
        });
    }
    
    // Vertical overlay 클릭 처리
    function initVerticalOverlay() {
        var overlays = document.getElementsByClassName("vertical-overlay");
        if (!overlays) return;
        
        Array.from(overlays).forEach(function(overlay) {
            overlay.addEventListener("click", function() {
                document.body.classList.remove("vertical-sidebar-enable");
            });
        });
    }
    
    // Tooltip 초기화
    function initTooltips() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Popover 초기화
    function initPopovers() {
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function(popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
    }
    
    // 프리로더
    function initPreloader() {
        var preloaderAttr = document.documentElement.getAttribute("data-preloader");
        if (preloaderAttr === "enable") {
            var preloader = document.getElementById("preloader");
            if (preloader) {
                if (document.readyState === "complete") {
                    // 이미 로드 완료된 경우
                    preloader.style.opacity = "0";
                    preloader.style.visibility = "hidden";
                } else {
                    // 로드 대기 중
                    window.addEventListener("load", function() {
                        preloader.style.opacity = "0";
                        preloader.style.visibility = "hidden";
                    });
                }
            }
        }
    }
    

    
    // 초기화
    function init() {
        // 레이아웃 초기화
        initLayout();
        
        // 프리로더 초기화
        initPreloader();
        
        // 네비게이션 초기화
        initNavbarCollapse();
        
        // 활성 메뉴 하이라이트
        highlightActiveMenu();
        
        // 반응형 처리
        handleResponsive();
        window.addEventListener("resize", handleResponsive);
        
        // Topbar shadow
        initTopbarShadow();
        
        // Vertical overlay
        initVerticalOverlay();
        
        // 햄버거 메뉴
        var hamburgerBtn = document.getElementById("topnav-hamburger-icon");
        if (hamburgerBtn) {
            hamburgerBtn.addEventListener("click", toggleHamburger);
        }
        
        // 전체화면 버튼
        var fullscreenBtn = document.querySelector('[data-toggle="fullscreen"]');
        if (fullscreenBtn) {
            fullscreenBtn.addEventListener("click", function(e) {
                e.preventDefault();
                toggleFullscreen();
            });
        }
        
        // 전체화면 종료 이벤트
        document.addEventListener("fullscreenchange", exitFullscreen);
        document.addEventListener("webkitfullscreenchange", exitFullscreen);
        document.addEventListener("mozfullscreenchange", exitFullscreen);
        
        // 라이트/다크 모드 토글
        var colorModeToggles = document.querySelectorAll(".light-dark-mode");
        if (colorModeToggles.length) {
            colorModeToggles[0].addEventListener("click", toggleColorMode);
        }
        
        // Tooltip & Popover
        initTooltips();
        initPopovers();
        
        // Back to top
        initBackToTop();
        
        // Feather icons
        feather.replace();
        
        // Waves effect
        Waves.init();
    }
    
    // DOMContentLoaded 이벤트
    document.addEventListener("DOMContentLoaded", function() {
        feather.replace();
    });
    
    // Load 이벤트
    window.addEventListener("load", function() {
        init();
    });
    
}();
