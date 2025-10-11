<?php
// Include sidebar menu functions
include_once __DIR__ . '/sidebar_menu_function.php';
?>

<!-- ========== App Menu ========== -->
<div class="app-menu navbar-menu">
    <!-- LOGO -->
    <div class="navbar-brand-box">
        <!-- Dark Logo-->
        <a href="<?= BASE_ASSETS_URL ?>" class="logo logo-dark">
            <span class="logo-sm">
                <img src="<?= BASE_ASSETS_URL ?>/assets/images/logo-sm.png" alt="" height="22">
            </span>
            <span class="logo-lg">
                <img src="<?= BASE_ASSETS_URL ?>/assets/images/logo-dark.png" alt="" height="17">
            </span>
        </a>
        <!-- Light Logo-->
        <a href="<?= BASE_ASSETS_URL ?>" class="logo logo-light">
            <span class="logo-sm">
                <img src="<?= BASE_ASSETS_URL ?>/assets/images/logo-sm.png" alt="" height="22">
            </span>
            <span class="logo-lg">
                <img src="<?= BASE_ASSETS_URL ?>/assets/images/logo-light.png" alt="" height="17">
            </span>
        </a>
        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">
            <div id="two-column-menu">
            </div>
            <ul class="navbar-nav" id="navbar-nav">
                <?php renderBootstrapMenuItemsAdvanced($allMenus ?? []); ?>
            </ul>
        </div>
    </div>

    <div class="sidebar-background"></div>
</div>
<!-- Left Sidebar End -->