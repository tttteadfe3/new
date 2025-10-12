<?php

namespace App\Core;

class View
{
    private static ?View $instance = null;
    private array $sections = [];
    private ?string $currentSection = null;
    private ?string $layout = null;
    private array $layoutData = [];
    private array $jsFiles = [];
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
        // Reset per-render properties, but keep jsFiles and cssFiles
        $this->sections = [];
        $this->currentSection = null;

        $this->layout = $layout;
        $this->layoutData = $data;

        $viewPath = ROOT_PATH . '/app/Views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            throw new \Exception("View not found: {$viewPath}");
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
            throw new \Exception("Layout not found: {$layoutPath}");
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
            throw new \Exception("Cannot start section '{$name}' while section '" . $this->currentSection . "' is still open.");
        }

        $this->currentSection = $name;
        ob_start();
    }

    public function endSection(): void
    {
        if ($this->currentSection === null) {
            throw new \Exception("No section to end.");
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
            return implode("\n", $this->jsFiles);
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

    public function addJs(string $path): void
    {
        $jsTag = '<script src="' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '"></script>';
        if (!in_array($jsTag, $this->jsFiles)) {
            $this->jsFiles[] = $jsTag;
        }
    }

    public function extends(string $layout): void
    {
        $this->layout = $layout;
    }
}
