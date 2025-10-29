<?php
use App\Core\View;

// 페이지별 CSS 추가
\App\Core\View::getInstance()->startSection('css');
?>
<link href="<?= BASE_ASSETS_URL ?>/assets/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" type="text/css" />
<style>
    .demo-section {
        margin-bottom: 2rem;
        padding: 1.5rem;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        background-color: #f8f9fa;
    }
</style>
<?php
\App\Core\View::getInstance()->endSection();

// 페이지별 JavaScript 추가
\App\Core\View::getInstance()->startSection('js');
?>
<script src="<?= BASE_ASSETS_URL ?>/assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('레이아웃 예제 페이지가 동적 JS와 함께 로드되었습니다!');

        // 페이지별 기능 예제
        const demoButton = document.getElementById('demo-button');
        if (demoButton) {
            demoButton.addEventListener('click', function() {
                alert('동적 JavaScript가 작동합니다!');
            });
        }
    });
</script>
<?php
\App\Core\View::getInstance()->endSection();
?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">레이아웃 시스템 데모</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">페이지</a></li>
                    <li class="breadcrumb-item active">레이아웃 데모</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">새로운 뷰 시스템 기능</h5>
            </div>
            <div class="card-body">
                <div class="demo-section">
                    <h6>1. 레이아웃 상속</h6>
                    <p>이 페이지는 헤더, 사이드바, 푸터 구성 요소를 포함하는 기본 'app' 레이아웃을 사용합니다.</p>
                    <p>레이아웃은 컨트롤러에서 지정됩니다: <code>$this->render('pages/demo/layout-example', $data, 'app')</code></p>
                </div>

                <div class="demo-section">
                    <h6>2. 동적 CSS 로딩</h6>
                    <p>페이지별 CSS는 <code>\App\Core\View::getInstance()->startSection('css')</code>와 <code>\App\Core\View::getInstance()->endSection()</code>을 사용하여 추가됩니다.</p>
                    <p>이를 통해 각 페이지는 레이아웃을 수정하지 않고도 자체 스타일시트를 포함할 수 있습니다.</p>
                </div>

                <div class="demo-section">
                    <h6>3. 동적 JavaScript 로딩</h6>
                    <p>페이지별 JavaScript는 동일한 섹션 시스템을 사용하여 추가됩니다.</p>
                    <button type="button" class="btn btn-primary" id="demo-button">동적 JS 테스트</button>
                </div>

                <div class="demo-section">
                    <h6>4. 헬퍼 메소드</h6>
                    <p>View 클래스는 또한 헬퍼 메소드를 제공합니다:</p>
                    <ul>
                        <li><code>\App\Core\View::getInstance()->addCss($path)</code> - 프로그래밍 방식으로 CSS 파일 추가</li>
                        <li><code>\App\Core\View::getInstance()->addJs($path)</code> - 프로그래밍 방식으로 JS 파일 추가</li>
                        <li><code>\App\Core\View::getInstance()->hasSection($name)</code> - 섹션 존재 여부 확인</li>
                        <li><code>\App\Core\View::getInstance()->yieldSection($name, $default)</code> - 섹션 내용 출력</li>
                    </ul>
                </div>

                <div class="demo-section">
                    <h6>5. 조직화된 디렉토리 구조</h6>
                    <p>이제 뷰는 논리적인 구조로 구성됩니다:</p>
                    <ul>
                        <li><code>app/Views/layouts/</code> - 레이아웃 템플릿</li>
                        <li><code>app/Views/pages/</code> - 기능별로 구성된 페이지별 뷰</li>
                        <li><code>app/Views/auth/</code> - 인증 관련 뷰</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>