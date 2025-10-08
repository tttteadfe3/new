<?php

namespace App\Controllers;

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
    public function index(): string
    {
        $this->requireAuth();
        
        $pageTitle = "내 프로필";
        log_menu_access($pageTitle);

        return $this->render('pages/profile/index', [
            'pageTitle' => $pageTitle
        ]);
    }

}