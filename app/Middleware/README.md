# 미들웨어 시스템

이 디렉터리에는 MVC 애플리케이션용 미들웨어 클래스가 포함되어 있습니다. 미들웨어는 애플리케이션에 들어오는 HTTP 요청을 필터링하는 편리한 메커니즘을 제공합니다.

## 사용 가능한 미들웨어

### AuthMiddleware
- **목적**: 사용자가 인증되었는지 확인합니다.
- **사용법**: `'auth'`
- **동작**:
  - 인증되지 않은 경우 웹 요청에 대해 `/login`으로 리디렉션합니다.
  - 인증되지 않은 경우 API 요청에 대해 401 JSON 응답을 반환합니다.

### PermissionMiddleware
- **목적**: 사용자에게 특정 권한이 있는지 확인합니다.
- **사용법**: `'permission:resource.action'`
- **매개변수**: 권한 키가 필요합니다 (예: `employee.view`, `user.update`).
- **동작**:
  - 사용자에게 권한이 없는 경우 웹/API 요청에 대해 403 오류를 반환합니다.
  - 사용자가 이미 인증되었다고 가정합니다 (AuthMiddleware 다음에 사용해야 함).

## 경로에서의 사용법

### Fluent 사용법 (권장)
```php
// 인증만 있는 경로
$router->get('/dashboard', [DashboardController::class, 'index'])->middleware('auth');

// 인증 및 권한이 있는 경로
$router->get('/employees', [EmployeeController::class, 'index'])
       ->middleware('auth')
       ->middleware('permission', 'employee.view');

$router->post('/employees', [EmployeeController::class, 'store'])
       ->middleware('auth')
       ->middleware('permission', 'employee.create');
```

## 사용자 지정 미들웨어 만들기

1. `handle` 메서드를 구현하는 새 클래스를 만듭니다.
2. `$router->addMiddleware('key', YourClass::class)`를 사용하여 `public/index.php`에 등록합니다.
3. 경로에서 `->middleware('key')`를 통해 사용합니다.

```php
<?php

namespace App\Middleware;

class CustomMiddleware
{
    public function handle($parameter = null): void
    {
        // 여기에 미들웨어 로직을 작성합니다.
        if ($someCondition) {
            // 예외를 발생시키거나 리디렉션하는 등 오류를 처리합니다.
        }
    }
}
```

## `requireAuth()`의 사용 중단

BaseController의 이전 `requireAuth()` 메서드는 이제 사용되지 않습니다. 경로에 유창하게 정의된 미들웨어가 표준입니다.

## 오류 처리

- **웹 요청**: 로그인으로 리디렉션하거나 HTML 오류 페이지를 표시합니다.
- **API 요청**: 적절한 HTTP 상태 코드로 JSON 응답을 반환합니다.
- **인증 오류**: 401 Unauthorized
- **권한 오류**: 403 Forbidden
