<?php
// 파일 경로: app/Core/Router.php
namespace App\Core;

/**
 * Class Router
 * @package App\Core
 * HTTP 요청을 분석하여 적절한 컨트롤러와 미들웨어로 연결하는 클래스
 */
class Router
{
    /** @var array 라우팅 테이블 */
    private static array $routes = [];

    /** @var string 라우트 그룹의 URI 접두사 */
    private static string $prefix = '';

    /** @var array 이름 있는 라우트의 '이름 => URI' 매핑 저장소 */
    private static array $namedRoutes = [];

    /** @var array 현재 등록 중인 라우트의 임시 정보 [method, uri] */
    private static ?array $currentRoute = null;

    /** @var array 등록된 미들웨어의 '키 => 클래스명' 매핑 저장소 */
    private static array $middlewares = [];

    /**
     * 라우트 그룹을 정의. 콜백 함수 내에서 정의된 모든 라우트에 접두사를 붙임.
     * @param string $prefix
     * @param callable $callback
     */
    public static function group(string $prefix, callable $callback): void
    {
        $oldPrefix = self::$prefix; // 이전 접두사 저장
        self::$prefix .= '/' . trim($prefix, '/');
        $callback();
        self::$prefix = $oldPrefix; // 그룹이 끝나면 원래 접두사로 복원
    }

    /**
     * GET 메소드 라우트를 등록
     * @param string $uri
     * @param array $action [Controller::class, 'methodName']
     * @return self
     */
    public static function get(string $uri, array $action): self
    {
        return self::add('GET', $uri, $action);
    }

    /**
     * POST 메소드 라우트를 등록
     * @param string $uri
     * @param array $action [Controller::class, 'methodName']
     * @return self
     */
    public static function post(string $uri, array $action): self
    {
        return self::add('POST', $uri, $action);
    }

    /**
     * PUT 메소드 라우트를 등록
     * @param string $uri
     * @param array $action [Controller::class, 'methodName']
     * @return self
     */
    public static function put(string $uri, array $action): self
    {
        return self::add('PUT', $uri, $action);
    }

    /**
     * DELETE 메소드 라우트를 등록
     * @param string $uri
     * @param array $action [Controller::class, 'methodName']
     * @return self
     */
    public static function delete(string $uri, array $action): self
    {
        return self::add('DELETE', $uri, $action);
    }

    /**
     * 라우팅 테이블에 라우트를 추가하는 내부 메소드
     * @param string $method
     * @param string $uri
     * @param array $action
     * @return self
     */
    private static function add(string $method, string $uri, array $action): self
    {
        $uriWithPrefix = rtrim(self::$prefix . '/' . ltrim($uri, '/'), '/');
        $uriWithPrefix = $uriWithPrefix ?: '/';

        self::$routes[$method][$uriWithPrefix] = ['action' => $action, 'middleware' => [], 'name' => null];
        self::$currentRoute = ['method' => $method, 'uri' => $uriWithPrefix];

        // 메소드 체이닝을 시작하기 위해 새 인스턴스를 반환
        return new self();
    }

    /**
     * 마지막으로 추가된 라우트에 이름을 부여
     * @param string $name
     * @return $this
     */
    public function name(string $name): self
    {
        if (self::$currentRoute) {
            $method = self::$currentRoute['method'];
            $uri = self::$currentRoute['uri'];

            self::$namedRoutes[$name] = $uri;
            self::$routes[$method][$uri]['name'] = $name;
        }
        // 메소드 체이닝을 위해 현재 인스턴스를 반환
        return $this;
    }

    /**
     * 마지막으로 추가된 라우트에 미들웨어를 연결
     * @param string $key 미들웨어의 키 (예: 'can')
     * @param string|null $value 미들웨어에 전달할 값
     * @return $this
     */
    public function middleware(string $key, string $value = null): self
    {
        if (self::$currentRoute) {
            $uri = self::$currentRoute['uri'];
            $method = self::$currentRoute['method'];
            self::$routes[$method][$uri]['middleware'][$key] = $value;
        }
        // 메소드 체이닝을 위해 현재 인스턴스를 반환
        return $this;
    }

    /**
     * 미들웨어 클래스를 키와 함께 등록 (index.php에서 호출)
     * @param string $key
     * @param string $class
     */
    public static function addMiddleware(string $key, string $class): void
    {
        self::$middlewares[$key] = $class;
    }

    /**
     * 이름으로 라우트의 URI를 찾음 (route() 헬퍼 함수용)
     * @param string $name
     * @return string|null
     */
    public static function getUriByName(string $name): ?string
    {
        return self::$namedRoutes[$name] ?? null;
    }

    /**
     * 요청을 분석하여 미들웨어를 거쳐 컨트롤러로 전달
     */
    public static function dispatch(): void
    {
        // HTML form에서 PUT/DELETE 등을 사용하기 위한 _method 지원
        $method = $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'];
        $uri = strtok($_SERVER['REQUEST_URI'], '?');

        foreach (self::$routes[$method] ?? [] as $route => $actionData) {
            // URI 끝에 있는 슬래시(/)를 선택적으로 처리하여 유연하게 매칭
            $routePattern = rtrim($route, '/');
            $pattern = '#^' . preg_replace('/\{([a-zA-Z_]+)\}/', '(?<$1>[^/]+)', $routePattern) . '/?$#';

            $uriToMatch = rtrim($uri, '/');
            if ($uriToMatch === '') $uriToMatch = '/'; // 루트 URI 처리

            if (preg_match($pattern, $uriToMatch, $matches)) {
                // 미들웨어 실행
                foreach ($actionData['middleware'] as $key => $value) {
                    $middlewareClass = self::$middlewares[$key] ?? null;
                    if ($middlewareClass && class_exists($middlewareClass)) {
                        (new $middlewareClass)->handle($value);
                    } else {
                        self::handleError(500, "미들웨어 '{$key}'가 등록되지 않았습니다.");
                        return;
                    }
                }

                // 컨트롤러 액션 실행
                [$controller, $methodName] = $actionData['action'];
                 if (!class_exists($controller) || !method_exists($controller, $methodName)) {
                     self::handleError(500, "경로 '{$uri}'에 대한 컨트롤러 또는 메소드를 찾을 수 없습니다.");
                     return;
                }

                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                // 컨트롤러의 반환값을 출력(echo)하도록 수정
                $response = (new $controller)->{$methodName}(...array_values($params));
                if (is_string($response)) {
                    echo $response;
                }
                return;
            }
        }
        self::handleError(404, "페이지를 찾을 수 없습니다.");
    }

    /**
     * 오류 처리 핸들러
     */
    public static function handleError(int $statusCode, string $message): void
    {
        http_response_code($statusCode);
        $isApiRequest = str_starts_with(strtok($_SERVER['REQUEST_URI'], '?'), '/api/');

        if ($isApiRequest) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
        } else {
            $viewPath = defined('BASE_PATH') ? BASE_PATH . "/views/errors/{$statusCode}.php" : __DIR__ . "/../../views/errors/{$statusCode}.php";
            if (file_exists($viewPath)) {
                require $viewPath;
            } else {
                echo "<h1>오류 {$statusCode}</h1><p>{$message}</p>";
            }
        }
        exit();
    }
}