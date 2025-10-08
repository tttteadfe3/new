<!doctype html>
<html lang="ko" data-layout="vertical" data-sidebar="dark" data-sidebar-size="lg" data-preloader="enable" data-theme="default">
<head>

    <meta charset="utf-8" />
    <title>원진실업(주)<?= isset($pageTitle) ? ' | ' . e($pageTitle) : '' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- App favicon -->
    <link rel="shortcut icon" href="<?= BASE_ASSETS_URL ?>/assets/images/favicon.ico">

<?php if (!empty($pageCss)): ?>
    <?php foreach ($pageCss as $css): ?>
        <link href="<?php echo $css; ?>" rel="stylesheet" type="text/css" />
    <?php endforeach; ?>
<?php endif; ?>

    <!-- sweetalert2 Css -->
    <link href="<?= BASE_ASSETS_URL ?>/assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
    <!-- Bootstrap Css -->
    <link href="<?= BASE_ASSETS_URL ?>/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="<?= BASE_ASSETS_URL ?>/assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="<?= BASE_ASSETS_URL ?>/assets/css/app.min.css" rel="stylesheet" type="text/css" />
    <!-- custom Css-->
    <link href="<?= BASE_ASSETS_URL ?>/assets/css/custom.min.css" rel="stylesheet" type="text/css" />


<style type="text/css">
.footer .item {
    font-size: 9px;
    letter-spacing: 0;
    text-align: center;
    width: 100%;
    height: 56px;
    line-height: 1.2em;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}
	.footer .item:before {
    content: '';
    display: block;
    height: 2px;
    border-radius: 0 0 10px 10px;
    background: transparent;
    position: absolute;
    left: 4px;
    right: 4px;
    top: 0;
}
.footer .item .col {
    width: 100%;
    padding: 0 4px;
    text-align: center;
}
.footer .item i.icon, .footer .item ion-icon {
    display: inline-flex;
    margin: 1px auto 3px auto;
    font-size: 26px;
    line-height: 1em;
    color: #141515;
    transition: 0.1s all;
    display: block;
    margin-top: 1px;
    margin-bottom: 3px;
}
.footer .item.active i.icon, .footer .item.active ion-icon, .footer .item.active strong {
    color: #1E74FD !important;
}
        .btn-kakao {
            display: inline-flex;
            align-items: center;
            background-color: #FEE500; /* 카카오 노란색 */
            color: #000;
            font-weight: bold;
            font-size: 15px;
            border-radius: 12px;
            padding: 10px 16px;
            text-decoration: none;
            transition: background-color 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-kakao .kakao-icon {
            width: 20px;
            height: 20px;
            margin-right: 8px;
            flex-shrink: 0;
        }
</style>
</head>