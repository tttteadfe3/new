/**
 * 지급품 보고서 메인 페이지 스크립트
 */

$(document).ready(function() {
    // 페이지 로드 시 애니메이션 효과
    $('.card').each(function(index) {
        $(this).delay(100 * index).fadeIn(300);
    });
});
