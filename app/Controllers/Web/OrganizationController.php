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
        $pageTitle = '조직도';

        // 1. Add page-specific JavaScript using the singleton instance
        View::getInstance()->addJs('/assets/js/pages/organization-chart.js');

        // 2. Log menu access
        $this->activityLogger->logMenuAccess($pageTitle);

        // 3. Render the view with echo and specify the layout
        echo $this->render('organization/chart', [
            'pageTitle' => $pageTitle
        ], 'layouts/app');
    }
}
