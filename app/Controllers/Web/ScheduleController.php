<?php

namespace App\Controllers\Web;

class ScheduleController extends BaseController
{
    /**
     * 운행 스케줄 페이지
     */
    public function index()
    {
        $this->requireAuth('admin.fleet.schedules'); // Assuming a permission key

        $pageTitle = "운행 스케줄 관리";
        // \App\Core\View::addJs(...); // Add necessary JS files

        echo $this->render('pages/fleet/schedules', [
            'pageTitle' => $pageTitle
        ], 'layouts/app');
    }
}