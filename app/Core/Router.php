<?php
// 파일 경로: app/Core/Router.php
namespace App\Core;

use App\Core\Container;
use App\Core\JsonResponse;

/**
 * Class Router
 * @package App\Core
 * HTTP 요청을 분석하여 적절한 컨트롤러와 미들웨어로 연결하는 클래스
 */
class Router
{
    protected Container $container;
    protected array $routes = [];
    protected string $prefix = '';
    protected array $namedRoutes = [];
    protected ?array $currentRoute = null;
    protected array $middlewares = [];
    protected ?JsonResponse $jsonResponse = null;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * JsonResponse 인스턴스를 설정합니다.
     * @param JsonResponse $jsonResponse
     */
    public function setJsonResponse(JsonResponse $jsonResponse): void
    {
        $this->jsonResponse = $jsonResponse;
    }

    /**
     * 라우트 그룹을 정의. 콜백 함수 내에서 정의된 모든 라우트에 접두사를 붙임.
     * @param string $prefix
     * @param callable $callback
     */
    public function group(string $prefix, callable $callback): void
    {
        $oldPrefix = $this->prefix; // 이전 접두사 저장
        $this->prefix .= '/' . trim($prefix, '/');
        $callback($this);
        $this->prefix = $oldPrefix; // 그룹이 끝나면 원래 접두사로 복원
    }

    /**
     * GET 메소드 라우트를 등록
     * @param string $uri
     * @param array $action [Controller::class, 'methodName']
     * @return self
     */
    public function get(string $uri, array $action): self
    {
        return $this->add('GET', $uri, $action);
    }

    /**
     * POST 메소드 라우트를 등록
     * @param string $uri
     * @param array $action [Controller::class, 'methodName']
     * @return self
     */
    public function post(string $uri, array $action): self
    {
        return $this->add('POST', $uri, $action);
    }

    /**
     * PUT 메소드 라우트를 등록
     * @param string $uri
     * @param array $action [Controller::class, 'methodName']
     * @return self
     */
    public function put(string $uri, array $action): self
    {
        return $this->add('PUT', $uri, $action);
    }

    /**
     * DELETE 메소드 라우트를 등록
     * @param string $uri
     * @param array $action [Controller::class, 'methodName']
     * @return self
     */
    public function delete(string $uri, array $action): self
    {
        return $this->add('DELETE', $uri, $action);
    }

    /**
     * 라우팅 테이블에 라우트를 추가하는 내부 메소드
     * @param string $method
     * @param string $uri
     * @param array $action
     * @return self
     */
    private function add(string $method, string $uri, array $action): self
    {
        $uriWithPrefix = rtrim($this->prefix . '/' . ltrim($uri, '/'), '/');
        $uriWithPrefix = $uriWithPrefix ?: '/';

        $this->routes[$method][$uriWithPrefix] = ['action' => $action, 'middleware' => [], 'name' => null];
        $this->currentRoute = ['method' => $method, 'uri' => $uriWithPrefix];

        return $this;
    }

    /**
     * 마지막으로 추가된 라우트에 이름을 부여
     * @param string $name
     * @return $this
     */
    public function name(string $name): self
    {
        if ($this->currentRoute) {
            $method = $this->currentRoute['method'];
            $uri = $this->currentRoute['uri'];

            $this->namedRoutes[$name] = $uri;
            $this->routes[$method][$uri]['name'] = $name;
        }
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
        if ($this->currentRoute) {
            $uri = $this->currentRoute['uri'];
            $method = $this->currentRoute['method'];
            $this->routes[$method][$uri]['middleware'][$key] = $value;
        }
        return $this;
    }

    /**
     * 미들웨어 클래스를 키와 함께 등록 (index.php에서 호출)
     * @param string $key
     * @param string $class
     */
    public function addMiddleware(string $key, string $class): void
    {
        $this->middlewares[$key] = $class;
    }

    /**
     * 이름으로 라우트의 URI를 찾음 (route() 헬퍼 함수용)
     * @param string $name
     * @return string|null
     */
    public function getUriByName(string $name): ?string
    {
        return $this->namedRoutes[$name] ?? null;
    }

    /**
     * 요청을 분석하여 미들웨어를 거쳐 컨트롤러로 전달
     */
    public function dispatch(): void
    {
        $method = $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'];
        $uri = strtok($_SERVER['REQUEST_URI'], '?');

        foreach ($this->routes[$method] ?? [] as $route => $actionData) {
            $routePattern = rtrim($route, '/');
            $pattern = '#^' . preg_replace('/\{([a-zA-Z_]+)\}/', '(?<$1>[^/]+)', $routePattern) . '/?$#';

            $uriToMatch = rtrim($uri, '/');
            if ($uriToMatch === '') $uriToMatch = '/';

            if (preg_match($pattern, $uriToMatch, $matches)) {
                // 미들웨어 실행
                foreach ($actionData['middleware'] as $key => $value) {
                    $middlewareClass = $this->middlewares[$key] ?? null;
                    if ($middlewareClass && class_exists($middlewareClass)) {
                        $middlewareInstance = $this->container->resolve($middlewareClass);
                        $middlewareInstance->handle($value);
                    } else {
                        $this->handleError(500, "미들웨어 '{$key}'가 등록되지 않았습니다.");
                        return;
                    }
                }

                // 컨트롤러 액션 실행
                [$controller, $methodName] = $actionData['action'];
                 if (!class_exists($controller) || !method_exists($controller, $methodName)) {
                     $this->handleError(500, "경로 '{$uri}'에 대한 컨트롤러 또는 메소드를 찾을 수 없습니다.");
                     return;
                }

                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // DI 컨테이너를 통해 컨트롤러 인스턴스 생성
                $controllerInstance = $this->container->resolve($controller);

                $response = $controllerInstance->{$methodName}(...array_values($params));
                if (is_string($response)) {
                    echo $response;
                }
                return;
            }
        }
        $this->handleError(404, "페이지를 찾을 수 없습니다.");
    }

    /**
     * 오류 처리 핸들러
     */
    public function handleError(int $statusCode, string $message): void
    {
        $isApiRequest = str_starts_with(strtok($_SERVER['REQUEST_URI'], '?'), '/api/');

        if ($isApiRequest) {
            if ($this->jsonResponse) {
                // JsonResponse를 사용하여 표준화된 에러 응답 반환
                $errorCode = match ($statusCode) {
                    404 => 'NOT_FOUND',
                    403 => 'FORBIDDEN',
                    400 => 'BAD_REQUEST',
                    default => 'SERVER_ERROR'
                };
                $this->jsonResponse->error($message, $errorCode, $statusCode);
            } else {
                // JsonResponse가 없는 경우 (fallback)
                http_response_code($statusCode);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => $message, 'error_code' => 'SERVER_ERROR', 'data' => null], JSON_UNESCAPED_UNICODE);
                exit;
            }
        } else {
            http_response_code($statusCode);
            $viewPath = defined('BASE_PATH') ? BASE_PATH . "/errors/{$statusCode}.php" : __DIR__ . "/../../errors/{$statusCode}.php";
            if (file_exists($viewPath)) {
                require $viewPath;
            } else {
                echo "<h1>오류 {$statusCode}</h1><p>{$message}</p>";
            }
            exit();
        }
    }
}
