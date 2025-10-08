<?php
// layouts/sidebar.php

// The logic to calculate $sideMenuItems has been moved to config/config.php
// to be available globally before any view is rendered.

include ROOT_PATH . "/layouts/sidebar_menu_function.php";
?>
            <div id="scrollbar">
                <div class="container-fluid">
                    <div id="two-column-menu">
                    </div>
                    <ul class="navbar-nav" id="navbar-nav">
                        <?php renderBootstrapMenuItemsAdvanced($sideMenuItems ?? []); ?>
                    </ul>
                </div>