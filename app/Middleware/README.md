# Middleware

이 디렉토리는 애플리케이션의 모든 미들웨어(Middleware) 클래스를 포함합니다. 미들웨어는 컨트롤러가 실행되기 전후에 HTTP 요청을 가로채고 필터링하는 강력한 메커니즘을 제공합니다. 주로 인증, 권한 부여, 로깅, 요청 데이터 검증 등과 같은 교차 관심사(cross-cutting concerns)를 처리하는 데 사용됩니다.

> **참고:** 미들웨어가 애플리케이션의 요청 생명주기 및 라우팅 시스템과 어떻게 통합되는지에 대한 포괄적인 이해를 위해서는 최상위 `docs/` 디렉토리의 문서를 참조하십시오.
> - `../../docs/architecture.md`
> - `../../docs/backend-guide.md`

---

## 미들웨어 시스템

### 사용 가능한 미들웨어

#### AuthMiddleware
- **목적**: 사용자가 인증되었는지 확인합니다.
- **키**: `'auth'`
- **동작**:
  - 인증되지 않은 웹 요청은 `/login` 페이지로 리디렉션합니다.
  - 인증되지 않은 API 요청은 `401 Unauthorized` JSON 응답을 반환합니다.

#### PermissionMiddleware
- **목적**: 사용자에게 특정 작업을 수행할 권한이 있는지 확인합니다.
- **키**: `'permission:resource.action'`
- **매개변수**: 권한 키가 필요합니다 (예: `employee.view`, `user.update`).
- **동작**:
  - 사용자에게 필요한 권한이 없는 경우, 웹 또는 API 요청에 대해 `403 Forbidden` 오류를 반환합니다.
  - 이 미들웨어는 `AuthMiddleware` 다음에 실행되어야 합니다.

### 라우트에서 사용법

라우트 정의 시 미들웨어를 연결하는 것이 권장되는 표준 방식입니다.

```php
// routes/web.php 또는 routes/api.php

// 인증만 필요한 라우트
$router->get('/dashboard', [DashboardController::class, 'index'])->middleware('auth');

// 인증과 특정 권한이 모두 필요한 라우트
$router->get('/employees', [EmployeeController::class, 'index'])
       ->middleware('auth')
       ->middleware('permission', 'employee.view');

$router->post('/employees', [EmployeeController::class, 'store'])
       ->middleware('auth')
       ->middleware('permission', 'employee.create');
```

### 사용자 정의 미들웨어 만들기

1.  `handle` 메서드를 구현하는 새로운 미들웨어 클래스를 `app/Middleware` 디렉토리 안에 생성합니다.
2.  생성한 미들웨어를 `public/index.php` 파일에 키와 함께 등록합니다: `$router->addMiddleware('your-key', YourMiddleware::class);`
3.  라우트에서 `->middleware('your-key')`를 사용하여 적용합니다.

**예시:**
```php
<?php

namespace App\Middleware;

class CustomMiddleware
{
    public function handle($parameter = null): void
    {
        // 여기에 미들웨어 로직을 구현합니다.
        // 예를 들어, 특정 조건이 충족되지 않으면 예외를 발생시키거나 리디렉션할 수 있습니다.
        if (!some_condition_is_met()) {
            // 오류 처리
            header('Location: /error-page');
            exit();
        }
    }
}
```

### 오류 처리

- **웹 요청**: 로그인 페이지로 리디렉션하거나 적절한 HTML 오류 페이지(예: 403, 404)를 표시합니다.
- **API 요청**: 적절한 HTTP 상태 코드와 함께 JSON 오류 응답을 반환합니다.
  - **인증 오류**: 401 Unauthorized
  - **권한 오류**: 403 Forbidden
