<?php

namespace App\Controllers\Web;

use App\Services\HolidayService;
use App\Core\View;
use Exception;

class HolidayController extends BaseController
{
    private HolidayService $holidayService;

    public function __construct()
    {
        parent::__construct();
        $this->holidayService = new HolidayService();
    }

    /**
     * Display the holiday administration page.
     */
    public function index(): void
    {
        $this->requireAuth('holiday_admin');

        // Set page-specific CSS and JS
        View::addCss(BASE_ASSETS_URL . '/assets/libs/flatpickr/flatpickr.min.css');
        View::addJs(BASE_ASSETS_URL . '/assets/libs/flatpickr/flatpickr.min.js');
        View::addJs(BASE_ASSETS_URL . '/assets/js/pages/holiday-admin-app.js');

        $data = [
            'pageTitle' => '휴일/근무일 설정'
        ];

        echo $this->render('pages/holidays/index', $data, 'layouts/app');
    }

}