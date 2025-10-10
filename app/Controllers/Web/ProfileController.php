<?php

namespace App\Controllers\Web;

use App\Services\ProfileService;
use App\Core\JsonResponse;
use Exception;

class ProfileController extends BaseController
{
    private ProfileService $profileService;

    public function __construct()
    {
        parent::__construct();
        $this->profileService = new ProfileService();
    }

    /**
     * Display the profile page
     */
    public function index(): void
    {
        $this->requireAuth();
        
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/services/api-service.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/components/base-app.js");
        \App\Core\View::addJs(BASE_ASSETS_URL . "/assets/js/pages/profile-app.js");

        $pageTitle = "내 프로필";
        \App\Services\ActivityLogger::logMenuAccess($pageTitle);

        echo $this->render('pages/profile/index', [
            'pageTitle' => $pageTitle
        ], 'layouts/app');
    }

}