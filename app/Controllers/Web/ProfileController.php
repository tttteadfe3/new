<?php

namespace App\Controllers\Web;

use App\Services\ProfileService;
use App\Core\JsonResponse;
use App\Core\View;
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
        \App\Core\View::getInstance()->addJs(BASE_ASSETS_URL . "/assets/js/pages/profile.js");

        $pageTitle = "내 프로필";
        $this->activityLogger->logMenuAccess($pageTitle);

        echo $this->render('pages/profile/index', [
            'pageTitle' => $pageTitle
        ], 'layouts/app');
    }

}
