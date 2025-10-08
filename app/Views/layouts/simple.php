<?php
use App\Core\View;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? '원진실업(주)' ?></title>
    
    <!-- Dynamic CSS Section -->
    <?= View::yieldSection('css') ?>
    
    <!-- Default CSS -->
    <link href="<?= BASE_ASSETS_URL ?>/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
</head>
<body>
    <div class="container-fluid">
        <?= $content ?>
    </div>
    
    <!-- Default JS -->
    <script src="<?= BASE_ASSETS_URL ?>/assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <!-- Dynamic JS Section -->
    <?= View::yieldSection('js') ?>
</body>
</html>