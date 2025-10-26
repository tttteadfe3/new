<?php

namespace App\Core;

class View
{
    private static ?View $instance = null;
    private array $sections = [];
    private ?string $currentSection = null;
    private ?string $layout = null;
    private array $layoutData = [];
    private array $jsFiles = []; // path => tag
    private array $cssFiles = [];

    private function __construct() {}

    public static function getInstance(): View
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function render(string $view, array $data = [], ?string $layout = null): string
    {
        // 렌더링별 속성을 재설정하지만 jsFiles 및 cssFiles는 유지합니다
        $this->sections = [];
        $this->currentSection = null;

        $this->layout = $layout;
        $this->layoutData = $data;

        $viewPath = ROOT_PATH . '/app/Views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            throw new \Exception("뷰를 찾을 수 없습니다: {$viewPath}");
        }

        extract($data);

        ob_start();
        require $viewPath;
        $viewContent = ob_get_clean();

        if ($this->layout && !isset($this->sections['content'])) {
            $this->sections['content'] = $viewContent;
        }

        if ($this->layout) {
            return $this->renderWithLayout($this->layout, $this->layoutData);
        }

        return $this->sections['content'] ?? $viewContent;
    }

    private function renderWithLayout(string $layout, array $data = []): string
    {
        $layoutPath = ROOT_PATH . '/app/Views/' . str_replace('.', '/', $layout) . '.php';

        if (!file_exists($layoutPath)) {
            throw new \Exception("레이아웃을 찾을 수 없습니다: {$layoutPath}");
        }

        $data['sections'] = $this->sections;
        extract($data);

        ob_start();
        require $layoutPath;
        return ob_get_clean();
    }

    public function startSection(string $name): void
    {
        if ($this->currentSection !== null) {
            throw new \Exception("섹션 '" . $this->currentSection . "'이(가) 아직 열려 있는 동안에는 섹션 '{$name}'을(를) 시작할 수 없습니다.");
        }

        $this->currentSection = $name;
        ob_start();
    }

    public function endSection(): void
    {
        if ($this->currentSection === null) {
            throw new \Exception("종료할 섹션이 없습니다.");
        }

        $content = ob_get_clean();
        $this->sections[$this->currentSection] = $content;
        $this->currentSection = null;
    }

    public function yieldSection(string $name, string $default = ''): string
    {
        if ($name === 'css') {
            return implode("\n", $this->cssFiles);
        }
        if ($name === 'js') {
            return implode("\n", array_values($this->jsFiles));
        }
        return $this->sections[$name] ?? $default;
    }

    public function hasSection(string $name): bool
    {
        return isset($this->sections[$name]);
    }

    public function addCss(string $path): void
    {
        $cssTag = '<link href="' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '" rel="stylesheet" type="text/css" />';
        if (!in_array($cssTag, $this->cssFiles)) {
            $this->cssFiles[] = $cssTag;
        }
    }

    public function addJs(string $path, array $options = []): void
    {
        $attributes = '';
        if (!empty($options)) {
            $attributes = ' data-options=\'' . htmlspecialchars(json_encode($options), ENT_QUOTES, 'UTF-8') . '\'';
        }
        $jsTag = '<script src="' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '"' . $attributes . '></script>';
        // 중복을 방지하기 위해 경로를 키로 사용하고, 마지막 것이 이깁니다.
        $this->jsFiles[$path] = $jsTag;
    }

    public function extends(string $layout): void
    {
        $this->layout = $layout;
    }
}
