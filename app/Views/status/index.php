<?php
use App\Core\View;

// Add custom CSS for this page
View::startSection('css');
?>
<style>
    body {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background-color: #f8f9fa;
    }
    .status-card {
        text-align: center;
        padding: 40px;
        border-radius: 10px;
        background-color: #fff;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
</style>
<?php
View::endSection();
?>

<div class="status-card">
    <h1 class="display-4">승인 대기 중</h1>
    <p class="lead">계정이 아직 승인되지 않았습니다. 관리자에게 문의하세요.</p>
    <hr class="my-4">
    <p>Your account is pending approval. Please contact an administrator.</p>
    <a class="btn btn-primary btn-lg mt-3" href="/logout" role="button">로그아웃</a>
</div>