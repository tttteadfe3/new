<?php

namespace App\Controllers;

use App\Core\AuthManager;
use App\Core\View;

class DashboardController extends BaseController
{
    public function index()
    {
        $this->requireAuth();

        $user = $this->user();

        // For now, just a simple welcome message.
        // In the future, this will render a full dashboard view.
        echo "<h1>Welcome, " . htmlspecialchars($user['properties']['nickname'] ?? 'User') . "!</h1>";
        echo '<a href="/logout">Logout</a>';
    }
}