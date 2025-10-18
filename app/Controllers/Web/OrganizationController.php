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
        // 1. Add page-specific JavaScript using the singleton instance
        View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/pages/organization-chart.js");

        // 3. Render the view with echo and specify the layout
        echo $this->render('organization/chart', [], 'layouts/app');
    }
}
