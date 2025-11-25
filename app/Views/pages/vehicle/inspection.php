<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">차량 검사 관리</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">차량 관리</a></li>
                        <li class="breadcrumb-item active">검사 관리</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <div class="text-sm-end">
                                <button type="button" class="btn btn-primary waves-effect waves-light mb-2 me-2" data-bs-toggle="modal" data-bs-target="#addInspectionModal">
                                    <i class="mdi mdi-plus me-1"></i> 검사 등록
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="inspectionTable" class="table table-bordered dt-responsive nowrap w-100">
                            <thead>
                                <tr>
                                    <th>차량번호</th>
                                    <th>모델명</th>
                                    <th>검사일자</th>
                                    <th>만료일자</th>
                                    <th>검사자</th>
                                    <th>결과</th>
                                    <th>비용</th>
                                    <th>관리</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Inspection Modal -->
<div class="modal fade" id="addInspectionModal" tabindex="-1" aria-labelledby="addInspectionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addInspectionModalLabel">차량 검사 등록</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addInspectionForm">
                    <input type="hidden" id="inspection_id" name="inspection_id">
                    <div class="mb-3">
                        <label for="vehicle_id" class="form-label">차량 선택</label>
                        <select class="form-select" id="vehicle_id" name="vehicle_id" required>
                            <option value="">차량을 선택하세요</option>
                            <!-- Options will be loaded via AJAX -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="inspection_date" class="form-label">검사일자</label>
                        <input type="date" class="form-control" id="inspection_date" name="inspection_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="expiry_date" class="form-label">만료일자</label>
                        <input type="date" class="form-control" id="expiry_date" name="expiry_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="inspector_name" class="form-label">검사자/검사소</label>
                        <input type="text" class="form-control" id="inspector_name" name="inspector_name">
                    </div>
                    <div class="mb-3">
                        <label for="result" class="form-label">검사 결과</label>
                        <select class="form-select" id="result" name="result" required>
                            <option value="합격">합격</option>
                            <option value="불합격">불합격</option>
                            <option value="재검사">재검사</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="cost" class="form-label">검사 비용</label>
                        <input type="number" class="form-control" id="cost" name="cost" placeholder="0">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="saveInspectionBtn">저장</button>
            </div>
        </div>
    </div>
</div>
