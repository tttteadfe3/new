<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title mb-0">팀 연차 캘린더</h5>
                    </div>
                    <div class="col-auto">
                        <div class="d-flex align-items-center gap-2">
                            <select class="form-select form-select-sm" id="department-filter" style="width: 150px;">
                                <option value="">내 부서</option>
                                <!-- 부서 목록은 JS로 채웁니다 -->
                            </select>
                            <a href="<?= BASE_URL ?>/leaves/apply" class="btn btn-leave-secondary btn-sm">
                                <i class="bx bx-arrow-back me-1"></i>연차 관리로
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- 범례 -->
                <div class="mb-3">
                    <div class="d-flex flex-wrap gap-3 align-items-center">
                        <small class="text-muted">범례:</small>
                        <div class="d-flex align-items-center gap-1">
                            <div class="legend-item full-day"></div>
                            <small>전일 연차</small>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <div class="legend-item half-day"></div>
                            <small>반차</small>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <div class="legend-item holiday"></div>
                            <small>휴일</small>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <div class="legend-item multiple"></div>
                            <small>중복 휴가</small>
                        </div>
                    </div>
                </div>
                
                <!-- FullCalendar -->
                <div id="team-calendar" style="min-height: 600px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- 팀원 연차 현황 -->
<div class="row mt-4">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">팀원 연차 현황</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle table-nowrap table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>직원명</th>
                                <th>직급</th>
                                <th>부여</th>
                                <th>사용</th>
                                <th>잔여</th>
                                <th>사용률</th>
                                <th>이번 달 계획</th>
                            </tr>
                        </thead>
                        <tbody id="team-status-body">
                            <tr>
                                <td colspan="7" class="text-center text-muted">팀원 정보를 불러오는 중...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 이번 달 휴가 통계 -->
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">이번 달 휴가 통계</h5>
            </div>
            <div class="card-body">
                <!-- Chart.js 도넛 차트 -->
                <div class="mb-4">
                    <canvas id="leave-stats-chart" width="200" height="200"></canvas>
                </div>
                
                <div class="stats-card mb-3">
                    <div class="stats-number text-primary" id="month-total-leaves">0</div>
                    <div class="stats-label">총 휴가 일수</div>
                </div>
                
                <div class="stats-card mb-3">
                    <div class="stats-number text-warning" id="month-overlap-days">0</div>
                    <div class="stats-label">중복 휴가 일수</div>
                </div>
                
                <div class="stats-card">
                    <div class="stats-number text-success" id="month-active-employees">0</div>
                    <div class="stats-label">휴가 사용자 수</div>
                </div>
                
                <hr class="my-3">
                
                <h6 class="mb-2">휴일 정보</h6>
                <div id="month-holidays">
                    <div class="text-muted text-center">휴일 정보를 불러오는 중...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 일별 상세 정보 모달 -->
<div class="modal fade" id="day-detail-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="day-detail-title">날짜별 휴가 현황</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="day-detail-content">
                    <!-- 일별 상세 정보가 여기에 표시됩니다 -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-leave-secondary" data-bs-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<style>
/* FullCalendar 커스텀 스타일 */
#team-calendar {
    font-family: inherit;
}

.fc-event {
    border: none;
    border-radius: 3px;
    font-size: 0.75rem;
    padding: 1px 3px;
    margin: 1px 0;
}

.fc-event.full-day {
    background-color: #007bff;
    color: white;
}

.fc-event.half-day {
    background-color: #ffc107;
    color: #212529;
}

.fc-event.multiple-leaves {
    background-color: #fd7e14;
    color: white;
    font-weight: bold;
}

.fc-daygrid-day.holiday {
    background-color: #ffe6e6;
}

.fc-daygrid-day.weekend {
    background-color: #f8f9fa;
}

/* 범례 스타일 */
.legend-item {
    width: 16px;
    height: 16px;
    border-radius: 3px;
    display: inline-block;
}

.legend-item.full-day {
    background-color: #007bff;
}

.legend-item.half-day {
    background-color: #ffc107;
}

.legend-item.holiday {
    background-color: #dc3545;
}

.legend-item.multiple {
    background-color: #fd7e14;
}

/* 팀원 현황 테이블 스타일 */
.usage-rate-bar {
    width: 100%;
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

.usage-rate-fill {
    height: 100%;
    background: linear-gradient(90deg, #28a745 0%, #ffc107 70%, #dc3545 100%);
    transition: width 0.3s ease;
}

.employee-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #007bff;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.8rem;
}

/* 월별 계획 표시 */
.monthly-plan {
    font-size: 0.8rem;
    color: #6c757d;
}

.monthly-plan .plan-item {
    background: #e3f2fd;
    border-radius: 3px;
    padding: 0.2rem 0.4rem;
    margin: 0.1rem;
    display: inline-block;
}

/* 휴일 정보 스타일 */
.holiday-info {
    padding: 0.5rem 0;
    border-bottom: 1px solid #e9ecef;
    font-size: 0.85rem;
}

.holiday-info:last-child {
    border-bottom: none;
}

.holiday-date {
    font-weight: 600;
    color: #dc3545;
}

.holiday-name {
    color: #6c757d;
    margin-left: 0.5rem;
}

/* 일별 상세 모달 스타일 */
.day-detail-employee {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    border: 1px solid #e9ecef;
    border-radius: 5px;
    margin-bottom: 0.5rem;
}

.day-detail-employee .employee-info {
    flex: 1;
}

.day-detail-employee .leave-type {
    background: #007bff;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 3px;
    font-size: 0.8rem;
}

.day-detail-employee .leave-type.half {
    background: #ffc107;
    color: #212529;
}

/* 로딩 상태 */
.calendar-loading {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 10;
}

/* 반응형 개선 */
@media (max-width: 768px) {
    .calendar-header-controls {
        flex-direction: column;
        gap: 1rem;
    }
    
    .calendar-month-title {
        order: -1;
    }
    
    .team-status-body .d-none.d-md-table-cell {
        display: none !important;
    }
}
</style>

<!-- FullCalendar CSS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js"></script>

<!-- FullCalendar JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/ko.global.min.js"></script>

<script>
// TeamCalendar 인스턴스 생성 - BasePage가 DOMContentLoaded를 자동 처리
if (typeof TeamCalendar !== 'undefined') {
    window.teamCalendar = new TeamCalendar();
} else {
    console.error('TeamCalendar class not found');
}
</script>
<?php \App\Core\View::getInstance()->endSection(); ?>