<?php \App\Core\View::getInstance()->startSection('content'); ?>
                                <div class="text-center">
                                    <img src="/assets/images/logo-sm.png" class="avatar-lg" alt="thumbnail">
                                </div>
                                <div class="text-center mt-4">
                                    <h5 class="text-primary">승인 대기 중</h5>
                                    <p class="text-muted">계정이 아직 승인되지 않았습니다. 관리자에게 문의하세요</p>
                                </div>
                                <div class="p-2">
                                    <div class="mb-2 mt-4">
                                        <a class="btn btn-success btn-lg mt-3 w-100" href="/logout" role="button">로그아웃</a>
                                    </div>
                                </div>
<?php \App\Core\View::getInstance()->endSection(); ?>