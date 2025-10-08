<?php
// layouts/footer.php
use App\Core\SessionManager;
use App\Repositories\MenuRepository;

?>
                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->
        </div>
        <!-- end main content-->
<?php
$topMenus = MenuRepository::getTopLevelMenus($userPermissions, $currentUrlPath);

include_once ROOT_PATH . '/layouts/footer_menu_function.php';
?>

            <footer class="footer">
<?php renderFooterMenu($topMenus); ?>
            </footer>
    </div>
    <!-- END layout-wrapper -->



    <!--start back-to-top-->
    <button onclick="topFunction()" class="btn btn-danger btn-icon" id="back-to-top">
        <i class="ri-arrow-up-line"></i>
    </button>
    <!--end back-to-top-->

    <!--preloader-->
    <div id="preloader">
        <div id="status">
            <div class="spinner-border text-primary avatar-sm" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script src="<?= BASE_ASSETS_URL ?>/assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_ASSETS_URL ?>/assets/libs/simplebar/simplebar.min.js"></script>
    <script src="<?= BASE_ASSETS_URL ?>/assets/libs/node-waves/waves.min.js"></script>
    <script src="<?= BASE_ASSETS_URL ?>/assets/libs/feather-icons/feather.min.js"></script>
    <script src="<?= BASE_ASSETS_URL ?>/assets/js/pages/plugins/lord-icon-2.1.0.js"></script>
    <script src="<?= BASE_ASSETS_URL ?>/assets/js/plugins.js"></script>

    <!-- toastify js -->
    <script src="<?= BASE_ASSETS_URL ?>/assets/libs/toastify-js/src/toastify.js"></script>
    <!-- sweetalert2 js -->
    <script src="<?= BASE_ASSETS_URL ?>/assets/libs/sweetalert2/sweetalert2.min.js"></script>
    <!-- Custom UI js -->
    <script src="<?= BASE_ASSETS_URL ?>/assets/js/utils/ui.js"></script>

    <!-- App js -->
    <script src="<?= BASE_ASSETS_URL ?>/assets/js/app.js"></script>

    <!-- Page js -->
<?php if (!empty($pageJs)): ?>
    <?php foreach ($pageJs as $js): ?>
        <script src="<?php echo $js; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>

</html>