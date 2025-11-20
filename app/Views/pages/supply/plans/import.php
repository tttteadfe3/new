<?php \App\Core\View::getInstance()->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><?= e($pageTitle) ?></h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="/">홈</a></li>
                    <li class="breadcrumb-item"><a href="/supply">지급품 관리</a></li>
                    <li class="breadcrumb-item"><a href="/supply/plans">연간 계획</a></li>
                    <li class="breadcrumb-item active">엑셀 업로드</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <!-- 업로드 안내 -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">엑셀 업로드 안내</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6 class="alert-heading">업로드 전 확인사항</h6>
                    <ul class="mb-0">
                        <li>CSV 형식의 파일만 업로드 가능합니다.</li>
                        <li>첫 번째 행은 헤더로 인식되며, 아래 형식을 정확히 따라야 합니다.</li>
                        <li>품목코드는 시스템에 등록된 코드와 정확히 일치해야 합니다.</li>
                        <li>이미 계획이 등록된 품목은 중복 등록되지 않습니다.</li>
                    </ul>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h6>필수 컬럼 (헤더)</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>컬럼명</th>
                                        <th>설명</th>
                                        <th>예시</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>year</code></td>
                                        <td>계획 연도</td>
                                        <td><?= $year ?></td>
                                    </tr>
                                    <tr>
                                        <td><code>item_code</code></td>
                                        <td>품목 코드</td>
                                        <td>ITEM001</td>
                                    </tr>
                                    <tr>
                                        <td><code>planned_quantity</code></td>
                                        <td>계획 수량</td>
                                        <td>100</td>
                                    </tr>
                                    <tr>
                                        <td><code>unit_price</code></td>
                                        <td>단가</td>
                                        <td>5000</td>
                                    </tr>
                                    <tr>
                                        <td><code>notes</code></td>
                                        <td>비고 (선택)</td>
                                        <td>특이사항</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>샘플 템플릿 다운로드</h6>
                        <p class="text-muted">아래 버튼을 클릭하여 샘플 템플릿을 다운로드하고 참고하세요.</p>
                        <button type="button" class="btn btn-outline-success" id="download-template-btn">
                            <i class="ri-download-2-line me-1"></i> 샘플 템플릿 다운로드
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <!-- 파일 업로드 -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">파일 업로드</h5>
                    <a href="/supply/plans?year=<?= $year ?>" class="btn btn-secondary">
                        <i class="ri-arrow-left-line me-1"></i> 목록으로
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form id="importForm" enctype="multipart/form-data">
                    <input type="hidden" name="year" value="<?= $year ?>">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <label for="excel-file" class="form-label">CSV 파일 선택 <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="excel-file" name="excel_file" 
                                   accept=".csv" required>
                            <div class="form-text">CSV 파일만 업로드 가능합니다. (최대 5MB)</div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100" id="upload-btn">
                                <i class="ri-upload-2-line me-1"></i> 업로드 및 가져오기
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 업로드 결과 -->
<div class="row" id="upload-result" style="display: none;">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">업로드 결과</h5>
            </div>
            <div class="card-body">
                <div id="result-summary"></div>
                <div id="result-details" class="mt-3"></div>
                <div class="text-end mt-3">
                    <a href="/supply/plans?year=<?= $year ?>" class="btn btn-success">
                        <i class="ri-eye-line me-1"></i> 계획 목록 보기
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php \App\Core\View::getInstance()->endSection(); ?>

<?php \App\Core\View::getInstance()->startSection('scripts'); ?>
<script src="/assets/js/pages/supply-plans-import.js"></script>
<?php \App\Core\View::getInstance()->endSection(); ?>
