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
        // Call addJs() on the View instance obtained via getInstance()
        View::getInstance()->addJs('/assets/js/pages/organization-chart.js');

        $this->render('organization/chart', [
            'title' => '조직도'
        ]);
    }
}
