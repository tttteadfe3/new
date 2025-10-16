<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>시스템 오류 (500)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            text-align: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 600px;
        }
        .icon {
            font-size: 5rem;
            color: #dc3545;
        }
        .title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-top: 1.5rem;
        }
        .message {
            color: #6c757d;
            margin-top: 1rem;
        }
        .home-button {
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <i class="bi bi-bug-fill"></i>
        </div>
        <p class="title">시스템에 예기치 않은 오류가 발생했습니다.</p>
        <p class="message">
            요청을 처리하는 중 문제가 발생했습니다.<br>
            잠시 후 다시 시도해 주시기 바랍니다. 문제가 지속되면 관리자에게 문의해주세요.
        </p>
        <a href="<?= BASE_URL ?? '/' ?>" class="btn btn-primary home-button">홈으로 돌아가기</a>
    </div>
</body>
</html>
