# 뷰 시스템 문서

## 개요

향상된 뷰 시스템은 레이아웃 상속, 동적 CSS/JS 로딩, 그리고 더 나은 유지보수성을 위한 체계적인 디렉토리 구조를 제공합니다.

## 디렉토리 구조

```
app/Views/
├── layouts/           # 레이아웃 템플릿
│   ├── app.php       # 메인 애플리케이션 레이아웃
│   ├── simple.php    # 기본 페이지를 위한 심플 레이아웃
│   ├── header.php    # 헤더 컴포넌트
│   ├── sidebar.php   # 사이드바 컴포넌트
│   ├── footer.php    # 푸터 컴포넌트
│   └── functions.php # 레이아웃 헬퍼 함수
├── pages/            # 기능별로 구성된 페이지별 뷰
│   ├── employees/    # 직원 관리 뷰
│   ├── holidays/     # 휴일 관리 뷰
│   ├── leaves/       # 연차 관리 뷰
│   ├── littering/    # 무단투기 신고 뷰
│   ├── waste/        # 폐기물 수거 뷰
│   ├── admin/        # 관리자 패널 뷰
│   └── demo/         # 데모 및 예제 뷰
└── auth/             # 인증 뷰
    └── login.php
```

## 사용법

### 기본 뷰 렌더링

```php
// 컨트롤러에서
return $this->render('pages/employees/index', $data);
```

### 레이아웃 상속

```php
// 컨트롤러에서 - 세 번째 파라미터로 레이아웃 지정
return $this->render('pages/employees/index', $data, 'app');
```

### 동적 CSS 로딩

```php
// 뷰 파일에서
<?php
use App\Core\View;

View::startSection('css');
?>
<link href="<?= BASE_ASSETS_URL ?>/assets/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
<style>
    .custom-style { color: red; }
</style>
<?php
View::endSection();
?>
```

### 동적 JavaScript 로딩

```php
// 뷰 파일에서
<?php
View::startSection('js');
?>
<script src="<?= BASE_ASSETS_URL ?>/assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script>
    // 페이지별 JavaScript
    console.log('페이지 로드됨');
</script>
<?php
View::endSection();
?>
```

### 헬퍼 메소드

```php
// 프로그래밍 방식으로 CSS 파일 추가
View::addCss(BASE_ASSETS_URL . '/assets/css/custom.css');

// 프로그래밍 방식으로 JS 파일 추가
View::addJs(BASE_ASSETS_URL . '/assets/js/custom.js');

// 섹션 존재 여부 확인
if (View::hasSection('custom-section')) {
    // 무언가 수행
}

// 기본값과 함께 섹션 출력
echo View::yieldSection('custom-section', '기본 내용');
```

## 레이아웃 컴포넌트

### 메인 레이아웃 (app.php)
- 헤더, 사이드바, 푸터가 포함된 전체 애플리케이션 레이아웃
- 모든 기본 CSS/JS 라이브러리 포함
- 동적 CSS/JS 섹션 지원
- 인증된 페이지에 사용

### 심플 레이아웃 (simple.php)
- 기본 페이지를 위한 최소한의 레이아웃
- 필수 CSS/JS만 포함
- 로그인, 상태 페이지 등에 사용

### 컴포넌트
- **header.php**: 사용자 메뉴가 있는 상단 네비게이션 바
- **sidebar.php**: 왼쪽 네비게이션 메뉴
- **footer.php**: 하단 네비게이션 메뉴

## 이전 시스템에서의 마이그레이션

### 이전 (구 시스템)
```php
// 구 방식 - 수동 include
include_once ROOT_PATH . '/layouts/header.php';
// 이제 EmployeeController@index에서 적절한 MVC 구조로 처리
include_once ROOT_PATH . '/layouts/footer.php';
```

### 이후 (새 시스템)
```php
// 새 방식 - 컨트롤러에서
return $this->render('pages/employees/index', $data, 'app');
```

## 장점

1. **레이아웃 상속**: 페이지 전반에 걸친 일관된 레이아웃
2. **동적 에셋**: 페이지별 CSS/JS 로딩
3. **체계적인 구조**: 논리적인 디렉토리 구성
4. **유지보수성**: 중앙 집중식 레이아웃 관리
5. **유연성**: 다양한 레이아웃 옵션
6. **성능**: 페이지당 필요한 에셋만 로드

## 예제

모든 기능을 보여주는 완전한 예제는 `app/Views/pages/demo/layout-example.php`를 참조하세요.
