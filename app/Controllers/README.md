# Controllers

이 디렉토리는 애플리케이션의 모든 컨트롤러를 포함하며, MVC-S (Model-View-Controller-Service) 패턴을 따릅니다. 컨트롤러는 들어오는 HTTP 요청을 처리하고, 서비스와 상호 작용하여 비즈니스 로직을 수행하며, 뷰를 렌더링하거나 JSON 데이터를 반환하는 응답을 생성하는 책임을 가집니다.

> **참고:** 애플리케이션 아키텍처, 요청 생명주기, 그리고 컨트롤러가 전체 구조에 어떻게 부합하는지에 대한 포괄적인 이해를 위해서는 최상위 `docs/` 디렉토리의 문서를 참조하십시오.
> - `../../docs/architecture.md`
> - `../../docs/backend-guide.md`

## 디렉토리 구조

- **`/Web`**: 웹 페이지 요청을 처리하고 HTML 뷰를 렌더링하는 컨트롤러입니다. 이 컨트롤러들은 `\App\Controllers\Web\BaseController`를 상속받아야 합니다.
- **`/Api`**: API 요청을 처리하고 JSON 응답을 반환하는 컨트롤러입니다. 이 컨트롤러들은 `\App\Controllers\Api\BaseApiController`를 상속받아야 합니다.

---

## 핵심 기능: BaseController

모든 컨트롤러는 필수적인 공통 기능을 제공하는 기본 컨트롤러 중 하나를 상속받아야 합니다.

### 1. 인증 및 권한 부여

```php
class MyController extends BaseController
{
    public function index()
    {
        // 인증만 요구
        $this->requireAuth();
        
        // 특정 권한 요구
        $this->requireAuth('admin_access');
        
        // 사용자가 인증되었는지 확인
        if ($this->isAuthenticated()) {
            // 로그인된 사용자
        }
        
        // 현재 사용자 가져오기
        $user = $this->user();
    }
}
```

### 2. 뷰 렌더링

```php
public function show()
{
    $data = ['title' => 'Page Title', 'content' => 'Page content'];
    return $this->render('pages/show', $data);
}
```

### 3. JSON 응답

```php
public function apiEndpoint()
{
    // 성공 응답
    $this->json([
        'data' => $someData,
        'message' => 'Success'
    ]);
    
    // 오류 응답
    $this->json([
        'message' => 'Error occurred',
        'errors' => ['field' => 'Error message']
    ], 400);
}
```

### 4. 리다이렉트

```php
public function store()
{
    // 데이터 처리...
    $this->redirect('/success-page');
}
```

### 5. 요청 입력 처리

```php
public function process()
{
    // 모든 입력 가져오기
    $allData = $this->request->all();
    
    // 특정 입력 가져오기
    $name = $this->request->input('name', 'default');
    
    // 여러 입력 가져오기
    $data = $this->request->only(['name', 'email']);
    
    // 입력 유효성 검사
    $errors = $this->request->validate([
        'name' => 'required|min:2',
        'email' => 'required|email'
    ]);
    
    if (!empty($errors)) {
        // 유효성 검사 오류 처리
    }
}
```

## API 컨트롤러

API 엔드포인트의 경우, `BaseApiController`를 상속받으십시오:

```php
class MyApiController extends BaseApiController
{
    public function index()
    {
        $this->requireAuth('api_access');
        
        $data = $this->someService->getData();
        $this->success($data, 'Data retrieved successfully');
    }
    
    public function store()
    {
        $errors = $this->request->validate([
            'name' => 'required'
        ]);
        
        if (!empty($errors)) {
            $this->validationError($errors);
            return;
        }
        
        // 데이터 처리 및 저장...
        $this->success($result, 'Data saved successfully', 201);
    }
}
```

## 레거시 컨트롤러 마이그레이션

레거시 컨트롤러를 변환할 때:

1. `BaseController`를 상속받습니다.
2. 직접적인 `AuthManager` 호출을 `$this->requireAuth()`로 교체합니다.
3. `View::render()`를 `$this->render()`로 교체합니다.
4. `header('Location: ...')`을 `$this->redirect()`로 교체합니다.
5. `$_GET`/`$_POST` 대신 `$this->request->input()`을 사용합니다.
