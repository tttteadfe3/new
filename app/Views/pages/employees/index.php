<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-lg-12">
        <div class="card" id="employeeList">
            <div class="card-header border-bottom-dashed">

                <div class="row g-4 align-items-center">
                    <div class="col-sm">
                        <div>
                            <h5 class="card-title mb-0">직원 목록</h5>
                        </div>
                    </div>
                    <div class="col-sm-auto">
                        <div class="d-flex flex-wrap align-items-start gap-2">
                            <button type="button" class="btn btn-success add-btn" data-bs-toggle="modal" id="add-employee-btn" data-bs-target="#employee-modal"><i class="ri-add-line align-bottom me-1"></i> 신규 등록</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body border-bottom-dashed border-bottom">
                <form>
                    <div class="row g-3">
                        <div class="col-xl-4">
                            <select class="form-select" id="filter-department">
                                <option value="">모든 부서</option>
                            </select>
                        </div>
                        <div class="col-xl-4">
                            <select class="form-select" id="filter-position">
                                <option value="">모든 직급</option>
                            </select>
                        </div>
                        <div class="col-xl-4">
                            <select class="form-select" id="filter-status">
                                <option value="">모든 직원</option>
                                <option value="재직중" selected>재직중</option>
                                <option value="퇴사">퇴사</option>
                            </select>
                        </div>
                    </div>
                    <!--end row-->
                </form>
            </div>
            <div class="card-body">
                <div>
                    <div class="table-responsive table-card mb-1">
                        <table class="table align-middle" id="employeeTable">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="sort" data-sort="employee_name">직원 이름</th>
                                    <th class="sort" data-sort="department">부서</th>
                                    <th class="sort" data-sort="position">직급</th>
                                    <th class="sort" data-sort="employee_number">사번</th>
                                    <th class="sort" data-sort="hire_date">입사일</th>
                                    <th class="sort" data-sort="resignation_date">퇴사일</th>
                                    <th class="sort" data-sort="user_account">연결된 계정(닉네임)</th>
                                    <th class="sort" data-sort="action">관리</th>
                                </tr>
                            </thead>
                            <tbody id="employee-table-body" class="list form-check-all">
                                <tr>
                                    <td colspan="8" class="text-center">목록을 불러오는 중...</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="noresult" style="display: none">
                            <div class="text-center">
                                <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px"></lord-icon>
                                <h5 class="mt-2">검색 결과가 없습니다.</h5>
                                <p class="text-muted mb-0">다른 검색어로 다시 시도해주세요.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--end col-->
</div>


<div class="modal fade" id="employee-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-title">직원 정보</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="employee-form">
                    <input type="hidden" id="id" name="id">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">직원 이름</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="employee_number" class="form-label">사번</label>
                            <input type="text" class="form-control" id="employee_number" name="employee_number" placeholder="신규 등록 시 자동 생성됩니다" readonly>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="department_id" class="form-label">부서</label>
                            <select class="form-select" id="department_id" name="department_id">
                                <option value="">부서 선택</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="position_id" class="form-label">직급</label>
                            <select class="form-select" id="position_id" name="position_id">
                                <option value="">직급 선택</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="hire_date" class="form-label">입사일</label>
                            <input type="date" class="form-control" id="hire_date" name="hire_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone_number" class="form-label">연락처</label>
                            <input type="tel" class="form-control" id="phone_number" name="phone_number">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">주소</label>
                        <input type="text" class="form-control" id="address" name="address">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="emergency_contact_name" class="form-label">비상연락처</label>
                            <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="emergency_contact_relation" class="form-label">비상연락처와의 관계</label>
                            <input type="text" class="form-control" id="emergency_contact_relation" name="emergency_contact_relation">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="clothing_top_size" class="form-label">상의 사이즈</label>
                            <input type="text" class="form-control" id="clothing_top_size" name="clothing_top_size">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="clothing_bottom_size" class="form-label">하의 사이즈</label>
                            <input type="text" class="form-control" id="clothing_bottom_size" name="clothing_bottom_size">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="shoe_size" class="form-label">신발 사이즈</label>
                            <input type="text" class="form-control" id="shoe_size" name="shoe_size">
                        </div>
                    </div>
                </form>
                <hr class="d-none" id="history-separator">
                <div id="change-history-container" class="d-none">
                    <h5><i class="bi bi-clock-history"></i> 변경 이력</h5>
                    <div id="history-log-list" class="list-group list-group-flush border" style="max-height: 200px; overflow-y: auto;">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" form="employee-form" id="delete-btn" class="btn btn-danger me-auto">삭제</button>
                <div class="hstack gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">닫기</button>
                    <button type="submit" form="employee-form" class="btn btn-primary">저장하기</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>
