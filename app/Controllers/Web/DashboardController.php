<?php

namespace App\Controllers\Web;

class DashboardController extends BaseController
{
    /**
     * Display a blank dashboard page.
     */
    public function index(): void
    {
        echo $this->render('pages/dashboard/index', [], 'layouts/app');
    }
}
