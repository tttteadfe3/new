<?php

namespace App\Controllers\Web;

use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;
use App\Services\HumanResourceService;
use App\Core\View;

class HumanResourceController extends BaseController
{
    private HumanResourceService $hrService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        HumanResourceService $hrService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger);
        $this->hrService = $hrService;
    }

    /**
     * 인사 발령 등록 페이지를 표시합니다.
     */
    public function create(): void
    {
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/core/base-page.js');
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/services/api-service.js');
        View::getInstance()->addJs(BASE_ASSETS_URL . '/assets/js/pages/hr-order.js');

        echo $this->render('pages/hr/order', [], 'layouts/app');
    }

    /**
     * 특정 직원의 인사 발령 기록을 표시합니다.
     */
    public function history(): void
    {
        $employeeId = $this->request->get('employee_id');
        if (!$employeeId) {
            $this->redirect('/employees');
            return;
        }

        // Note: getHistory
        $history = $this->hrService->getHistory((int)$employeeId);

        echo $this->render('pages/hr/history', [
            'history' => $history,
            'employee_id' => $employeeId
        ], 'layouts/app');
    }
}
