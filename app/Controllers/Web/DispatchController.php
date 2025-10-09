<?php

namespace App\Controllers\Web;

class DispatchController extends BaseController
{
    /**
     * 배차 관리 페이지
     */
    public function index()
    {
        $this->requireAuth('admin.dispatches.index'); // Assuming a permission key

        $pageTitle = "배차 관리";
        // \App\Core\View::addJs(...); // Add necessary JS files

        echo $this->render('pages/fleet/dispatches', [
            'pageTitle' => $pageTitle
        ], 'layouts/app');
    }
}