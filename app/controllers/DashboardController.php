<?php
require_once APP_ROOT . '/app/core/Controller.php';

class DashboardController extends Controller
{
    public function index(): void
    {
        requireLogin();

        $this->view('dashboard/index', [
            'usuario' => currentUser(),
        ]);
    }
}
