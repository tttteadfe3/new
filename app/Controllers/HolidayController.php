<?php

namespace App\Controllers;

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
    public function index(): string
    {
        $this->requireAuth('holiday_admin');

        // Set page-specific CSS and JS
        View::addCss(BASE_ASSETS_URL . '/assets/libs/flatpickr/flatpickr.min.css');
        View::addJs(BASE_ASSETS_URL . '/assets/libs/flatpickr/flatpickr.min.js');
        View::addJs(BASE_ASSETS_URL . '/assets/js/pages/holiday_admin.js');

        $data = [
            'pageTitle' => '휴일/근무일 설정'
        ];

        return $this->render('pages/holidays/index', $data, 'app');
    }

    /**
     * Get all holidays and departments (AJAX).
     */
    public function list(): void
    {
        $this->requireAuth('holiday_admin');

        try {
            $holidays = $this->holidayService->getAllHolidays();
            $departments = $this->holidayService->getAllDepartments();

            $this->json([
                'success' => true,
                'data' => [
                    'holidays' => $holidays,
                    'departments' => $departments
                ]
            ]);
        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific holiday (AJAX).
     */
    public function show(int $id): void
    {
        $this->requireAuth('holiday_admin');

        try {
            $holiday = $this->holidayService->getHoliday($id);
            
            if (!$holiday) {
                $this->json([
                    'success' => false,
                    'message' => 'Holiday not found.'
                ], 404);
                return;
            }

            $this->json([
                'success' => true,
                'data' => $holiday
            ]);
        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new holiday (AJAX).
     */
    public function store(): void
    {
        $this->requireAuth('holiday_admin');

        try {
            $data = $this->request->all();
            
            // Validate required fields
            if (empty(trim($data['name']))) {
                $this->json([
                    'success' => false,
                    'message' => '이름은 필수입니다.',
                    'errors' => ['name' => '이름은 필수입니다.']
                ], 422);
                return;
            }

            if (empty($data['date'])) {
                $this->json([
                    'success' => false,
                    'message' => '날짜는 필수입니다.',
                    'errors' => ['date' => '날짜는 필수입니다.']
                ], 422);
                return;
            }

            if (empty($data['type']) || !in_array($data['type'], ['holiday', 'workday'])) {
                $this->json([
                    'success' => false,
                    'message' => '유효한 타입을 선택해주세요.',
                    'errors' => ['type' => '유효한 타입을 선택해주세요.']
                ], 422);
                return;
            }

            // Convert deduct_leave to boolean
            $data['deduct_leave'] = isset($data['deduct_leave']) && $data['deduct_leave'];

            $holiday = $this->holidayService->createHoliday($data);

            $this->json([
                'success' => true,
                'data' => $holiday,
                'message' => '성공적으로 생성되었습니다.'
            ], 201);
        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Update an existing holiday (AJAX).
     */
    public function update(int $id): void
    {
        $this->requireAuth('holiday_admin');

        try {
            $data = $this->request->all();
            
            // Validate required fields
            if (empty(trim($data['name']))) {
                $this->json([
                    'success' => false,
                    'message' => '이름은 필수입니다.',
                    'errors' => ['name' => '이름은 필수입니다.']
                ], 422);
                return;
            }

            if (empty($data['date'])) {
                $this->json([
                    'success' => false,
                    'message' => '날짜는 필수입니다.',
                    'errors' => ['date' => '날짜는 필수입니다.']
                ], 422);
                return;
            }

            if (empty($data['type']) || !in_array($data['type'], ['holiday', 'workday'])) {
                $this->json([
                    'success' => false,
                    'message' => '유효한 타입을 선택해주세요.',
                    'errors' => ['type' => '유효한 타입을 선택해주세요.']
                ], 422);
                return;
            }

            // Convert deduct_leave to boolean
            $data['deduct_leave'] = isset($data['deduct_leave']) && $data['deduct_leave'];

            $holiday = $this->holidayService->updateHoliday($id, $data);

            $this->json([
                'success' => true,
                'data' => $holiday,
                'message' => '성공적으로 수정되었습니다.'
            ]);
        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Delete a holiday (AJAX).
     */
    public function destroy(int $id): void
    {
        $this->requireAuth('holiday_admin');

        try {
            $this->holidayService->deleteHoliday($id);

            $this->json([
                'success' => true,
                'message' => '성공적으로 삭제되었습니다.'
            ]);
        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }
}