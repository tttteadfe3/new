# 3. 인증 및 권한 부여 분석

## 3.1. 분석

이 애플리케이션의 인증 및 권한 부여 시스템은 서비스, 미들웨어, 그리고 리포지토리의 세 가지 구성 요소가 유기적으로 연동하여 구현되어 있습니다.

### 3.1.1. 핵심 구성 요소

1.  **`AuthService` (`app/Services/AuthService.php`)**:
    -   **역할**: 모든 인증 및 권한 관련 로직의 중심입니다. 인스턴스 기반으로 설계되어 컨트롤러나 미들웨어에서 `new AuthService()`를 통해 생성하여 사용합니다.
    -   **주요 기능**:
        -   `login()`, `logout()`: 사용자 로그인 및 로그아웃 처리, 세션 관리.
        -   `isLoggedIn()`, `user()`: 현재 사용자의 로그인 상태 확인 및 사용자 정보 조회.
        -   `check(string $permission_key)`: 현재 로그인된 사용자가 특정 권한 키를 가지고 있는지 확인합니다.
    -   **권한 캐시 관리**: `storage/permissions_last_updated.txt` 파일의 타임스탬프를 활용한 독창적인 캐시 무효화 전략을 사용합니다. 관리자가 역할을 수정하면 이 파일의 타임스탬프가 갱신되고, 사용자가 다음 권한 체크를 할 때 세션에 저장된 권한 정보가 오래된 것임이 감지되면, `_refreshSessionPermissions()` 메서드를 통해 자동으로 최신 권한 정보를 데이터베이스에서 다시 불러와 세션을 갱신합니다. 이는 성능과 실시간성을 모두 잡는 효율적인 방법입니다.

2.  **`AuthMiddleware` (`app/Middleware/AuthMiddleware.php`)**:
    -   **역할**: **인증(Authentication)**을 담당합니다. 특정 라우트에 접근하기 위해 사용자가 반드시 로그인해야 하는지 확인합니다.
    -   **작동 방식**: `AuthService->isLoggedIn()`을 호출하여 로그인 상태를 확인합니다. 로그인되어 있지 않으면 API 요청에 대해서는 401 Unauthorized JSON 응답을, 웹 요청에 대해서는 `/login` 페이지로 리디렉션합니다.

3.  **`PermissionMiddleware` (`app/Middleware/PermissionMiddleware.php`)**:
    -   **역할**: **권한 부여(Authorization)**를 담당합니다. 로그인된 사용자가 특정 작업을 수행할 수 있는 구체적인 권한을 가지고 있는지 확인합니다.
    -   **작동 방식**: 라우트에서 전달받은 권한 키(예: `admin.users.edit`)를 `AuthService->check()` 메서드에 넘겨 권한을 확인합니다. 권한이 없는 경우 API 요청에 대해서는 403 Forbidden JSON 응답을, 웹 요청에 대해서는 일관된 레이아웃을 포함한 403 오류 페이지를 사용자에게 보여줍니다.

### 3.1.2. 인증 및 권한 부여 흐름

1.  사용자가 로그인을 시도하면, 컨트롤러는 `AuthService->login()`을 호출합니다.
2.  `login()` 메서드는 사용자 상태를 확인하고, 성공 시 `UserRepository`와 `RoleRepository`를 통해 사용자의 역할과 모든 권한을 조회하여 세션에 저장합니다.
3.  사용자가 특정 페이지(예: `/admin/users`)에 접근을 요청하면, `Router`는 해당 라우트에 연결된 `AuthMiddleware`와 `PermissionMiddleware`를 실행합니다.
4.  `AuthMiddleware`가 먼저 실행되어 사용자의 로그인 여부를 확인합니다.
5.  로그인 상태라면 `PermissionMiddleware`가 실행되어, 라우트에 정의된 권한 키(예: `admin.users.view`)를 사용자가 가지고 있는지 `AuthService->check()`를 통해 확인합니다.
6.  모든 미들웨어를 통과하면 컨트롤러의 메서드가 실행되어 페이지가 정상적으로 표시됩니다.

## 3.2. 개선 방안 및 제안

현재 시스템은 매우 효과적이고 잘 설계되어 있습니다. 그럼에도 불구하고 코드의 일관성과 확장성을 더욱 향상시키기 위한 몇 가지 제안을 드립니다.

### 제안 1: `AuthService` 인스턴스 중앙 관리

-   **문제점**: `AuthMiddleware`, `PermissionMiddleware`, `BaseController` 등 여러 곳에서 필요할 때마다 `new AuthService()`를 통해 객체를 생성하고 있습니다. 이는 동일한 요청 내에서 여러 개의 `AuthService` 인스턴스가 생성될 수 있음을 의미하며, 비효율을 초래할 수 있습니다.
-   **개선 방안**: **`BaseController`에서 `AuthService` 인스턴스를 한 번 생성하고, 이를 미들웨어에 전달**하는 방식을 고려해볼 수 있습니다. 하지만 현재 미들웨어 실행 구조상 컨트롤러보다 미들웨어가 먼저 실행되므로 이는 쉽지 않습니다.
    -   **차선책**: 의존성 주입 컨테이너가 없다는 제약 하에, 간단한 **서비스 로케이터(Service Locator)** 패턴을 도입하여 `AuthService` 인스턴스를 관리할 수 있습니다. `App` 또는 `Registry`와 같은 전역 클래스를 만들어 인스턴스를 한 번만 생성하고 저장해두면, 애플리케이션의 다른 부분에서는 이 클래스를 통해 동일한 인스턴스를 가져다 쓸 수 있습니다.

    ```php
    // 예시: 간단한 서비스 로케이터
    class App {
        private static $services = [];

        public static function get(string $key) {
            if (!isset(self::$services[$key])) {
                // 클래스 이름에 따라 인스턴스 생성 (간단한 예시)
                self::$services[$key] = new $key();
            }
            return self::$services[$key];
        }
    }

    // 미들웨어에서의 사용
    // (new AuthService()) 대신
    App::get(AuthService::class)->isLoggedIn();
    ```

### 제안 2: 역할 기반 접근 제어(RBAC) 추상화 강화

-   **문제점**: 현재는 개별 권한 키(`permission_key`)를 직접 확인하는 방식입니다. 시스템이 복잡해져 "관리자는 모든 것을 할 수 있다" 또는 "편집자는 게시물 관련 모든 권한을 가진다"와 같은 규칙을 적용하려면 여러 곳에서 코드를 수정해야 할 수 있습니다.
-   **개선 방안**: `AuthService`에 `userHasRole(string $roleName)`과 같은 메서드를 추가하여 역할 기반 검사를 단순화할 수 있습니다. 더 나아가, `spatie/laravel-permission`과 같은 전문 라이브러리에서 영감을 얻어, 역할과 권한을 더 유연하게 관리할 수 있는 구조를 도입하는 것을 장기적으로 고려해볼 수 있습니다. 예를 들어, 특정 역할에 와일드카드 권한(`post.*`)을 부여하는 기능 등을 추가할 수 있습니다.

### 제안 3: `magic string` 대신 상수(Constant) 사용

-   **문제점**: 라우트 정의나 미들웨어에서 사용되는 권한 키(예: 'admin.users.view')가 문자열로 하드코딩되어 있습니다. 만약 권한 키의 이름이 변경되면 코드베이스 전체에서 해당 문자열을 찾아 모두 수정해야 하며, 오타가 발생하기 쉽습니다.
-   **개선 방안**: 권한 키들을 `Permission` 클래스나 관련 모델에 **상수로 정의**하여 사용하는 것을 권장합니다.

    ```php
    // app/Models/Permission.php
    class Permission {
        public const VIEW_USERS = 'admin.users.view';
        public const EDIT_USERS = 'admin.users.edit';
        // ...
    }

    // 라우트에서의 사용
    use App\Models\Permission;
    Router::get('/users', [UserController::class, 'index'])->middleware('permission', Permission::VIEW_USERS);
    ```
    -   **장점**:
        -   IDE의 자동 완성을 통해 오타를 방지할 수 있습니다.
        -   권한 키의 이름이 변경되어도 상수 값만 수정하면 되므로 유지보수성이 크게 향상됩니다.
        -   코드를 읽는 사람이 문자열의 의미를 더 명확하게 이해할 수 있습니다.