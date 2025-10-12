<?php

namespace App\Controllers\Web;

use App\Services\ProfileService;
use App\Core\JsonResponse;
use App\Core\View;
use Exception;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ViewDataService;
use App\Services\ActivityLogger;

class ProfileController extends BaseController
{
    private ProfileService $profileService;

    public function __construct(
        Request $request,
        AuthService $authService,
        ViewDataService $viewDataService,
        ActivityLogger $activityLogger,
        ProfileService $profileService
    ) {
        parent::__construct($request, $authService, $viewDataService, $activityLogger);
        $this->profileService = $profileService;
    }

    /**
     * Display the profile page
     */
    public function index(): void
    {
        \App\Core\View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/pages/profile.js");

        $pageTitle = "내 프로필";
        $this->activityLogger->logMenuAccess($pageTitle);

        echo $this->render('pages/profile/index', [
            'pageTitle' => $pageTitle
        ], 'layouts/app');
    }

}
