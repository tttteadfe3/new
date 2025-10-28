<?php

namespace App\Controllers\Web;

use App\Core\View;

class OrganizationController extends BaseController
{
    /**
     * 조직도 페이지를 표시합니다.
     */
    public function chart(): void
    {
        // 1. 싱글톤 인스턴스를 사용하여 페이지별 JavaScript 추가
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/pages/organization-chart.js");

        // 3. 에코로 뷰를 렌더링하고 레이아웃 지정
        echo $this->render('pages/organization/chart', [], 'layouts/app');
    }
}
