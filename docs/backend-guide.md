# PHP 개발 가이드

이 문서는 프로젝트의 PHP 코드 구조와 규칙을 정의하여 일관성을 유지하고 개발 생산성을 높이는 것을 목표로 합니다.

## 1. 핵심 아키텍처 (MVC-S)

본 프로젝트는 표준적인 MVC(Model-View-Controller) 패턴을 서비스 계층(Service Layer)으로 확장한 **MVC-S 아키텍처**를 따릅니다. 각 컴포넌트의 역할은 다음과 같습니다.

- **Controller (`app/Controllers`)**:
  - **역할**: HTTP 요청의 진입점. 사용자 입력(Request)을 받아 서비스 계층으로 전달하고, 서비스의 처리 결과를 뷰(View)나 JSON으로 응답합니다.
  - **책임**:
    - 요청 데이터 유효성 검사 (필요시).
    - 적절한 서비스 메서드 호출.
    - `View::render()` 또는 `JsonResponse::send()` 호출.
  - **규칙**: **"Thin Controller"** 원칙을 따릅니다. 비즈니스 로직이나 데이터베이스 쿼리를 포함해서는 안 됩니다.

- **Service (`app/Services`)**:
  - **역할**: 애플리케이션의 핵심 비즈니스 로직을 처리합니다.
  - **책임**:
    - 복잡한 계산, 데이터 가공, 권한 검증 등 비즈니스 규칙을 구현.
    - 여러 리포지토리를 호출하여 필요한 데이터를 조합하고 트랜잭션을 관리.
  - **규칙**: **"Fat Service"** 원칙을 따릅니다. 대부분의 로직은 서비스 계층에 위치해야 합니다.

- **Repository (`app/Repositories`)**:
  - **역할**: 데이터베이스와의 모든 상호작용을 담당합니다.
  - **책임**:
    - SQL 쿼리 작성 및 실행.
    - 데이터베이스로부터 받은 데이터를 가공 없이 그대로 서비스 계층에 반환.
  - **규칙**: **"One Repository per Table"**을 기본 원칙으로 합니다. 모든 SQL 쿼리는 반드시 리포지토리 내에 있어야 합니다. 서비스나 컨트롤러에서 직접 DB에 접근해서는 안 됩니다.

- **View (`app/Views`)**:
  - **역할**: 사용자에게 보여질 UI를 렌더링합니다.
  - **책임**:
    - 컨트롤러로부터 전달받은 데이터를 화면에 표시.
    - 최소한의 표현 로직(반복문, 조건문 등)만 포함.

## 2. 의존성 주입 (DI)

이 프로젝트는 `public/index.php`에 정의된 간단한 DI 컨테이너를 사용하여 클래스 간의 의존성을 관리합니다.

#### 새로운 서비스/리포지토리 등록 절차

1.  **클래스 생성**: `app/Services` 또는 `app/Repositories` 디렉토리에 새로운 클래스 파일을 생성합니다. 생성자(`__construct`)를 통해 필요한 의존성을 주입받도록 설계합니다.

    ```php
    // app/Services/NewFeatureService.php
    class NewFeatureService {
        private $dependencyRepository;

        public function __construct(DependencyRepository $dependencyRepository) {
            $this->dependencyRepository = $dependencyRepository;
        }
        // ...
    }
    ```

2.  **컨테이너에 등록**: `public/index.php` 파일을 열고, `$container->register()` 메서드를 사용하여 새 클래스를 등록합니다. **리포지토리를 먼저 등록하고, 그 다음에 서비스를 등록해야 합니다.**

    ```php
    // public/index.php

    // ... Repositories
    $container->register(\App\Repositories\DependencyRepository::class, fn($c) => new \App\Repositories\DependencyRepository($c->resolve(Database::class)));

    // ... Services
    $container->register(\App\Services\NewFeatureService::class, fn($c) => new \App\Services\NewFeatureService(
        $c->resolve(\App\Repositories\DependencyRepository::class)
    ));
    ```

> **Warning**: DI 컨테이너에 클래스를 등록하는 것을 잊으면 `Fatal error: Uncaught Error: Call to a member function ... on null` 오류가 발생합니다.

## 3. 새로운 기능 추가 절차 (End-to-End 예시)

"공지사항(Notices)" 기능을 추가하는 예시입니다.

1.  **데이터베이스 테이블 생성**: `notices` 테이블을 생성하고 `database/schema.sql`에 반영합니다.

2.  **라우트 정의 (`routes/web.php`)**:
    ```php
    $router->get('/notices', [NoticeController::class, 'index'])->name('notices.index');
    ```

3.  **리포지토리 생성 (`app/Repositories/NoticeRepository.php`)**:
    ```php
    class NoticeRepository {
        private $db;
        public function __construct(Database $db) { $this->db = $db; }

        public function findAll() {
            $stmt = $this->db->prepare("SELECT * FROM notices ORDER BY created_at DESC");
            $stmt->execute();
            return $stmt->fetchAll(); // 연관 배열로 반환
        }
    }
    ```

4.  **서비스 생성 (`app/Services/NoticeService.php`)**:
    ```php
    class NoticeService {
        private $noticeRepository;
        public function __construct(NoticeRepository $noticeRepository) {
            $this->noticeRepository = $noticeRepository;
        }

        public function getVisibleNotices() {
            // 예: 관리자만 볼 수 있는 공지사항을 필터링하는 등의 비즈니스 로직
            $allNotices = $this->noticeRepository->findAll();
            // ... 로직 ...
            return $allNotices;
        }
    }
    ```

5.  **컨트롤러 생성 (`app/Controllers/Web/NoticeController.php`)**:
    ```php
    class NoticeController extends BaseController {
        private $noticeService;
        public function __construct(NoticeService $noticeService, ViewDataService $viewDataService) {
            parent::__construct($viewDataService);
            $this->noticeService = $noticeService;
        }

        public function index() {
            $notices = $this->noticeService->getVisibleNotices();

            View::getInstance()->addCss('/assets/css/pages/notices.css');
            View::getInstance()->addJs('/assets/js/pages/notices.js');

            $this->render('notices/index', ['notices' => $notices]);
        }
    }
    ```

6.  **뷰 생성 (`app/Views/pages/notices/index.php`)**:
    `$notices` 변수를 사용하여 공지사항 목록을 HTML로 렌더링합니다. 모든 뷰 파일은 `layouts/app.php` 레이아웃을 상속받으므로, 반드시 `startSection('content')`와 `endSection()`으로 콘텐츠 영역을 감싸야 합니다.

    ```php
    <?php \App\Core\View::getInstance()->startSection('content'); ?>

    <h1>공지사항</h1>
    <div class="list-group">
        <?php foreach ($notices as $notice): ?>
            <a href="#" class="list-group-item list-group-item-action">
                <h5 class="mb-1"><?= htmlspecialchars($notice['title']) ?></h5>
                <small><?= htmlspecialchars($notice['created_at']) ?></small>
            </a>
        <?php endforeach; ?>
    </div>

    <?php \App\Core\View::getInstance()->endSection(); ?>
    ```

7.  **DI 컨테이너 등록 (`public/index.php`)**:
    위에서 생성한 `NoticeRepository`, `NoticeService`, `NoticeController`를 DI 컨테이너에 순서대로 등록합니다.

## 4. 코딩 스타일 및 규칙

- **명명 규칙**: `README.md`의 명명 규칙을 따릅니다.
  - 클래스: `PascalCase`
  - 메서드: `camelCase`
- **DocBlocks**: 모든 클래스와 public 메서드에는 PHPDoc 형식의 주석을 작성하여 역할, 파라미터, 반환 값을 명시합니다.
- **Strict Types**: 가능하면 `declare(strict_types=1);`을 파일 상단에 선언하여 타입 검사를 강화합니다.
- **리턴 타입**: 리포지토리의 조회 메서드는 항상 **연관 배열(associative array)** 형태로 반환해야 합니다. (`fetchAll(PDO::FETCH_ASSOC)`)

## 5. 개발 프로세스 및 기타

### 5.1. 환경 설정

-   **`.env`**: 데이터베이스 호스트(DB_HOST)는 `localhost` 대신 `127.0.0.1`로 설정하여 소켓 연결 오류를 방지하고 TCP/IP 연결을 강제합니다.
-   **PHP**: `php-mysql` 확장 모듈이 반드시 활성화되어 있어야 합니다.
-   **제약사항**: 개발 환경에 `php`, `mysql` CLI가 없어 일부 자동화 스크립트 실행 및 프론트엔드 검증에 제약이 있을 수 있습니다.

### 5.2. 테스트 및 검증

-   **테스트 스위트**: 현재 프로젝트에는 PHPUnit, Pest 등 자동화된 테스트 프레임워크가 구성되어 있지 않습니다.
-   **프론트엔드 검증**: 환경 문제로 인해 프론트엔드 검증 스크립트 실행이 불가능할 경우, 해당 단계를 건너뛰고 수동으로 검증할 수 있습니다.

### 5.3. 코드 관리

-   **리팩토링**: 라우트 URL을 변경할 경우, 일관성을 위해 관련된 View, JavaScript, CSS 파일명도 함께 변경하는 것을 원칙으로 합니다.
-   **코드 정리**: 사용하지 않는 코드나 파일을 정리할 때는, 먼저 명확한 분석 내용을 제시하고 동의를 얻은 후에 삭제와 같은 파괴적인 작업을 수행합니다.
-   **버그 처리**: 수정 요청이 명시적으로 없었던 버그나 개선점은 즉시 수정하는 대신, `KNOWN_ISSUES.md` 파일이나 관련 코드의 docstring에 상세히 기록하여 추적합니다.
