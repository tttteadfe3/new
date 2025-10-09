<?php

namespace App\Controllers\Web;

class TripLogController extends BaseController
{
    /**
     * 운행 일지 상세 페이지
     * @param int $id The ID of the trip log
     */
    public function show($id)
    {
        $this->requireAuth('admin.trips.show'); // Assuming a permission key

        $pageTitle = "운행 일지 상세";
        // \App\Core\View::addJs(...); // Add necessary JS files

        // Example: Fetch trip log data from a service/repository
        // $tripLog = TripLogRepository::findById($id);

        echo $this->render('pages/fleet/trip_log_show', [
            'pageTitle' => $pageTitle,
            'tripLogId' => $id
            // 'tripLog' => $tripLog
        ], 'layouts/app');
    }
}