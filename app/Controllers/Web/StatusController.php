<?php

namespace App\Controllers\Web;

use App\Core\AuthManager;
use App\Core\View;

class StatusController extends BaseController
{
    public function index()
    {
        $user = $this->user();

        // 사용자가 보류 중이 아니면 대시보드로 리디렉션합니다.
        if ($user['status'] !== 'pending') {
            $this->redirect('/dashboard');
        }

        return $this->render('status/index', [], 'layouts/basic');
    }
}
