<?php

namespace App\Controllers\Web;

class AttendanceController extends BaseController
{
    /**
     * 내 출근 현황 페이지
     */
    public function myAttendance()
    {
        $this->requireAuth();

        $pageTitle = "나의 출근 현황";
        // \App\Core\View::addJs(...); // 필요 시 자바스크립트 추가

        echo $this->render('pages/attendance/my', [
            'pageTitle' => $pageTitle
        ], 'layouts/app');
    }
}