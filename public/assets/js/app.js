!function() {
    // Global variables
    var navbarMenuHTML = document.querySelector(".navbar-menu").innerHTML,
        horizontalMenuSplit = 7;

    // Bootstrap collapse handler
    function isCollapseMenu() {
        if (document.querySelectorAll(".navbar-nav .collapse")) {
            var collapses = document.querySelectorAll(".navbar-nav .collapse");
            Array.from(collapses).forEach(function(collapse) {
                var bsCollapse = new bootstrap.Collapse(collapse, { toggle: false });
                
                collapse.addEventListener("show.bs.collapse", function(event) {
                    event.stopPropagation();
                    var parentCollapse = collapse.parentElement.closest(".collapse");
                    
                    if (parentCollapse) {
                        var siblingCollapses = parentCollapse.querySelectorAll(".collapse");
                        Array.from(siblingCollapses).forEach(function(sibling) {
                            var siblingInstance = bootstrap.Collapse.getInstance(sibling);
                            if (siblingInstance !== bsCollapse) siblingInstance.hide();
                        });
                    } else {
                        var siblings = getSiblings(collapse.parentElement);
                        Array.from(siblings).forEach(function(sibling) {
                            if (2 < sibling.childNodes.length) {
                                sibling.firstElementChild.setAttribute("aria-expanded", "false");
                            }
                            var childElements = sibling.querySelectorAll("*[id]");
                            Array.from(childElements).forEach(function(child) {
                                child.classList.remove("show");
                                if (2 < child.childNodes.length) {
                                    var links = child.querySelectorAll("ul li a");
                                    Array.from(links).forEach(function(link) {
                                        if (link.hasAttribute("aria-expanded")) {
                                            link.setAttribute("aria-expanded", "false");
                                        }
                                    });
                                }
                            });
                        });
                    }
                });

                collapse.addEventListener("hide.bs.collapse", function(event) {
                    event.stopPropagation();
                    var childCollapses = collapse.querySelectorAll(".collapse");
                    Array.from(childCollapses).forEach(function(child) {
                        var childInstance = bootstrap.Collapse.getInstance(child);
                        childInstance.hide();
                    });
                });
            });
        }

        // Helper function to get siblings
        function getSiblings(element) {
            var siblings = [];
            var sibling = element.parentNode.firstChild;
            while (sibling) {
                if (1 === sibling.nodeType && sibling !== element) {
                    siblings.push(sibling);
                }
                sibling = sibling.nextSibling;
            }
            return siblings;
        }
    }

    // Two column layout initializer
    function twoColumnMenuGenerate() {
        var layout = document.documentElement.getAttribute("data-layout");
        
        if ("twocolumn" != layout) {
            return;
        }

        if (document.querySelector(".navbar-menu")) {
            document.querySelector(".navbar-menu").innerHTML = navbarMenuHTML;
        }

        var iconView = document.createElement("ul");
        iconView.innerHTML = '<a href="#" class="logo"><img src="assets/images/logo-sm.png" alt="" height="22"></a>';
        
        Array.from(document.getElementById("navbar-nav").querySelectorAll(".menu-link")).forEach(function(link) {
            iconView.className = "twocolumn-iconview";
            var listItem = document.createElement("li");
            var clonedLink = link;
            
            clonedLink.querySelectorAll("span").forEach(function(span) {
                span.classList.add("d-none");
            });
            
            if (link.parentElement.classList.contains("twocolumn-item-show")) {
                link.classList.add("active");
            }
            
            listItem.appendChild(clonedLink);
            iconView.appendChild(listItem);
            
            if (clonedLink.classList.contains("nav-link")) {
                clonedLink.classList.replace("nav-link", "nav-icon");
            }
            clonedLink.classList.remove("collapsed", "menu-link");
        });

        // Set active menu based on current page
        var currentPage = "/" == location.pathname ? "index.html" : location.pathname.substring(1);
        currentPage = currentPage.substring(currentPage.lastIndexOf("/") + 1);
        
        if (currentPage) {
            var currentLink = document.getElementById("navbar-nav").querySelector('[href="' + currentPage + '"]');
            if (currentLink) {
                var menuDropdown = currentLink.closest(".collapse.menu-dropdown");
                if (menuDropdown) {
                    menuDropdown.classList.add("show");
                    menuDropdown.parentElement.children[0].classList.add("active");
                    menuDropdown.parentElement.children[0].setAttribute("aria-expanded", "true");
                    
                    var parentDropdown = menuDropdown.parentElement.closest(".collapse.menu-dropdown");
                    if (parentDropdown) {
                        parentDropdown.classList.add("show");
                        if (parentDropdown.previousElementSibling) {
                            parentDropdown.previousElementSibling.classList.add("active");
                        }
                        
                        var grandParentDropdown = parentDropdown.parentElement.parentElement.parentElement.parentElement.closest(".collapse.menu-dropdown");
                        if (grandParentDropdown) {
                            grandParentDropdown.classList.add("show");
                            if (grandParentDropdown.previousElementSibling) {
                                grandParentDropdown.previousElementSibling.classList.add("active");
                            }
                        }
                    }
                }
            }
            
            document.getElementById("two-column-menu").innerHTML = iconView.outerHTML;
            
            // Add event listeners to two column menu items
            Array.from(document.querySelector("#two-column-menu ul").querySelectorAll("li a")).forEach(function(menuLink) {
                var currentPath = "/" == location.pathname ? "index.html" : location.pathname.substring(1);
                currentPath = currentPath.substring(currentPath.lastIndexOf("/") + 1);
                
                menuLink.addEventListener("click", function(event) {
                    if (currentPath != "/" + menuLink.getAttribute("href") || menuLink.getAttribute("data-bs-toggle")) {
                        if (document.body.classList.contains("twocolumn-panel")) {
                            document.body.classList.remove("twocolumn-panel");
                        }
                    }
                    
                    document.getElementById("navbar-nav").classList.remove("twocolumn-nav-hide");
                    var hamburger = document.querySelector(".hamburger-icon");
                    if (hamburger) hamburger.classList.remove("open");
                    
                    if ((event.target && event.target.matches("a.nav-icon")) || 
                        (event.target && event.target.matches("i"))) {
                        
                        var activeIcon = document.querySelector("#two-column-menu ul .nav-icon.active");
                        if (activeIcon !== null) {
                            activeIcon.classList.remove("active");
                        }
                        
                        var targetLink = event.target.matches("i") ? event.target.closest("a") : event.target;
                        targetLink.classList.add("active");
                        
                        var activeItems = document.getElementsByClassName("twocolumn-item-show");
                        if (0 < activeItems.length) {
                            activeItems[0].classList.remove("twocolumn-item-show");
                        }
                        
                        var targetId = targetLink.getAttribute("href").slice(1);
                        var targetElement = document.getElementById(targetId);
                        if (targetElement) {
                            targetElement.parentElement.classList.add("twocolumn-item-show");
                        }
                    }
                });
                
                if (currentPath != "/" + menuLink.getAttribute("href") || menuLink.getAttribute("data-bs-toggle")) {
                    // Do nothing
                } else {
                    menuLink.classList.add("active");
                    document.getElementById("navbar-nav").classList.add("twocolumn-nav-hide");
                    var hamburger = document.querySelector(".hamburger-icon");
                    if (hamburger) hamburger.classList.add("open");
                }
            });
            
            // Initialize SimpleBar for scrolling
            if ("horizontal" !== document.documentElement.getAttribute("data-layout")) {
                var navbarScrollbar = new SimpleBar(document.getElementById("navbar-nav"));
                if (navbarScrollbar) navbarScrollbar.getContentElement();
                
                var iconScrollbar = new SimpleBar(document.getElementsByClassName("twocolumn-iconview")[0]);
                if (iconScrollbar) iconScrollbar.getContentElement();
            }
        }
    }

    // Element visibility checker
    function isElementInViewport(element) {
        if (element) {
            var top = element.offsetTop,
                left = element.offsetLeft,
                width = element.offsetWidth,
                height = element.offsetHeight;
                
            if (element.offsetParent) {
                while (element.offsetParent) {
                    element = element.offsetParent;
                    top += element.offsetTop;
                    left += element.offsetLeft;
                }
            }
            
            return top >= window.pageYOffset && 
                   left >= window.pageXOffset && 
                   top + height <= window.pageYOffset + window.innerHeight && 
                   left + width <= window.pageXOffset + window.innerWidth;
        }
    }

    // Layout manager - simplified to only handle basic layouts
    function initLeftMenuCollapse() {
        var layout = document.documentElement.getAttribute("data-layout");
        
        if ("vertical" == layout || "semibox" == layout) {
            document.getElementById("two-column-menu").innerHTML = "";
            if (document.querySelector(".navbar-menu")) {
                document.querySelector(".navbar-menu").innerHTML = navbarMenuHTML;
            }
            document.getElementById("scrollbar").setAttribute("data-simplebar", "");
            document.getElementById("navbar-nav").setAttribute("data-simplebar", "");
            document.getElementById("scrollbar").classList.add("h-100");
        }
        
        if ("twocolumn" == layout) {
            document.getElementById("scrollbar").removeAttribute("data-simplebar");
            document.getElementById("scrollbar").classList.remove("h-100");
        }
        
        if ("horizontal" == layout) {
            setupHorizontalLayout();
        }
    }

    // Responsive handler - simplified
    function windowResizeHover() {
        feather.replace();
        var clientWidth = document.documentElement.clientWidth;
        
        if (clientWidth < 1025 && 767 < clientWidth) {
            document.body.classList.remove("twocolumn-panel");
            if ("twocolumn" == document.documentElement.getAttribute("data-layout")) {
                document.documentElement.setAttribute("data-layout", "twocolumn");
                twoColumnMenuGenerate();
                u();
                isCollapseMenu();
            }
            if ("vertical" == document.documentElement.getAttribute("data-layout")) {
                document.documentElement.setAttribute("data-sidebar-size", "sm");
            }
            if ("semibox" == document.documentElement.getAttribute("data-layout")) {
                document.documentElement.setAttribute("data-sidebar-size", "sm");
            }
            var hamburger = document.querySelector(".hamburger-icon");
            if (hamburger) hamburger.classList.add("open");
        } else if (1025 <= clientWidth) {
            document.body.classList.remove("twocolumn-panel");
            if ("twocolumn" == document.documentElement.getAttribute("data-layout")) {
                document.documentElement.setAttribute("data-layout", "twocolumn");
                twoColumnMenuGenerate();
                u();
                isCollapseMenu();
            }
            if ("vertical" == document.documentElement.getAttribute("data-layout")) {
                document.documentElement.setAttribute("data-sidebar-size", "lg");
            }
            if ("semibox" == document.documentElement.getAttribute("data-layout")) {
                document.documentElement.setAttribute("data-sidebar-size", "lg");
            }
            var hamburger = document.querySelector(".hamburger-icon");
            if (hamburger) hamburger.classList.remove("open");
        } else if (clientWidth <= 767) {
            document.body.classList.remove("vertical-sidebar-enable");
            document.body.classList.add("twocolumn-panel");
            if ("twocolumn" == document.documentElement.getAttribute("data-layout")) {
                document.documentElement.setAttribute("data-layout", "vertical");
                isCollapseMenu();
            }
            if ("horizontal" != document.documentElement.getAttribute("data-layout")) {
                document.documentElement.setAttribute("data-sidebar-size", "lg");
            }
            var hamburger = document.querySelector(".hamburger-icon");
            if (hamburger) hamburger.classList.add("open");
        }

        // Handle navbar menu items
        var navItems = document.querySelectorAll("#navbar-nav > li.nav-item");
        Array.from(navItems).forEach(function(navItem) {
            navItem.addEventListener("click", handleNavClick.bind(this), false);
            navItem.addEventListener("mouseover", handleNavClick.bind(this), false);
        });
    }

    // Navigation click handler
    function handleNavClick(event) {
        if (event.target && event.target.matches("a.nav-link span")) {
            if (false == isElementInViewport(event.target.parentElement.nextElementSibling)) {
                event.target.parentElement.nextElementSibling.classList.add("dropdown-custom-right");
                event.target.parentElement.parentElement.parentElement.parentElement.classList.add("dropdown-custom-right");
                var dropdown = event.target.parentElement.nextElementSibling;
                Array.from(dropdown.querySelectorAll(".menu-dropdown")).forEach(function(submenu) {
                    submenu.classList.add("dropdown-custom-right");
                });
            } else if (true == isElementInViewport(event.target.parentElement.nextElementSibling) && 1848 <= window.innerWidth) {
                var customRights = document.getElementsByClassName("dropdown-custom-right");
                while (0 < customRights.length) {
                    customRights[0].classList.remove("dropdown-custom-right");
                }
            }
        }

        if (event.target && event.target.matches("a.nav-link")) {
            if (false == isElementInViewport(event.target.nextElementSibling)) {
                event.target.nextElementSibling.classList.add("dropdown-custom-right");
                event.target.parentElement.parentElement.parentElement.classList.add("dropdown-custom-right");
                var dropdown = event.target.nextElementSibling;
                Array.from(dropdown.querySelectorAll(".menu-dropdown")).forEach(function(submenu) {
                    submenu.classList.add("dropdown-custom-right");
                });
            } else if (true == isElementInViewport(event.target.nextElementSibling) && 1848 <= window.innerWidth) {
                var customRights = document.getElementsByClassName("dropdown-custom-right");
                while (0 < customRights.length) {
                    customRights[0].classList.remove("dropdown-custom-right");
                }
            }
        }
    }

    // Hamburger menu toggle
    function toggleHamburger() {
        var clientWidth = document.documentElement.clientWidth;
        if (767 < clientWidth) {
            var hamburger = document.querySelector(".hamburger-icon");
            if (hamburger) hamburger.classList.toggle("open");
        }

        var layout = document.documentElement.getAttribute("data-layout");
        
        if ("horizontal" === layout) {
            if (document.body.classList.contains("menu")) {
                document.body.classList.remove("menu");
            } else {
                document.body.classList.add("menu");
            }
        }

        if ("vertical" === layout) {
            if (clientWidth <= 1025 && 767 < clientWidth) {
                document.body.classList.remove("vertical-sidebar-enable");
                var sidebarSize = document.documentElement.getAttribute("data-sidebar-size");
                if ("sm" == sidebarSize) {
                    document.documentElement.setAttribute("data-sidebar-size", "");
                } else {
                    document.documentElement.setAttribute("data-sidebar-size", "sm");
                }
            } else if (1025 < clientWidth) {
                document.body.classList.remove("vertical-sidebar-enable");
                var sidebarSize = document.documentElement.getAttribute("data-sidebar-size");
                if ("lg" == sidebarSize) {
                    document.documentElement.setAttribute("data-sidebar-size", "sm");
                } else {
                    document.documentElement.setAttribute("data-sidebar-size", "lg");
                }
            } else if (clientWidth <= 767) {
                document.body.classList.add("vertical-sidebar-enable");
                document.documentElement.setAttribute("data-sidebar-size", "lg");
            }
        }

        if ("semibox" === layout) {
            if (767 < clientWidth) {
                var visibility = document.documentElement.getAttribute("data-sidebar-visibility");
                if ("show" == visibility) {
                    var sidebarSize = document.documentElement.getAttribute("data-sidebar-size");
                    if ("lg" == sidebarSize) {
                        document.documentElement.setAttribute("data-sidebar-size", "sm");
                    } else {
                        document.documentElement.setAttribute("data-sidebar-size", "lg");
                    }
                } else {
                    var showBtn = document.getElementById("sidebar-visibility-show");
                    if (showBtn) showBtn.click();
                    document.documentElement.setAttribute("data-sidebar-size", document.documentElement.getAttribute("data-sidebar-size"));
                }
            } else if (clientWidth <= 767) {
                document.body.classList.add("vertical-sidebar-enable");
                document.documentElement.setAttribute("data-sidebar-size", "lg");
            }
        }

        if ("twocolumn" == layout) {
            if (document.body.classList.contains("twocolumn-panel")) {
                document.body.classList.remove("twocolumn-panel");
            } else {
                document.body.classList.add("twocolumn-panel");
            }
        }
    }

    // Preloader configuration handler
    function initializePreloader() {
        var preloaderSetting = document.documentElement.getAttribute("data-preloader");
        var preloader = document.getElementById("preloader");
        if (preloaderSetting !== "enable" && preloader) {
            // Hide preloader immediately if not enabled
            document.documentElement.setAttribute("data-preloader", "disable");
        } else if (preloaderSetting === "enable" && preloader) {
            // Show preloader and handle window load event
            window.addEventListener("load", function() {			
               setTimeout(function() {
                   preloader.style.opacity = "0";
                   preloader.style.visibility = "hidden";
               }, 300);
            });
        }
    }

    // Main initialization function
    function initializeApp() {
        document.addEventListener("DOMContentLoaded", function() {
            initializePreloader();

            // Code switcher functionality
            var codeSwitchers = document.getElementsByClassName("code-switcher");
            Array.from(codeSwitchers).forEach(function(switcher) {
                switcher.addEventListener("change", function() {
                    var card = switcher.closest(".card");
                    var livePreview = card.querySelector(".live-preview");
                    var codeView = card.querySelector(".code-view");
                    
                    if (switcher.checked) {
                        livePreview.classList.add("d-none");
                        codeView.classList.remove("d-none");
                    } else {
                        livePreview.classList.remove("d-none");
                        codeView.classList.add("d-none");
                    }
                });
            });

            feather.replace();
        });

        // Window resize handler
        window.addEventListener("resize", windowResizeHover);
        windowResizeHover();

        // Initialize Waves effect
        Waves.init();

        // Scroll handler for topbar shadow
        document.addEventListener("scroll", function() {
            var topbar = document.getElementById("page-topbar");
            if (topbar) {
                if (50 <= document.body.scrollTop || 50 <= document.documentElement.scrollTop) {
                    topbar.classList.add("topbar-shadow");
                } else {
                    topbar.classList.remove("topbar-shadow");
                }
            }
        });

        // Window load handler
        window.addEventListener("load", function() {
            var layout = document.documentElement.getAttribute("data-layout");
            if ("twocolumn" == layout) {
                u();
            } else {
                g();
            }

            // Vertical overlay click handler
            var overlays = document.getElementsByClassName("vertical-overlay");
            if (overlays) {
                Array.from(overlays).forEach(function(overlay) {
                    overlay.addEventListener("click", function() {
                        document.body.classList.remove("vertical-sidebar-enable");
                        if ("twocolumn" == document.documentElement.getAttribute("data-layout")) {
                            document.body.classList.add("twocolumn-panel");
                        } else {
                            document.documentElement.setAttribute("data-sidebar-size", "lg");
                        }
                    });
                });
            }

            initializeVerticalHover();
        });

        // Hamburger icon click handler
        var hamburgerIcon = document.getElementById("topnav-hamburger-icon");
        if (hamburgerIcon) {
            hamburgerIcon.addEventListener("click", toggleHamburger);
        }

        // Two column menu mobile handler
        var clientWidth = document.documentElement.clientWidth;
        
        if ("twocolumn" == document.documentElement.getAttribute("data-layout") && clientWidth < 767) {
            var twoColumnItems = document.getElementById("two-column-menu").querySelectorAll("li");
            Array.from(twoColumnItems).forEach(function(item) {
                item.addEventListener("click", function(event) {
                    document.body.classList.remove("twocolumn-panel");
                });
            });
        }
    }

    // Two column active menu handler
    function u() {
        feather.replace();
        
        var currentPage = "/" == location.pathname ? "index.html" : location.pathname.substring(1);
        currentPage = currentPage.substring(currentPage.lastIndexOf("/") + 1);
        
        if (currentPage) {
            // Handle two column panel active state
            if ("twocolumn-panel" == document.body.className) {
                var twoColumnLink = document.getElementById("two-column-menu").querySelector('[href="' + currentPage + '"]');
                if (twoColumnLink) {
                    twoColumnLink.classList.add("active");
                }
            }

            var navLink = document.getElementById("navbar-nav").querySelector('[href="' + currentPage + '"]');
            if (navLink) {
                navLink.classList.add("active");
                var menuDropdown = navLink.closest(".collapse.menu-dropdown");
                var targetId;
                
                if (menuDropdown && menuDropdown.parentElement.closest(".collapse.menu-dropdown")) {
                    menuDropdown.classList.add("show");
                    menuDropdown.parentElement.children[0].classList.add("active");
                    menuDropdown.parentElement.closest(".collapse.menu-dropdown").parentElement.classList.add("twocolumn-item-show");
                    
                    var grandParentDropdown = menuDropdown.parentElement.parentElement.parentElement.parentElement.closest(".collapse.menu-dropdown");
                    if (grandParentDropdown) {
                        targetId = grandParentDropdown.getAttribute("id");
                        grandParentDropdown.parentElement.classList.add("twocolumn-item-show");
                        menuDropdown.parentElement.closest(".collapse.menu-dropdown").parentElement.classList.remove("twocolumn-item-show");
                        
                        var twoColumnTarget = document.getElementById("two-column-menu").querySelector('[href="#' + targetId + '"]');
                        if (twoColumnTarget) {
                            twoColumnTarget.classList.add("active");
                        }
                    }
                    targetId = menuDropdown.parentElement.closest(".collapse.menu-dropdown").getAttribute("id");
                } else {
                    if (navLink.closest(".collapse.menu-dropdown")) {
                        navLink.closest(".collapse.menu-dropdown").parentElement.classList.add("twocolumn-item-show");
                    }
                    targetId = menuDropdown.getAttribute("id");
                }
                
                var twoColumnTarget = document.getElementById("two-column-menu").querySelector('[href="#' + targetId + '"]');
                if (twoColumnTarget) {
                    twoColumnTarget.classList.add("active");
                }
            } else {
                document.body.classList.add("twocolumn-panel");
            }
        }
    }

    // Regular menu active state handler
    function g() {
        var currentUrl = location.href.split("?")[0].split("#")[0];
        if (currentUrl) {
            var navLink = document.getElementById("navbar-nav").querySelector('[href="' + currentUrl + '"]');
            if (navLink) {
                navLink.classList.add("active");
                var menuDropdown = navLink.closest(".collapse.menu-dropdown");

                if (menuDropdown) {
                    menuDropdown.classList.add("show");
                    menuDropdown.parentElement.children[0].classList.add("active");
                    menuDropdown.parentElement.children[0].setAttribute("aria-expanded", "true");

                    var parentDropdown = menuDropdown.parentElement.closest(".collapse.menu-dropdown");
                    if (parentDropdown) {
                        parentDropdown.classList.add("show");
                        if (parentDropdown.previousElementSibling) {
                            parentDropdown.previousElementSibling.classList.add("active");
                            parentDropdown.previousElementSibling.setAttribute("aria-expanded", "true");
                        }

                        var grandParentDropdown = parentDropdown.parentElement.parentElement.parentElement.parentElement.closest(".collapse.menu-dropdown");
                        if (grandParentDropdown) {
                            grandParentDropdown.classList.add("show");
                            if (grandParentDropdown.previousElementSibling) {
                                grandParentDropdown.previousElementSibling.classList.add("active");
                                grandParentDropdown.previousElementSibling.setAttribute("aria-expanded", "true");

                                // Handle horizontal layout deep nesting
                                if ("horizontal" == document.documentElement.getAttribute("data-layout")) {
                                    var deepestDropdown = grandParentDropdown.parentElement.parentElement.parentElement.parentElement.parentElement.parentElement.parentElement.closest(".collapse");
                                    if (deepestDropdown && deepestDropdown.previousElementSibling) {
                                        deepestDropdown.previousElementSibling.classList.add("active");
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // Counter animation
    function animateCounters() {
        var counters = document.querySelectorAll(".counter-value");
        
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        
        if (counters) {
            Array.from(counters).forEach(function(counter) {
                function updateCounter() {
                    var target = +counter.getAttribute("data-target");
                    var current = +counter.innerText;
                    var increment = target / 250;
                    
                    if (increment < 1) {
                        increment = 1;
                    }
                    
                    if (current < target) {
                        counter.innerText = (current + increment).toFixed(0);
                        setTimeout(updateCounter, 1);
                    } else {
                        counter.innerText = formatNumber(target);
                    }
                    
                    counter.innerText = formatNumber(counter.innerText);
                }
                updateCounter();
            });
        }
    }

    // Horizontal layout setup
    function setupHorizontalLayout() {
        document.getElementById("two-column-menu").innerHTML = "";
        if (document.querySelector(".navbar-menu")) {
            document.querySelector(".navbar-menu").innerHTML = navbarMenuHTML;
        }
        
        document.getElementById("scrollbar").removeAttribute("data-simplebar");
        document.getElementById("navbar-nav").removeAttribute("data-simplebar");
        document.getElementById("scrollbar").classList.remove("h-100");

        var maxItems = horizontalMenuSplit;
        var navItems = document.querySelectorAll("ul.navbar-nav > li.nav-item");
        var moreItemsHtml = "";
        var moreButton;

        Array.from(navItems).forEach(function(item, index) {
            if (index + 1 === maxItems) {
                moreButton = item;
            }
            if (maxItems < index + 1) {
                moreItemsHtml += item.outerHTML;
                item.remove();
            }
            if (index + 1 === navItems.length && moreButton.insertAdjacentHTML) {
                moreButton.insertAdjacentHTML("afterend", 
                    '<li class="nav-item">' +
                        '<a class="nav-link" href="#sidebarMore" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarMore">' +
                            '<i class="ri-briefcase-2-line"></i> <span data-key="t-more">More</span>' +
                        '</a>' +
                        '<div class="collapse menu-dropdown" id="sidebarMore">' +
                            '<ul class="nav nav-sm flex-column">' + moreItemsHtml + '</ul>' +
                        '</div>' +
                    '</li>'
                );
            }
        });
    }

    // Vertical hover initialization
    function initializeVerticalHover() {
        var verticalHover = document.getElementById("vertical-hover");
        if (verticalHover) {
            verticalHover.addEventListener("click", function() {
                var sidebarSize = document.documentElement.getAttribute("data-sidebar-size");
                if ("sm-hover" === sidebarSize) {
                    document.documentElement.setAttribute("data-sidebar-size", "sm-hover-active");
                } else {
                    document.documentElement.setAttribute("data-sidebar-size", "sm-hover");
                }
            });
        }
    }

    // Scroll to active menu
    function scrollToActiveMenu() {
        setTimeout(function() {
            var navbar = document.getElementById("navbar-nav");
            if (navbar) {
                var activeLink = navbar.querySelector(".nav-item .active");
                var offsetTop = activeLink ? activeLink.offsetTop : 0;
                
                if (300 < offsetTop) {
                    var appMenu = document.getElementsByClassName("app-menu");
                    var menuContainer = appMenu ? appMenu[0] : "";
                    
                    if (menuContainer && menuContainer.querySelector(".simplebar-content-wrapper")) {
                        setTimeout(function() {
                            var scrollTop = 330 == offsetTop ? offsetTop + 85 : offsetTop;
                            menuContainer.querySelector(".simplebar-content-wrapper").scrollTop = scrollTop;
                        }, 0);
                    }
                }
            }
        }, 250);
    }

    // Initialize SimpleBar scrollbars
    function initializeScrollbars() {
        var layout = document.documentElement.getAttribute("data-layout");
        if ("horizontal" !== layout) {
            var navbar = document.getElementById("navbar-nav");
            if (navbar) {
                var navbarScrollbar = new SimpleBar(navbar);
                if (navbarScrollbar) navbarScrollbar.getContentElement();
            }
            
            var iconView = document.getElementsByClassName("twocolumn-iconview")[0];
            if (iconView) {
                var iconScrollbar = new SimpleBar(iconView);
                if (iconScrollbar) iconScrollbar.getContentElement();
            }
            
            clearTimeout(resizeTimeout);
        }
    }

    var resizeTimeout;

    // Basic light/dark mode toggle (keeping only essential theme switching)
    function toggleLightDarkMode() {
        var htmlElement = document.getElementsByTagName("HTML")[0];
        if (htmlElement.hasAttribute("data-bs-theme") && "dark" == htmlElement.getAttribute("data-bs-theme")) {
            htmlElement.setAttribute("data-bs-theme", "light");
        } else {
            htmlElement.setAttribute("data-bs-theme", "dark");
        }
    }

    // Initialize two column layout
    twoColumnMenuGenerate();

    // Initialize fullscreen toggle
    var fullscreenButton = document.querySelector('[data-toggle="fullscreen"]');
    if (fullscreenButton) {
        fullscreenButton.addEventListener("click", function(event) {
            event.preventDefault();
            document.body.classList.toggle("fullscreen-enable");
            
            if (document.fullscreenElement || document.mozFullScreenElement || document.webkitFullscreenElement) {
                if (document.cancelFullScreen) {
                    document.cancelFullScreen();
                } else if (document.mozCancelFullScreen) {
                    document.mozCancelFullScreen();
                } else if (document.webkitCancelFullScreen) {
                    document.webkitCancelFullScreen();
                }
            } else {
                if (document.documentElement.requestFullscreen) {
                    document.documentElement.requestFullscreen();
                } else if (document.documentElement.mozRequestFullScreen) {
                    document.documentElement.mozRequestFullScreen();
                } else if (document.documentElement.webkitRequestFullscreen) {
                    document.documentElement.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
                }
            }
        });
    }

    // Fullscreen exit handler
    function handleFullscreenExit() {
        if (!document.webkitIsFullScreen && !document.mozFullScreen && !document.msFullscreenElement) {
            document.body.classList.remove("fullscreen-enable");
        }
    }

    // Add fullscreen event listeners
    document.addEventListener("fullscreenchange", handleFullscreenExit);
    document.addEventListener("webkitfullscreenchange", handleFullscreenExit);
    document.addEventListener("mozfullscreenchange", handleFullscreenExit);

    // Initialize light/dark mode toggle
    var lightDarkButtons = document.querySelectorAll(".light-dark-mode");
    if (lightDarkButtons && lightDarkButtons.length) {
        lightDarkButtons[0].addEventListener("click", function(event) {
            toggleLightDarkMode();
        });
    }

    // Initialize main application
    initializeApp();
    animateCounters();
    initLeftMenuCollapse();

    // Initialize tooltips and popovers
    var tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    Array.from(tooltipElements).map(function(element) {
        return new bootstrap.Tooltip(element);
    });

    var popoverElements = document.querySelectorAll('[data-bs-toggle="popover"]');
    Array.from(popoverElements).map(function(element) {
        return new bootstrap.Popover(element);
    });

    // Initialize toast notifications
    var toastElements = document.querySelectorAll("[data-toast]");
    Array.from(toastElements).forEach(function(toastElement) {
        toastElement.addEventListener("click", function() {
            var toastConfig = {};
            var attributes = toastElement.attributes;

            if (attributes["data-toast-text"]) {
                toastConfig.text = attributes["data-toast-text"].value.toString();
            }
            if (attributes["data-toast-gravity"]) {
                toastConfig.gravity = attributes["data-toast-gravity"].value.toString();
            }
            if (attributes["data-toast-position"]) {
                toastConfig.position = attributes["data-toast-position"].value.toString();
            }
            if (attributes["data-toast-className"]) {
                toastConfig.className = attributes["data-toast-className"].value.toString();
            }
            if (attributes["data-toast-duration"]) {
                toastConfig.duration = attributes["data-toast-duration"].value.toString();
            }
            if (attributes["data-toast-close"]) {
                toastConfig.close = attributes["data-toast-close"].value.toString();
            }
            if (attributes["data-toast-style"]) {
                toastConfig.style = attributes["data-toast-style"].value.toString();
            }
            if (attributes["data-toast-offset"]) {
                toastConfig.offset = attributes["data-toast-offset"];
            }

            Toastify({
                newWindow: true,
                text: toastConfig.text,
                gravity: toastConfig.gravity,
                position: toastConfig.position,
                className: "bg-" + toastConfig.className,
                stopOnFocus: true,
                offset: {
                    x: toastConfig.offset ? 50 : 0,
                    y: toastConfig.offset ? 10 : 0
                },
                duration: toastConfig.duration,
                close: "close" == toastConfig.close,
                style: "style" == toastConfig.style ? {
                    background: "linear-gradient(to right, var(--vz-success), var(--vz-primary))"
                } : ""
            }).showToast();
        });
    });

    // Initialize Choices.js
    var choicesElements = document.querySelectorAll("[data-choices]");
    Array.from(choicesElements).forEach(function(element) {
        var choicesConfig = {};
        var attributes = element.attributes;

        if (attributes["data-choices-groups"]) {
            choicesConfig.placeholderValue = "This is a placeholder set in the config";
        }
        if (attributes["data-choices-search-false"]) {
            choicesConfig.searchEnabled = false;
        }
        if (attributes["data-choices-search-true"]) {
            choicesConfig.searchEnabled = true;
        }
        if (attributes["data-choices-removeItem"]) {
            choicesConfig.removeItemButton = true;
        }
        if (attributes["data-choices-sorting-false"]) {
            choicesConfig.shouldSort = false;
        }
        if (attributes["data-choices-sorting-true"]) {
            choicesConfig.shouldSort = true;
        }
        if (attributes["data-choices-multiple-remove"]) {
            choicesConfig.removeItemButton = true;
        }
        if (attributes["data-choices-limit"]) {
            choicesConfig.maxItemCount = attributes["data-choices-limit"].value.toString();
        }
        if (attributes["data-choices-editItem-true"]) {
            choicesConfig.editItems = true;
        }
        if (attributes["data-choices-editItem-false"]) {
            choicesConfig.editItems = false;
        }
        if (attributes["data-choices-text-unique-true"]) {
            choicesConfig.duplicateItemsAllowed = false;
        }
        if (attributes["data-choices-text-disabled-true"]) {
            choicesConfig.addItems = false;
        }

        if (attributes["data-choices-text-disabled-true"]) {
            new Choices(element, choicesConfig).disable();
        } else {
            new Choices(element, choicesConfig);
        }
    });

    // Initialize date/time pickers
    var dateTimeElements = document.querySelectorAll("[data-provider]");
    Array.from(dateTimeElements).forEach(function(element) {
        var provider = element.getAttribute("data-provider");
        
        if ("flatpickr" == provider) {
            var flatpickrConfig = {};
            var attributes = element.attributes;
            
            flatpickrConfig.disableMobile = "true";
            
            if (attributes["data-date-format"]) {
                flatpickrConfig.dateFormat = attributes["data-date-format"].value.toString();
            }
            if (attributes["data-enable-time"]) {
                flatpickrConfig.enableTime = true;
                flatpickrConfig.dateFormat = attributes["data-date-format"].value.toString() + " H:i";
            }
            if (attributes["data-altFormat"]) {
                flatpickrConfig.altInput = true;
                flatpickrConfig.altFormat = attributes["data-altFormat"].value.toString();
            }
            if (attributes["data-minDate"]) {
                flatpickrConfig.minDate = attributes["data-minDate"].value.toString();
                flatpickrConfig.dateFormat = attributes["data-date-format"].value.toString();
            }
            if (attributes["data-maxDate"]) {
                flatpickrConfig.maxDate = attributes["data-maxDate"].value.toString();
                flatpickrConfig.dateFormat = attributes["data-date-format"].value.toString();
            }
            if (attributes["data-deafult-date"]) {
                flatpickrConfig.defaultDate = attributes["data-deafult-date"].value.toString();
                flatpickrConfig.dateFormat = attributes["data-date-format"].value.toString();
            }
            if (attributes["data-multiple-date"]) {
                flatpickrConfig.mode = "multiple";
                flatpickrConfig.dateFormat = attributes["data-date-format"].value.toString();
            }
            if (attributes["data-range-date"]) {
                flatpickrConfig.mode = "range";
                flatpickrConfig.dateFormat = attributes["data-date-format"].value.toString();
            }
            if (attributes["data-inline-date"]) {
                flatpickrConfig.inline = true;
                flatpickrConfig.defaultDate = attributes["data-deafult-date"].value.toString();
                flatpickrConfig.dateFormat = attributes["data-date-format"].value.toString();
            }
            if (attributes["data-disable-date"]) {
                var disableDates = [];
                disableDates.push(attributes["data-disable-date"].value);
                flatpickrConfig.disable = disableDates.toString().split(",");
            }
            if (attributes["data-week-number"]) {
                var weekNumbers = [];
                weekNumbers.push(attributes["data-week-number"].value);
                flatpickrConfig.weekNumbers = true;
            }
            
            flatpickr(element, flatpickrConfig);
            
        } else if ("timepickr" == provider) {
            var timepickrConfig = {};
            var attributes = element.attributes;
            
            if (attributes["data-time-basic"]) {
                timepickrConfig.enableTime = true;
                timepickrConfig.noCalendar = true;
                timepickrConfig.dateFormat = "H:i";
            }
            if (attributes["data-time-hrs"]) {
                timepickrConfig.enableTime = true;
                timepickrConfig.noCalendar = true;
                timepickrConfig.dateFormat = "H:i";
                timepickrConfig.time_24hr = true;
            }
            if (attributes["data-min-time"]) {
                timepickrConfig.enableTime = true;
                timepickrConfig.noCalendar = true;
                timepickrConfig.dateFormat = "H:i";
                timepickrConfig.minTime = attributes["data-min-time"].value.toString();
            }
            if (attributes["data-max-time"]) {
                timepickrConfig.enableTime = true;
                timepickrConfig.noCalendar = true;
                timepickrConfig.dateFormat = "H:i";
                timepickrConfig.maxTime = attributes["data-max-time"].value.toString();
            }
            if (attributes["data-default-time"]) {
                timepickrConfig.enableTime = true;
                timepickrConfig.noCalendar = true;
                timepickrConfig.dateFormat = "H:i";
                timepickrConfig.defaultDate = attributes["data-default-time"].value.toString();
            }
            if (attributes["data-time-inline"]) {
                timepickrConfig.enableTime = true;
                timepickrConfig.noCalendar = true;
                timepickrConfig.defaultDate = attributes["data-time-inline"].value.toString();
                timepickrConfig.inline = true;
            }
            
            flatpickr(element, timepickrConfig);
        }
    });

    // Initialize dropdown tab toggles
    var dropdownTabs = document.querySelectorAll('.dropdown-menu a[data-bs-toggle="tab"]');
    Array.from(dropdownTabs).forEach(function(tab) {
        tab.addEventListener("click", function(event) {
            event.stopPropagation();
            bootstrap.Tab.getInstance(event.target).show();
        });
    });

    // Initialize collapse handlers and scroll to active menu
    isCollapseMenu();
    scrollToActiveMenu();

    // Handle window resize for scrollbars
    window.addEventListener("resize", function() {
        if (resizeTimeout) {
            clearTimeout(resizeTimeout);
        }
        resizeTimeout = setTimeout(initializeScrollbars, 2000);
    });

}();

// Back to top functionality
var backToTopButton = document.getElementById("back-to-top");

function handleScroll() {
    if (100 < document.body.scrollTop || 100 < document.documentElement.scrollTop) {
        backToTopButton.style.display = "block";
    } else {
        backToTopButton.style.display = "none";
    }
}

function scrollToTop() {
    document.body.scrollTop = 0;
    document.documentElement.scrollTop = 0;
}

if (backToTopButton) {
    window.onscroll = function() {
        handleScroll();
    };
}