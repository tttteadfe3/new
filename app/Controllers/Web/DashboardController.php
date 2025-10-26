<?php

namespace App\Controllers\Web;

class DashboardController extends BaseController
{
    /**
     * 빈 대시보드 페이지를 표시합니다.
     */
    public function index(): void
    {
        echo $this->render('pages/dashboard/index', [], 'layouts/app');
    }
}
