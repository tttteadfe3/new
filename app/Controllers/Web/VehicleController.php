<?php

namespace App\Controllers\Web;

class VehicleController extends BaseController
{
    /**
     * 차량 목록 페이지
     */
    public function index()
    {
        $this->requireAuth('admin.fleet.vehicles'); // Assuming a permission key

        $pageTitle = "차량 관리";
        // \App\Core\View::addJs(...); // Add necessary JS files

        echo $this->render('pages/fleet/vehicles', [
            'pageTitle' => $pageTitle
        ], 'layouts/app');
    }
}