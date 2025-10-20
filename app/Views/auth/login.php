<?php \App\Core\View::getInstance()->startSection('content'); ?>
                                <div class="user-thumb text-center">
                                    <img src="/assets/images/logo-sm.png" class="avatar-lg" alt="thumbnail">
                                </div>
                                <div class="text-center mt-4">
                                    <h5 class="text-primary">로그인</h5>
                                    <p class="text-muted">프로그램 사용을 위해 로그인이 필요합니다.</p>
                                </div>
                                <div class="p-2">
                                    <div class="mb-2 mt-4">
                                        <a href="<?= htmlspecialchars($kakaoLoginUrl) ?>" class="btn btn-lg btn-kakao w-100">
                                        <svg class="kakao-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                            <path fill="#000" d="M12 3C6.48 3 2 6.92 2 11.5c0 2.77 1.73 5.22 4.45 6.77-.15.57-.83 3.15-.86 3.36 0 0-.02.14.07.19.09.05.19.01.19.01.25-.04 2.9-1.9 3.36-2.22.91.14 1.84.21 2.79.21 5.52 0 10-3.92 10-8.5S17.52 3 12             3z"/>
                                        </svg>
                                            카카오 계정으로 로그인
                                        </a>
                                    </div>
                                </div>
<?php \App\Core\View::getInstance()->endSection(); ?>