<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0">부적정배출 수정</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="/littering/update">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($littering['id'] ?? '') ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="address" class="form-label">주소</label>
                            <input type="text" class="form-control" id="address" name="address" 
                                   value="<?= htmlspecialchars($littering['address'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="latitude" class="form-label">위도</label>
                            <input type="text" class="form-control" id="latitude" name="latitude" 
                                   value="<?= htmlspecialchars($littering['latitude'] ?? '') ?>" readonly>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="longitude" class="form-label">경도</label>
                            <input type="text" class="form-control" id="longitude" name="longitude" 
                                   value="<?= htmlspecialchars($littering['longitude'] ?? '') ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="mainType" class="form-label">주성상</label>
                            <select class="form-select" id="mainType" name="mainType" required>
                                <option value="">선택하세요</option>
                                <option value="생활폐기물" <?= ($littering['mainType'] ?? '') === '생활폐기물' ? 'selected' : '' ?>>생활폐기물</option>
                                <option value="음식물" <?= ($littering['mainType'] ?? '') === '음식물' ? 'selected' : '' ?>>음식물</option>
                                <option value="재활용" <?= ($littering['mainType'] ?? '') === '재활용' ? 'selected' : '' ?>>재활용</option>
                                <option value="대형" <?= ($littering['mainType'] ?? '') === '대형' ? 'selected' : '' ?>>대형</option>
                                <option value="소각" <?= ($littering['mainType'] ?? '') === '소각' ? 'selected' : '' ?>>소각</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="subType" class="form-label">부성상 (혼합 시)</label>
                            <select class="form-select" id="subType" name="subType">
                                <option value="">없음</option>
                                <option value="생활폐기물" <?= ($littering['subType'] ?? '') === '생활폐기물' ? 'selected' : '' ?>>생활폐기물</option>
                                <option value="음식물" <?= ($littering['subType'] ?? '') === '음식물' ? 'selected' : '' ?>>음식물</option>
                                <option value="재활용" <?= ($littering['subType'] ?? '') === '재활용' ? 'selected' : '' ?>>재활용</option>
                                <option value="대형" <?= ($littering['subType'] ?? '') === '대형' ? 'selected' : '' ?>>대형</option>
                                <option value="소각" <?= ($littering['subType'] ?? '') === '소각' ? 'selected' : '' ?>>소각</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="/littering" class="btn btn-secondary">취소</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line me-1"></i>저장
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>