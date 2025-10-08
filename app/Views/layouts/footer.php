<?php
use App\Core\SessionManager;
use App\Repositories\MenuRepository;

// Include footer menu functions
include_once __DIR__ . '/../footer_menu_function.php';

// Get top menus for footer
$topMenus = MenuRepository::getTopLevelMenus($userPermissions, $currentUrlPath);
?>

<footer class="footer">
    <?php renderFooterMenu($topMenus); ?>
</footer>